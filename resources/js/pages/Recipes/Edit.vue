<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import RecipeForm from '@/components/RecipeForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as recipesShow } from '@/routes/recipes';
import type { Recipe } from '@/types/recipes';

const props = defineProps<{
    recipe: Recipe;
    categories: string[];
}>();
</script>

<template>
    <Head :title="`Edit ${props.recipe.title}`" />

    <AppLayout>
        <Link :href="recipesShow(props.recipe.slug).url" class="text-sm text-[#706f6c] hover:underline dark:text-[#A1A09A]">
            ← Back to recipe
        </Link>

        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Edit recipe</h1>

        <div class="mt-8">
            <RecipeForm :recipe="props.recipe" :categories="categories" />
        </div>
    </AppLayout>
</template>
