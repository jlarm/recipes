<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatQuantity, scaleQuantity } from '@/lib/quantity';
import { destroy as recipesDestroy, edit as recipesEdit, index as recipesIndex } from '@/routes/recipes';
import type { Recipe } from '@/types/recipes';

const props = defineProps<{
    recipe: Recipe;
}>();

const page = usePage();
const passcodeVerified = computed(() => page.props.passcodeVerified === true);

const servings = ref(props.recipe.servings);

const isScaled = computed(() => servings.value !== props.recipe.servings);

const steps = computed(() =>
    props.recipe.instructions
        .split('\n')
        .map((step) => step.trim())
        .filter((step) => step.length > 0),
);

const scaledIngredients = computed(() =>
    props.recipe.ingredients.map((ingredient) => ({
        ...ingredient,
        display: formatQuantity(
            scaleQuantity(ingredient.quantity, props.recipe.servings, servings.value),
        ),
    })),
);

function adjust(delta: number): void {
    servings.value = Math.min(100, Math.max(1, servings.value + delta));
}

function reset(): void {
    servings.value = props.recipe.servings;
}

function print(): void {
    window.print();
}

function destroy(): void {
    if (confirm(`Delete “${props.recipe.title}”? This cannot be undone.`)) {
        router.delete(recipesDestroy(props.recipe.slug).url);
    }
}

// --- Cook mode -------------------------------------------------------------

interface WakeLockSentinel {
    release(): Promise<void>;
}

const cookMode = ref(false);
const checkedIngredients = ref<Set<number>>(new Set());
const checkedSteps = ref<Set<number>>(new Set());
let wakeLock: WakeLockSentinel | null = null;

const nav = navigator as Navigator & {
    wakeLock?: { request(type: 'screen'): Promise<WakeLockSentinel> };
};

async function enterCookMode(): Promise<void> {
    cookMode.value = true;

    try {
        wakeLock = (await nav.wakeLock?.request('screen')) ?? null;
    } catch {
        wakeLock = null;
    }
}

function exitCookMode(): void {
    cookMode.value = false;
    void wakeLock?.release();
    wakeLock = null;
}

function toggle(current: Set<number>, index: number): Set<number> {
    const next = new Set(current);

    if (next.has(index)) {
        next.delete(index);
    } else {
        next.add(index);
    }

    return next;
}

function toggleIngredient(index: number): void {
    checkedIngredients.value = toggle(checkedIngredients.value, index);
}

function toggleStep(index: number): void {
    checkedSteps.value = toggle(checkedSteps.value, index);
}

onBeforeUnmount(() => {
    void wakeLock?.release();
});
</script>

