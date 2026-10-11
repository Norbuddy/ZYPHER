<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { router } from '@inertiajs/vue3';

defineOptions({ layout: GuestLayout });

const props = defineProps({
    reason: {
        type: String,
        default: 'suspended',
    },
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="mx-auto flex min-h-[70vh] max-w-2xl flex-col items-center justify-center px-6 py-16 text-center">
        <div class="mb-6 rounded-full bg-amber-100 p-4 text-amber-700">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 3 3.5 19h17L12 3Z" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M12 9v4M12 16h.01" stroke-linecap="round" />
            </svg>
        </div>

        <h1 class="text-3xl font-semibold tracking-tight text-[#101114]">
            {{ props.reason === 'unlinked' ? 'Branch Account Not Linked' : 'Branch Account Suspended' }}
        </h1>
        <p class="mt-4 max-w-xl text-lg leading-8 text-[#5F5E5A]">
            <template v-if="props.reason === 'unlinked'">
                This branch account is not linked to a branch yet. Please contact the seller account owner or an administrator for assistance.
            </template>
            <template v-else>
                This branch account has been suspended by a Zypher administrator.
                Please contact the seller account owner for assistance.
            </template>
        </p>

        <button
            type="button"
            @click="logout"
            class="mt-8 rounded-xl bg-[#101114] px-5 py-3 text-sm font-medium text-white transition hover:bg-[#1f2330]"
        >
            Log out
        </button>
    </div>
</template>