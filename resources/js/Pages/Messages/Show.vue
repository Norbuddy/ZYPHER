<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, nextTick, watch } from 'vue';

const props = defineProps({ conversation: Object });

const form = useForm({ body: '' });
const scrollBox = ref(null);
let pollTimer = null;

const scrollToBottom = () => {
    nextTick(() => {
        if (scrollBox.value) scrollBox.value.scrollTop = scrollBox.value.scrollHeight;
    });
};

const send = () => {
    if (!form.body.trim()) return;
    form.post(route('messages.store', props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => { form.reset(); scrollToBottom(); },
    });
};

onMounted(() => {
    scrollToBottom();
    // Lightweight polling for a "live" feel without needing a WebSocket server running.
    pollTimer = setInterval(() => {
        router.reload({ only: ['conversation'], preserveScroll: true });
    }, 4000);
});

onUnmounted(() => clearInterval(pollTimer));

watch(() => props.conversation.messages.length, scrollToBottom);
</script>

<template>
    <GuestLayout>
        <Head :title="'Chat with ' + conversation.team?.name" />

        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
            <Link :href="route('messages.index')" class="text-sm text-[#8A8B90] hover:text-[#101114]">&larr; All messages</Link>
            <h1 class="text-xl font-semibold text-[#101114] mt-2 mb-6">{{ conversation.team?.name }}</h1>

            <div ref="scrollBox" class="h-96 overflow-y-auto border border-[#E4E4E0] rounded-xl p-4 mb-4 bg-white space-y-3">
                <div
                    v-for="msg in conversation.messages"
                    :key="msg.id"
                    class="flex"
                    :class="msg.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[75%] px-3 py-2 rounded-xl text-sm"
                        :class="msg.sender_id === $page.props.auth.user.id
                            ? 'bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white'
                            : 'bg-[#F0F0EC] text-[#101114]'"
                    >
                        {{ msg.body }}
                        <p class="text-[10px] mt-1 opacity-70">{{ new Date(msg.created_at).toLocaleTimeString() }}</p>
                    </div>
                </div>
                <p v-if="conversation.messages.length === 0" class="text-center text-sm text-[#8A8B90] py-10">
                    Say hello to start the conversation.
                </p>
            </div>

            <form @submit.prevent="send" class="flex gap-2">
                <input
                    v-model="form.body"
                    type="text"
                    placeholder="Type a message..."
                    class="flex-1 h-11 px-4 rounded-xl border border-[#E4E4E0] text-sm focus:border-[#6D5EF8] outline-none"
                />
                <button type="submit" :disabled="form.processing" class="h-11 px-5 rounded-xl bg-[#101114] text-white text-sm font-medium">
                    Send
                </button>
            </form>
        </div>
    </GuestLayout>
</template>
