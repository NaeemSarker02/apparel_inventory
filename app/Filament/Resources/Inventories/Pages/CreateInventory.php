<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Filament\Resources\Inventories\InventoryResource;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return Inventory::query()->updateOrCreate(
            ['product_variant_id' => $data['product_variant_id']],
            [
                'on_hand' => $data['on_hand'],
                'reserved' => $data['reserved'],
                'reorder_point' => $data['reorder_point'],
                'reorder_quantity' => $data['reorder_quantity'],
                'safety_stock' => $data['safety_stock'],
            ]
        );
    }
}