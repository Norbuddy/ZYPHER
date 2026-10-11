<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ popupAds: Array });

const showForm = ref(false);
const editingAd = ref(null);
const imagePreview = ref(null);

const form = useForm({
    title: '',
    message: '',
    button_text: '',
    button_link: '',
    starts_at: '',
    ends_at: '',
    image: null,
});

const openCreate = () => {
    editingAd.value = null;
    form.reset();
    imagePreview.value = null;
    showForm.value = true;
};

const openEdit = (ad) => {
    editingAd.value = ad;
    form.title = ad.title;
    form.message = ad.message ?? '';
    form.button_text = ad.button_text ?? '';
    form.button_link = ad.button_link ?? '';
    form.starts_at = ad.starts_at ? toLocalInputValue(ad.starts_at) : '';
    form.ends_at = ad.ends_at ? toLocalInputValue(ad.ends_at) : '';
    form.image = null;
    imagePreview.value = ad.image_path ? imageUrl(ad.image_path) : null;
    showForm.value = true;
};

const imageUrl = (path) => (path.startsWith('images/') ? '/' : '/storage/') + path;

// The backend stores starts_at/ends_at in UTC. datetime-local inputs need
// "YYYY-MM-DDTHH:mm" in the browser's own (local/Philippine) time, so we
// convert via a real Date object rather than slicing the raw UTC string.
const toLocalInputValue = (utcString) => {
    if (!utcString) return '';
    const d = new Date(utcString);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const onImageSelected = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    imagePreview.value = URL.createObjectURL(file);
};

const submit = () => {
    const routeName = editingAd.value ? 'admin.popup-ads.update' : 'admin.popup-ads.store';
    const routeParams = editingAd.value ? editingAd.value.id : undefined;

    form.post(route(routeName, routeParams), {
        forceFormData: true,
        onSuccess: () => { showForm.value = false; },
    });
};

const toggleActive = (ad) => {
    router.post(route('admin.popup-ads.update', ad.id), {
        title: ad.title,
        message: ad.message,
        button_text: ad.button_text,
        button_link: ad.button_link,
        starts_at: ad.starts_at,
        ends_at: ad.ends_at,
        is_active: !ad.is_active,
    }, { preserveScroll: true });
};

const destroy = (ad) => {
    if (confirm('Remove this pop-up ad?')) router.delete(route('admin.popup-ads.destroy', ad.id));
};
</script>

<template>
    <AdminLayout>
        <Head title="Pop-up Ads" />

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-[#101114]">Pop-up Ads</h1>
                <p class="text-xs text-[#8A8B90] mt-1">Promotions, collaborations, and voucher announcements shown as a storefront pop-up.</p>
            </div>

            <button @click="openCreate" class="h-10 px-4 rounded-lg bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white text-sm font-medium">
                + Add Pop-up Ad
            </button>
        </div>

        <div class="space-y-3">
            <div v-if="popupAds.length === 0" class="text-center py-16 text-sm text-[#8A8B90] bg-white rounded-xl border border-[#E4E4E0]">
                No pop-up ads yet. Add one to promote something on the storefront.
            </div>

            <div v-for="ad in popupAds" :key="ad.id" class="bg-white rounded-xl border border-[#E4E4E0] p-4 flex items-center gap-4">
                <img v-if="ad.image_path" :src="imageUrl(ad.image_path)" class="w-16 h-16 object-cover rounded border border-[#E4E4E0] shrink-0" />
                <div v-else class="w-16 h-16 rounded border border-dashed border-[#E4E4E0] shrink-0 flex items-center justify-center text-[10px] text-[#A0A19D]">No image</div>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-[#101114] truncate">{{ ad.title }}</p>
                    <p class="text-xs text-[#8A8B90] truncate">{{ ad.message }}</p>
                    <p v-if="ad.starts_at || ad.ends_at" class="text-[10px] text-[#8A8B90] mt-1">
                        {{ ad.starts_at ? new Date(ad.starts_at).toLocaleDateString() : 'Now' }}
                        &rarr;
                        {{ ad.ends_at ? new Date(ad.ends_at).toLocaleDateString() : 'No end date' }}
                    </p>
                </div>

                <span
                    class="text-xs font-medium px-2.5 py-1 rounded-full"
                    :class="ad.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
                >
                    {{ ad.is_active ? 'Active' : 'Hidden' }}
                </span>

                <div class="flex items-center gap-3 shrink-0">
                    <button @click="toggleActive(ad)" class="text-xs text-[#6D5EF8] font-medium">
                        {{ ad.is_active ? 'Hide' : 'Show' }}
                    </button>
                    <button @click="openEdit(ad)" class="text-xs text-[#6D5EF8] font-medium">Edit</button>
                    <button @click="destroy(ad)" class="text-xs text-red-500 font-medium">Delete</button>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="showForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="showForm = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
                <h3 class="text-sm font-semibold mb-4">{{ editingAd ? 'Edit Pop-up Ad' : 'Add Pop-up Ad' }}</h3>

                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Title</label>
                <input v-model="form.title" type="text" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm mb-3" />
                <p v-if="form.errors.title" class="text-xs text-red-500 -mt-2 mb-3">{{ form.errors.title }}</p>

                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Message</label>
                <textarea v-model="form.message" rows="3" class="w-full px-3 py-2 rounded-lg border border-[#E4E4E0] text-sm mb-3"></textarea>

                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Button Text</label>
                <input v-model="form.button_text" type="text" placeholder="e.g. Claim Voucher" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm mb-3" />

                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Button Link</label>
                <input v-model="form.button_link" type="text" placeholder="/search?category=sale or https://..." class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm mb-3" />

                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Starts</label>
                        <input v-model="form.starts_at" type="datetime-local" min="2020-01-01T00:00" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Ends</label>
                        <input v-model="form.ends_at" type="datetime-local" min="2020-01-01T00:00" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                    </div>
                </div>
                <p v-if="form.errors.ends_at" class="text-xs text-red-500 -mt-2 mb-3">{{ form.errors.ends_at }}</p>
                <p class="text-[10px] text-[#8A8B90] -mt-2 mb-3">Leave both blank to show it indefinitely once active.</p>

                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Image (optional)</label>
                <input type="file" accept="image/*" @change="onImageSelected" class="text-sm mb-2" />
                <p v-if="form.errors.image" class="text-xs text-red-500 mb-2">{{ form.errors.image }}</p>

                <img v-if="imagePreview" :src="imagePreview" class="w-full h-32 object-cover rounded border border-[#E4E4E0] mb-4" />

                <div class="flex gap-2">
                    <button @click="showForm = false" class="flex-1 h-10 rounded-lg border border-[#E4E4E0] text-sm">Cancel</button>
                    <button @click="submit" :disabled="form.processing" class="flex-1 h-10 rounded-lg bg-[#101114] text-white text-sm disabled:opacity-40">
                        {{ form.processing ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>