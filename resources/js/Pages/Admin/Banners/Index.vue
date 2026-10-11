<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ banners: Array });

const FONTS = ['Inter', 'Space Grotesk', 'Poppins', 'Roboto', 'Montserrat', 'Georgia', 'Times New Roman', 'Arial', 'Verdana', 'Courier New'];

const showForm = ref(false);
const showHelp = ref(false);
const editingBanner = ref(null);
const imagePreview = ref(null);
const dimensionError = ref('');

const defaults = () => ({
    title: '',
    subtitle: '',
    button_text: '',
    button_link: '',
    title_font: 'Inter',
    subtitle_font: 'Inter',
    button_font: 'Inter',
    title_color: '#101114',
    subtitle_color: '#666762',
    button_text_color: '#FFFFFF',
    button_background: '#101114',
    button_gradient_color: '#33D6C8',
    button_style: 'fill',
    image: null,
});

const form = useForm(defaults());

const openCreate = () => {
    editingBanner.value = null;
    form.defaults(defaults());
    form.reset();
    form.clearErrors();
    imagePreview.value = null;
    dimensionError.value = '';
    showForm.value = true;
};

const openEdit = (banner) => {
    editingBanner.value = banner;
    form.clearErrors();

    form.title = banner.title;
    form.subtitle = banner.subtitle ?? '';
    form.button_text = banner.button_text;
    form.button_link = banner.button_link ?? '';
    form.title_font = banner.title_font;
    form.subtitle_font = banner.subtitle_font;
    form.button_font = banner.button_font;
    form.title_color = banner.title_color;
    form.subtitle_color = banner.subtitle_color;
    form.button_text_color = banner.button_text_color;
    form.button_background = banner.button_background;
    form.button_gradient_color = banner.button_gradient_color ?? '#33D6C8';
    form.button_style = banner.button_style;
    form.image = null;

    imagePreview.value = imageUrl(banner.image_path);
    dimensionError.value = '';
    showForm.value = true;
};

const imageUrl = (path) => (path.startsWith('images/') ? '/' : '/storage/') + path;

const onImageSelected = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    dimensionError.value = '';
    const img = new Image();
    img.onload = () => {
        if (img.width !== 1280 || img.height !== 210) {
            dimensionError.value = `This image is ${img.width}x${img.height}px. It must be exactly 1280x210px.`;
        }
        URL.revokeObjectURL(img.src);
    };
    img.src = URL.createObjectURL(file);

    form.image = file;
    imagePreview.value = URL.createObjectURL(file);
};

const buttonPreviewStyle = computed(() => {
    const style = { color: form.button_text_color, fontFamily: form.button_font };

    if (form.button_style === 'outline') {
        style.background = 'transparent';
        style.border = `2px solid ${form.button_background}`;
        style.color = form.button_background;
    } else if (form.button_style === 'gradient') {
        style.background = `linear-gradient(90deg, ${form.button_background}, ${form.button_gradient_color})`;
        style.border = 'none';
    } else {
        style.background = form.button_background;
        style.border = 'none';
    }

    return style;
});

const submit = () => {
    if (dimensionError.value) return;

    const url = editingBanner.value
        ? route('admin.banners.update', editingBanner.value.id)
        : route('admin.banners.store');

    form.post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            editingBanner.value = null;
        },
    });
};

const toggleActive = (banner) => {
    router.post(route('admin.banners.update', banner.id), {
        title: banner.title,
        subtitle: banner.subtitle,
        button_text: banner.button_text,
        button_link: banner.button_link,
        title_font: banner.title_font,
        subtitle_font: banner.subtitle_font,
        button_font: banner.button_font,
        title_color: banner.title_color,
        subtitle_color: banner.subtitle_color,
        button_text_color: banner.button_text_color,
        button_background: banner.button_background,
        button_gradient_color: banner.button_gradient_color,
        button_style: banner.button_style,
        is_active: !banner.is_active,
    }, { preserveScroll: true });
};

