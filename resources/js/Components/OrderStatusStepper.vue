<script setup>
import { computed } from 'vue';

const props = defineProps({
    orderShop: { type: Object, required: true },
});

// Ordered list of steps. If status is cancelled/refund, we show a distinct branch instead.
const steps = computed(() => {
    const s = props.orderShop;
    const isTerminalIssue = ['cancelled', 'refund_requested', 'refunded'].includes(s.status);

    const base = [
        { key: 'placed', label: 'Order placed', timestamp: s.created_at, done: true },
        { key: 'processing', label: 'Processing', timestamp: s.processing_at, done: !!s.processing_at },
        {
            key: 'shipped',
            label: 'Shipped',
            timestamp: s.shipped_at,
            done: !!s.shipped_at,
            detail: s.shipped_at ? `Courier: ${s.courier_name} · Tracking: ${s.tracking_number}` : null,
        },
        { key: 'completed', label: 'Delivered', timestamp: s.completed_at, done: !!s.completed_at },
    ];

    if (isTerminalIssue) {
        base.push({
            key: 'issue',
            label: s.status === 'cancelled' ? 'Cancelled' : s.status === 'refunded' ? 'Refunded' : 'Refund Requested',
            timestamp: s.cancelled_at || s.updated_at,
            done: true,
            isIssue: true,
        });
    }

    return base;
});

const formatDate = (ts) => {
    if (!ts) return null;
    return new Date(ts).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
};
</script>

<template>
    <div class="space-y-0">
        <div v-for="(step, i) in steps" :key="step.key" class="flex gap-4">
            <!-- Timeline dot + connector line -->
            <div class="flex flex-col items-center">
                <div
                    class="w-3 h-3 rounded-full shrink-0 mt-1.5"
                    :class="step.isIssue ? 'bg-red-500' : step.done ? 'bg-[#6D5EF8]' : 'bg-[#E4E4E0]'"
                />
                <div v-if="i < steps.length - 1" class="w-px flex-1 min-h-[2.5rem]" :class="step.done ? 'bg-[#6D5EF8]/30' : 'bg-[#E4E4E0]'" />
            </div>

            <div class="pb-8">
                <p class="text-sm font-medium" :class="step.done ? 'text-[#101114]' : 'text-[#B4B2A9]'">
                    {{ step.label }}
                </p>
                <p v-if="step.timestamp" class="text-xs text-[#8A8B90] mt-0.5">{{ formatDate(step.timestamp) }}</p>
                <p v-if="step.detail" class="text-xs text-[#5F5E5A] mt-1 bg-[#F0F0EC] inline-block px-2 py-1 rounded">
                    {{ step.detail }}
                </p>
            </div>
        </div>
    </div>
</template>
