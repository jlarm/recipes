<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatQuantity, scaleQuantity } from '@/lib/quantity';
import { create as recipesCreate, show as recipesShow } from '@/routes/recipes';
import type { ShoppingRecipe } from '@/types/recipes';

const props = defineProps<{
    recipes: ShoppingRecipe[];
}>();

const page = usePage();
const passcodeVerified = computed(() => page.props.passcodeVerified === true);

const SELECTION_KEY = 'shopping-selection';
const CHECKED_KEY = 'shopping-checked';

// slug -> chosen servings for that recipe
const selection = ref<Record<string, number>>({});
const checked = ref<Set<string>>(new Set());

const selectedCount = computed(() => Object.keys(selection.value).length);

function isSelected(slug: string): boolean {
    return slug in selection.value;
}

function toggleRecipe(recipe: ShoppingRecipe): void {
    const next = { ...selection.value };

    if (recipe.slug in next) {
        delete next[recipe.slug];
    } else {
        next[recipe.slug] = recipe.servings;
    }

    selection.value = next;
}

function setServings(slug: string, value: number): void {
    const clamped = Math.min(100, Math.max(1, Math.round(value) || 1));
    selection.value = { ...selection.value, [slug]: clamped };
}

function adjustServings(slug: string, delta: number): void {
    setServings(slug, (selection.value[slug] ?? 1) + delta);
}

const items = computed(() => {
    const map = new Map<string, { name: string; unit: string | null; quantity: number | null }>();

    for (const recipe of props.recipes) {
        const servings = selection.value[recipe.slug];

        if (servings === undefined) {
            continue;
        }

        for (const ingredient of recipe.ingredients) {
            const scaled = scaleQuantity(ingredient.quantity, recipe.servings, servings);
            const key = `${ingredient.name.trim().toLowerCase()}|${(ingredient.unit ?? '').trim().toLowerCase()}`;
            const existing = map.get(key);

            if (!existing) {
                map.set(key, { name: ingredient.name.trim(), unit: ingredient.unit, quantity: scaled });
            } else if (scaled !== null) {
                existing.quantity = (existing.quantity ?? 0) + scaled;
            }
        }
    }

    return [...map.entries()]
        .map(([key, value]) => ({
            key,
            name: value.name,
            unit: value.unit,
            display: formatQuantity(value.quantity),
        }))
        .sort((a, b) => a.name.localeCompare(b.name));
});

const remainingCount = computed(() => items.value.filter((item) => !checked.value.has(item.key)).length);

function toggleChecked(key: string): void {
    const next = new Set(checked.value);

    if (next.has(key)) {
        next.delete(key);
    } else {
        next.add(key);
    }

    checked.value = next;
}

function clearAll(): void {
    selection.value = {};
    checked.value = new Set();
}

const copied = ref(false);

async function copyList(): Promise<void> {
    const text = items.value
        .map((item) => {
            const amount = item.display ? `${item.display}${item.unit ? ` ${item.unit}` : ''} ` : '';

            return `- ${amount}${item.name}`;
        })
        .join('\n');

    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 1500);
    } catch {
        /* clipboard unavailable (e.g. non-secure context) */
    }
}

onMounted(() => {
    try {
        const known = new Set(props.recipes.map((recipe) => recipe.slug));
        const storedSelection = localStorage.getItem(SELECTION_KEY);

        if (storedSelection) {
            const parsed = JSON.parse(storedSelection) as Record<string, unknown>;
            const valid: Record<string, number> = {};

            for (const [slug, servings] of Object.entries(parsed)) {
                if (known.has(slug) && typeof servings === 'number') {
                    valid[slug] = servings;
                }
            }

            selection.value = valid;
        }

        const storedChecked = localStorage.getItem(CHECKED_KEY);

        if (storedChecked) {
            checked.value = new Set(JSON.parse(storedChecked) as string[]);
        }
    } catch {
        /* ignore malformed storage */
    }
});

watch(
    selection,
    (value) => {
        try {
            localStorage.setItem(SELECTION_KEY, JSON.stringify(value));
        } catch {
            /* storage unavailable */
        }
    },
    { deep: true },
);

watch(checked, (value) => {
    try {
        localStorage.setItem(CHECKED_KEY, JSON.stringify([...value]));
    } catch {
        /* storage unavailable */
    }
});
</script>

