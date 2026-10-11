<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });
</script>

<template>
    <div class="mx-auto flex max-w-3xl flex-col items-center justify-center px-6 py-24 text-center">
        <div class="mb-6 rounded-full bg-red-100 p-4 text-red-600">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>

        <h1 class="text-3xl font-semibold tracking-tight text-[#101114]">Your seller application was denied</h1>
        <p class="mt-4 max-w-2xl text-lg text-[#5F5E5A]">
            Unfortunately, your shop application was not approved. You can return to the storefront or contact support for more information.
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a :href="route('home')" class="rounded-xl bg-[#101114] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#1f2330]">
                Back to Storefront
            </a>
        </div>
    </div>
</template>
