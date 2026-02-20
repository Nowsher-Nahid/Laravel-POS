<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::inRandomOrder()->first()->id,
            'name' => $name = $this->faker->unique()->word(),
            'slug' => Str::slug($name),
            'sku' => $this->faker->unique()->bothify('SKU-####'),
            'barcode' => $this->faker->unique()->ean13(), // Generate a unique EAN-13 barcode
            'image' => $this->faker->imageUrl(640, 480, 'products', true), // Placeholder image URL
            'cost_price' => $this->faker->randomFloat(2, 1, 100), // Cost price between 1 and 100
            'selling_price' => $this->faker->randomFloat(2, 1, 200),
            'unit' => $this->faker->randomElement(['pcs', 'kg', 'liters']),
            'is_active' => $this->faker->boolean(90), // 90% chance to be active
        ];
    }
}
