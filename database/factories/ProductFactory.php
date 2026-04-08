<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'product_code' => Str::upper(fake()->unique()->bothify('PRD-####')),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name.'-'.fake()->unique()->numberBetween(1000, 9999)),
            'target_gender' => fake()->randomElement(array_keys(Product::genderOptions())),
            'article_type' => fake()->randomElement(['T-Shirt', 'Shirt', 'Jeans', 'Jacket', 'Dress', 'Sneakers']),
            'status' => fake()->randomElement(array_keys(Product::statusOptions())),
            'base_cost' => fake()->randomFloat(2, 12, 75),
            'retail_price' => fake()->randomFloat(2, 30, 180),
            'description' => fake()->paragraph(),
            'image_url' => null,
        ];
    }
}
