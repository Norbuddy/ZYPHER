<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    terms: String,
});
</script>

<template>
    <Head title="Terms of Service — Zypher" />

    <div class="font-sans text-[#101114] antialiased bg-[#F7F7F5] min-h-screen py-8 sm:py-12 px-4 sm:px-6">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-8 pb-4 border-b border-[#EAEAE5]">
                <Link :href="route('home')" class="flex items-center gap-2 text-[#101114]">
                    <svg viewBox="0 0 200 130" class="h-7 w-auto fill-black">
                        <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                    </svg>
                    <span class="font-bold text-lg tracking-tight font-display">Zypher</span>
                </Link>
                <Link :href="route('home')" class="text-xs font-semibold text-[#101114] hover:text-[#6D5EF8] transition-colors">
                    &larr; Return to Store
                </Link>
            </div>

            <div class="p-6 sm:p-10 bg-white shadow-sm border border-[#EAEAE5] rounded-3xl prose prose-sm sm:prose max-w-none text-[#101114] leading-relaxed" v-html="terms" />
        </div>
    </div>
</template>
