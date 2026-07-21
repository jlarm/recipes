<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'name' => $this->faker->randomElement([
                'flour', 'sugar', 'eggs', 'butter', 'milk', 'olive oil',
                'garlic cloves', 'onion', 'salt', 'black pepper', 'chicken breast',
            ]),
            'quantity' => $this->faker->randomElement([0.5, 1, 1.5, 2, 3, 200, 250]),
            'unit' => $this->faker->randomElement(['g', 'ml', 'cup', 'tbsp', 'tsp', null]),
            'position' => 0,
        ];
    }
}
