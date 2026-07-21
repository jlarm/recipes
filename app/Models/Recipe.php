<?php

namespace App\Models;

use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int|null $category_id
 * @property string|null $description
 * @property string|null $image_path
 * @property int $servings
 * @property int|null $prep_minutes
 * @property int|null $cook_minutes
 * @property string $instructions
 * @property string|null $image_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'slug', 'category_id', 'description', 'image_path', 'servings', 'prep_minutes', 'cook_minutes', 'instructions'])]
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['image_url'];

    protected static function booted(): void
    {
        static::creating(function (Recipe $recipe): void {
            if (blank($recipe->slug)) {
                $recipe->slug = static::uniqueSlug($recipe->title);
            }
        });
    }

    /**
     * @return HasMany<Ingredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class)->orderBy('position');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Public URL for the recipe's cover image, or null when it has none.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->image_path === null
                ? null
                : Storage::disk('public')->url($this->image_path),
        )->shouldCache();
    }

    /**
     * Filter by a free-text term against the title, description, and ingredient names.
     *
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('ingredients', fn (Builder $ingredients) => $ingredients->where('name', 'like', $like));
        });
    }

    /**
     * Filter by a category slug.
     *
     * @param  Builder<Recipe>  $query
     * @return Builder<Recipe>
     */
    public function scopeInCategory(Builder $query, ?string $categorySlug): Builder
    {
        $categorySlug = trim((string) $categorySlug);

        if ($categorySlug === '') {
            return $query;
        }

        return $query->whereHas('category', fn (Builder $category) => $category->where('slug', $categorySlug));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'servings' => 'integer',
            'prep_minutes' => 'integer',
            'cook_minutes' => 'integer',
        ];
    }
}
