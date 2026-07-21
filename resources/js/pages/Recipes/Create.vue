<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import RecipeForm from '@/components/RecipeForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as recipesIndex } from '@/routes/recipes';

defineProps<{
    categories: string[];
}>();
</script>

<template>
    <Head title="Add a recipe" />

    <AppLayout>
        <Link :href="recipesIndex().url" class="text-sm text-[#706f6c] hover:underline dark:text-[#A1A09A]">
            ← All recipes
        </Link>

        <h1 class="mt-4 text-3xl font-semibold tracking-tight">Add a recipe</h1>

        <div class="mt-8">
            <RecipeForm :categories="categories" />
        </div>
    </AppLayout>
</template>
