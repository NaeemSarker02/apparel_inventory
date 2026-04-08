<?php

namespace Tests\Feature;

use App\Filament\Widgets\VariantDemandHeatmap;
use App\Models\User;
use Database\Seeders\InventorySystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_dashboard(): void
    {
        $this->seed(InventorySystemSeeder::class);

        $admin = User::query()->where('email', 'admin@test.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_variant_demand_heatmap_widget_renders_proposal_analytics(): void
    {
        $this->seed(InventorySystemSeeder::class);

        $admin = User::query()->where('email', 'admin@test.com')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(VariantDemandHeatmap::class)
            ->assertSee('Size / Color Demand Heatmap')
            ->assertSee('Units Sold')
            ->assertSee('Demand Band')
            ->assertSee('Revenue');
    }
}