<?php

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

it('shows the recipe index publicly', function () {
    Recipe::factory()->count(2)->create();

    get(route('recipes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Recipes/Index')
            ->has('recipes.data', 2)
        );
});

it('shows a single recipe publicly with its ingredients', function () {
    $recipe = Recipe::factory()->create();
    Ingredient::factory()->count(3)->for($recipe)->create();

    get(route('recipes.show', $recipe))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Recipes/Show')
            ->where('recipe.slug', $recipe->slug)
            ->has('recipe.ingredients', 3)
        );
});

it('redirects to the passcode form when adding without verification', function () {
    get(route('recipes.create'))->assertRedirect(route('passcode.show'));

    post(route('recipes.store'), [])->assertRedirect(route('passcode.show'));

    expect(Recipe::count())->toBe(0);
});

it('allows the create page once the passcode is verified', function () {
    withSession(['passcode_verified' => true])
        ->get(route('recipes.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Recipes/Create'));
});

it('stores a recipe with ordered ingredients when verified', function () {
    $response = withSession(['passcode_verified' => true])->post(route('recipes.store'), [
        'title' => 'Test Pancakes',
        'category' => 'Breakfast',
        'description' => 'Tasty',
        'servings' => 4,
        'prep_minutes' => 10,
        'cook_minutes' => 15,
        'rating' => 5,
        'instructions' => "Mix.\nCook.",
        'ingredients' => [
            ['name' => 'flour', 'quantity' => 250, 'unit' => 'g'],
            ['name' => 'salt', 'quantity' => null, 'unit' => null],
        ],
    ]);

    $recipe = Recipe::first();

    $response->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->title)->toBe('Test Pancakes')
        ->and($recipe->slug)->toBe('test-pancakes')
        ->and($recipe->category->name)->toBe('Breakfast')
        ->and($recipe->rating)->toBe(5)
        ->and($recipe->ingredients)->toHaveCount(2)
        ->and($recipe->ingredients[0]->name)->toBe('flour')
        ->and($recipe->ingredients[0]->position)->toBe(0)
        ->and($recipe->ingredients[1]->quantity)->toBeNull();
});

it('rejects a rating outside the 1 to 5 range', function () {
    withSession(['passcode_verified' => true])
        ->post(route('recipes.store'), [
            'title' => 'Overrated',
            'category' => 'Dinner',
            'servings' => 2,
            'rating' => 6,
            'instructions' => 'Do it.',
            'ingredients' => [['name' => 'water', 'quantity' => 1, 'unit' => 'cup']],
        ])
        ->assertSessionHasErrors(['rating']);
});

it('treats a blank rating as unrated', function () {
    withSession(['passcode_verified' => true])->post(route('recipes.store'), [
        'title' => 'No Rating',
        'category' => 'Dinner',
        'servings' => 2,
        'rating' => '',
        'instructions' => 'Do it.',
        'ingredients' => [['name' => 'water', 'quantity' => 1, 'unit' => 'cup']],
    ]);

    expect(Recipe::first()->rating)->toBeNull();
});

it('validates required fields when storing', function () {
    withSession(['passcode_verified' => true])
        ->post(route('recipes.store'), [])
        ->assertSessionHasErrors(['title', 'category', 'servings', 'instructions', 'ingredients']);
});

it('requires each ingredient to have a name', function () {
    withSession(['passcode_verified' => true])
        ->post(route('recipes.store'), [
            'title' => 'No Name',
            'category' => 'Dinner',
            'servings' => 2,
            'instructions' => 'Do it.',
            'ingredients' => [
                ['name' => '', 'quantity' => 1, 'unit' => 'cup'],
            ],
        ])
        ->assertSessionHasErrors(['ingredients.0.name']);
});

it('redirects to the passcode form when editing without verification', function () {
    $recipe = Recipe::factory()->create();

    get(route('recipes.edit', $recipe))->assertRedirect(route('passcode.show'));
});

it('shows the edit page with the recipe once verified', function () {
    $recipe = Recipe::factory()->create();
    Ingredient::factory()->count(2)->for($recipe)->create();

    withSession(['passcode_verified' => true])
        ->get(route('recipes.edit', $recipe))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Recipes/Edit')
            ->where('recipe.slug', $recipe->slug)
            ->has('recipe.ingredients', 2)
        );
});

