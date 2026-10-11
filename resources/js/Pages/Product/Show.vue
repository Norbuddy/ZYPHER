<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import ActionMessage from '@/Components/ActionMessage.vue';

const props = defineProps({ product: Object });

// Track selected value id per option (axis)
const selectedValues = ref({});
props.product.options.forEach(opt => { selectedValues.value[opt.id] = null; });

// For products that use simple size variants (no option axes), allow selecting by variant label.
const selectedSize = ref(null);
const selectedVariantByLabel = computed(() => {
    if (!selectedSize.value) return null;
    return props.product.variants.find(v => String(v.label) === String(selectedSize.value));
});

const quantity = ref(1);

const selectedImageIndex = ref(0);

const ratingColorClass = (rating) => {
    const r = Number(rating);
    if (!r) return 'text-gray-400';
    if (r < 2.5) return 'text-gray-400';
    if (r < 4) return 'text-green-500';
    return 'text-yellow-500';
};

const imageUrl = (img) => {
    if (!img) return '';
    return (img.path.startsWith('images/') ? '/' : '/storage/') + img.path;
};

const nextImage = () => {
    if (!props.product.images || props.product.images.length === 0) return;
    selectedImageIndex.value = (selectedImageIndex.value + 1) % props.product.images.length;
};

const prevImage = () => {
    if (!props.product.images || props.product.images.length === 0) return;
    selectedImageIndex.value = (selectedImageIndex.value - 1 + props.product.images.length) % props.product.images.length;
};

const touchStartX = ref(0);

const onTouchStart = (e) => {
    touchStartX.value = e.changedTouches[0].screenX;
};

const onTouchEnd = (e) => {
    const diff = e.changedTouches[0].screenX - touchStartX.value;
    const threshold = 50;

    if (diff > threshold) {
        prevImage();
    } else if (diff < -threshold) {
        nextImage();
    }
};

const matchedVariant = computed(() => {
    const selectedIds = Object.values(selectedValues.value).filter(Boolean).sort();
    if (selectedIds.length !== props.product.options.length) return null;

    return props.product.variants.find(variant => {
        const variantValueIds = variant.option_values.map(v => v.id).sort();
        return JSON.stringify(variantValueIds) === JSON.stringify(selectedIds);
    });
});

const form = useForm({ product_variant_id: null, quantity: 1 });
const page = usePage();
const showMessageModal = ref(false);
const messageSuccess = ref(false);
const messageForm = useForm({
    team_id: props.product.team_id,
    product_id: props.product.id,
    product_name: props.product.name,
    body: '',
});

const addToCart = () => {
    const variant = matchedVariant.value || selectedVariantByLabel.value;
    if (!variant) return;
    form.product_variant_id = variant.id;
    form.quantity = quantity.value;
    form.post(route('cart.store'), { preserveScroll: true });
};

const buyNow = () => {
    const variant = matchedVariant.value || selectedVariantByLabel.value;
    if (!variant) return;
    form.product_variant_id = variant.id;
    form.quantity = quantity.value;
    form.post(route('cart.store'), {
        preserveScroll: true,
        onSuccess: () => router.visit(route('checkout.index')),
    });
};

const openMessageModal = () => {
    messageSuccess.value = false;
    messageForm.reset('body');
    showMessageModal.value = true;
};

const closeMessageModal = () => {
    showMessageModal.value = false;
};

