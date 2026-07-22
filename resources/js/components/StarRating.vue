<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        /** Current rating, 1–5, or null when unrated. */
        modelValue?: number | null;
        /** When true the stars are buttons that update the value; otherwise display-only. */
        editable?: boolean;
        /** Show a "Clear" affordance when editable and a rating is set. */
        clearable?: boolean;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        modelValue: null,
        editable: false,
        clearable: true,
        size: 'md',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const hovered = ref<number | null>(null);

const active = computed(() => hovered.value ?? props.modelValue ?? 0);

const sizeClass = computed(() => ({ sm: 'text-sm', md: 'text-lg', lg: 'text-2xl' })[props.size]);

const stars = [1, 2, 3, 4, 5];

function pick(value: number): void {
    // Clicking the current single-star value again clears it.
    emit('update:modelValue', props.modelValue === value ? null : value);
}
</script>

<template>
    <div class="inline-flex items-center gap-0.5">
        <template v-if="editable">
            <button
                v-for="star in stars"
                :key="star"
                type="button"
                :aria-label="`${star} star${star === 1 ? '' : 's'}`"
                :aria-pressed="(modelValue ?? 0) >= star"
                class="leading-none transition hover:scale-110"
                :class="sizeClass"
                @click="pick(star)"
                @mouseenter="hovered = star"
                @mouseleave="hovered = null"
                @focus="hovered = star"
                @blur="hovered = null"
            >
                <span :class="star <= active ? 'text-amber-500' : 'text-black/20 dark:text-white/25'">★</span>
            </button>
            <button
                v-if="clearable && modelValue"
                type="button"
                class="ml-1.5 text-xs text-[#706f6c] hover:underline dark:text-[#A1A09A]"
                @click="emit('update:modelValue', null)"
            >
                Clear
            </button>
        </template>
        <template v-else>
            <span
                v-for="star in stars"
                :key="star"
                class="leading-none"
                :class="[sizeClass, star <= (modelValue ?? 0) ? 'text-amber-500' : 'text-black/20 dark:text-white/25']"
                aria-hidden="true"
            >
                ★
            </span>
            <span class="sr-only">{{ modelValue ? `Rated ${modelValue} out of 5` : 'Not rated' }}</span>
        </template>
    </div>
</template>
