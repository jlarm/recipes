<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst($this->faker->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 100000),
            'category_id' => Category::factory(),
            'description' => $this->faker->sentence(12),
            'servings' => $this->faker->numberBetween(2, 8),
            'prep_minutes' => $this->faker->numberBetween(5, 30),
            'cook_minutes' => $this->faker->numberBetween(10, 90),
            'rating' => $this->faker->optional(0.7)->numberBetween(1, 5),
            'instructions' => collect(range(1, $this->faker->numberBetween(3, 6)))
                ->map(fn (int $step): string => $this->faker->sentence(10))
                ->implode("\n"),
        ];
    }
}
