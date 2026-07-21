<?php

use App\Models\Ingredient;
use App\Models\Recipe;

use function Pest\Laravel\get;

it('renders the shopping list with recipes and their ingredients', function () {
    $recipe = Recipe::factory()->create(['title' => 'Soup']);
    Ingredient::factory()->count(3)->for($recipe)->create();

    get(route('shopping-list.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ShoppingList/Index')
            ->has('recipes', 1)
            ->where('recipes.0.title', 'Soup')
            ->has('recipes.0.ingredients', 3)
        );
});

it('is publicly accessible without the passcode', function () {
    get(route('shopping-list.index'))->assertOk();
});
