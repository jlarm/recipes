<?php

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;

use function Pest\Laravel\get;

it('filters recipes by category slug', function () {
    $breakfast = Category::factory()->create(['name' => 'Breakfast', 'slug' => 'breakfast']);
    $dinner = Category::factory()->create(['name' => 'Dinner', 'slug' => 'dinner']);

    $pancakes = Recipe::factory()->for($breakfast)->create();
    Recipe::factory()->for($dinner)->create();

    get(route('recipes.index', ['category' => 'breakfast']))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.slug', $pancakes->slug)
            ->where('filters.category', 'breakfast')
        );
});

it('searches recipes by title', function () {
    Recipe::factory()->create(['title' => 'Chocolate Cake']);
    Recipe::factory()->create(['title' => 'Garden Salad']);

    get(route('recipes.index', ['search' => 'chocolate']))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.title', 'Chocolate Cake')
        );
});

it('searches recipes by ingredient name', function () {
    $target = Recipe::factory()->create(['title' => 'Mystery Dish']);
    Ingredient::factory()->for($target)->create(['name' => 'saffron']);

    $other = Recipe::factory()->create(['title' => 'Plain Toast']);
    Ingredient::factory()->for($other)->create(['name' => 'bread']);

    get(route('recipes.index', ['search' => 'saffron']))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.title', 'Mystery Dish')
        );
});

it('combines search and category filters', function () {
    $dinner = Category::factory()->create(['name' => 'Dinner', 'slug' => 'dinner']);
    $lunch = Category::factory()->create(['name' => 'Lunch', 'slug' => 'lunch']);

    $match = Recipe::factory()->for($dinner)->create(['title' => 'Spicy Curry']);
    Recipe::factory()->for($lunch)->create(['title' => 'Spicy Wrap']);
    Recipe::factory()->for($dinner)->create(['title' => 'Mild Stew']);

    get(route('recipes.index', ['search' => 'spicy', 'category' => 'dinner']))
        ->assertInertia(fn ($page) => $page
            ->has('recipes.data', 1)
            ->where('recipes.data.0.title', $match->title)
        );
});

it('sorts recipes alphabetically by title', function () {
    Recipe::factory()->create(['title' => 'Banana Bread']);
    Recipe::factory()->create(['title' => 'Apple Pie']);
    Recipe::factory()->create(['title' => 'Cherry Tart']);

    get(route('recipes.index', ['sort' => 'title']))
        ->assertInertia(fn ($page) => $page
            ->where('recipes.data.0.title', 'Apple Pie')
            ->where('recipes.data.2.title', 'Cherry Tart')
            ->where('filters.sort', 'title')
        );
});

it('sorts recipes by quickest total time', function () {
    Recipe::factory()->create(['title' => 'Slow', 'prep_minutes' => 30, 'cook_minutes' => 60]);
    Recipe::factory()->create(['title' => 'Fast', 'prep_minutes' => 5, 'cook_minutes' => 10]);

    get(route('recipes.index', ['sort' => 'quickest']))
        ->assertInertia(fn ($page) => $page->where('recipes.data.0.title', 'Fast'));
});

it('falls back to newest for an unknown sort value', function () {
    get(route('recipes.index', ['sort' => 'bogus']))
        ->assertInertia(fn ($page) => $page->where('filters.sort', 'newest'));
});

it('exposes categories to the index for the filter UI', function () {
    Category::factory()->create(['name' => 'Zzz Last']);
    Category::factory()->create(['name' => 'Aaa First']);

    get(route('recipes.index'))
        ->assertInertia(fn ($page) => $page
            ->has('categories', 2)
            ->where('categories.0.name', 'Aaa First') // ordered by name
        );
});
