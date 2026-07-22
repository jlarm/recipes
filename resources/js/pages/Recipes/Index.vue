<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StarRating from '@/components/StarRating.vue';
import { FALLBACK_IMAGE, useFallbackImage } from '@/lib/image';
import { create as recipesCreate, index as recipesIndex, show as recipesShow } from '@/routes/recipes';
import type { Category, Paginated, RecipeFilters, RecipeSort, RecipeSummary } from '@/types/recipes';

const props = defineProps<{
    recipes: Paginated<RecipeSummary>;
    categories: Category[];
    filters: RecipeFilters;
}>();

const page = usePage();
const passcodeVerified = computed(() => page.props.passcodeVerified === true);

const SORT_OPTIONS: { value: RecipeSort; label: string }[] = [
    { value: 'newest', label: 'Newest' },
    { value: 'oldest', label: 'Oldest' },
    { value: 'title', label: 'A–Z' },
    { value: 'quickest', label: 'Quickest' },
    { value: 'top-rated', label: 'Top rated' },
];

const RATING_OPTIONS: { value: number; label: string }[] = [
    { value: 4, label: '4+' },
    { value: 3, label: '3+' },
    { value: 2, label: '2+' },
];

const search = ref(props.filters.search);
let debounce: ReturnType<typeof setTimeout> | undefined;

function applyFilters(overrides: Partial<RecipeFilters> = {}): void {
    const params: Record<string, string> = {};
    const next = {
        search: search.value,
        category: props.filters.category,
        minRating: props.filters.minRating,
        sort: props.filters.sort,
        ...overrides,
    };

    if (next.search.trim() !== '') {
        params.search = next.search.trim();
    }

    if (next.category !== '') {
        params.category = next.category;
    }

    if (next.minRating > 0) {
        params.min_rating = String(next.minRating);
    }

    if (next.sort !== 'newest') {
        params.sort = next.sort;
    }

    router.get(recipesIndex().url, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['recipes', 'filters'],
        reset: ['recipes'], // clear the merged infinite-scroll list before new results
    });
}

function onSortChange(event: Event): void {
    applyFilters({ sort: (event.target as HTMLSelectElement).value as RecipeSort });
}

watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => applyFilters(), 300);
});

function selectCategory(slug: string): void {
    applyFilters({ category: slug });
}

function selectMinRating(rating: number): void {
    // Clicking the active rating chip toggles it back off.
    applyFilters({ minRating: props.filters.minRating === rating ? 0 : rating });
}

function clearSearch(): void {
    search.value = '';
    applyFilters({ search: '' });
}

const hasActiveFilters = () =>
    props.filters.search !== '' || props.filters.category !== '' || props.filters.minRating > 0;

function totalTime(recipe: RecipeSummary): number | null {
    const total = (recipe.prep_minutes ?? 0) + (recipe.cook_minutes ?? 0);

    return total > 0 ? total : null;
}
</script>

