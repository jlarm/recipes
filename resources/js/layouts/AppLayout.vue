<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { index as recipesIndex, create as recipesCreate } from '@/routes/recipes';
import { index as shoppingListIndex } from '@/routes/shopping-list';

const page = usePage();
const flash = computed(() => (page.props.flash as { success?: string } | undefined)?.success);
</script>

<template>
    <div class="min-h-screen bg-[#FBF9F6] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <header
            class="sticky top-0 z-10 border-b border-black/5 bg-[#FBF9F6]/80 backdrop-blur dark:border-white/10 dark:bg-[#0a0a0a]/80 print:hidden"
        >
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
                <Link :href="recipesIndex().url" class="flex items-center gap-2 font-semibold tracking-tight">
                    <span class="text-xl">🍳</span>
                    <span>The Cookbook</span>
                </Link>
                <div class="flex items-center gap-2 sm:gap-3">
                    <Link
                        :href="shoppingListIndex().url"
                        class="rounded-full px-3 py-2 text-sm font-medium text-[#706f6c] transition hover:bg-black/5 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-white/10 dark:hover:text-white"
                    >
                        🛒 <span class="hidden sm:inline">Shopping list</span>
                    </Link>
                    <Link
                        :href="recipesCreate().url"
                        class="rounded-full bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#333] dark:bg-white dark:text-[#1b1b18] dark:hover:bg-[#e5e5e5]"
                    >
                        Add a recipe
                    </Link>
                </div>
            </div>
        </header>

        <div
            v-if="flash"
            class="mx-auto mt-4 max-w-4xl px-6"
        >
            <div class="rounded-lg border border-green-600/20 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-400/20 dark:bg-green-950/40 dark:text-green-300">
                {{ flash }}
            </div>
        </div>

        <main class="mx-auto max-w-4xl px-6 py-10">
            <slot />
        </main>
    </div>
</template>
