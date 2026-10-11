<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ conversations: Array });

const statusLabel = (status) => {
    switch (status) {
        case 'waiting_support': return 'Waiting for Support';
        case 'waiting_customer': return 'Waiting for Customer';
        case 'resolved': return 'Resolved';
        case 'closed': return 'Closed';
        default: return 'Open';
    }
};

const statusClass = (status) => {
    switch (status) {
        case 'waiting_support': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'waiting_customer': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'resolved': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'closed': return 'bg-gray-100 text-gray-600 border-gray-200';
        default: return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Support Inbox" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">Customer Support Inbox</h1>

        <div v-if="conversations.length === 0" class="text-center py-20 text-[#8A8B90]">
            No support conversations yet.
        </div>

        <div v-else class="space-y-3 max-w-3xl">
            <Link
                v-for="c in conversations"
                :key="c.id"
                :href="route('admin.support.show', c.id)"
                class="block rounded-2xl border border-[#E4E4E0] bg-white p-5 transition hover:border-[#C9C9C4] hover:shadow-sm"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <span v-if="c.support_token" class="font-mono text-xs font-semibold tracking-wider text-[#6D5EF8]">
                                {{ c.support_token }}
                            </span>
                            <span
                                class="rounded-full border px-2 py-0.5 text-[10px] font-medium"
                                :class="statusClass(c.support_status)"
                            >
                                {{ statusLabel(c.support_status) }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-[#101114]">
                            {{ c.user?.name }}
                            <span class="text-xs font-normal text-[#8A8B90]">{{ c.user?.email }}</span>
                        </p>
                        <p v-if="c.support_subject" class="mt-1 truncate text-sm text-[#555650]">
                            {{ c.support_subject }}
                        </p>
                        <p v-if="c.support_category" class="mt-0.5 text-xs capitalize text-[#8A8B90]">
                            {{ c.support_category }}
                        </p>
                    </div>
                    <div class="shrink-0 text-left sm:text-right">
                        <p class="text-xs text-[#999A95]">
                            {{ c.last_message_at ? new Date(c.last_message_at).toLocaleString() : 'No messages' }}
                        </p>
                        <span class="mt-2 inline-block text-xs font-medium text-[#101114]">
                            Open Chat →
                        </span>
                    </div>
                </div>
            </Link>
        </div>
    </AdminLayout>
</template>
