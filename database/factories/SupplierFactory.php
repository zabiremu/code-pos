<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company_name' => fake()->company(),
            'phone' => fake()->numerify('017########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
