<?php

namespace Tests\Feature;

use App\Filament\Resources\Inventories\Pages\CreateInventory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminInventoriesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_the_inventories_index_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $variant = ProductVariant::factory()->create();

        $variant->inventory()->updateOrCreate([], [
            'on_hand' => 12,
            'reserved' => 1,
            'reorder_point' => 8,
            'reorder_quantity' => 20,
            'safety_stock' => 4,
        ]);

        $this->actingAs($user)
            ->get('/admin/inventories')
            ->assertOk();
    }

    public function test_admin_can_open_the_inventory_create_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/inventories/create')
            ->assertOk();
    }

    public function test_admin_can_create_an_inventory_record_for_a_variant_without_inventory(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $variant = ProductVariant::factory()->create();
        $variant->inventory()->delete();

        $this->actingAs($user);

        Livewire::test(CreateInventory::class)
            ->set('data.product_variant_id', $variant->id)
            ->set('data.on_hand', 18)
            ->set('data.reserved', 2)
            ->set('data.reorder_point', 8)
            ->set('data.reorder_quantity', 24)
            ->set('data.safety_stock', 4)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $variant->id,
            'on_hand' => 18,
            'reserved' => 2,
            'reorder_point' => 8,
            'reorder_quantity' => 24,
            'safety_stock' => 4,
        ]);
    }

    public function test_admin_can_update_an_existing_inventory_record_from_create_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $variant = ProductVariant::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateInventory::class)
            ->set('data.product_variant_id', $variant->id)
            ->set('data.on_hand', 30)
            ->set('data.reserved', 3)
            ->set('data.reorder_point', 12)
            ->set('data.reorder_quantity', 36)
            ->set('data.safety_stock', 6)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('inventories', 1);

        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $variant->id,
            'on_hand' => 30,
            'reserved' => 3,
            'reorder_point' => 12,
            'reorder_quantity' => 36,
            'safety_stock' => 6,
        ]);
    }
}