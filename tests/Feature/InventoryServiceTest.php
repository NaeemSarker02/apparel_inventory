<?php

namespace Tests\Feature;

use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_receiving_a_purchase_order_increases_inventory_and_logs_a_movement(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $variant = $this->createVariantWithInventory(onHand: 10, reorderPoint: 15);

        $purchaseOrder = PurchaseOrder::query()->create([
            'supplier_id' => $variant->supplier_id,
            'ordered_by' => $user->id,
            'ordered_at' => now(),
            'status' => PurchaseOrder::STATUS_ORDERED,
        ]);

        $item = PurchaseOrderItem::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_variant_id' => $variant->id,
            'ordered_quantity' => 25,
            'received_quantity' => 0,
            'unit_cost' => $variant->unit_cost,
        ]);

        app(InventoryService::class)->receivePurchaseOrder($purchaseOrder, $user);

        $variant->inventory->refresh();

        $this->assertSame(35, $variant->inventory->on_hand);
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $purchaseOrder->fresh()->status);
        $this->assertSame(25, $item->fresh()->received_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'inventory_id' => $variant->inventory->id,
            'type' => StockMovement::TYPE_PURCHASE,
            'quantity_change' => 25,
            'reference_type' => 'purchase_order',
        ]);
    }

    public function test_completing_a_sale_reduces_stock_and_marks_the_order_completed(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $variant = $this->createVariantWithInventory(onHand: 20, reorderPoint: 5);

        $salesOrder = SalesOrder::query()->create([
            'sold_by' => $user->id,
            'sold_at' => now(),
            'sales_channel' => 'in-store',
            'status' => SalesOrder::STATUS_DRAFT,
        ]);

        SalesOrderItem::query()->create([
            'sales_order_id' => $salesOrder->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'unit_price' => $variant->sale_price,
        ]);

        app(InventoryService::class)->completeSalesOrder($salesOrder, $user);

        $variant->inventory->refresh();

        $this->assertSame(17, $variant->inventory->on_hand);
        $this->assertSame(SalesOrder::STATUS_COMPLETED, $salesOrder->fresh()->status);

        $this->assertDatabaseHas('stock_movements', [
            'inventory_id' => $variant->inventory->id,
            'type' => StockMovement::TYPE_SALE,
            'quantity_change' => -3,
            'reference_type' => 'sales_order',
        ]);
    }

    public function test_sale_completion_is_blocked_when_inventory_would_go_negative(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $variant = $this->createVariantWithInventory(onHand: 2, reorderPoint: 5);

        $salesOrder = SalesOrder::query()->create([
            'sold_by' => $user->id,
            'sold_at' => now(),
            'sales_channel' => 'online',
            'status' => SalesOrder::STATUS_DRAFT,
        ]);

        SalesOrderItem::query()->create([
            'sales_order_id' => $salesOrder->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'unit_price' => $variant->sale_price,
        ]);

        try {
            app(InventoryService::class)->completeSalesOrder($salesOrder, $user);

            $this->fail('The sale should not complete when insufficient inventory is available.');
        } catch (DomainException) {
            $this->assertSame(2, $variant->inventory->fresh()->on_hand);
            $this->assertSame(SalesOrder::STATUS_DRAFT, $salesOrder->fresh()->status);
            $this->assertDatabaseCount('stock_movements', 0);
        }
    }

    private function createVariantWithInventory(int $onHand, int $reorderPoint): ProductVariant
    {
        $variant = ProductVariant::factory()->create();

        $variant->inventory()->update([
            'on_hand' => $onHand,
            'reserved' => 0,
            'reorder_point' => $reorderPoint,
            'reorder_quantity' => 20,
            'safety_stock' => 3,
        ]);

        return $variant->fresh('inventory');
    }
}