<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, nextTick, watch } from 'vue';

const props = defineProps({ conversation: Object });

const form = useForm({ body: '' });
const scrollBox = ref(null);
let pollTimer = null;

const scrollToBottom = () => {
    nextTick(() => { if (scrollBox.value) scrollBox.value.scrollTop = scrollBox.value.scrollHeight; });
};

const send = () => {
    if (!form.body.trim()) return;
    form.post(route('seller.messages.store', props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => { form.reset(); scrollToBottom(); },
    });
};

onMounted(() => {
    scrollToBottom();
    pollTimer = setInterval(() => router.reload({ only: ['conversation'], preserveScroll: true }), 4000);
});
onUnmounted(() => clearInterval(pollTimer));
watch(() => props.conversation.messages.length, scrollToBottom);
</script>

<template>
    <SellerLayout>
        <Head :title="'Chat with ' + conversation.user?.name" />

        <Link :href="route('seller.messages.index')" class="text-sm text-[#8A8B90] hover:text-[#101114]">&larr; All messages</Link>
        <h1 class="text-xl font-semibold text-[#101114] mt-2 mb-6">{{ conversation.user?.name }}</h1>
        <p v-if="conversation.team" class="text-sm text-[#8A8B90]">
            Team: <Link :href="route('teams.show', conversation.team.id)" class="font-semibold text-[#101114] hover:text-[#6D5EF8]">{{ conversation.team.name }}</Link>
        </p>

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
        </div>

        <form @submit.prevent="send" class="flex gap-2 max-w-2xl">
            <input v-model="form.body" type="text" placeholder="Type a reply..." class="flex-1 h-11 px-4 rounded-xl border border-[#E4E4E0] text-sm" />
            <button type="submit" :disabled="form.processing" class="h-11 px-5 rounded-xl bg-[#101114] text-white text-sm font-medium">Send</button>
        </form>
    </SellerLayout>
</template>