it('updates a recipe and replaces its ingredients', function () {
    $recipe = Recipe::factory()->create(['title' => 'Old Title', 'servings' => 4]);
    Ingredient::factory()->count(3)->for($recipe)->create();

    $originalSlug = $recipe->slug;

    $response = withSession(['passcode_verified' => true])->put(route('recipes.update', $recipe), [
        'title' => 'New Title',
        'category' => 'Dinner',
        'description' => 'Updated',
        'servings' => 6,
        'prep_minutes' => 5,
        'cook_minutes' => 20,
        'rating' => 4,
        'instructions' => "Step one.\nStep two.",
        'ingredients' => [
            ['name' => 'sugar', 'quantity' => 100, 'unit' => 'g'],
            ['name' => 'water', 'quantity' => null, 'unit' => null],
        ],
    ]);

    $recipe->refresh();

    $response->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->title)->toBe('New Title')
        ->and($recipe->slug)->toBe($originalSlug) // slug stays stable for links
        ->and($recipe->servings)->toBe(6)
        ->and($recipe->rating)->toBe(4)
        ->and($recipe->ingredients)->toHaveCount(2)
        ->and($recipe->ingredients[0]->name)->toBe('sugar');
});

it('validates when updating a recipe', function () {
    $recipe = Recipe::factory()->create();

    withSession(['passcode_verified' => true])
        ->put(route('recipes.update', $recipe), [])
        ->assertSessionHasErrors(['title', 'category', 'servings', 'instructions', 'ingredients']);
});

it('generates unique slugs for duplicate titles', function () {
    withSession(['passcode_verified' => true])->post(route('recipes.store'), [
        'title' => 'Same Title',
        'category' => 'Dinner',
        'servings' => 2,
        'instructions' => 'Step.',
        'ingredients' => [['name' => 'water', 'quantity' => 1, 'unit' => 'cup']],
    ]);

    withSession(['passcode_verified' => true])->post(route('recipes.store'), [
        'title' => 'Same Title',
        'category' => 'Dinner',
        'servings' => 2,
        'instructions' => 'Step.',
        'ingredients' => [['name' => 'water', 'quantity' => 1, 'unit' => 'cup']],
    ]);

    expect(Recipe::pluck('slug')->all())->toBe(['same-title', 'same-title-2']);
});

it('stores an uploaded cover image', function () {
    Storage::fake('public');

    withSession(['passcode_verified' => true])->post(route('recipes.store'), [
        'title' => 'With Photo',
        'category' => 'Dinner',
        'servings' => 2,
        'instructions' => 'Cook it.',
        'image' => UploadedFile::fake()->image('dish.jpg'),
        'ingredients' => [['name' => 'water', 'quantity' => 1, 'unit' => 'cup']],
    ]);

    $recipe = Recipe::first();

    expect($recipe->image_path)->not->toBeNull()
        ->and($recipe->image_url)->toContain('/storage/');
    Storage::disk('public')->assertExists($recipe->image_path);
});

it('replaces and deletes the old image on update', function () {
    Storage::fake('public');

    $recipe = Recipe::factory()->create([
        'image_path' => UploadedFile::fake()->image('old.jpg')->store('recipes', 'public'),
    ]);
    $oldPath = $recipe->image_path;

    withSession(['passcode_verified' => true])->put(route('recipes.update', $recipe), [
        'title' => $recipe->title,
        'category' => 'Dinner',
        'servings' => $recipe->servings,
        'instructions' => 'Updated.',
        'image' => UploadedFile::fake()->image('new.jpg'),
        'ingredients' => [['name' => 'water', 'quantity' => 1, 'unit' => 'cup']],
    ]);

    $recipe->refresh();

    expect($recipe->image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($recipe->image_path);
});

it('deletes a recipe and its image when verified', function () {
    Storage::fake('public');

    $recipe = Recipe::factory()->create([
        'image_path' => UploadedFile::fake()->image('shot.jpg')->store('recipes', 'public'),
    ]);
    $path = $recipe->image_path;

    withSession(['passcode_verified' => true])
        ->delete(route('recipes.destroy', $recipe))
        ->assertRedirect(route('recipes.index'));

    expect(Recipe::count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});

it('blocks deleting without the passcode', function () {
    $recipe = Recipe::factory()->create();

    $this->delete(route('recipes.destroy', $recipe))->assertRedirect(route('passcode.show'));

    expect(Recipe::count())->toBe(1);
});

it('reuses an existing category by slugified name', function () {
    withSession(['passcode_verified' => true])->post(route('recipes.store'), [
        'title' => 'First',
        'category' => 'Dinner',
        'servings' => 2,
        'instructions' => 'Step.',
        'ingredients' => [['name' => 'water', 'quantity' => 1, 'unit' => 'cup']],
    ]);

    withSession(['passcode_verified' => true])->post(route('recipes.store'), [
        'title' => 'Second',
        'category' => 'dinner', // same slug, different casing
        'servings' => 2,
        'instructions' => 'Step.',
        'ingredients' => [['name' => 'water', 'quantity' => 1, 'unit' => 'cup']],
    ]);

    expect(Category::count())->toBe(1)
        ->and(Recipe::pluck('category_id')->unique()->all())->toBe([Category::first()->id]);
});
