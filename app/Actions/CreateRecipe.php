<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

class CreateRecipe
{
    /**
     * Create a recipe together with its ingredients.
     *
     * @param  array{
     *     title: string,
     *     category: string,
     *     image_path?: string|null,
     *     description?: string|null,
     *     servings: int,
     *     prep_minutes?: int|null,
     *     cook_minutes?: int|null,
     *     instructions: string,
     *     ingredients: array<int, array{name: string, quantity?: float|null, unit?: string|null}>
     * }  $data
     */
    public function execute(array $data): Recipe
    {
        return DB::transaction(function () use ($data): Recipe {
            $recipe = Recipe::create([
                'title' => $data['title'],
                'category_id' => Category::resolveByName($data['category'])->id,
                'image_path' => $data['image_path'] ?? null,
                'description' => $data['description'] ?? null,
                'servings' => $data['servings'],
                'prep_minutes' => $data['prep_minutes'] ?? null,
                'cook_minutes' => $data['cook_minutes'] ?? null,
                'instructions' => $data['instructions'],
            ]);

            $ingredients = collect($data['ingredients'])
                ->values()
                ->map(fn (array $ingredient, int $index): array => [
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity'] ?? null,
                    'unit' => $ingredient['unit'] ?? null,
                    'position' => $index,
                ])
                ->all();

            $recipe->ingredients()->createMany($ingredients);

            return $recipe;
        });
    }
}
