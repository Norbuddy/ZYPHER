<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, required: true } });

const config = {
    pending_payment: { label: 'Pending Payment', classes: 'bg-amber-50 text-amber-700 border-amber-200' },
    processing: { label: 'Processing', classes: 'bg-amber-50 text-amber-700 border-amber-200' },
    shipped: { label: 'Shipped', classes: 'bg-blue-50 text-blue-700 border-blue-200' },
    completed: { label: 'Completed', classes: 'bg-green-50 text-green-700 border-green-200' },
    cancelled: { label: 'Cancelled', classes: 'bg-red-50 text-red-700 border-red-200' },
    refund_requested: { label: 'Refund Requested', classes: 'bg-red-50 text-red-700 border-red-200' },
    refunded: { label: 'Refunded', classes: 'bg-red-50 text-red-700 border-red-200' },
};

const style = computed(() => config[props.status] ?? { label: props.status, classes: 'bg-gray-50 text-gray-700 border-gray-200' });
</script>

<template>
    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border" :class="style.classes">
        {{ style.label }}
    </span>
</template>
