<script setup>
import { ref, watchEffect, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const show = ref(true);
const style = ref('success');
const message = ref('');
let timer = null;

const closeBanner = () => {
    show.value = false;
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }
};

watchEffect(() => {
    const flash = page.props.flash || {};
    style.value = flash.error ? 'danger' : flash.success ? 'success' : flash.bannerStyle || 'success';
    message.value = flash.success || flash.error || flash.banner || '';
    if (message.value) {
        show.value = true;
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(() => {
            show.value = false;
            timer = null;
        }, 5000);
    }
});

onBeforeUnmount(() => {
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }
});
</script>

<template>
    <div>
        <div v-if="show && message" :class="{ 'bg-indigo-500': style == 'success', 'bg-red-700': style == 'danger' }">
            <div class="max-w-screen-xl mx-auto py-2 px-3 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between flex-wrap">
                    <div class="w-0 flex-1 flex items-center min-w-0">
                        <span class="flex p-2 rounded-lg" :class="{ 'bg-indigo-600': style == 'success', 'bg-red-600': style == 'danger' }">
                            <svg v-if="style == 'success'" class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>

                            <svg v-if="style == 'danger'" class="size-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </span>

                        <p class="ms-3 font-medium text-sm text-white truncate">
                            {{ message }}
                        </p>
                    </div>

                    <div class="shrink-0 sm:ms-3">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-md bg-white/15 px-3 py-1.5 text-sm font-semibold text-white transition"
                            :class="{ 'hover:bg-white/25 focus:bg-white/25': style == 'success' || style == 'danger' }"
                            aria-label="Dismiss"
                            @click.prevent="closeBanner"
                        >
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>