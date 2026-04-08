<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reorderPoint = fake()->numberBetween(10, 25);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'on_hand' => fake()->numberBetween($reorderPoint - 3, 140),
            'reserved' => fake()->numberBetween(0, 8),
            'reorder_point' => $reorderPoint,
            'reorder_quantity' => fake()->numberBetween(20, 60),
            'safety_stock' => fake()->numberBetween(3, 12),
            'last_restocked_at' => fake()->dateTimeBetween('-90 days', '-3 days'),
            'last_counted_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}