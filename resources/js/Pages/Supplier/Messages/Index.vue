<template>
  <div class="space-y-6">
    <Head title="Supplier Messages" />

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-[#101114]">Messages</h1>
        <p class="text-sm text-slate-500">Respond to sellers and track order-related chats.</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-400">New order alerts</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ newOrderNotifications }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="lg:col-span-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div v-if="!conversations || conversations.length === 0" class="text-center py-20 text-slate-400">
            No conversations yet.
          </div>
          <div v-else class="space-y-3">
            <Link
              v-for="conversation in conversations"
              :key="conversation.id"
              :href="route('supplier.messages.show', conversation.id)"
              class="block rounded-2xl border border-slate-200 p-4 hover:border-slate-400 transition"
            >
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="font-semibold text-slate-900">{{ conversation.user?.name }}</p>
                  <p class="text-sm text-slate-500">{{ conversation.orderShop ? `Order #${conversation.orderShop.order_id}` : 'General chat' }}</p>
                  <p v-if="conversation.team" class="text-sm text-slate-500">
                    Team: {{ conversation.team.name }}
                  </p>
                </div>
                <span
                  class="rounded-full px-2 py-1 text-xs font-semibold uppercase"
                  :class="conversation.orderShop ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'"
                >
                  {{ conversation.orderShop ? 'Order' : 'Message' }}
                </span>
              </div>
              <div class="mt-3 flex items-center justify-between text-sm text-slate-500">
                <span>{{ conversation.last_message_at ? new Date(conversation.last_message_at).toLocaleString() : 'No messages yet' }}</span>
                <span>{{ conversation.messages?.length ?? 0 }} messages</span>
              </div>
            </Link>
          </div>
        </div>
      </div>

      <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-sm font-semibold text-slate-900">Quick actions</p>
          <p class="mt-2 text-sm text-slate-500">Use supplier conversations to keep sellers informed about orders and fulfillment.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-sm font-semibold text-slate-900">Order alerts</p>
          <p class="mt-2 text-sm text-slate-500">New pending orders are highlighted here automatically.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({
  conversations: { type: Array, default: () => [] },
  newOrderNotifications: { type: Number, default: 0 },
});

let pollTimer = null;

onMounted(() => {
  pollTimer = setInterval(() => {
    router.reload({ only: ['conversations', 'new_order_notifications'], preserveScroll: true });
  }, 4000);
});

onUnmounted(() => {
  clearInterval(pollTimer);
});
</script>
