<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import ActionMessage from '@/Components/ActionMessage.vue';

const props = defineProps({
    product: Object,
    available_sizes: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

// Track selected value id per option axis
const selectedValues = ref({});
props.product.options?.forEach(opt => {
    selectedValues.value[opt.id] = opt.values?.[0]?.id || null;
});

// For products using simple labeled variants
const initialVariant = props.product.variants?.[0]?.label || null;
const selectedSize = ref(initialVariant);

const selectedVariantByLabel = computed(() => {
    if (!props.product.variants || props.product.variants.length === 0) return null;
    if (selectedSize.value) {
        const found = props.product.variants.find(v => String(v.label) === String(selectedSize.value));
        if (found) return found;
    }
    return props.product.variants[0];
});

const matchedVariant = computed(() => {
    if (!props.product.options || props.product.options.length === 0) {
        return selectedVariantByLabel.value;
    }

    const selectedIds = Object.values(selectedValues.value).filter(Boolean).sort();
    if (selectedIds.length !== props.product.options.length) {
        return selectedVariantByLabel.value;
    }

    const found = props.product.variants.find(variant => {
        const variantValueIds = (variant.option_values || []).map(v => v.id).sort();
        return JSON.stringify(variantValueIds) === JSON.stringify(selectedIds);
    });

    return found || selectedVariantByLabel.value;
});

// Effective variant and stock
const currentVariant = computed(() => matchedVariant.value || selectedVariantByLabel.value);

const availableStock = computed(() => {
    if (currentVariant.value) {
        return Math.max(0, currentVariant.value.stock - (currentVariant.value.reserved_stock ?? 0));
    }
    return Math.max(0, props.product.stock ?? 0);
});

const quantity = ref(1);

const isExceedingStock = computed(() => {
    return quantity.value > availableStock.value || availableStock.value <= 0;
});

const decreaseQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};

const increaseQuantity = () => {
    if (quantity.value < availableStock.value) {
        quantity.value++;
    } else {
        quantity.value++;
    }
};

// Gallery handling
const selectedImageIndex = ref(0);
const isFullscreenImage = ref(false);

const imageUrl = (img) => {
    if (!img) return '';
    const path = typeof img === 'string' ? img : img.path;
    if (!path) return '';
    return (path.startsWith('images/') || path.startsWith('http') ? (path.startsWith('http') ? path : '/' + path) : '/storage/' + path);
};

const shopLogoUrl = (team) => {
    if (!team || !team.logo_path) return null;
    const path = team.logo_path;
    return (path.startsWith('images/') || path.startsWith('http') ? (path.startsWith('http') ? path : '/' + path) : '/storage/' + path);
};

const currentImages = computed(() => {
    if (props.product.images && props.product.images.length > 0) {
        return props.product.images;
    }
    return [{ id: 0, path: 'images/placeholder-product.png' }];
});

const nextImage = () => {
    if (currentImages.value.length <= 1) return;
    selectedImageIndex.value = (selectedImageIndex.value + 1) % currentImages.value.length;
};

const prevImage = () => {
    if (currentImages.value.length <= 1) return;
    selectedImageIndex.value = (selectedImageIndex.value - 1 + currentImages.value.length) % currentImages.value.length;
};

// Touch swipe support
const touchStartX = ref(0);
const onTouchStart = (e) => {
    touchStartX.value = e.changedTouches[0].screenX;
};
const onTouchEnd = (e) => {
    const diff = e.changedTouches[0].screenX - touchStartX.value;
    if (diff > 50) prevImage();
    else if (diff < -50) nextImage();
};

// Rating colors strictly preserved as requested
const ratingColorClass = (rating) => {
    const r = Number(rating);
    if (!r) return 'text-gray-400';
    if (r < 2.5) return 'text-gray-400';
    if (r < 4) return 'text-green-500';
    return 'text-yellow-500';
};

// Add to Cart with Animated Notifier Popup
const showAddToCartModal = ref(false);
const addedItemSnapshot = ref(null);
const cartForm = useForm({ product_variant_id: null, quantity: 1 });
const buyNowForm = useForm({ product_variant_id: null, quantity: 1 });

const addToCart = () => {
    if (!page.props.auth?.user) {
        router.visit(route('login'));
        return;
    }
    if (isExceedingStock.value || availableStock.value < 1) return;
    const variant = currentVariant.value;
    if (!variant) return;

    cartForm.product_variant_id = variant.id;
    cartForm.quantity = quantity.value;

    addedItemSnapshot.value = {
        name: props.product.name,
        price: variant.price || props.product.min_price,
        quantity: quantity.value,
        variantLabel: variant.label || 'Default',
        image: currentImages.value[selectedImageIndex.value] || currentImages.value[0],
    };

    cartForm.post(route('cart.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddToCartModal.value = true;
        },
    });
};

