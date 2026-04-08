<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function ensureInventory(ProductVariant $variant): Inventory
    {
        return $variant->inventory()->firstOrCreate([], [
            'on_hand' => 0,
            'reserved' => 0,
            'reorder_point' => 10,
            'reorder_quantity' => 30,
            'safety_stock' => 5,
        ]);
    }

    public function adjustInventory(
        Inventory $inventory,
        int $quantityChange,
        string $type = StockMovement::TYPE_ADJUSTMENT,
        ?User $actor = null,
        ?string $referenceType = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
    ): Inventory {
        return DB::transaction(function () use ($inventory, $quantityChange, $type, $actor, $referenceType, $referenceNumber, $notes): Inventory {
            $inventory->refresh();

            $newBalance = $inventory->on_hand + $quantityChange;

            if ($newBalance < 0) {
                throw new DomainException('Stock adjustment would result in a negative balance.');
            }

            $inventory->forceFill([
                'on_hand' => $newBalance,
                'last_counted_at' => now(),
                'last_restocked_at' => $quantityChange > 0 ? now() : $inventory->last_restocked_at,
            ])->save();

            $this->recordMovement(
                inventory: $inventory,
                quantityChange: $quantityChange,
                type: $type,
                actor: $actor,
                referenceType: $referenceType,
                referenceNumber: $referenceNumber,
                notes: $notes,
            );

            return $inventory->fresh();
        });
    }

    public function receivePurchaseOrder(PurchaseOrder $purchaseOrder, ?User $actor = null): void
    {
        $purchaseOrder->loadMissing('items.productVariant.inventory');

        DB::transaction(function () use ($purchaseOrder, $actor): void {
            foreach ($purchaseOrder->items as $item) {
                $outstandingQuantity = $item->ordered_quantity - $item->received_quantity;

                if ($outstandingQuantity <= 0) {
                    continue;
                }

                $inventory = $this->ensureInventory($item->productVariant);

                $this->adjustInventory(
                    inventory: $inventory,
                    quantityChange: $outstandingQuantity,
                    type: StockMovement::TYPE_PURCHASE,
                    actor: $actor,
                    referenceType: 'purchase_order',
                    referenceNumber: $purchaseOrder->order_number,
                    notes: 'Goods received from supplier order.',
                );

                $item->forceFill([
                    'received_quantity' => $item->ordered_quantity,
                ])->save();
            }

            $purchaseOrder->forceFill([
                'status' => PurchaseOrder::STATUS_RECEIVED,
                'received_at' => now(),
            ])->save();

            $purchaseOrder->syncTotals();
        });
    }

    public function completeSalesOrder(SalesOrder $salesOrder, ?User $actor = null): void
    {
        $salesOrder->loadMissing('items.productVariant.inventory');

        DB::transaction(function () use ($salesOrder, $actor): void {
            foreach ($salesOrder->items as $item) {
                $inventory = $this->ensureInventory($item->productVariant);

                if ($inventory->availableToSell() < $item->quantity) {
                    throw new DomainException("Insufficient stock available for {$item->productVariant->display_name}.");
                }

                $this->adjustInventory(
                    inventory: $inventory,
                    quantityChange: -$item->quantity,
                    type: StockMovement::TYPE_SALE,
                    actor: $actor,
                    referenceType: 'sales_order',
                    referenceNumber: $salesOrder->order_number,
                    notes: 'Inventory reduced after sale fulfillment.',
                );
            }

            $salesOrder->forceFill([
                'status' => SalesOrder::STATUS_COMPLETED,
            ])->save();

            $salesOrder->syncTotals();
        });
    }

    protected function recordMovement(
        Inventory $inventory,
        int $quantityChange,
        string $type,
        ?User $actor = null,
        ?string $referenceType = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
    ): void {
        $inventory->stockMovements()->create([
            'product_variant_id' => $inventory->product_variant_id,
            'user_id' => $actor?->id,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'balance_after' => $inventory->on_hand,
            'reference_type' => $referenceType,
            'reference_number' => $referenceNumber,
            'notes' => $notes,
            'occurred_at' => now(),
        ]);
    }
}