<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });
</script>

<template>
    <div class="mx-auto flex max-w-3xl flex-col items-center justify-center px-6 py-24 text-center">
        <div class="mb-6 rounded-full bg-[#6D5EF8]/10 p-4 text-[#6D5EF8]">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 3l7 4v5c0 4.2-2.6 7.8-7 9-4.4-1.2-7-4.8-7-9V7l7-4Z" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M9.5 12.5l1.7 1.7 3.3-3.6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>

        <h1 class="text-3xl font-semibold tracking-tight text-[#101114]">Your seller request is pending approval</h1>
        <p class="mt-4 max-w-2xl text-lg text-[#5F5E5A]">
            Your shop application has been submitted successfully. We’re reviewing it now and will notify you as soon as it’s approved.
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a :href="route('home')" class="rounded-xl bg-[#101114] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#1f2330]">
                Back to Storefront
            </a>
        </div>
    </div>
</template>
