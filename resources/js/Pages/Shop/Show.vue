<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import ActionMessage from '@/Components/ActionMessage.vue';

const props = defineProps({
    shop: Object,
    products: Object,
    reviews: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ q: '', sort: 'featured' }),
    },
});

const page = usePage();

const activeTab = ref('products');
const searchQuery = ref(props.filters.q || '');
const sortOrder = ref(props.filters.sort || 'featured');

const handleSearch = () => {
    router.get(
        route('shops.show', props.shop.slug || props.shop.id),
        {
            q: searchQuery.value || undefined,
            sort: sortOrder.value,
        },
        { preserveState: true, replace: true }
    );
};

const handleSortChange = () => {
    router.get(
        route('shops.show', props.shop.slug || props.shop.id),
        {
            q: searchQuery.value || undefined,
            sort: sortOrder.value,
        },
        { preserveState: true, replace: true }
    );
};

const imageUrl = (path) => {
    if (!path) return '/images/placeholder-product.png';
    const p = typeof path === 'object' ? (path.path || '') : path;
    if (!p) return '/images/placeholder-product.png';
    return (p.startsWith('images/') || p.startsWith('http') ? (p.startsWith('http') ? p : '/' + p) : '/storage/' + p);
};

const shopLogoUrl = (path) => {
    if (!path) return null;
    return (path.startsWith('images/') || path.startsWith('http') ? (path.startsWith('http') ? path : '/' + path) : '/storage/' + path);
};

// Rating colors strictly preserved as requested
const ratingColorClass = (rating) => {
    const r = Number(rating);
    if (!r) return 'text-gray-400';
    if (r < 2.5) return 'text-gray-400';
    if (r < 4) return 'text-green-500';
    return 'text-yellow-500';
};