<template>
    <Head title="Shopping list" />

    <AppLayout>
        <div class="mb-6">
            <h1 class="text-3xl font-semibold tracking-tight">Shopping list</h1>
            <p class="mt-1 text-[#706f6c] dark:text-[#A1A09A]">
                Pick recipes and set servings — ingredients combine into one list.
            </p>
        </div>

        <div v-if="recipes.length === 0" class="rounded-xl border border-dashed border-black/10 p-12 text-center dark:border-white/15">
            <p class="text-[#706f6c] dark:text-[#A1A09A]">No recipes to add yet.</p>
            <Link
                v-if="passcodeVerified"
                :href="recipesCreate().url"
                class="mt-4 inline-block rounded-full bg-[#1b1b18] px-5 py-2 text-sm font-medium text-white dark:bg-white dark:text-[#1b1b18]"
            >
                Add a recipe
            </Link>
        </div>

        <div v-else class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
            <!-- Recipe picker -->
            <section>
                <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">
                    Recipes
                </h2>
                <ul class="space-y-2">
                    <li
                        v-for="recipe in recipes"
                        :key="recipe.id"
                        class="flex items-center justify-between gap-4 rounded-xl border p-4 transition"
                        :class="isSelected(recipe.slug)
                            ? 'border-amber-500/60 bg-amber-50 dark:bg-amber-500/10'
                            : 'border-black/5 bg-white dark:border-white/10 dark:bg-[#161615]'"
                    >
                        <label class="flex flex-1 cursor-pointer items-center gap-3">
                            <input
                                type="checkbox"
                                class="h-5 w-5 shrink-0 accent-amber-600"
                                :checked="isSelected(recipe.slug)"
                                @change="toggleRecipe(recipe)"
                            />
                            <span class="font-medium">{{ recipe.title }}</span>
                        </label>

                        <div v-if="isSelected(recipe.slug)" class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                aria-label="Fewer servings"
                                class="flex h-9 w-9 items-center justify-center rounded-full border border-black/10 text-lg leading-none transition hover:bg-black/5 disabled:opacity-40 dark:border-white/15 dark:hover:bg-white/10"
                                :disabled="selection[recipe.slug] <= 1"
                                @click="adjustServings(recipe.slug, -1)"
                            >
                                −
                            </button>
                            <input
                                type="number"
                                min="1"
                                max="100"
                                :value="selection[recipe.slug]"
                                class="w-14 rounded-lg border border-black/10 bg-transparent px-2 py-1 text-center text-sm font-semibold tabular-nums dark:border-white/15"
                                @input="setServings(recipe.slug, Number(($event.target as HTMLInputElement).value))"
                            />
                            <button
                                type="button"
                                aria-label="More servings"
                                class="flex h-9 w-9 items-center justify-center rounded-full border border-black/10 text-lg leading-none transition hover:bg-black/5 disabled:opacity-40 dark:border-white/15 dark:hover:bg-white/10"
                                :disabled="selection[recipe.slug] >= 100"
                                @click="adjustServings(recipe.slug, 1)"
                            >
                                +
                            </button>
                        </div>
                        <Link
                            v-else
                            :href="recipesShow(recipe.slug).url"
                            class="shrink-0 text-xs text-[#706f6c] hover:underline dark:text-[#A1A09A]"
                        >
                            View
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- Consolidated list -->
            <section class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold">
                            List
                            <span class="text-sm font-normal text-[#706f6c] dark:text-[#A1A09A]">
                                ({{ remainingCount }} left)
                            </span>
                        </h2>
                        <button
                            v-if="selectedCount > 0"
                            type="button"
                            class="text-xs text-[#706f6c] hover:underline dark:text-[#A1A09A]"
                            @click="clearAll"
                        >
                            Clear
                        </button>
                    </div>

                    <p v-if="items.length === 0" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Select a recipe to start your list.
                    </p>

                    <template v-else>
                        <ul class="mt-4 space-y-1">
                            <li v-for="item in items" :key="item.key">
                                <button
                                    type="button"
                                    class="flex w-full items-baseline justify-between gap-3 rounded-lg px-2 py-2 text-left text-sm transition hover:bg-black/5 dark:hover:bg-white/5"
                                    :class="checked.has(item.key) ? 'text-[#a1a09a] line-through dark:text-[#6b6b66]' : ''"
                                    @click="toggleChecked(item.key)"
                                >
                                    <span>{{ item.name }}</span>
                                    <span class="shrink-0 font-medium tabular-nums">
                                        <template v-if="item.display">
                                            {{ item.display }}<template v-if="item.unit"> {{ item.unit }}</template>
                                        </template>
                                        <span v-else class="text-[#706f6c] dark:text-[#A1A09A]">to taste</span>
                                    </span>
                                </button>
                            </li>
                        </ul>

                        <button
                            type="button"
                            class="mt-4 w-full rounded-full bg-[#1b1b18] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#333] dark:bg-white dark:text-[#1b1b18] dark:hover:bg-[#e5e5e5]"
                            @click="copyList"
                        >
                            {{ copied ? 'Copied!' : 'Copy list' }}
                        </button>
                    </template>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
