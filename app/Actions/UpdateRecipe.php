<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

class UpdateRecipe
{
    /**
     * Update a recipe and replace its ingredient list.
     *
     * The slug is intentionally left unchanged so existing links keep working.
     *
     * The image_path key is only present when the cover image is being changed
     * or removed, so an unset key leaves the current image untouched.
     *
     * @param  array{
     *     title: string,
     *     category: string,
     *     image_path?: string|null,
     *     description?: string|null,
     *     servings: int,
     *     prep_minutes?: int|null,
     *     cook_minutes?: int|null,
     *     rating?: int|null,
     *     instructions: string,
     *     ingredients: array<int, array{name: string, quantity?: float|null, unit?: string|null}>
     * }  $data
     */
    public function execute(Recipe $recipe, array $data): Recipe
    {
        return DB::transaction(function () use ($recipe, $data): Recipe {
            $attributes = [
                'title' => $data['title'],
                'category_id' => Category::resolveByName($data['category'])->id,
                'description' => $data['description'] ?? null,
                'servings' => $data['servings'],
                'prep_minutes' => $data['prep_minutes'] ?? null,
                'cook_minutes' => $data['cook_minutes'] ?? null,
                'rating' => $data['rating'] ?? null,
                'instructions' => $data['instructions'],
            ];

            if (array_key_exists('image_path', $data)) {
                $attributes['image_path'] = $data['image_path'];
            }

            $recipe->update($attributes);

            $recipe->ingredients()->delete();

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
