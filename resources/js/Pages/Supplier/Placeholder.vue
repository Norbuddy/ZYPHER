<script setup>
import { Head } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';

defineOptions({ layout: SupplierLayout });

defineProps({
    title: {
        type: String,
        default: 'Supplier Center',
    },
    description: {
        type: String,
        default: 'This section is ready for your next supplier workflow.',
    },
});
</script>

<template>
    <div class="space-y-6">
        <Head :title="title" />

        <div class="mx-auto max-w-6xl rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-semibold text-[#101114]">{{ title }}</h1>
            <p class="mt-3 text-sm text-[#8A8B90]">{{ description }}</p>
        </div>
    </div>
</template>
