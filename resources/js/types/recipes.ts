export interface Category {
    id: number;
    name: string;
    slug: string;
}

export interface Ingredient {
    id: number;
    name: string;
    quantity: number | null;
    unit: string | null;
    position: number;
}

export interface Recipe {
    id: number;
    title: string;
    slug: string;
    category: Category | null;
    description: string | null;
    image_url: string | null;
    servings: number;
    prep_minutes: number | null;
    cook_minutes: number | null;
    rating: number | null;
    instructions: string;
    ingredients: Ingredient[];
    created_at: string;
}

export type RecipeSummary = Omit<Recipe, 'ingredients' | 'instructions'> & {
    ingredients_count: number;
};

export type RecipeSort = 'newest' | 'oldest' | 'title' | 'quickest' | 'top-rated';

export interface RecipeFilters {
    search: string;
    category: string;
    minRating: number;
    sort: RecipeSort;
}

export interface Paginated<T> {
    data: T[];
}

/** A recipe reduced to what the shopping list needs to combine ingredients. */
export interface ShoppingRecipe {
    id: number;
    title: string;
    slug: string;
    servings: number;
    ingredients: Ingredient[];
}
