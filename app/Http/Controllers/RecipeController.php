<?php

namespace App\Http\Controllers;

use App\Actions\CreateRecipe;
use App\Actions\UpdateRecipe;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    /**
     * Allowed sort keys mapped to a query orderer.
     *
     * @var array<string, callable(Builder<Recipe>): Builder<Recipe>>
     */
    private const SORTS = [
        'newest' => [self::class, 'sortNewest'],
        'oldest' => [self::class, 'sortOldest'],
        'title' => [self::class, 'sortTitle'],
        'quickest' => [self::class, 'sortQuickest'],
        'top-rated' => [self::class, 'sortTopRated'],
    ];

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $category = $request->string('category')->trim()->value();
        $minRating = $request->integer('min_rating');
        $minRating = ($minRating >= 1 && $minRating <= 5) ? $minRating : 0;
        $sort = $request->string('sort')->value();
        $sort = array_key_exists($sort, self::SORTS) ? $sort : 'newest';

        $query = Recipe::query()
            ->with('category:id,name,slug')
            ->withCount('ingredients')
            ->search($search)
            ->inCategory($category)
            ->minRating($minRating);

        (self::SORTS[$sort])($query);

        $columns = [
            'id', 'title', 'slug', 'category_id', 'description', 'image_path',
            'servings', 'prep_minutes', 'cook_minutes', 'rating', 'created_at',
        ];

        return Inertia::render('Recipes/Index', [
            'recipes' => Inertia::scroll(fn () => $query->paginate(12, $columns)->withQueryString()),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'minRating' => $minRating,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Recipe $recipe): Response
    {
        $recipe->load(['ingredients', 'category:id,name,slug']);

        return Inertia::render('Recipes/Show', [
            'recipe' => $recipe,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Recipes/Create', [
            'categories' => $this->categoryNames(),
        ]);
    }

    public function store(StoreRecipeRequest $request, CreateRecipe $createRecipe): RedirectResponse
    {
        $data = $request->validated();
        $data['image_path'] = $request->file('image')?->store('recipes', 'public');

        $recipe = $createRecipe->execute($data);

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', 'Recipe added.');
    }

    public function edit(Recipe $recipe): Response
    {
        $recipe->load(['ingredients', 'category:id,name,slug']);

        return Inertia::render('Recipes/Edit', [
            'recipe' => $recipe,
            'categories' => $this->categoryNames(),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe, UpdateRecipe $updateRecipe): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImage($recipe);
            $data['image_path'] = $request->file('image')->store('recipes', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($recipe);
            $data['image_path'] = null;
        }

        $updateRecipe->execute($recipe, $data);

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', 'Recipe updated.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->deleteImage($recipe);
        $recipe->delete();

        return redirect()
            ->route('recipes.index')
            ->with('success', 'Recipe deleted.');
    }

    /**
     * The existing category names, used as suggestions in the recipe form.
     *
     * @return array<int, string>
     */
    private function categoryNames(): array
    {
        return Category::query()->orderBy('name')->pluck('name')->all();
    }

    private function deleteImage(Recipe $recipe): void
    {
        if ($recipe->image_path !== null) {
            Storage::disk('public')->delete($recipe->image_path);
        }
    }

    /**
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    private static function sortNewest(Builder $query): Builder
    {
        return $query->latest();
    }

    /**
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    private static function sortOldest(Builder $query): Builder
    {
        return $query->oldest();
    }

    /**
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    private static function sortTitle(Builder $query): Builder
    {
        return $query->orderBy('title');
    }

    /**
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    private static function sortQuickest(Builder $query): Builder
    {
        return $query->orderByRaw('(COALESCE(prep_minutes, 0) + COALESCE(cook_minutes, 0)) asc');
    }

    /**
     * Highest-rated first, with unrated recipes last.
     *
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    private static function sortTopRated(Builder $query): Builder
    {
        return $query->orderByRaw('rating is null asc')->orderByDesc('rating')->latest();
    }
}
