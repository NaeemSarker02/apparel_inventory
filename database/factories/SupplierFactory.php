<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_code' => Str::upper(fake()->unique()->bothify('SUP-####')),
            'name' => fake()->unique()->company().' Apparel Supply',
            'contact_person' => fake()->name(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'lead_time_days' => fake()->numberBetween(4, 21),
            'payment_terms_days' => fake()->randomElement([15, 30, 45]),
            'address' => fake()->address(),
            'status' => fake()->randomElement(array_keys(Supplier::statusOptions())),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