<template>
    <Head title="Recipes" />

    <AppLayout>
        <div class="mb-6">
            <h1 class="text-3xl font-semibold tracking-tight">Recipes</h1>
            <p class="mt-1 text-[#706f6c] dark:text-[#A1A09A]">
                Browse the collection. Open one to scale it to any number of servings.
            </p>
        </div>

        <!-- Search + sort -->
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search recipes, ingredients…"
                    class="w-full rounded-full border border-black/10 bg-white py-2.5 pr-10 pl-5 text-sm outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-white/15 dark:bg-[#161615]"
                />
                <button
                    v-if="search !== ''"
                    type="button"
                    aria-label="Clear search"
                    class="absolute top-1/2 right-3 -translate-y-1/2 text-[#706f6c] hover:text-[#1b1b18] dark:hover:text-white"
                    @click="clearSearch"
                >
                    ✕
                </button>
            </div>
            <label class="flex shrink-0 items-center gap-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                <span class="hidden sm:inline">Sort</span>
                <div class="relative">
                    <select
                        :value="filters.sort"
                        class="appearance-none rounded-full border border-black/10 bg-white py-2.5 pr-10 pl-5 text-sm text-[#1b1b18] outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-white/15 dark:bg-[#161615] dark:text-[#EDEDEC]"
                        @change="onSortChange"
                    >
                        <option v-for="option in SORT_OPTIONS" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <svg
                        class="pointer-events-none absolute top-1/2 right-3.5 h-4 w-4 -translate-y-1/2 text-[#706f6c] dark:text-[#A1A09A]"
                        viewBox="0 0 20 20"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M6 8l4 4 4-4" />
                    </svg>
                </div>
            </label>
        </div>

        <!-- Category filter chips -->
        <div class="mt-4 flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-full border px-3.5 py-1.5 text-sm font-medium transition"
                :class="filters.category === ''
                    ? 'border-transparent bg-[#1b1b18] text-white dark:bg-white dark:text-[#1b1b18]'
                    : 'border-black/10 hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10'"
                @click="selectCategory('')"
            >
                All
            </button>
            <button
                v-for="category in categories"
                :key="category.id"
                type="button"
                class="rounded-full border px-3.5 py-1.5 text-sm font-medium transition"
                :class="filters.category === category.slug
                    ? 'border-transparent bg-[#1b1b18] text-white dark:bg-white dark:text-[#1b1b18]'
                    : 'border-black/10 hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10'"
                @click="selectCategory(category.slug)"
            >
                {{ category.name }}
            </button>
        </div>

        <!-- Rating filter -->
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">Rating</span>
            <button
                v-for="option in RATING_OPTIONS"
                :key="option.value"
                type="button"
                class="inline-flex items-center gap-1 rounded-full border px-3 py-1.5 text-sm font-medium transition"
                :class="filters.minRating === option.value
                    ? 'border-transparent bg-[#1b1b18] text-white dark:bg-white dark:text-[#1b1b18]'
                    : 'border-black/10 hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10'"
                @click="selectMinRating(option.value)"
            >
                <span class="text-amber-500">★</span>{{ option.label }}
            </button>
        </div>

        <!-- Results -->
        <div v-if="recipes.data.length === 0" class="mt-8 rounded-xl border border-dashed border-black/10 p-12 text-center dark:border-white/15">
            <template v-if="hasActiveFilters()">
                <p class="text-[#706f6c] dark:text-[#A1A09A]">No recipes match your filters.</p>
                <Link :href="recipesIndex().url" class="mt-3 inline-block text-sm text-amber-700 hover:underline dark:text-amber-500">
                    Clear filters
                </Link>
            </template>
            <template v-else>
                <p class="text-[#706f6c] dark:text-[#A1A09A]">No recipes yet.</p>
                <Link
                    v-if="passcodeVerified"
                    :href="recipesCreate().url"
                    class="mt-4 inline-block rounded-full bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white dark:bg-white dark:text-[#1b1b18]"
                >
                    Add the first one
                </Link>
            </template>
        </div>

        <InfiniteScroll v-else data="recipes" class="mt-6 grid gap-5 sm:grid-cols-2">
            <Link
                v-for="recipe in recipes.data"
                :key="recipe.id"
                :href="recipesShow(recipe.slug).url"
                prefetch
                class="group flex flex-col overflow-hidden rounded-xl border border-black/5 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-white/10 dark:bg-[#161615]"
            >
                <div
                    class="aspect-[16/9] w-full overflow-hidden bg-black/5 dark:bg-white/5"
                >
                    <img
                        :src="recipe.image_url ?? FALLBACK_IMAGE"
                        :alt="recipe.title"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        loading="lazy"
                        @error="useFallbackImage"
                    />
                </div>
                <div class="flex flex-1 flex-col p-6">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold tracking-tight group-hover:text-amber-700 dark:group-hover:text-amber-500">
                        {{ recipe.title }}
                    </h2>
                    <span
                        v-if="recipe.category"
                        class="shrink-0 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-500/15 dark:text-amber-400"
                    >
                        {{ recipe.category.name }}
                    </span>
                </div>
                <StarRating v-if="recipe.rating" :model-value="recipe.rating" size="sm" class="mt-2" />
                <p v-if="recipe.description" class="mt-2 line-clamp-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    {{ recipe.description }}
                </p>
                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                    <span>🍽 Serves {{ recipe.servings }}</span>
                    <span>🧂 {{ recipe.ingredients_count }} ingredients</span>
                    <span v-if="totalTime(recipe)">⏱ {{ totalTime(recipe) }} min</span>
                </div>
                </div>
            </Link>
        </InfiniteScroll>
    </AppLayout>
</template>
