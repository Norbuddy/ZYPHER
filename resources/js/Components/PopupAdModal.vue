<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const page = usePage();
const show = ref(false);

const isStorefront = computed(() => page.component === 'Home');
const ad = computed(() => (isStorefront.value ? page.props.activePopupAd : null));

const imageUrl = (path) => (path.startsWith('images/') ? '/' : '/storage/') + path;

const isDismissed = (id) => {
    try {
        return !!sessionStorage.getItem(`popup_ad_dismissed_${id}`);
    } catch {
        return false;
    }
};

const markDismissed = (id) => {
    try {
        sessionStorage.setItem(`popup_ad_dismissed_${id}`, 'true');
    } catch {}
};

const dismiss = () => {
    show.value = false;
    if (ad.value?.id) {
        markDismissed(ad.value.id);
    }
};

const checkAndShow = () => {
    if (!isStorefront.value || !ad.value) {
        show.value = false;
        return;
    }
    if (ad.value?.id && isDismissed(ad.value.id)) {
        show.value = false;
        return;
    }
    show.value = true;
};

watch([() => page.component, () => page.props.activePopupAd], () => {
    checkAndShow();
});

onMounted(() => {
    checkAndShow();
});
</script>

<template>
    <div v-if="show && ad" class="fixed inset-0 z-[100] bg-black/50 flex items-center justify-center p-4" @click.self="dismiss">
        <div class="relative bg-white rounded-2xl w-full max-w-sm overflow-hidden shadow-xl">
            <button
                @click="dismiss"
                class="absolute top-3 right-3 z-10 w-7 h-7 rounded-full bg-white/90 hover:bg-white flex items-center justify-center text-[#101114] text-sm shadow-sm"
                aria-label="Close"
            >
                ✕
            </button>

            <img v-if="ad.image_path" :src="imageUrl(ad.image_path)" :alt="ad.title" class="w-full h-64 object-cover" />

            <div class="p-6">
                <h3 class="text-lg font-semibold text-[#101114] mb-1.5">{{ ad.title }}</h3>
                <p v-if="ad.message" class="text-sm text-[#5F5E5A] mb-4">{{ ad.message }}</p>

                <div class="flex gap-2">
                    <Link
                        v-if="ad.button_link"
                        :href="ad.button_link"
                        @click="dismiss"
                        class="flex-1 h-10 rounded-lg bg-[#101114] text-white text-sm font-medium flex items-center justify-center hover:bg-[#2A2B2E] transition-colors"
                    >
                        {{ ad.button_text || 'View' }}
                    </Link>
                    <button
                        @click="dismiss"
                        class="h-10 px-4 rounded-lg border border-[#E4E4E0] text-sm text-[#5F5E5A]"
                        :class="{ 'flex-1': !ad.button_link }"
                    >
                        {{ ad.button_link ? 'Not now' : 'Close' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>