<?php

namespace Tests\Unit;

use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_scope_returns_only_variants_below_their_reorder_point(): void
    {
        $criticalVariant = ProductVariant::factory()->create();
        $healthyVariant = ProductVariant::factory()->create();

        $criticalVariant->inventory()->update([
            'on_hand' => 4,
            'reorder_point' => 10,
            'reserved' => 1,
            'safety_stock' => 5,
        ]);

        $healthyVariant->inventory()->update([
            'on_hand' => 20,
            'reorder_point' => 10,
            'reserved' => 2,
            'safety_stock' => 5,
        ]);

        $lowStockIds = $criticalVariant->inventory()->newQuery()->lowStock()->pluck('id');

        $this->assertTrue($lowStockIds->contains($criticalVariant->inventory->id));
        $this->assertFalse($lowStockIds->contains($healthyVariant->inventory->id));
        $this->assertSame('critical', $criticalVariant->inventory->fresh()->stockStatus());
        $this->assertSame(3, $criticalVariant->inventory->fresh()->availableToSell());
    }
}