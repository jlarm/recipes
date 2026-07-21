<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatQuantity, parseQuantity } from '@/lib/quantity';
import { COMMON_UNITS } from '@/lib/units';
import { index as recipesIndex, store as recipesStore, update as recipesUpdate } from '@/routes/recipes';
import type { Recipe } from '@/types/recipes';

interface IngredientRow {
    // Free text so contributors can enter fractions ("1/2") or decimals ("0.5").
    quantity: string;
    unit: string;
    name: string;
}

const props = defineProps<{
    recipe?: Recipe;
    categories: string[];
}>();

const isEdit = computed(() => props.recipe !== undefined);

function initialIngredients(): IngredientRow[] {
    if (props.recipe) {
        return props.recipe.ingredients.map((ingredient) => ({
            quantity: formatQuantity(ingredient.quantity),
            unit: ingredient.unit ?? '',
            name: ingredient.name,
        }));
    }

    return [
        { quantity: '', unit: '', name: '' },
        { quantity: '', unit: '', name: '' },
        { quantity: '', unit: '', name: '' },
    ];
}

const form = useForm<{
    title: string;
    category: string;
    image: File | null;
    remove_image: boolean;
    description: string;
    servings: number;
    prep_minutes: number | null;
    cook_minutes: number | null;
    instructions: string;
    ingredients: IngredientRow[];
}>({
    title: props.recipe?.title ?? '',
    category: props.recipe?.category?.name ?? '',
    image: null,
    remove_image: false,
    description: props.recipe?.description ?? '',
    servings: props.recipe?.servings ?? 4,
    prep_minutes: props.recipe?.prep_minutes ?? null,
    cook_minutes: props.recipe?.cook_minutes ?? null,
    instructions: props.recipe?.instructions ?? '',
    ingredients: initialIngredients(),
});

// Preview of a newly-selected file, if any.
const newImagePreview = ref<string | null>(null);

// What the image slot should show: new upload > existing (unless removed) > none.
const previewUrl = computed<string | null>(() => {
    if (newImagePreview.value) {
        return newImagePreview.value;
    }

    if (form.remove_image) {
        return null;
    }

    return props.recipe?.image_url ?? null;
});

function onImageChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (newImagePreview.value) {
        URL.revokeObjectURL(newImagePreview.value);
    }

    form.image = file;
    form.remove_image = false;
    newImagePreview.value = file ? URL.createObjectURL(file) : null;
}

function removePhoto(): void {
    if (newImagePreview.value) {
        URL.revokeObjectURL(newImagePreview.value);
        newImagePreview.value = null;
    }

    form.image = null;
    form.remove_image = true;
}

function isInvalidQuantity(row: IngredientRow): boolean {
    return row.quantity.trim() !== '' && parseQuantity(row.quantity) === null;
}

const hasInvalidQuantity = computed(() => form.ingredients.some(isInvalidQuantity));

function addIngredient(): void {
    form.ingredients.push({ quantity: '', unit: '', name: '' });
}

function removeIngredient(index: number): void {
    if (form.ingredients.length > 1) {
        form.ingredients.splice(index, 1);
    }
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        ingredients: data.ingredients.map((ingredient) => ({
            name: ingredient.name,
            quantity: parseQuantity(ingredient.quantity),
            unit: ingredient.unit,
        })),
    }));

    if (props.recipe) {
        form.put(recipesUpdate(props.recipe.slug).url);
    } else {
        form.post(recipesStore().url);
    }
}

const inputClass =
    'w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-white/15 dark:bg-[#161615]';
const labelClass = 'block text-sm font-medium';
</script>

