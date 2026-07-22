<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';
import { verify as passcodeVerify } from '@/routes/passcode';

const open = defineModel<boolean>('open', { required: true });

const input = ref<HTMLInputElement | null>(null);

const form = useForm({
    passcode: '',
    redirect: '',
});

// Focus the field and clear stale errors each time the dialog opens.
watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        void nextTick(() => input.value?.focus());
    } else {
        form.reset('passcode');
    }
});

function close(): void {
    open.value = false;
}

function submit(): void {
    // Return to the page the contributor was on rather than the create form.
    form.redirect = window.location.pathname + window.location.search;

    form.post(passcodeVerify().url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onFinish: () => form.reset('passcode'),
    });
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-start justify-center bg-black/40 px-4 pt-24 backdrop-blur-sm"
                @click.self="close"
                @keydown.esc="close"
            >
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="passcode-dialog-title"
                    class="w-full max-w-sm rounded-xl border border-black/5 bg-white p-8 shadow-xl dark:border-white/10 dark:bg-[#161615]"
                >
                    <div class="text-center">
                        <span class="text-3xl">🔒</span>
                        <h2 id="passcode-dialog-title" class="mt-3 text-xl font-semibold tracking-tight">Contributor passcode</h2>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Recipes are public to read. Enter the shared passcode to add or edit one.
                        </p>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div>
                            <input
                                ref="input"
                                v-model="form.passcode"
                                type="password"
                                autocomplete="off"
                                placeholder="Passcode"
                                class="w-full rounded-lg border border-black/10 bg-white px-3 py-2.5 text-center outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-white/15 dark:bg-[#0f0f0e]"
                            />
                            <p v-if="form.errors.passcode" class="mt-2 text-center text-sm text-red-600">
                                {{ form.errors.passcode }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-full bg-[#1b1b18] px-6 py-2.5 text-sm font-medium text-white transition hover:bg-[#333] disabled:opacity-50 dark:bg-white dark:text-[#1b1b18] dark:hover:bg-[#e5e5e5]"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Checking…' : 'Continue' }}
                        </button>
                        <button
                            type="button"
                            class="w-full text-center text-sm text-[#706f6c] hover:underline dark:text-[#A1A09A]"
                            @click="close"
                        >
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
