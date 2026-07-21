<?php

use App\Http\Controllers\PasscodeController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ShoppingListController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/recipes')->name('home');

// Public recipe browsing.
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');

// Shopping list builder (public utility).
Route::get('/shopping-list', [ShoppingListController::class, 'index'])->name('shopping-list.index');

// Passcode gate for contributors.
Route::get('/passcode', [PasscodeController::class, 'show'])->name('passcode.show');
Route::post('/passcode', [PasscodeController::class, 'verify'])
    ->middleware('throttle:6,1')
    ->name('passcode.verify');

// Adding and editing recipes is gated behind the shared passcode.
Route::middleware('passcode')->group(function () {
    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
    Route::get('/recipes/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy');
});

// Public recipe detail (declared last so /recipes/create is not captured by {recipe}).
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');