const sendMessage = () => {
    messageForm.post(route('messages.send'), {
        preserveScroll: true,
        onSuccess: () => {
            messageSuccess.value = true;
            messageForm.reset('body');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="product.name" />

        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 grid grid-cols-1 md:grid-cols-2 gap-10">
            <div>
                <div
                    class="relative aspect-square rounded-2xl bg-[#F0F0EC] overflow-hidden mb-3"
                    @touchstart="onTouchStart"
                    @touchend="onTouchEnd"
                >
                    <img v-if="product.images?.[selectedImageIndex]" :src="imageUrl(product.images[selectedImageIndex])" class="w-full h-full object-cover" />

                    <template v-if="product.images && product.images.length > 1">
                        <button
                            type="button"
                            @click="prevImage"
                            class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white/80 hover:bg-white text-[#101114] flex items-center justify-center shadow"
                        >
                            ‹
                        </button>
                        <button
                            type="button"
                            @click="nextImage"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white/80 hover:bg-white text-[#101114] flex items-center justify-center shadow"
                        >
                            ›
                        </button>
                    </template>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    <div
                        v-for="(img, index) in product.images"
                        :key="img.id"
                        class="aspect-square rounded-lg bg-[#F0F0EC] overflow-hidden cursor-pointer border-2 transition-colors"
                        :class="index === selectedImageIndex ? 'border-[#6D5EF8]' : 'border-transparent'"
                        @click="selectedImageIndex = index"
                    >
                        <img :src="imageUrl(img)" class="w-full h-full object-cover" />
                    </div>
                </div>
            </div>

            <div>
                <p class="text-xs text-[#8A8B90] mb-1">
                    {{ product.team?.name }}
                    <template v-if="$page.props.auth.user">
                        <button type="button" @click="openMessageModal" class="text-[#6D5EF8] hover:text-[#33D6C8] ml-2">
                            Message Seller
                        </button>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-[#6D5EF8] hover:text-[#33D6C8] ml-2">
                            Message Seller
                        </Link>
                    </template>
                </p>
                <h1 class="text-2xl font-semibold text-[#101114] mb-2">{{ product.name }}</h1>
                <div class="flex items-center gap-2 mb-4 text-sm text-[#8A8B90]">
                    <span :class="ratingColorClass(product.avg_rating)">★ {{ product.avg_rating || '—' }}</span>
                    <span>·</span>
                    <span>{{ product.rating_count }} reviews</span>
                    <span>·</span>
                    <span>{{ product.sold_count }} sold</span>
                </div>

                <p class="text-2xl font-semibold text-[#6D5EF8] mb-6">
                    ₱{{ matchedVariant?.price ?? product.min_price }}<span v-if="!matchedVariant && product.min_price !== product.max_price"> – ₱{{ product.max_price }}</span>
                </p>

                <div v-for="option in product.options" :key="option.id" class="mb-4">
                    <p class="text-xs font-medium text-[#8A8B90] mb-2">{{ option.name }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="value in option.values"
                            :key="value.id"
                            type="button"
                            @click="selectedValues[option.id] = value.id"
                            class="px-4 py-2 rounded-lg border text-sm transition-colors"
                            :class="selectedValues[option.id] === value.id
                                ? 'border-[#6D5EF8] bg-[#6D5EF8]/10 text-[#6D5EF8]'
                                : 'border-[#E4E4E0] text-[#5F5E5A] hover:border-[#B4B2A9]'"
                        >
                            {{ value.value }}
                        </button>
                    </div>
                </div>

                <!-- If there are no option axes but variants exist with labels, treat labels as sizes -->
                <div v-if="product.options.length === 0 && product.variants && product.variants.length > 0" class="mb-4">
                    <p class="text-xs font-medium text-[#8A8B90] mb-2">Available Sizes</p>
                    <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="v in product.variants"
                                    :key="v.id"
                                    type="button"
                                    @click="selectedSize = v.label"
                                    class="px-3 py-2 rounded-lg border text-sm transition-colors"
                                    :class="selectedSize === v.label ? 'border-[#6D5EF8] bg-[#6D5EF8]/10 text-[#6D5EF8]' : 'border-[#E4E4E0] text-[#5F5E5A] hover:border-[#B4B2A9]'"
                                    :disabled="(v.stock - (v.reserved_stock ?? 0)) <= 0"
                                >
                                    {{ v.label }} <span v-if="(v.stock - (v.reserved_stock ?? 0)) <= 0" class="text-xs text-rose-600">(Out)</span>
                                </button>
                    </div>
                </div>

                <div v-if="matchedVariant" class="text-xs text-[#8A8B90] mb-4">
                    {{ (matchedVariant.stock - (matchedVariant.reserved_stock ?? 0)) > 0 ? `${matchedVariant.stock - (matchedVariant.reserved_stock ?? 0)} in stock` : 'Out of stock' }}
                </div>

                <div class="flex items-center gap-3 mb-6">
                    <div class="flex items-center border border-[#E4E4E0] rounded-lg">
                        <button type="button" @click="quantity = Math.max(1, quantity - 1)" class="w-9 h-9 text-[#5F5E5A]">−</button>
                        <input v-model.number="quantity" type="number" min="1" class="w-12 text-center text-sm border-x border-[#E4E4E0] h-9" />
                        <button type="button" @click="quantity++" class="w-9 h-9 text-[#5F5E5A]">+</button>
                    </div>

                    <button type="button" @click="addToCart" :disabled="(!matchedVariant && !selectedVariantByLabel) || ((matchedVariant ? (matchedVariant.stock - (matchedVariant.reserved_stock ?? 0)) : (selectedVariantByLabel ? (selectedVariantByLabel.stock - (selectedVariantByLabel.reserved_stock ?? 0)) : 0)) < 1) || form.processing" class="flex-1 h-11 rounded-xl border border-[#6D5EF8] text-[#6D5EF8] font-medium text-sm hover:bg-[#6D5EF8]/5 disabled:opacity-40">
                        {{ matchedVariant || selectedVariantByLabel ? 'Add to Cart' : 'Select options' }}
                    </button>

                    <button
                        type="button"
                        @click="buyNow"
                        :disabled="(!matchedVariant && !selectedVariantByLabel) || ((matchedVariant ? (matchedVariant.stock - (matchedVariant.reserved_stock ?? 0)) : (selectedVariantByLabel ? (selectedVariantByLabel.stock - (selectedVariantByLabel.reserved_stock ?? 0)) : 0)) < 1) || form.processing"
                        class="flex-1 h-11 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white font-medium text-sm disabled:opacity-40"
                    >
                        Buy Now
                    </button>
                </div>

                <div class="prose prose-sm max-w-none text-[#5F5E5A]" v-html="product.description"></div>
            </div>
        </div>

        <DialogModal :show="showMessageModal" @close="closeMessageModal" max-width="lg">
            <template #title>Message Seller</template>
            <template #content>
                <div class="space-y-4">
                    <div class="space-y-2 border-b border-[#EAEAE5] pb-4">
                        <p class="text-sm font-medium text-[#101114]">Seller: {{ product.team?.name }}</p>
                        <p class="text-sm text-[#5F5E5A]">Product: {{ product.name }}</p>
                        <p v-if="product.id" class="text-xs text-[#8A8B90]">Product ID: {{ product.id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[#101114] mb-2">Type your message:</label>
                        <textarea
                            v-model="messageForm.body"
                            rows="5"
                            class="w-full rounded-xl border border-[#E4E4E0] p-3 text-sm text-[#101114] focus:border-[#6D5EF8] outline-none"
                            placeholder="Type your message..."
                        ></textarea>
                        <p v-if="messageForm.errors.body" class="text-sm text-red-600 mt-2">{{ messageForm.errors.body }}</p>
                    </div>

                    <ActionMessage :on="messageSuccess">
                        <span class="text-sm text-emerald-600">✓ Message Sent Successfully!</span>
                    </ActionMessage>
                </div>
            </template>
            <template #footer>
                <button type="button" @click="closeMessageModal" class="rounded-xl border border-[#E4E4E0] px-4 py-2 text-sm text-[#5F5E5A] hover:bg-[#F7F7F5]">
                    Cancel
                </button>
                <button type="button" @click="sendMessage" :disabled="messageForm.processing" class="rounded-xl bg-[#101114] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b2b]">
                    Send
                </button>
            </template>
        </DialogModal>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 border-t border-[#EAEAE5]">
            <h2 class="text-lg font-semibold mb-4">Reviews ({{ product.reviews?.length ?? 0 }})</h2>
                        <div v-for="review in product.reviews" :key="review.id" class="py-4 border-b border-[#EAEAE5]">
                <p class="text-sm font-medium">{{ review.user?.name }} — <span :class="ratingColorClass(review.rating)">{{ '★'.repeat(review.rating) }}</span></p>
                <p class="text-sm text-[#5F5E5A] mt-1">{{ review.comment }}</p>

                <div v-if="review.image_paths?.length" class="flex gap-2 mt-2">
                    <img
                        v-for="(path, i) in review.image_paths"
                        :key="i"
                        :src="(path.startsWith('images/') ? '/' : '/storage/') + path"
                        class="w-16 h-16 rounded-lg object-cover border border-[#E4E4E0]"
                    />
                </div>

                <video
                    v-if="review.video_path"
                    controls
                    class="mt-2 w-full max-w-xs rounded-lg border border-[#E4E4E0]"
                    :src="(review.video_path.startsWith('videos/') ? '/' : '/storage/') + review.video_path"
                ></video>
            </div>
        </div>
    </GuestLayout>
</template>
