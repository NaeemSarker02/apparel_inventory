<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colorName = fake()->safeColorName();

        return [
            'product_id' => Product::factory(),
            'supplier_id' => Supplier::factory(),
            'sku' => Str::upper(fake()->unique()->bothify('SKU-####-???')),
            'barcode' => fake()->unique()->numerify('############'),
            'size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            'color_name' => Str::title($colorName),
            'color_code' => sprintf('#%06X', fake()->numberBetween(0, 0xFFFFFF)),
            'season' => fake()->randomElement(array_keys(ProductVariant::seasonOptions())),
            'unit_cost' => fake()->randomFloat(2, 12, 70),
            'sale_price' => fake()->randomFloat(2, 25, 190),
            'is_active' => fake()->boolean(95),
        ];
    }
}