const destroy = (banner) => {
    if (confirm('Remove this banner?')) {
        router.delete(route('admin.banners.destroy', banner.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Banners" />

        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2 relative">
                <h1 class="text-xl font-semibold text-[#101114]">Storefront Banners</h1>

                <button
                    type="button"
                    @click="showHelp = !showHelp"
                    class="w-5 h-5 rounded-full bg-[#E4E4E0] hover:bg-[#6D5EF8] hover:text-white text-[#5F5E5A] text-xs font-bold flex items-center justify-center transition-colors"
                >
                    ?
                </button>

                <div v-if="showHelp" class="absolute left-0 top-8 z-30 w-80 bg-white rounded-xl border border-[#E4E4E0] shadow-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-[#101114]">How to add a banner</p>
                        <button @click="showHelp = false" class="text-[#8A8B90] hover:text-[#101114] text-xs">✕</button>
                    </div>
                    <ol class="space-y-3 text-xs text-[#5F5E5A]">
                        <li class="flex gap-2"><span class="shrink-0 w-5 h-5 rounded-full bg-[#6D5EF8] text-white flex items-center justify-center text-[10px] font-bold">1</span><span>Click <strong>"+ Add Banner"</strong> above.</span></li>
                        <li class="flex gap-2"><span class="shrink-0 w-5 h-5 rounded-full bg-[#6D5EF8] text-white flex items-center justify-center text-[10px] font-bold">2</span><span>Fill in Title, Subtitle, Button Text, and pick fonts/colors for each.</span></li>
                        <li class="flex gap-2"><span class="shrink-0 w-5 h-5 rounded-full bg-[#6D5EF8] text-white flex items-center justify-center text-[10px] font-bold">3</span><span>Upload an image that is <strong>exactly 1280x210px</strong>.</span></li>
                        <li class="flex gap-2"><span class="shrink-0 w-5 h-5 rounded-full bg-[#6D5EF8] text-white flex items-center justify-center text-[10px] font-bold">4</span><span>Use the preview to check how it will look before saving.</span></li>
                        <li class="flex gap-2"><span class="shrink-0 w-5 h-5 rounded-full bg-[#6D5EF8] text-white flex items-center justify-center text-[10px] font-bold">5</span><span>Click <strong>Save</strong>. It appears right away on the storefront.</span></li>
                    </ol>
                </div>
            </div>

            <button @click="openCreate" class="h-10 px-4 rounded-lg bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white text-sm font-medium">
                + Add Banner
            </button>
        </div>

        <div class="space-y-3">
            <div v-if="props.banners.length === 0" class="text-center py-16 text-sm text-[#8A8B90] bg-white rounded-xl border border-[#E4E4E0]">
                No banners yet. Add one to populate the storefront carousel.
            </div>

            <div v-for="banner in props.banners" :key="banner.id" class="bg-white rounded-xl border border-[#E4E4E0] p-4 flex items-center gap-4">
                <img :src="imageUrl(banner.image_path)" class="w-40 h-[26px] object-cover rounded border border-[#E4E4E0] shrink-0" />

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-[#101114] truncate">{{ banner.title }}</p>
                    <p class="text-xs text-[#8A8B90] truncate">{{ banner.subtitle }}</p>
                    <p class="text-[10px] text-[#8A8B90] mt-1">Button: "{{ banner.button_text }}" · {{ banner.button_style }}</p>
                </div>

                <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="banner.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                    {{ banner.is_active ? 'Active' : 'Hidden' }}
                </span>

                <div class="flex items-center gap-3 shrink-0">
                    <button @click="toggleActive(banner)" class="text-xs text-[#6D5EF8] font-medium">{{ banner.is_active ? 'Hide' : 'Show' }}</button>
                    <button @click="openEdit(banner)" class="text-xs text-[#6D5EF8] font-medium">Edit</button>
                    <button @click="destroy(banner)" class="text-xs text-red-500 font-medium">Delete</button>
                </div>
            </div>
        </div>

        <div v-if="showForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="showForm = false">
            <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[92vh] overflow-y-auto">
                <h3 class="text-sm font-semibold mb-4">{{ editingBanner ? 'Edit Banner' : 'Add Banner' }}</h3>

                <div class="mb-5 rounded-lg border border-dashed border-[#D8D8D3] bg-[#FAFAF8] p-3">
                    <p class="text-[10px] font-medium text-[#8A8B90] mb-2">Where each field appears on the banner:</p>
                    <svg viewBox="0 0 400 66" class="w-full h-auto">
                        <rect x="1" y="1" width="398" height="64" rx="6" fill="#EEEEEC" stroke="#D8D8D3" />
                        <rect x="14" y="12" width="140" height="10" rx="2" fill="#B9B9F5" />
                        <text x="18" y="20" font-size="7" fill="#4B3FBF">Title</text>
                        <rect x="14" y="28" width="110" height="7" rx="2" fill="#CFCFCF" />
                        <text x="18" y="34" font-size="6" fill="#777">Subtitle</text>
                        <rect x="14" y="42" width="56" height="14" rx="7" fill="#101114" />
                        <text x="24" y="52" font-size="7" fill="white">Button</text>
                        <rect x="280" y="8" width="108" height="50" rx="4" fill="none" stroke="#A78BFA" stroke-dasharray="4 2" />
                        <text x="292" y="36" font-size="7" fill="#7C3AED">Image (full banner)</text>
                    </svg>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Title</label>
                            <input v-model="form.title" type="text" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                            <p v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</p>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-[10px] font-medium text-[#8A8B90] mb-1">Title Font</label>
                                <select v-model="form.title_font" class="w-full h-9 px-2 rounded-lg border border-[#E4E4E0] text-xs">
                                    <option v-for="f in FONTS" :key="f" :value="f">{{ f }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-medium text-[#8A8B90] mb-1">Color</label>
                                <input v-model="form.title_color" type="color" class="h-9 w-12 rounded border-0 cursor-pointer" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Subtitle</label>
                            <input v-model="form.subtitle" type="text" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-[10px] font-medium text-[#8A8B90] mb-1">Subtitle Font</label>
                                <select v-model="form.subtitle_font" class="w-full h-9 px-2 rounded-lg border border-[#E4E4E0] text-xs">
                                    <option v-for="f in FONTS" :key="f" :value="f">{{ f }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-medium text-[#8A8B90] mb-1">Color</label>
                                <input v-model="form.subtitle_color" type="color" class="h-9 w-12 rounded border-0 cursor-pointer" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Button Text</label>
                            <input v-model="form.button_text" type="text" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                            <p v-if="form.errors.button_text" class="text-xs text-red-500 mt-1">{{ form.errors.button_text }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Button Redirect Link</label>
                            <input v-model="form.button_link" type="text" placeholder="/search?category=shoes or https://..." class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                        </div>

                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-[10px] font-medium text-[#8A8B90] mb-1">Button Font</label>
                                <select v-model="form.button_font" class="w-full h-9 px-2 rounded-lg border border-[#E4E4E0] text-xs">
                                    <option v-for="f in FONTS" :key="f" :value="f">{{ f }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-medium text-[#8A8B90] mb-1">Text Color</label>
                                <input v-model="form.button_text_color" type="color" class="h-9 w-12 rounded border-0 cursor-pointer" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-medium text-[#8A8B90] mb-1">Button Style</label>
                            <div class="flex gap-2">
                                <button type="button" @click="form.button_style = 'fill'" class="flex-1 h-9 rounded-lg border text-xs" :class="form.button_style === 'fill' ? 'border-[#6D5EF8] text-[#6D5EF8]' : 'border-[#E4E4E0]'">Fill</button>
                                <button type="button" @click="form.button_style = 'outline'" class="flex-1 h-9 rounded-lg border text-xs" :class="form.button_style === 'outline' ? 'border-[#6D5EF8] text-[#6D5EF8]' : 'border-[#E4E4E0]'">Outline</button>
                                <button type="button" @click="form.button_style = 'gradient'" class="flex-1 h-9 rounded-lg border text-xs" :class="form.button_style === 'gradient' ? 'border-[#6D5EF8] text-[#6D5EF8]' : 'border-[#E4E4E0]'">Gradient</button>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <div>
                                <label class="block text-[10px] font-medium text-[#8A8B90] mb-1">{{ form.button_style === 'gradient' ? 'Gradient Start' : 'Button Color' }}</label>
                                <input v-model="form.button_background" type="color" class="h-9 w-12 rounded border-0 cursor-pointer" />
                            </div>
                            <div v-if="form.button_style === 'gradient'">
                                <label class="block text-[10px] font-medium text-[#8A8B90] mb-1">Gradient End</label>
                                <div class="flex items-center gap-1">
                                    <input v-model="form.button_gradient_color" type="color" class="h-9 w-12 cursor-pointer rounded border-0 p-0" />
                                    <input v-model="form.button_gradient_color" type="text" maxlength="7" class="h-9 w-20 rounded border border-[#E4E4E0] px-2 text-xs uppercase" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Image (exactly 1280x210px)</label>
                            <input type="file" accept="image/*" @change="onImageSelected" class="text-sm" />
                            <p v-if="dimensionError" class="text-xs text-red-500 mt-1">{{ dimensionError }}</p>
                            <p v-if="form.errors.image" class="text-xs text-red-500 mt-1">{{ form.errors.image }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-medium text-[#8A8B90] mb-1.5">Live Preview</label>
                        <div class="relative rounded-xl overflow-hidden border border-[#E4E4E0] bg-[#EEEEEC]" style="aspect-ratio: 1280 / 210;">
                            <img v-if="imagePreview" :src="imagePreview" class="absolute inset-0 w-full h-full object-cover" />
                            <div class="absolute inset-0 flex flex-col justify-center gap-1.5 px-6">
                                <p class="text-base font-bold leading-tight" :style="{ color: form.title_color, fontFamily: form.title_font }">{{ form.title || 'Your Title Here' }}</p>
                                <p class="text-xs leading-tight" :style="{ color: form.subtitle_color, fontFamily: form.subtitle_font }">{{ form.subtitle || 'Your subtitle text here' }}</p>
                                <button type="button" class="mt-1 w-fit px-3 py-1.5 rounded-lg text-[10px] font-medium" :style="buttonPreviewStyle">
                                    {{ form.button_text || 'Button' }}
                                </button>
                            </div>
                        </div>
                        <p class="text-[10px] text-[#8A8B90] mt-2">This preview approximates how the banner will look on the storefront carousel.</p>
                    </div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button @click="showForm = false" class="flex-1 h-10 rounded-lg border border-[#E4E4E0] text-sm">Cancel</button>
                    <button @click="submit" :disabled="form.processing || !!dimensionError" class="flex-1 h-10 rounded-lg bg-[#101114] text-white text-sm disabled:opacity-40">
                        {{ form.processing ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>