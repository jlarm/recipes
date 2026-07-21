<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingListController extends Controller
{
    public function index(): Response
    {
        $recipes = Recipe::query()
            ->with('ingredients:id,recipe_id,name,quantity,unit,position')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'servings']);

        return Inertia::render('ShoppingList/Index', [
            'recipes' => $recipes,
        ]);
    }
}