<template>
    <form class="space-y-8" @submit.prevent="submit">
        <div class="space-y-4 rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5" :class="labelClass" for="title">Title</label>
                    <input id="title" v-model="form.title" type="text" :class="inputClass" placeholder="Fluffy Buttermilk Pancakes" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="mb-1.5" :class="labelClass" for="category">Category</label>
                    <input
                        id="category"
                        v-model="form.category"
                        type="text"
                        list="category-options"
                        autocomplete="off"
                        :class="inputClass"
                        placeholder="Breakfast"
                    />
                    <datalist id="category-options">
                        <option v-for="name in categories" :key="name" :value="name" />
                    </datalist>
                    <p v-if="form.errors.category" class="mt-1 text-xs text-red-600">{{ form.errors.category }}</p>
                </div>
            </div>

            <div>
                <span class="mb-1.5 block text-sm font-medium">Photo <span class="font-normal text-[#706f6c] dark:text-[#A1A09A]">(optional)</span></span>
                <div class="flex items-center gap-4">
                    <div class="flex h-24 w-32 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-black/10 bg-black/5 dark:border-white/15 dark:bg-white/5">
                        <img v-if="previewUrl" :src="previewUrl" alt="Recipe photo preview" class="h-full w-full object-cover" />
                        <span v-else class="text-2xl opacity-40">🍽</span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="inline-flex cursor-pointer items-center rounded-lg border border-black/10 px-4 py-2 text-sm font-medium transition hover:bg-black/5 dark:border-white/15 dark:hover:bg-white/10">
                            {{ previewUrl ? 'Change photo' : 'Upload photo' }}
                            <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onImageChange" />
                        </label>
                        <button
                            v-if="previewUrl"
                            type="button"
                            class="text-left text-xs text-red-600 hover:underline"
                            @click="removePhoto"
                        >
                            Remove photo
                        </button>
                    </div>
                </div>
                <p v-if="form.errors.image" class="mt-1 text-xs text-red-600">{{ form.errors.image }}</p>
            </div>

            <div>
                <label class="mb-1.5" :class="labelClass" for="description">Description <span class="font-normal text-[#706f6c] dark:text-[#A1A09A]">(optional)</span></label>
                <textarea id="description" v-model="form.description" rows="2" :class="inputClass" placeholder="A short summary of the dish." />
                <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="mb-1.5" :class="labelClass" for="servings">Servings</label>
                    <input id="servings" v-model.number="form.servings" type="number" min="1" max="100" :class="inputClass" />
                    <p v-if="form.errors.servings" class="mt-1 text-xs text-red-600">{{ form.errors.servings }}</p>
                </div>
                <div>
                    <label class="mb-1.5" :class="labelClass" for="prep">Prep (min)</label>
                    <input id="prep" v-model.number="form.prep_minutes" type="number" min="0" max="1440" :class="inputClass" />
                    <p v-if="form.errors.prep_minutes" class="mt-1 text-xs text-red-600">{{ form.errors.prep_minutes }}</p>
                </div>
                <div>
                    <label class="mb-1.5" :class="labelClass" for="cook">Cook (min)</label>
                    <input id="cook" v-model.number="form.cook_minutes" type="number" min="0" max="1440" :class="inputClass" />
                    <p v-if="form.errors.cook_minutes" class="mt-1 text-xs text-red-600">{{ form.errors.cook_minutes }}</p>
                </div>
            </div>
        </div>

        <!-- Ingredients -->
        <div class="rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Ingredients</h2>
                    <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                        Amounts are for {{ form.servings || '?' }} servings and scale automatically when viewed.
                        Enter quantities as fractions or decimals, e.g. <code>1/2</code> or <code>0.5</code>. Leave blank for “to taste”.
                    </p>
                </div>
            </div>

            <p v-if="typeof form.errors.ingredients === 'string'" class="mt-2 text-xs text-red-600">
                {{ form.errors.ingredients }}
            </p>

            <datalist id="unit-options">
                <option v-for="unit in COMMON_UNITS" :key="unit" :value="unit" />
            </datalist>

            <datalist id="qty-options">
                <option v-for="amount in ['1/4', '1/3', '1/2', '2/3', '3/4', '1', '2']" :key="amount" :value="amount" />
            </datalist>

            <div class="mt-4 space-y-3">
                <div
                    v-for="(ingredient, index) in form.ingredients"
                    :key="index"
                    class="flex items-start gap-2"
                >
                    <div class="w-20 shrink-0">
                        <input
                            v-model="ingredient.quantity"
                            type="text"
                            inputmode="text"
                            list="qty-options"
                            placeholder="Qty"
                            autocomplete="off"
                            :class="[inputClass, isInvalidQuantity(ingredient) ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : '']"
                        />
                    </div>
                    <div class="w-24 shrink-0">
                        <input
                            v-model="ingredient.unit"
                            type="text"
                            list="unit-options"
                            placeholder="unit"
                            autocomplete="off"
                            :class="inputClass"
                        />
                    </div>
                    <div class="flex-1">
                        <input v-model="ingredient.name" type="text" placeholder="Ingredient name" :class="inputClass" />
                        <p v-if="(form.errors as Record<string, string>)[`ingredients.${index}.name`]" class="mt-1 text-xs text-red-600">
                            {{ (form.errors as Record<string, string>)[`ingredients.${index}.name`] }}
                        </p>
                    </div>
                    <button
                        type="button"
                        aria-label="Remove ingredient"
                        class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-[#706f6c] transition hover:bg-red-50 hover:text-red-600 disabled:opacity-30 dark:hover:bg-red-950/40"
                        :disabled="form.ingredients.length <= 1"
                        @click="removeIngredient(index)"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <button
                type="button"
                class="mt-4 rounded-lg border border-dashed border-black/15 px-4 py-2 text-sm font-medium transition hover:bg-black/5 dark:border-white/20 dark:hover:bg-white/5"
                @click="addIngredient"
            >
                + Add ingredient
            </button>

            <p v-if="hasInvalidQuantity" class="mt-3 text-xs text-red-600">
                Some quantities aren’t a valid number or fraction. Use forms like 2, 0.5, or 1/2.
            </p>
        </div>

        <!-- Method -->
        <div class="rounded-xl border border-black/5 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#161615]">
            <label class="mb-1.5" :class="labelClass" for="instructions">Method</label>
            <p class="mb-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">One step per line.</p>
            <textarea id="instructions" v-model="form.instructions" rows="8" :class="inputClass" placeholder="Whisk the dry ingredients together…" />
            <p v-if="form.errors.instructions" class="mt-1 text-xs text-red-600">{{ form.errors.instructions }}</p>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="rounded-full bg-[#1b1b18] px-6 py-2.5 text-sm font-medium text-white transition hover:bg-[#333] disabled:opacity-50 dark:bg-white dark:text-[#1b1b18] dark:hover:bg-[#e5e5e5]"
                :disabled="form.processing || hasInvalidQuantity"
            >
                <template v-if="form.processing">{{ isEdit ? 'Updating…' : 'Saving…' }}</template>
                <template v-else>{{ isEdit ? 'Update recipe' : 'Save recipe' }}</template>
            </button>
            <Link :href="recipesIndex().url" class="text-sm text-[#706f6c] hover:underline dark:text-[#A1A09A]">
                Cancel
            </Link>
        </div>
    </form>
</template>
