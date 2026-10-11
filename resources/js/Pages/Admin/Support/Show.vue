<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, nextTick, watch, computed } from 'vue';

const props = defineProps({ conversation: Object });

const form = useForm({ body: '' });
const closeForm = useForm({});
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
    form.post(route('admin.support.store', props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => { form.reset(); scrollToBottom(); },
    });
};

const closeConversation = () => {
    if (!confirm('Close this support request? The chat state will be reset and no further messages can be sent.')) return;
    closeForm.post(route('admin.support.close', props.conversation.id), {
        preserveScroll: true,
    });
};

onMounted(() => {
    scrollToBottom();
    if (!isClosed.value) {
        pollTimer = setInterval(() => router.reload({ only: ['conversation'], preserveScroll: true }), 4000);
    }
});
onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});
watch(() => props.conversation.messages?.length, scrollToBottom);
</script>

<template>
    <AdminLayout>
        <Head :title="'Support: ' + (conversation.user?.name || '')" />

        <Link :href="route('admin.support.index')" class="text-sm text-[#8A8B90] hover:text-[#101114]">&larr; All conversations</Link>

        <div class="mt-2 mb-6 flex flex-wrap items-start justify-between gap-4 max-w-2xl">
            <div>
                <h1 class="text-xl font-semibold text-[#101114]">{{ conversation.user?.name }}</h1>
                <p class="text-xs text-[#8A8B90]">{{ conversation.user?.email }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span v-if="conversation.support_token" class="font-mono text-xs font-semibold tracking-wider text-[#6D5EF8]">
                        {{ conversation.support_token }}
                    </span>
                    <span
                        v-if="conversation.support_status"
                        class="rounded-full border px-2 py-0.5 text-[10px] font-medium capitalize"
                        :class="{
                            'bg-amber-50 text-amber-700 border-amber-200': conversation.support_status === 'waiting_support',
                            'bg-blue-50 text-blue-700 border-blue-200': conversation.support_status === 'waiting_customer',
                            'bg-emerald-50 text-emerald-700 border-emerald-200': conversation.support_status === 'resolved',
                            'bg-gray-100 text-gray-600 border-gray-200': conversation.support_status === 'closed',
                        }"
                    >
                        {{ conversation.support_status?.replace('_', ' ') }}
                    </span>
                    <span v-if="conversation.support_category" class="text-xs capitalize text-[#8A8B90]">
                        · {{ conversation.support_category }}
                    </span>
                </div>
                <p v-if="conversation.support_subject" class="mt-1 text-sm font-medium text-[#101114]">
                    {{ conversation.support_subject }}
                </p>
            </div>
            <button
                v-if="!isClosed"
                type="button"
                @click="closeConversation"
                :disabled="closeForm.processing"
                class="shrink-0 rounded-xl border border-[#E4E4E0] bg-white px-4 py-2 text-sm font-medium text-[#555650] hover:bg-[#F8F8F5] disabled:opacity-50"
            >
                {{ closeForm.processing ? 'Closing...' : 'Close Request' }}
            </button>
        </div>

        <div ref="scrollBox" class="h-96 overflow-y-auto border border-[#E4E4E0] rounded-xl p-4 mb-4 bg-white space-y-3 max-w-2xl">
            <div
                v-for="msg in conversation.messages"
                :key="msg.id"
                class="flex"
                :class="msg.sender_id === $page.props.auth.user.id ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="max-w-[75%] px-3 py-2 rounded-xl text-sm"
                    :class="msg.sender_id === $page.props.auth.user.id ? 'bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white' : 'bg-[#F0F0EC] text-[#101114]'"
                >
                    {{ msg.body }}
                    <p class="text-[10px] mt-1 opacity-70">{{ new Date(msg.created_at).toLocaleTimeString() }}</p>
                </div>
            </div>
            <p v-if="!conversation.messages?.length" class="text-center text-sm text-[#8A8B90] py-10">
                No messages yet.
            </p>
        </div>

        <div v-if="isClosed" class="max-w-2xl rounded-xl border border-[#E4E4E0] bg-[#F8F8F5] px-4 py-5 text-center">
            <p class="text-sm font-medium text-[#555650]">This support request is closed.</p>
            <p class="mt-1 text-xs text-[#999A95]">Chat state has been reset. Customer can open a new request if needed.</p>
        </div>

        <form v-else @submit.prevent="send" class="flex gap-2 max-w-2xl">
            <input
                v-model="form.body"
                type="text"
                placeholder="Reply as Zypher Support..."
                class="flex-1 h-11 px-4 rounded-xl border border-[#E4E4E0] text-sm"
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
    </AdminLayout>
</template>
