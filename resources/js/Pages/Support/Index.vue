<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
})

const showForm = ref(false)

const form = useForm({
    subject: '',
    category: 'order',
    body: '',
})

const categories = [
    { value: 'order', label: 'Order Issue' },
    { value: 'payment', label: 'Payment' },
    { value: 'delivery', label: 'Delivery' },
    { value: 'account', label: 'Account' },
    { value: 'product', label: 'Product' },
    { value: 'other', label: 'Other' },
]

const submit = () => {
    form.post(route('support.store'), {
        preserveScroll: true,

        onSuccess: () => {
            form.reset()
            showForm.value = false
        },
    })
}

const statusLabel = (status) => {
    switch (status) {
        case 'waiting_support':
            return 'Waiting for Support'

        case 'waiting_customer':
            return 'Waiting for You'

        case 'resolved':
            return 'Resolved'

        case 'closed':
            return 'Closed'

        default:
            return 'Open'
    }
}

const statusClass = (status) => {
    switch (status) {
        case 'waiting_support':
            return 'bg-amber-50 text-amber-700 border-amber-200'

        case 'waiting_customer':
            return 'bg-blue-50 text-blue-700 border-blue-200'

        case 'resolved':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200'

        case 'closed':
            return 'bg-gray-100 text-gray-600 border-gray-200'

        default:
            return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    }
}
</script>

<template>
    <GuestLayout>
        <Head title="Support Center" />

        <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">

            <!-- Header -->
            <div class="mb-8 flex items-start justify-between gap-4">

                <div>
                    <h1 class="text-2xl font-semibold text-[#101114]">
                        Support Center
                    </h1>

                    <p class="mt-1 text-sm text-[#8A8B90]">
                        Need help? Create a support request and we'll get back to you.
                    </p>
                </div>

                <button
                    type="button"
                    @click="showForm = !showForm"
                    class="shrink-0 rounded-xl bg-[#101114] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#292A2D]"
                >
                    {{ showForm ? 'Cancel' : '+ New Request' }}
                </button>

            </div>

            <!-- New ticket -->
            <div
                v-if="showForm"
                class="mb-8 rounded-2xl border border-[#E4E4E0] bg-white p-5 sm:p-6"
            >

                <h2 class="text-base font-semibold text-[#101114]">
                    New Support Request
                </h2>

                <p class="mt-1 text-xs text-[#8A8B90]">
                    Tell us what you need help with.
                </p>

                <form
                    @submit.prevent="submit"
                    class="mt-5 space-y-4"
                >

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-[#555650]">
                            Category
                        </label>

                        <select
                            v-model="form.category"
                            class="h-11 w-full rounded-xl border border-[#E4E4E0] bg-white px-3 text-sm text-[#101114] outline-none focus:border-[#B7B7B2]"
                        >
                            <option
                                v-for="category in categories"
                                :key="category.value"
                                :value="category.value"
                            >
                                {{ category.label }}
                            </option>
                        </select>

                        <p
                            v-if="form.errors.category"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.category }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-[#555650]">
                            Subject
                        </label>

                        <input
                            v-model="form.subject"
                            type="text"
                            maxlength="255"
                            placeholder="What is your concern?"
                            class="h-11 w-full rounded-xl border border-[#E4E4E0] px-3 text-sm outline-none placeholder:text-[#A0A19C] focus:border-[#B7B7B2]"
                        />

                        <p
                            v-if="form.errors.subject"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.subject }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-[#555650]">
                            Message
                        </label>

                        <textarea
                            v-model="form.body"
                            rows="5"
                            maxlength="2000"
                            placeholder="Describe your issue..."
                            class="w-full resize-none rounded-xl border border-[#E4E4E0] px-3 py-3 text-sm outline-none placeholder:text-[#A0A19C] focus:border-[#B7B7B2]"
                        ></textarea>

                        <p
                            v-if="form.errors.body"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.body }}
                        </p>
                    </div>

                    <div class="flex justify-end">

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-xl bg-[#101114] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#292A2D] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? 'Creating...'
                                    : 'Create Support Request'
                            }}
                        </button>

                    </div>

                </form>

            </div>

            <!-- Tickets -->
            <div>

                <div class="mb-3 flex items-center justify-between">

                    <h2 class="text-sm font-semibold text-[#101114]">
                        Your Support Requests
                    </h2>

                    <span class="text-xs text-[#999A95]">
                        {{ conversations.length }} request{{ conversations.length === 1 ? '' : 's' }}
                    </span>

                </div>

                <div
                    v-if="conversations.length === 0"
                    class="rounded-2xl border border-dashed border-[#D8D8D3] bg-white px-6 py-16 text-center"
                >

                    <p class="text-sm font-medium text-[#555650]">
                        No support requests yet.
                    </p>

                    <p class="mt-1 text-xs text-[#999A95]">
                        Create a request if you need help from Zypher Support.
                    </p>

                </div>

                <div
                    v-else
                    class="space-y-3"
                >

                    <Link
                        v-for="conversation in conversations"
                        :key="conversation.id"
                        :href="route('support.show', conversation.id)"
                        class="block rounded-2xl border border-[#E4E4E0] bg-white p-5 transition hover:border-[#C9C9C4] hover:shadow-sm"
                    >

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                            <div class="min-w-0">

                                <div class="mb-2 flex flex-wrap items-center gap-2">

                                    <span class="font-mono text-xs font-semibold tracking-wider text-[#6D5EF8]">
                                        {{ conversation.support_token }}
                                    </span>

                                    <span
                                        class="rounded-full border px-2 py-0.5 text-[10px] font-medium"
                                        :class="statusClass(conversation.support_status)"
                                    >
                                        {{ statusLabel(conversation.support_status) }}
                                    </span>

                                </div>

                                <h3 class="truncate text-sm font-semibold text-[#101114]">
                                    {{ conversation.support_subject || 'Support Request' }}
                                </h3>

                                <p class="mt-1 text-xs capitalize text-[#8A8B90]">
                                    {{ conversation.support_category }}
                                </p>

                            </div>

                            <div class="shrink-0 text-left sm:text-right">

                                <p class="text-xs text-[#999A95]">
                                    {{
                                        conversation.last_message_at
                                            ? new Date(conversation.last_message_at).toLocaleString()
                                            : 'No messages'
                                    }}
                                </p>

                                <span class="mt-2 inline-block text-xs font-medium text-[#101114]">
                                    Open Chat →
                                </span>

                            </div>

                        </div>

                    </Link>

                </div>

            </div>

        </div>
    </GuestLayout>
</template>