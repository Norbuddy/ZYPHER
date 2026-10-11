<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';

const page = usePage();
const showModal = ref(false);

const handlePopState = (e) => {
    // Only trap if user is logged in
    if (!page.props.auth?.user) return;

    // Push current URL back so the browser remains on the current page
    if (window.history && window.history.pushState) {
        window.history.pushState({ zypherAuthGuard: true }, document.title, window.location.href);
    }

    // Show confirmation modal
    showModal.value = true;
};

onMounted(() => {
    if (page.props.auth?.user && typeof window !== 'undefined') {
        // Prime history entry so back button emits popstate on first press
        if (window.history && window.history.pushState) {
            window.history.pushState({ zypherAuthGuard: true }, document.title, window.location.href);
        }
        window.addEventListener('popstate', handlePopState);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('popstate', handlePopState);
    }
});

const stayOnPage = () => {
    showModal.value = false;
};

const confirmLogout = () => {
    showModal.value = false;
    router.post(route('logout'));
};
</script>

<template>
    <DialogModal :show="showModal" @close="stayOnPage" max-width="md">
        <template #title>
            <div class="flex items-center gap-2.5 text-rose-600">
                <div class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center font-bold text-base">
                    ⚠️
                </div>
                <span class="text-base font-bold text-[#101114]">Gusto mo bang mag-log out?</span>
            </div>
        </template>

        <template #content>
            <div class="space-y-3 text-xs sm:text-sm text-[#5F5E5A] leading-relaxed">
                <p>
                    Pinindot mo ang <strong>browser back button</strong> habang naka-login ang iyong account.
                </p>
                <p class="p-3 rounded-xl bg-amber-50 border border-amber-200/60 text-amber-900 text-xs">
                    💡 <em>Paalala:</em> Upang maiwasan ang session mismatch at page errors, manatili po sa kasalukuyang pahina o i-confirm kung nais mo nang mag-log out.
                </p>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-end gap-2.5">
                <button
                    type="button"
                    @click="stayOnPage"
                    class="px-4 py-2.5 rounded-xl border border-[#E4E4E0] hover:bg-[#F7F7F5] text-xs font-semibold text-[#101114] transition cursor-pointer"
                >
                    Manatili (Stay on Page)
                </button>
                <button
                    type="button"
                    @click="confirmLogout"
                    class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition shadow-sm cursor-pointer"
                >
                    Oo, Mag-Log Out
                </button>
            </div>
        </template>
    </DialogModal>
</template>