// Buy Now: Direct checkout WITHOUT adding to user's persistent cart!
const buyNow = () => {
    if (!page.props.auth?.user) {
        router.visit(route('login'));
        return;
    }
    if (isExceedingStock.value || availableStock.value < 1) return;
    const variant = currentVariant.value;
    if (!variant) return;

    buyNowForm.product_variant_id = variant.id;
    buyNowForm.quantity = quantity.value;

    buyNowForm.post(route('buy-now'), {
        preserveScroll: true,
    });
};

// Message Seller Modal
const showMessageModal = ref(false);
const messageSuccess = ref(false);
const messageForm = useForm({
    team_id: props.product.team_id,
    product_id: props.product.id,
    product_name: props.product.name,
    body: '',
});

const openMessageModal = () => {
    if (!page.props.auth?.user) {
        router.visit(route('login'));
        return;
    }
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

// Review sorting
const reviewSort = ref('recent');
const sortedReviews = computed(() => {
    const list = [...(props.product.reviews || [])];
    if (reviewSort.value === 'recent') {
        return list.reverse();
    }
    if (reviewSort.value === 'highest') {
        return list.sort((a, b) => (b.rating || 0) - (a.rating || 0));
    }
    if (reviewSort.value === 'lowest') {
        return list.sort((a, b) => (a.rating || 0) - (b.rating || 0));
    }
    return list;
});

const formatReviewDate = (dateStr) => {
    if (!dateStr) return 'Recent';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch (e) {
        return dateStr;
    }
};
</script>

<template>
    <GuestLayout>
        <Head :title="product.name + ' — Zypher'" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs text-[#8A8B90] mb-6 overflow-x-auto whitespace-nowrap scrollbar-none">
                <Link :href="route('home')" class="hover:text-[#101114] transition-colors">Home</Link>
                <span>›</span>
                <template v-if="product.category?.parent">
                    <span class="hover:text-[#101114]">{{ product.category.parent.name }}</span>
                    <span>›</span>
                </template>
                <Link
                    v-if="product.category"
                    :href="route('search', { category_id: product.category.slug || product.category.id })"
                    class="hover:text-[#101114] transition-colors"
                >
                    {{ product.category.name }}
                </Link>
                <span>›</span>
                <span class="text-[#101114] font-medium truncate max-w-xs">{{ product.name }}</span>
            </nav>

            <!-- Main Product Section -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 pb-12 border-b border-[#EAEAE5]">
                <!-- Left Column: Gallery (5 cols) -->
                <div class="lg:col-span-6 space-y-4">
                    <!-- Main Image Frame -->
                    <div
                        class="relative aspect-square rounded-2xl bg-[#0F1115] border border-[#EAEAE5] overflow-hidden group select-none shadow-xs"
                        @touchstart="onTouchStart"
                        @touchend="onTouchEnd"
                    >
                        <img
                            :src="imageUrl(currentImages[selectedImageIndex])"
                            :alt="product.name"
                            class="w-full h-full object-contain p-4 transition-transform duration-300 group-hover:scale-[1.02]"
                        />

                        <!-- Fullscreen Button -->
                        <button
                            type="button"
                            @click="isFullscreenImage = true"
                            title="View Fullscreen"
                            class="absolute top-3.5 right-3.5 w-8 h-8 rounded-full bg-white/80 hover:bg-white text-[#101114] flex items-center justify-center shadow-md backdrop-blur-xs transition-transform active:scale-95 z-10"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </button>

                        <!-- Nav Prev / Next -->
                        <template v-if="currentImages.length > 1">
                            <button
                                type="button"
                                @click="prevImage"
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/85 hover:bg-white text-[#101114] flex items-center justify-center shadow-md backdrop-blur-xs transition-transform active:scale-95 z-10"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                @click="nextImage"
                                class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/85 hover:bg-white text-[#101114] flex items-center justify-center shadow-md backdrop-blur-xs transition-transform active:scale-95 z-10"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </template>

                        <!-- Counter Pill Badge -->
                        <div class="absolute bottom-3 right-3 px-2.5 py-1 rounded-md bg-black/75 text-white text-xs font-semibold backdrop-blur-xs shadow-xs pointer-events-none">
                            {{ selectedImageIndex + 1 }}/{{ currentImages.length }}
                        </div>
                    </div>

                    <!-- Thumbnails Row -->
                    <div v-if="currentImages.length > 1" class="flex items-center gap-3 overflow-x-auto pb-1 scrollbar-none">
                        <button
                            v-for="(img, idx) in currentImages"
                            :key="img.id || idx"
                            type="button"
                            @click="selectedImageIndex = idx"
                            class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden bg-[#0F1115] border-2 transition-all shrink-0 shadow-2xs"
                            :class="idx === selectedImageIndex ? 'border-[#101114] ring-2 ring-[#101114]/20' : 'border-transparent opacity-70 hover:opacity-100'"
                        >
                            <img :src="imageUrl(img)" class="w-full h-full object-contain p-1" />
                        </button>
                    </div>
                </div>

                <!-- Right Column: Product Details & Purchase Actions (6 cols) -->
                <div class="lg:col-span-6 space-y-6">
                    <!-- Shop Card Widget -->
                    <div class="p-3.5 rounded-2xl border border-[#EAEAE5] bg-white flex items-center justify-between gap-4 shadow-2xs">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Shop Logo Circle -->
                            <div class="w-11 h-11 rounded-full bg-[#101114] text-white flex items-center justify-center text-sm font-bold overflow-hidden shrink-0 border border-[#EAEAE5]">
                                <img
                                    v-if="shopLogoUrl(product.team)"
                                    :src="shopLogoUrl(product.team)"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else>{{ product.team?.name?.charAt(0) || 'S' }}</span>
                            </div>

                            <div class="min-w-0">
                                <Link
                                    :href="route('shops.show', product.team?.slug || product.team?.id)"
                                    class="text-sm font-bold text-[#101114] hover:text-[#6D5EF8] transition-colors flex items-center gap-1 group truncate"
                                >
                                    <span class="truncate">{{ product.team?.name || 'Zypher Verified Shop' }}</span>
                                    <svg class="w-3.5 h-3.5 shrink-0 opacity-60 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                                <Link
                                    :href="route('shops.show', product.team?.slug || product.team?.id)"
                                    class="text-[11px] text-[#8A8B90] hover:text-[#101114] transition-colors block"
                                >
                                    View Shop
                                </Link>
                            </div>
                        </div>

                        <!-- Message Seller Button -->
                        <button
                            type="button"
                            @click="openMessageModal"
                            class="shrink-0 px-3.5 py-2 rounded-xl border border-[#E4E4E0] hover:border-[#101114] text-xs font-semibold text-[#101114] bg-white hover:bg-[#F7F7F5] transition-colors flex items-center gap-2 shadow-2xs"
                        >
                            <svg class="w-4 h-4 text-[#8A8B90]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span>Message Seller</span>
                        </button>
                    </div>

                    <!-- Title & Metrics -->
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#101114] tracking-tight leading-tight">
                            {{ product.name }}
                        </h1>

                        <!-- Rating & Sales row (colors preserved!) -->
                        <div class="flex items-center gap-2 mt-2 text-sm text-[#8A8B90] flex-wrap">
                            <span class="flex items-center gap-1 font-semibold" :class="ratingColorClass(product.avg_rating)">
                                <span>★</span>
                                <span>{{ product.avg_rating || '5.0' }}</span>
                            </span>
                            <span>·</span>
                            <span>{{ product.rating_count ?? (product.reviews?.length || 0) }} reviews</span>
                            <span>·</span>
                            <span>{{ product.sold_count || 0 }} sold</span>
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="pt-1">
                        <div class="text-3xl font-extrabold text-[#101114] tracking-tight">
                            ₱{{ Number(currentVariant?.price ?? product.min_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            <span v-if="!currentVariant && product.min_price !== product.max_price" class="text-xl font-medium text-[#8A8B90]">
                                – ₱{{ Number(product.max_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </span>
                        </div>
                        <p class="text-xs text-[#8A8B90] mt-1">Inclusive of applicable marketplace taxes</p>
                    </div>

                    <!-- Description Preview -->
                    <div class="text-xs text-[#5F5E5A] leading-relaxed line-clamp-3">
                        {{ product.description }}
                    </div>

                    <!-- Option Axes (e.g. Size / Color) -->
                    <div v-if="product.options && product.options.length > 0" class="space-y-4 pt-2">
                        <div v-for="option in product.options" :key="option.id">
                            <label class="block text-xs font-bold text-[#101114] uppercase tracking-wider mb-2">
                                {{ option.name }}
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="val in option.values"
                                    :key="val.id"
                                    type="button"
                                    @click="selectedValues[option.id] = val.id"
                                    class="min-w-[2.75rem] h-10 px-3.5 rounded-lg text-xs font-semibold transition-all border"
                                    :class="selectedValues[option.id] === val.id
                                        ? 'bg-[#101114] text-white border-[#101114] shadow-xs'
                                        : 'bg-white text-[#101114] border-[#E4E4E0] hover:border-[#101114]'"
                                >
                                    {{ val.value }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Labeled Variants / Size Selector (Square pill buttons matching Image 1) -->
                    <div v-else-if="product.variants && product.variants.length > 0" class="pt-2">
                        <label class="block text-xs font-bold text-[#101114] uppercase tracking-wider mb-2">
                            Size
                        </label>
                        <div class="flex flex-wrap gap-2.5">
                            <button
                                v-for="v in product.variants"
                                :key="v.id"
                                type="button"
                                @click="selectedSize = v.label"
                                :disabled="(v.stock - (v.reserved_stock ?? 0)) <= 0"
                                class="min-w-[2.75rem] h-10 px-4 rounded-lg text-xs font-semibold transition-all border flex items-center justify-center"
                                :class="selectedSize === v.label
                                    ? 'bg-[#101114] text-white border-[#101114] shadow-xs'
                                    : ((v.stock - (v.reserved_stock ?? 0)) <= 0
                                        ? 'bg-[#F7F7F5] text-gray-400 border-gray-200 line-through cursor-not-allowed'
                                        : 'bg-white text-[#101114] border-[#E4E4E0] hover:border-[#101114]')"
                            >
                                {{ v.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Quantity Section with Stock Indicator -->
                    <div class="space-y-2 pt-2">
                        <label class="block text-xs font-bold text-[#101114] uppercase tracking-wider">
                            Quantity
                        </label>

                        <div class="flex items-center gap-4 flex-wrap">
                            <!-- Stepper Box -->
                            <div class="inline-flex items-center border border-[#E4E4E0] rounded-xl bg-white overflow-hidden shadow-2xs">
                                <button
                                    type="button"
                                    @click="decreaseQuantity"
                                    :disabled="quantity <= 1"
                                    class="w-10 h-10 flex items-center justify-center text-[#101114] hover:bg-[#F7F7F5] disabled:opacity-30 disabled:cursor-not-allowed transition-colors font-medium text-base"
                                >
                                    −
                                </button>
                                <input
                                    v-model.number="quantity"
                                    type="number"
                                    min="1"
                                    class="w-14 text-center text-sm font-semibold text-[#101114] border-0 focus:ring-0 p-0"
                                />
                                <button
                                    type="button"
                                    @click="increaseQuantity"
                                    class="w-10 h-10 flex items-center justify-center text-[#101114] hover:bg-[#F7F7F5] transition-colors font-medium text-base"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Stock Status Icon & Amount -->
                            <div class="flex items-center gap-1.5 text-xs text-[#8A8B90] font-medium">
                                <svg class="w-4 h-4 text-[#8A8B90]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span v-if="availableStock > 0">{{ availableStock }} in stock</span>
                                <span v-else class="text-rose-600 font-semibold">Out of stock</span>
                            </div>
                        </div>

                        <!-- EXCEEDING STOCK ALERT (User Requirement) -->
                        <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                            <div
                                v-if="isExceedingStock && availableStock > 0"
                                class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2.5 shadow-2xs"
                            >
                                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <span class="font-bold">Paunawa:</span> Lagpas sa available stock ang inilagay mong quantity! Mayroon na lamang <strong>{{ availableStock }}</strong> piraso na available. Paki-bawasan ang dami upang makapag-order.
                                </div>
                            </div>
                            <div
                                v-else-if="availableStock <= 0"
                                class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2.5"
                            >
                                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <span class="font-bold">Sold Out:</span> Kasalukuyang out of stock ang produktong ito.
                                </div>
                            </div>
                        </transition>
                    </div>

                    <!-- Action Buttons: Add to Cart & Buy Now -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3">
                        <!-- Add to Cart (Solid Black) -->
                        <button
                            type="button"
                            @click="addToCart"
                            :disabled="isExceedingStock || availableStock < 1 || cartForm.processing"
                            class="h-12 rounded-xl bg-[#101114] hover:bg-[#25272C] active:bg-black text-white font-semibold text-sm flex items-center justify-center gap-2 shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>{{ cartForm.processing ? 'Adding...' : 'Add to Cart' }}</span>
                        </button>

                        <!-- Buy Now (Light Outline with Lightning Icon - DIRECT CHECKOUT) -->
                        <button
                            type="button"
                            @click="buyNow"
                            :disabled="isExceedingStock || availableStock < 1 || buyNowForm.processing"
                            class="h-12 rounded-xl bg-white hover:bg-[#F7F7F5] active:bg-[#EAEAE5] border border-[#101114] text-[#101114] font-semibold text-sm flex items-center justify-center gap-2 shadow-2xs transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            <svg class="w-4 h-4 text-[#101114]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>{{ buyNowForm.processing ? 'Processing...' : 'Buy Now' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Customer Reviews Section (Matching Image 1) -->
            <section class="py-10">
                <div class="flex items-center justify-between gap-4 mb-6 flex-wrap">
                    <div>
                        <h2 class="text-xl font-bold text-[#101114] tracking-tight">
                            Reviews ({{ sortedReviews.length }})
                        </h2>
                        <p class="text-xs text-[#8A8B90] mt-0.5">
                            Ratings and feedback from customers
                        </p>
                    </div>

                    <!-- Review Filter Sort -->
                    <div class="flex items-center gap-2">
                        <select
                            v-model="reviewSort"
                            class="text-xs font-medium text-[#101114] border border-[#E4E4E0] rounded-xl px-3 py-2 bg-white focus:border-[#101114] focus:ring-0 shadow-2xs"
                        >
                            <option value="recent">Most recent</option>
                            <option value="highest">Highest rating</option>
                            <option value="lowest">Lowest rating</option>
                        </select>
                    </div>
                </div>

                <!-- Review List -->
                <div v-if="sortedReviews.length > 0" class="space-y-4">
                    <div
                        v-for="rev in sortedReviews"
                        :key="rev.id"
                        class="p-5 rounded-2xl border border-[#EAEAE5] bg-white shadow-2xs space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <!-- User Avatar Circle -->
                                <div class="w-10 h-10 rounded-full bg-[#EAEAE5] text-[#101114] font-bold text-sm flex items-center justify-center shrink-0">
                                    {{ rev.user?.name?.charAt(0) || 'N' }}
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-[#101114]">{{ rev.user?.name || 'Customer' }}</span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-black text-white">
                                            ✔ Verified purchase
                                        </span>
                                    </div>

                                    <!-- Stars & Date (Colors preserved!) -->
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs tracking-wider font-bold" :class="ratingColorClass(rev.rating)">
                                            {{ '★'.repeat(rev.rating || 5) }}
                                        </span>
                                        <span class="text-[11px] text-[#8A8B90]">{{ formatReviewDate(rev.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Options Dropdown placeholder / icon -->
                            <button type="button" class="text-gray-400 hover:text-gray-600 p-1">
                                •••
                            </button>
                        </div>

                        <!-- Review Text -->
                        <p class="text-xs sm:text-sm text-[#101114] leading-relaxed">
                            {{ rev.comment || 'Maganda ang product at de-kalidad ang tela!' }}
                        </p>

                        <!-- Attached Images -->
                        <div v-if="rev.image_paths && rev.image_paths.length > 0" class="flex gap-2 pt-1 flex-wrap">
                            <img
                                v-for="(img, idx) in rev.image_paths"
                                :key="idx"
                                :src="imageUrl(img)"
                                class="w-14 h-14 rounded-xl object-cover border border-[#EAEAE5]"
                            />
                        </div>

                        <!-- Helpful / Reply actions matching mockup -->
                        <div class="flex items-center gap-4 pt-1 text-xs text-[#8A8B90]">
                            <button type="button" class="flex items-center gap-1.5 hover:text-[#101114] transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                </svg>
                                <span>Helpful ({{ rev.helpful_count || 1 }})</span>
                            </button>
                            <span>|</span>
                            <button type="button" class="flex items-center gap-1.5 hover:text-[#101114] transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span>Reply</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12 border border-dashed border-[#EAEAE5] rounded-2xl bg-white text-xs text-[#8A8B90]">
                    No customer reviews yet. Be the first to order and review this item!
                </div>
            </section>
        </div>

        <!-- COOL ANIMATED POP-UP NOTIFIER FOR ADD TO CART -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
        >
            <div
                v-if="showAddToCartModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                @click.self="showAddToCartModal = false"
            >
                <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-md w-full shadow-2xl border border-gray-100 transform transition-all relative overflow-hidden">
                    <!-- Top Cool Glow Accent -->
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-emerald-400/20 rounded-full blur-2xl pointer-events-none"></div>

                    <!-- Header with animated success check -->
                    <div class="flex items-center gap-3.5 mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100 animate-pulse">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-[#101114]">Successfully Added to Cart!</h3>
                            <p class="text-xs text-[#8A8B90]">Nalagay na po ang item sa iyong shopping cart.</p>
                        </div>
                    </div>

                    <!-- Product Summary Card -->
                    <div v-if="addedItemSnapshot" class="p-4 rounded-2xl bg-[#F7F7F5] border border-[#EAEAE5] flex items-center gap-3.5 mb-6">
                        <div class="w-16 h-16 rounded-xl bg-white border border-[#EAEAE5] overflow-hidden shrink-0">
                            <img :src="imageUrl(addedItemSnapshot.image)" class="w-full h-full object-contain p-1" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs font-bold text-[#101114] truncate">{{ addedItemSnapshot.name }}</h4>
                            <p class="text-[11px] text-[#8A8B90] mt-0.5">Size/Option: {{ addedItemSnapshot.variantLabel }} · Qty: {{ addedItemSnapshot.quantity }}</p>
                            <p class="text-xs font-extrabold text-[#101114] mt-1">₱{{ Number(addedItemSnapshot.price * addedItemSnapshot.quantity).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="showAddToCartModal = false"
                            class="h-11 rounded-xl border border-[#E4E4E0] hover:bg-[#F7F7F5] text-xs font-bold text-[#101114] transition-colors"
                        >
                            Continue Shopping
                        </button>
                        <Link
                            :href="route('cart.index')"
                            class="h-11 rounded-xl bg-[#101114] hover:bg-black text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm transition-colors"
                        >
                            <span>View Cart</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </transition>

        <!-- FULLSCREEN IMAGE LIGHTBOX -->
        <div
            v-if="isFullscreenImage"
            class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center p-4 backdrop-blur-md"
            @click.self="isFullscreenImage = false"
        >
            <button
                type="button"
                @click="isFullscreenImage = false"
                class="absolute top-6 right-6 text-white hover:text-gray-300 w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-lg z-10"
            >
                ✕
            </button>
            <div class="relative max-w-4xl max-h-[85vh] flex items-center justify-center">
                <img :src="imageUrl(currentImages[selectedImageIndex])" class="max-w-full max-h-[85vh] object-contain rounded-2xl" />
            </div>
        </div>

        <!-- MESSAGE SELLER MODAL -->
        <DialogModal :show="showMessageModal" @close="closeMessageModal" max-width="lg">
            <template #title>Message Seller — {{ product.team?.name }}</template>
            <template #content>
                <div class="space-y-4">
                    <div class="p-3 rounded-xl bg-[#F7F7F5] border border-[#EAEAE5] text-xs text-[#5F5E5A]">
                        Inquiring about: <strong class="text-[#101114]">{{ product.name }}</strong>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[#101114] uppercase tracking-wider mb-2">Your Message</label>
                        <textarea
                            v-model="messageForm.body"
                            rows="4"
                            class="w-full rounded-xl border border-[#E4E4E0] p-3 text-xs text-[#101114] focus:border-[#101114] focus:ring-0 outline-none"
                            placeholder="Kumusta po, available pa po ba ito?..."
                        ></textarea>
                        <p v-if="messageForm.errors.body" class="text-xs text-rose-600 mt-1">{{ messageForm.errors.body }}</p>
                    </div>

                    <ActionMessage :on="messageSuccess">
                        <span class="text-xs text-emerald-600 font-bold">✓ Message sent successfully!</span>
                    </ActionMessage>
                </div>
            </template>
            <template #footer>
                <button type="button" @click="closeMessageModal" class="rounded-xl border border-[#E4E4E0] px-4 py-2 text-xs font-medium text-[#5F5E5A] hover:bg-[#F7F7F5]">
                    Cancel
                </button>
                <button type="button" @click="sendMessage" :disabled="messageForm.processing" class="rounded-xl bg-[#101114] px-5 py-2 text-xs font-bold text-white hover:bg-black ml-2">
                    Send Message
                </button>
            </template>
        </DialogModal>
    </GuestLayout>
</template>
