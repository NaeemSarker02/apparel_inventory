<?php

namespace Tests\Feature;

use App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Resources\Suppliers\RelationManagers\PurchaseOrdersRelationManager;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\InventorySystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_view_page_shows_procurement_summary_metrics(): void
    {
        $this->seed(InventorySystemSeeder::class);

        $admin = User::query()->where('email', 'admin@test.com')->firstOrFail();
        $supplier = Supplier::query()->has('purchaseOrders')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/suppliers/'.$supplier->id)
            ->assertOk()
            ->assertSee($supplier->name)
            ->assertSee('Purchase Orders')
            ->assertSee('Received Spend');
    }

    public function test_supplier_purchase_history_relation_manager_renders_orders(): void
    {
        $this->seed(InventorySystemSeeder::class);

        $admin = User::query()->where('email', 'admin@test.com')->firstOrFail();
        $supplier = Supplier::query()->has('purchaseOrders')->with('purchaseOrders')->firstOrFail();
        $purchaseOrder = $supplier->purchaseOrders->sortByDesc('ordered_at')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $supplier,
            'pageClass' => ViewSupplier::class,
        ])
            ->assertSee('Purchasing History')
            ->assertSee($purchaseOrder->order_number)
            ->assertSee('Open order');
    }
}