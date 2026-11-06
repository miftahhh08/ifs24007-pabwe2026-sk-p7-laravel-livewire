<?php

namespace Database\Factories;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'description' => fake()->sentence(),
            'quantity' => fake()->numberBetween(0, 100),
            'unit' => 'pcs',
            'price' => fake()->randomFloat(2, 1000, 500000),
            'min_stock' => 5,
            'status' => fn (array $attributes) => StockStatus::fromQuantity(
                (int) $attributes['quantity'],
                (int) $attributes['min_stock'],
            ),
            'cover_image_path' => null,
        ];
    }
}
