<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, nextTick, watch, computed } from 'vue';

const props = defineProps({ conversation: Object });

const form = useForm({ body: '' });
const scrollBox = ref(null);
let pollTimer = null;

const isClosed = computed(() => {
    return ['closed', 'resolved'].includes(props.conversation.support_status) || !!props.conversation.closed_at;
});

const scrollToBottom = () => {
    nextTick(() => {
        if (scrollBox.value) scrollBox.value.scrollTop = scrollBox.value.scrollHeight;
    });
};

const send = () => {
    if (!form.body.trim() || isClosed.value) return;
    form.post(route('support.message.store', props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => { form.reset(); scrollToBottom(); },
    });
};

onMounted(() => {
    scrollToBottom();
    if (!isClosed.value) {
        pollTimer = setInterval(() => {
            router.reload({ only: ['conversation'], preserveScroll: true });
        }, 4000);
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

watch(() => props.conversation.messages?.length, scrollToBottom);
</script>

<template>
    <GuestLayout>
        <Head :title="conversation.support_subject || 'Support'" />

        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
            <Link
                :href="route('support.index')"
                class="text-sm text-[#8A8B90] hover:text-[#101114] mb-4 inline-block"
            >
                &larr; Back to Support Center
            </Link>

            <div class="mb-4">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span v-if="conversation.support_token" class="font-mono text-xs font-semibold tracking-wider text-[#6D5EF8]">
                        {{ conversation.support_token }}
                    </span>
                    <span
                        v-if="conversation.support_status"
                        class="rounded-full border px-2 py-0.5 text-[10px] font-medium"
                        :class="{
                            'bg-amber-50 text-amber-700 border-amber-200': conversation.support_status === 'waiting_support',
                            'bg-blue-50 text-blue-700 border-blue-200': conversation.support_status === 'waiting_customer',
                            'bg-emerald-50 text-emerald-700 border-emerald-200': conversation.support_status === 'resolved',
                            'bg-gray-100 text-gray-600 border-gray-200': conversation.support_status === 'closed',
                        }"
                    >
                        {{ conversation.support_status?.replace('_', ' ') }}
                    </span>
                </div>
                <h1 class="text-xl font-semibold text-[#101114]">
                    {{ conversation.support_subject || 'Zypher Support' }}
                </h1>
                <p class="text-sm text-[#8A8B90] mt-0.5 capitalize">
                    {{ conversation.support_category || 'General' }} issue
                </p>
            </div>

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
                        <p v-if="msg.sender?.is_admin && msg.sender_id !== $page.props.auth.user.id" class="text-[10px] font-semibold mb-0.5 text-[#6D5EF8]">
                            Zypher Support
                        </p>
                        {{ msg.body }}
                        <p class="text-[10px] mt-1 opacity-70">{{ new Date(msg.created_at).toLocaleTimeString() }}</p>
                    </div>
                </div>
                <p v-if="!conversation.messages?.length" class="text-center text-sm text-[#8A8B90] py-10">
                    How can we help? Send us a message below.
                </p>
            </div>

            <div v-if="isClosed" class="rounded-xl border border-[#E4E4E0] bg-[#F8F8F5] px-4 py-6 text-center">
                <p class="text-sm font-medium text-[#555650]">This conversation has ended.</p>
                <p class="mt-1 text-xs text-[#999A95]">Chat state has been reset. You can create a new support request from the Support Center.</p>
                <Link
                    :href="route('support.index')"
                    class="mt-4 inline-block rounded-xl bg-[#101114] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#292A2D]"
                >
                    Back to Support Center
                </Link>
            </div>

            <form v-else @submit.prevent="send" class="flex gap-2">
                <input
                    v-model="form.body"
                    type="text"
                    placeholder="Describe your issue..."
                    class="flex-1 h-11 px-4 rounded-xl border border-[#E4E4E0] text-sm focus:border-[#6D5EF8] outline-none"
                    :disabled="form.processing"
                />
                <button
                    type="submit"
                    :disabled="form.processing || !form.body.trim()"
                    class="h-11 px-5 rounded-xl bg-[#101114] text-white text-sm font-medium disabled:opacity-50"
                >
                    Send
                </button>
            </form>
        </div>
    </GuestLayout>
</template>
