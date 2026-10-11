<template>
  <div class="space-y-6">
    <Head :title="`Chat with ${conversation.user?.name}`" />

    <div class="flex items-center justify-between gap-4">
      <div>
        <Link :href="route('supplier.messages.index')" class="text-sm text-slate-500 hover:text-slate-700">&larr; Back to messages</Link>
        <h1 class="text-2xl font-semibold text-[#101114] mt-3">Chat with {{ conversation.user?.name }}</h1>
        <p class="text-sm text-slate-500">{{ conversation.orderShop ? `Order #${conversation.orderShop.order_id}` : 'General conversation' }}</p>
        <p v-if="conversation.team" class="text-sm text-slate-500">
          Team: {{ conversation.team.name }}
        </p>
      </div>
      <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
        <p class="font-semibold">Conversation</p>
        <p class="mt-1">{{ conversation.messages.length }} messages</p>
      </div>
    </div>

    <div ref="scrollBox" class="h-[520px] overflow-y-auto rounded-3xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
      <div
        v-for="msg in conversation.messages"
        :key="msg.id"
        class="flex"
        :class="msg.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start'"
      >
        <div
          class="max-w-[70%] rounded-3xl px-4 py-3 text-sm leading-relaxed"
          :class="msg.sender_id === $page.props.auth.user.id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-900'"
        >
          <p>{{ msg.body }}</p>
          <p class="mt-2 text-[10px] opacity-70">{{ new Date(msg.created_at).toLocaleString() }}</p>
        </div>
      </div>
      <div v-if="conversation.messages.length === 0" class="text-center text-slate-500 py-10">
        No messages yet. Send the first reply to start the conversation.
      </div>
    </div>

    <form @submit.prevent="send" class="flex gap-3">
      <input
        v-model="body"
        type="text"
        placeholder="Type your reply..."
        class="flex-1 rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none"
      />
      <button type="submit" class="rounded-2xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-700">
        Send
      </button>
    </form>
  </div>
</template>

<script setup>
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, nextTick, watch } from 'vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({ conversation: Object });
const body = ref('');
const scrollBox = ref(null);
let pollTimer = null;

const scrollToBottom = () => {
  nextTick(() => {
    if (scrollBox.value) {
      scrollBox.value.scrollTop = scrollBox.value.scrollHeight;
    }
  });
};

const send = () => {
  if (!body.value.trim()) return;

  router.post(
    route('supplier.messages.store', props.conversation.id),
    { body: body.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        body.value = '';
        scrollToBottom();
      },
    }
  );
};

onMounted(() => {
  scrollToBottom();
  pollTimer = setInterval(() => router.reload({ only: ['conversation'], preserveScroll: true }), 4000);
});

onUnmounted(() => {
  clearInterval(pollTimer);
});

watch(() => props.conversation.messages.length, scrollToBottom);
</script>
