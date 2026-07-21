<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $breakfast = Category::resolveByName('Breakfast');
        $soups = Category::resolveByName('Soups');

        // A few extra categories so the filter has options out of the box.
        collect(['Dinner', 'Dessert', 'Salads', 'Drinks'])
            ->each(fn (string $name) => Category::resolveByName($name));

        $pancakes = Recipe::create([
            'title' => 'Fluffy Buttermilk Pancakes',
            'slug' => 'fluffy-buttermilk-pancakes',
            'category_id' => $breakfast->id,
            'description' => 'Light, tender pancakes that scale perfectly for a crowd.',
            'servings' => 4,
            'prep_minutes' => 10,
            'cook_minutes' => 15,
            'instructions' => "Whisk the dry ingredients together in a large bowl.\n"
                ."In a second bowl, whisk the buttermilk, eggs, and melted butter.\n"
                ."Fold the wet into the dry until just combined; a few lumps are fine.\n"
                ."Cook 1/4 cup of batter per pancake on a buttered griddle until bubbles form, then flip.\n"
                .'Serve warm with maple syrup.',
        ]);

        $this->addIngredients($pancakes, [
            ['flour', 250, 'g'],
            ['baking powder', 2, 'tsp'],
            ['sugar', 2, 'tbsp'],
            ['salt', 0.5, 'tsp'],
            ['buttermilk', 500, 'ml'],
            ['eggs', 2, null],
            ['butter, melted', 50, 'g'],
        ]);

        $soup = Recipe::create([
            'title' => 'Weeknight Tomato Soup',
            'slug' => 'weeknight-tomato-soup',
            'category_id' => $soups->id,
            'description' => 'A cozy, blender-smooth tomato soup ready in half an hour.',
            'servings' => 6,
            'prep_minutes' => 10,
            'cook_minutes' => 25,
            'instructions' => "Sweat the onion and garlic in olive oil until soft.\n"
                ."Add the tomatoes and stock, then simmer for 20 minutes.\n"
                ."Blend until smooth and stir through the cream.\n"
                .'Season to taste and serve with crusty bread.',
        ]);

        $this->addIngredients($soup, [
            ['olive oil', 2, 'tbsp'],
            ['onion, diced', 1, null],
            ['garlic cloves', 3, null],
            ['canned tomatoes', 800, 'g'],
            ['vegetable stock', 750, 'ml'],
            ['double cream', 100, 'ml'],
            ['salt', null, null],
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: float|null, 2: string|null}>  $ingredients
     */
    private function addIngredients(Recipe $recipe, array $ingredients): void
    {
        $rows = collect($ingredients)
            ->map(fn (array $ingredient, int $index): array => [
                'name' => $ingredient[0],
                'quantity' => $ingredient[1],
                'unit' => $ingredient[2],
                'position' => $index,
            ])
            ->all();

        $recipe->ingredients()->createMany($rows);
    }
}
