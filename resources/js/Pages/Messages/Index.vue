<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ conversations: Array });
</script>

<template>
    <GuestLayout>
        <Head title="Messages" />

        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
            <h1 class="text-xl font-semibold text-[#101114] mb-6">Messages</h1>

            <div v-if="conversations.length === 0" class="text-center py-20 text-[#8A8B90]">
                No conversations yet. Message a seller from any product page to start one.
            </div>

            <Link
                v-for="c in conversations"
                :key="c.id"
                :href="route('messages.show', c.id)"
                class="block p-4 border border-[#E4E4E0] rounded-xl mb-3 hover:border-[#6D5EF8] transition-colors"
            >
                <p class="text-sm font-medium text-[#101114]">{{ c.team?.name }}</p>
                <p class="text-xs text-[#8A8B90] mt-1">
                    {{ c.last_message_at ? new Date(c.last_message_at).toLocaleString() : 'No messages yet' }}
                </p>
            </Link>
        </div>
    </GuestLayout>
</template>
