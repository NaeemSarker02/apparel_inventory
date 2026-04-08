<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_all_primary_inventory_modules(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        collect([
            '/admin',
            '/admin/categories',
            '/admin/brands',
            '/admin/suppliers',
            '/admin/products',
            '/admin/product-variants',
            '/admin/inventories',
            '/admin/purchase-orders',
            '/admin/sales-orders',
            '/admin/stock-movements',
            '/admin/users',
        ])->each(function (string $url): void {
            $this->get($url)->assertOk();
        });
    }

    public function test_manager_can_access_operational_modules_but_not_user_management(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->actingAs($manager)
            ->get('/admin/products')
            ->assertOk();

        $this->actingAs($manager)
            ->get('/admin/inventories')
            ->assertOk();

        $this->actingAs($manager)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_viewer_can_access_read_only_inventory_pages_but_not_create_records(): void
    {
        $viewer = User::factory()->create([
            'role' => User::ROLE_VIEWER,
            'is_active' => true,
        ]);

        $this->actingAs($viewer)
            ->get('/admin/inventories')
            ->assertOk();

        $this->actingAs($viewer)
            ->get('/admin/products')
            ->assertOk();

        $this->actingAs($viewer)
            ->get('/admin/products/create')
            ->assertForbidden();
    }
}