<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
  conversations: Array,
  suppliers: { type: Array, default: () => [] },
});

const startConversation = async (teamId) => {
  await router.post(route('seller.messages.start'), {
    team_id: teamId,
  });
};
</script>

<template>
    <SellerLayout>
        <Head title="Messages" />

        <div class="mb-6">
            <h1 class="text-xl font-semibold text-[#101114] mb-2">Customer Messages</h1>
            <!-- <p class="text-sm text-[#8A8B90]">Start a new conversation with any supplier connected to your shop.</p> -->
        </div>

        <!-- <div class="space-y-3 mb-8">
            <div v-if="suppliers.length === 0" class="rounded-2xl border border-dashed border-gray-200 bg-slate-50 p-4 text-sm text-slate-500">
                No connected suppliers are available to message.
            </div>
            <div v-else class="grid gap-3">
                <div v-for="supplier in suppliers" :key="supplier.id" class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-[#101114]">{{ supplier.name }}</p>
                        <p class="text-sm text-slate-500">Team: {{ supplier.team?.name || 'Unknown' }}</p>
                    </div>
                    <button
                        type="button"
                        @click="startConversation(supplier.team_id)"
                        class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700"
                    >
                        Start Conversation
                    </button>
                </div>
            </div>
        </div> -->

        <div v-if="conversations.length === 0" class="text-center py-20 text-[#8A8B90]">
            No messages yet.
        </div>

        <Link
            v-for="c in conversations"
            :key="c.id"
            :href="route('seller.messages.show', c.id)"
            class="block p-4 bg-white border border-[#E4E4E0] rounded-xl mb-3 hover:border-[#6D5EF8] transition-colors"
        >
            <p class="text-sm font-medium text-[#101114]">{{ c.user?.name }}</p>
            <p class="text-xs text-[#8A8B90] mt-1">
                {{ c.last_message_at ? new Date(c.last_message_at).toLocaleString() : 'No messages yet' }}
            </p>
            <p v-if="c.team" class="text-xs text-[#8A8B90] mt-1">
                Team: <Link :href="route('teams.show', c.team.id)" class="font-semibold text-[#101114] hover:text-[#6D5EF8]">{{ c.team.name }}</Link>
            </p>
        </Link>
    </SellerLayout>
</template>