// Message Seller Modal
const showMessageModal = ref(false);
const messageSuccess = ref(false);
const messageForm = useForm({
    team_id: props.shop.id,
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
</script>

<template>
    <GuestLayout>
        <Head :title="shop.name + ' — Official Store | Zypher'" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs text-[#8A8B90] mb-8">
                <Link :href="route('home')" class="hover:text-[#101114] transition-colors">Home</Link>
                <span>›</span>
                <span class="hover:text-[#101114]">Shops</span>
                <span>›</span>
                <span class="text-[#101114] font-medium">{{ shop.name }}</span>
            </nav>

            <!-- Shop Banner / Profile Header (Matching Image 2) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-8 border-b border-[#EAEAE5]">
                <div class="flex items-center gap-5">
                    <!-- Shop Avatar Circle -->
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-[#101114] text-white flex items-center justify-center font-extrabold text-2xl sm:text-3xl shrink-0 overflow-hidden border-2 border-[#EAEAE5] shadow-xs">
                        <img
                            v-if="shopLogoUrl(shop.logo_path)"
                            :src="shopLogoUrl(shop.logo_path)"
                            :alt="shop.name"
                            class="w-full h-full object-cover"
                        />
                        <span v-else>{{ shop.name?.charAt(0) || 'S' }}</span>
                    </div>

                    <!-- Shop Details -->
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#101114] tracking-tight">
                            {{ shop.name }}
                        </h1>
                        <p class="text-xs sm:text-sm text-[#8A8B90] mt-1">
                            {{ shop.description || 'Independent apparel shop on Zypher' }}
                        </p>

                        <!-- Stats Row -->
                        <div class="flex items-center gap-2 mt-2 text-xs sm:text-sm text-[#8A8B90]">
                            <span class="flex items-center gap-1 font-semibold" :class="ratingColorClass(shop.rating_avg)">
                                <span>★</span>
                                <span>{{ shop.rating_avg || '5.0' }}</span>
                            </span>
                            <span>·</span>
                            <span>{{ shop.rating_count }} reviews</span>
                            <span>·</span>
                            <span>{{ shop.products_count }} products</span>
                        </div>
                    </div>
                </div>

                <!-- Message Seller Button -->
                <button
                    type="button"
                    @click="openMessageModal"
                    class="self-start sm:self-center px-4 py-2.5 rounded-xl border border-[#E4E4E0] hover:border-[#101114] text-xs font-semibold text-[#101114] bg-white hover:bg-[#F7F7F5] transition-colors flex items-center gap-2 shadow-2xs"
                >
                    <svg class="w-4 h-4 text-[#8A8B90]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span>Message Seller</span>
                </button>
            </div>

            <!-- Shop Navigation Tabs -->
            <div class="flex items-center gap-8 border-b border-[#EAEAE5] mt-6">
                <button
                    type="button"
                    @click="activeTab = 'products'"
                    class="pb-3 text-sm font-bold transition-all relative"
                    :class="activeTab === 'products' ? 'text-[#101114]' : 'text-[#8A8B90] hover:text-[#101114]'"
                >
                    <span>Products</span>
                    <span v-if="activeTab === 'products'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#101114]"></span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'about'"
                    class="pb-3 text-sm font-bold transition-all relative"
                    :class="activeTab === 'about' ? 'text-[#101114]' : 'text-[#8A8B90] hover:text-[#101114]'"
                >
                    <span>About Shop</span>
                    <span v-if="activeTab === 'about'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#101114]"></span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'reviews'"
                    class="pb-3 text-sm font-bold transition-all relative"
                    :class="activeTab === 'reviews' ? 'text-[#101114]' : 'text-[#8A8B90] hover:text-[#101114]'"
                >
                    <span>Reviews ({{ reviews.length }})</span>
                    <span v-if="activeTab === 'reviews'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#101114]"></span>
                </button>
            </div>

            <!-- Tab Content: Products -->
            <div v-if="activeTab === 'products'" class="py-6 space-y-6">
                <!-- Search & Sort Filter Bar (Matching Image 2) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <!-- Search In Shop -->
                    <div class="relative max-w-md w-full">
                        <input
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text"
                            placeholder="Search this shop..."
                            class="w-full rounded-xl border border-[#E4E4E0] pl-10 pr-4 py-2.5 text-xs text-[#101114] placeholder-[#8A8B90] focus:border-[#101114] focus:ring-0 shadow-2xs"
                        />
                        <svg class="w-4 h-4 text-[#8A8B90] absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Sort Dropdown -->
                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <select
                            v-model="sortOrder"
                            @change="handleSortChange"
                            class="rounded-xl border border-[#E4E4E0] px-4 py-2.5 text-xs font-semibold text-[#101114] bg-white focus:border-[#101114] focus:ring-0 shadow-2xs"
                        >
                            <option value="featured">Featured</option>
                            <option value="best_selling">Best Selling</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                            <option value="newest">Newest Arrivals</option>
                        </select>
                    </div>
                </div>

                <!-- 4-Column Product Grid (Matching Image 2) -->
                <div v-if="products.data && products.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <Link
                        v-for="prod in products.data"
                        :key="prod.id"
                        :href="route('products.show', { shopSlug: shop.slug || shop.id, productSlug: prod.slug })"
                        class="group block bg-white rounded-2xl p-3 border border-[#EAEAE5] hover:border-[#101114] transition-all hover:shadow-md"
                    >
                        <!-- Dark Container Image -->
                        <div class="aspect-square bg-[#0F1115] rounded-xl overflow-hidden relative flex items-center justify-center p-3">
                            <img
                                :src="imageUrl(prod.images?.[0]?.path)"
                                :alt="prod.name"
                                class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
                            />
                        </div>

                        <!-- Info -->
                        <div class="pt-3 space-y-1">
                            <h3 class="text-xs sm:text-sm font-bold text-[#101114] truncate group-hover:text-[#6D5EF8] transition-colors">
                                {{ prod.name }}
                            </h3>

                            <div class="text-base font-extrabold text-[#101114]">
                                ₱{{ Number(prod.min_price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </div>

                            <!-- Rating & Sold Row -->
                            <div class="flex items-center gap-2 text-xs text-[#8A8B90] pt-0.5">
                                <span class="font-bold tracking-tight" :class="ratingColorClass(prod.avg_rating)">
                                    {{ '★'.repeat(Math.round(prod.avg_rating || 5)) }}
                                </span>
                                <span>{{ prod.avg_rating || '5.0' }}</span>
                                <span>·</span>
                                <span>{{ prod.sold_count || 0 }} sold</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16 border border-dashed border-[#EAEAE5] rounded-2xl bg-white text-xs text-[#8A8B90]">
                    No products found matching your search.
                </div>

                <!-- Pagination (Matching Image 2) -->
                <div class="flex items-center justify-between pt-6 border-t border-[#EAEAE5]">
                    <div class="text-xs text-[#8A8B90]">
                        Showing {{ products.total || products.data?.length || 0 }} products
                    </div>

                    <div v-if="products.links && products.links.length > 3" class="flex items-center gap-1.5">
                        <Link
                            v-for="(link, i) in products.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="min-w-[2.25rem] h-9 px-2.5 rounded-lg text-xs font-semibold flex items-center justify-center transition-colors"
                            :class="link.active
                                ? 'bg-[#101114] text-white shadow-xs'
                                : (link.url ? 'bg-white border border-[#E4E4E0] text-[#101114] hover:bg-[#F7F7F5]' : 'bg-[#F7F7F5] text-gray-300 cursor-not-allowed')"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- Tab Content: About Shop -->
            <div v-else-if="activeTab === 'about'" class="py-8 max-w-2xl space-y-6">
                <div class="p-6 rounded-2xl bg-white border border-[#EAEAE5] space-y-4">
                    <h3 class="text-base font-bold text-[#101114]">About {{ shop.name }}</h3>
                    <p class="text-xs sm:text-sm text-[#5F5E5A] leading-relaxed">
                        {{ shop.description || 'Welcome to our official store on Zypher. We provide authentic streetwear and high quality apparel with fast shipping nationwide.' }}
                    </p>
                    <div class="pt-4 border-t border-[#EAEAE5] grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-[#8A8B90] block">Merchant Status</span>
                            <span class="font-bold text-emerald-600">✓ Verified Seller</span>
                        </div>
                        <div>
                            <span class="text-[#8A8B90] block">Response Rate</span>
                            <span class="font-bold text-[#101114]">100% within 1 hour</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Reviews -->
            <div v-else-if="activeTab === 'reviews'" class="py-8 space-y-4 max-w-3xl">
                <div v-if="reviews && reviews.length > 0" class="space-y-4">
                    <div
                        v-for="rev in reviews"
                        :key="rev.id"
                        class="p-5 rounded-2xl bg-white border border-[#EAEAE5] shadow-2xs space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#EAEAE5] text-[#101114] font-bold text-xs flex items-center justify-center">
                                    {{ rev.user?.name?.charAt(0) || 'U' }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-[#101114]">{{ rev.user?.name || 'Customer' }}</p>
                                    <div class="flex items-center gap-2 text-xs">
                                        <span class="font-bold" :class="ratingColorClass(rev.rating)">
                                            {{ '★'.repeat(rev.rating || 5) }}
                                        </span>
                                        <span class="text-[#8A8B90] text-[11px]">{{ rev.product?.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-[#101114] leading-relaxed pt-1">
                            {{ rev.comment || 'Smooth transaction and great quality!' }}
                        </p>
                    </div>
                </div>
                <div v-else class="text-center py-12 border border-dashed border-[#EAEAE5] rounded-2xl bg-white text-xs text-[#8A8B90]">
                    No shop reviews yet.
                </div>
            </div>
        </div>

        <!-- MESSAGE SELLER MODAL -->
        <DialogModal :show="showMessageModal" @close="closeMessageModal" max-width="lg">
            <template #title>Message Store — {{ shop.name }}</template>
            <template #content>
                <div class="space-y-4">
                    <p class="text-xs text-[#8A8B90]">
                        Send a direct inquiry or question to <strong>{{ shop.name }}</strong>.
                    </p>
                    <div>
                        <label class="block text-xs font-bold text-[#101114] uppercase tracking-wider mb-2">Message</label>
                        <textarea
                            v-model="messageForm.body"
                            rows="4"
                            class="w-full rounded-xl border border-[#E4E4E0] p-3 text-xs text-[#101114] focus:border-[#101114] focus:ring-0 outline-none"
                            placeholder="Type your message here..."
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
