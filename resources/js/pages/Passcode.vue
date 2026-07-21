<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { verify as passcodeVerify } from '@/routes/passcode';

const form = useForm({
    passcode: '',
});

function submit(): void {
    form.post(passcodeVerify().url, {
        onFinish: () => form.reset('passcode'),
    });
}
</script>

<template>
    <Head title="Enter passcode" />

    <AppLayout>
        <div class="mx-auto max-w-md">
            <div class="rounded-xl border border-black/5 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-[#161615]">
                <div class="text-center">
                    <span class="text-3xl">🔒</span>
                    <h1 class="mt-3 text-2xl font-semibold tracking-tight">Contributor passcode</h1>
                    <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Recipes are public to read. Enter the shared passcode to add one.
                    </p>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <input
                            v-model="form.passcode"
                            type="password"
                            autofocus
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
                </form>
            </div>
        </div>
    </AppLayout>
</template>