<template>
    <Head :title="recipe.title" />

    <AppLayout>
        <Link :href="recipesIndex().url" class="text-sm text-[#706f6c] hover:underline dark:text-[#A1A09A] print:hidden">
            ← All recipes
        </Link>

        <img
            v-if="recipe.image_url"
            :src="recipe.image_url"
            :alt="recipe.title"
            class="mt-4 aspect-[21/9] w-full rounded-2xl object-cover print:hidden"
        />

        <header class="mt-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <h1 class="text-3xl font-semibold tracking-tight">{{ recipe.title }}</h1>
                <div class="flex flex-wrap gap-2 print:hidden">
                    <button
                        type="button"
                        class="rounded-full bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-700"
                        @click="enterCookMode"
                    >
                        👨‍🍳 Cook mode
                    </button>
                    <button
                        type="button"
                        class="rounded-full border border-black/10 px-4 py-2 text-sm font-medium transition hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10"
                        @click="print"
                    >
                        Print
                    </button>
                    <template v-if="passcodeVerified">
                        <Link
                            :href="recipesEdit(recipe.slug).url"
                            class="rounded-full border border-black/10 px-4 py-2 text-sm font-medium transition hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10"
                        >
                            Edit
                        </Link>
                        <button
                            type="button"
                            class="rounded-full border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:hover:bg-red-950/40"
                            @click="destroy"
                        >
                            Delete
                        </button>
                    </template>
                </div>
            </div>
            <p v-if="recipe.description" class="mt-2 text-[#706f6c] dark:text-[#A1A09A]">
                {{ recipe.description }}
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                <Link
                    v-if="recipe.category"
                    :href="recipesIndex({ query: { category: recipe.category.slug } }).url"
                    class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 transition hover:bg-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:hover:bg-amber-500/25 print:hidden"
                >
                    {{ recipe.category.name }}
                </Link>
                <span v-if="recipe.prep_minutes">Prep {{ recipe.prep_minutes }} min</span>
                <span v-if="recipe.cook_minutes">Cook {{ recipe.cook_minutes }} min</span>
                <span>Original recipe serves {{ recipe.servings }}</span>
            </div>
        </header>

        <div class="mt-8 grid gap-10 md:grid-cols-[minmax(0,20rem)_1fr]">
            <!-- Ingredients + servings scaler -->
            <section>
                <div class="rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615] print:border-0 print:p-0 print:shadow-none">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold">Ingredients</h2>
                        <button
                            v-if="isScaled"
                            type="button"
                            class="text-xs text-amber-700 hover:underline dark:text-amber-500 print:hidden"
                            @click="reset"
                        >
                            Reset
                        </button>
                    </div>

                    <div class="mt-4 print:hidden">
                        <label class="text-xs font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">
                            Servings
                        </label>
                        <div class="mt-2 flex items-center gap-3">
                            <button
                                type="button"
                                aria-label="Fewer servings"
                                class="flex h-11 w-11 items-center justify-center rounded-full border border-black/10 text-xl leading-none transition hover:bg-black/5 disabled:opacity-40 dark:border-white/15 dark:hover:bg-white/10"
                                :disabled="servings <= 1"
                                @click="adjust(-1)"
                            >
                                −
                            </button>
                            <input
                                v-model.number="servings"
                                type="number"
                                min="1"
                                max="100"
                                class="w-16 rounded-lg border border-black/10 bg-transparent px-2 py-1.5 text-center text-lg font-semibold tabular-nums dark:border-white/15"
                            />
                            <button
                                type="button"
                                aria-label="More servings"
                                class="flex h-11 w-11 items-center justify-center rounded-full border border-black/10 text-xl leading-none transition hover:bg-black/5 disabled:opacity-40 dark:border-white/15 dark:hover:bg-white/10"
                                :disabled="servings >= 100"
                                @click="adjust(1)"
                            >
                                +
                            </button>
                        </div>
                        <p v-if="isScaled" class="mt-2 text-xs text-amber-700 dark:text-amber-500">
                            Scaled from {{ recipe.servings }} servings.
                        </p>
                    </div>

                    <p class="mt-2 hidden text-sm text-[#706f6c] print:block">Serves {{ servings }}</p>

                    <ul class="mt-6 space-y-2.5 print:mt-3">
                        <li
                            v-for="ingredient in scaledIngredients"
                            :key="ingredient.id"
                            class="flex items-baseline justify-between gap-4 border-b border-black/5 pb-2.5 text-sm last:border-0 dark:border-white/10"
                        >
                            <span>{{ ingredient.name }}</span>
                            <span class="shrink-0 font-medium tabular-nums text-right">
                                <template v-if="ingredient.display">
                                    {{ ingredient.display }}<template v-if="ingredient.unit">&nbsp;{{ ingredient.unit }}</template>
                                </template>
                                <span v-else class="text-[#706f6c] dark:text-[#A1A09A]">to taste</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Method -->
            <section>
                <h2 class="font-semibold">Method</h2>
                <ol class="mt-4 space-y-4">
                    <li
                        v-for="(step, index) in steps"
                        :key="index"
                        class="flex gap-4"
                    >
                        <span
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-800 dark:bg-amber-500/15 dark:text-amber-400 print:border print:border-black/40 print:bg-transparent print:text-black"
                        >
                            {{ index + 1 }}
                        </span>
                        <p class="pt-0.5 text-[#3a3a36] dark:text-[#c9c9c4]">{{ step }}</p>
                    </li>
                </ol>
            </section>
        </div>
    </AppLayout>

    <!-- Cook mode overlay -->
    <div
        v-if="cookMode"
        class="fixed inset-0 z-50 overflow-y-auto bg-[#FBF9F6] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <div class="mx-auto max-w-6xl px-6 py-6 lg:px-10">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight lg:text-3xl">{{ recipe.title }}</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">Tap items to check them off.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            aria-label="Fewer servings"
                            class="flex h-12 w-12 items-center justify-center rounded-full border border-black/10 text-2xl leading-none transition hover:bg-black/5 disabled:opacity-40 dark:border-white/15 dark:hover:bg-white/10"
                            :disabled="servings <= 1"
                            @click="adjust(-1)"
                        >
                            −
                        </button>
                        <span class="w-24 text-center text-base font-medium tabular-nums">{{ servings }} servings</span>
                        <button
                            type="button"
                            aria-label="More servings"
                            class="flex h-12 w-12 items-center justify-center rounded-full border border-black/10 text-2xl leading-none transition hover:bg-black/5 disabled:opacity-40 dark:border-white/15 dark:hover:bg-white/10"
                            :disabled="servings >= 100"
                            @click="adjust(1)"
                        >
                            +
                        </button>
                    </div>
                    <button
                        type="button"
                        class="rounded-full bg-[#1b1b18] px-5 py-3 text-base font-medium text-white dark:bg-white dark:text-[#1b1b18]"
                        @click="exitCookMode"
                    >
                        Done
                    </button>
                </div>
            </div>

            <div class="mt-8 grid gap-10 lg:grid-cols-[minmax(0,22rem)_1fr]">
                <!-- Ingredients -->
                <section>
                    <h2 class="text-lg font-semibold">Ingredients</h2>
                    <ul class="mt-4 space-y-1">
                        <li
                            v-for="(ingredient, index) in scaledIngredients"
                            :key="ingredient.id"
                        >
                            <button
                                type="button"
                                class="flex w-full items-baseline justify-between gap-4 rounded-lg px-3 py-3 text-left text-lg transition hover:bg-black/5 dark:hover:bg-white/5"
                                :class="checkedIngredients.has(index) ? 'text-[#a1a09a] line-through dark:text-[#6b6b66]' : ''"
                                @click="toggleIngredient(index)"
                            >
                                <span>{{ ingredient.name }}</span>
                                <span class="shrink-0 font-semibold tabular-nums">
                                    <template v-if="ingredient.display">
                                        {{ ingredient.display }}<template v-if="ingredient.unit">&nbsp;{{ ingredient.unit }}</template>
                                    </template>
                                    <span v-else class="text-[#706f6c] dark:text-[#A1A09A]">to taste</span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </section>

                <!-- Method -->
                <section>
                    <h2 class="text-lg font-semibold">Method</h2>
                    <ol class="mt-4 space-y-2">
                        <li v-for="(step, index) in steps" :key="index">
                            <button
                                type="button"
                                class="flex w-full items-start gap-4 rounded-xl px-3 py-4 text-left transition hover:bg-black/5 dark:hover:bg-white/5"
                                :class="checkedSteps.has(index) ? 'opacity-50' : ''"
                                @click="toggleStep(index)"
                            >
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-base font-semibold"
                                    :class="checkedSteps.has(index)
                                        ? 'bg-green-600 text-white'
                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-400'"
                                >
                                    {{ checkedSteps.has(index) ? '✓' : index + 1 }}
                                </span>
                                <p class="pt-1 text-lg leading-relaxed" :class="checkedSteps.has(index) ? 'line-through' : ''">
                                    {{ step }}
                                </p>
                            </button>
                        </li>
                    </ol>
                </section>
            </div>
        </div>
    </div>
</template>
