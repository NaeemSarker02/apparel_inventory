<?php

namespace Tests\Feature;

use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}