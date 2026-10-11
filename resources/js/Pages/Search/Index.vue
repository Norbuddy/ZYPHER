<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    results: Object,
    filters: Object,
    categories: {
        type: Array,
        default: () => [],
    },
});

const selectedCategoryId = ref(props.filters.category_id ? String(props.filters.category_id) : '');
const minPrice = ref(props.filters.min_price || '');
const maxPrice = ref(props.filters.max_price || '');
const minRating = ref(props.filters.min_rating || '');
const sort = ref(props.filters.sort || 'relevance');

const availableCategories = computed(() => {
    const list = [...props.categories];
    if (!list.some(c => c.name?.toLowerCase() === 'shoes' || c.slug === 'shoes')) {
        list.push({ id: 'shoes', name: 'Shoes', slug: 'shoes' });
    }
    return list;
});

const applyFilters = () => {
    router.get(route('search'), {
        q: props.filters.q,
        category_id: selectedCategoryId.value || undefined,
        min_price: minPrice.value || undefined,
        max_price: maxPrice.value || undefined,
        min_rating: minRating.value || undefined,
        sort: sort.value,
    }, { preserveState: true });
};
</script>

<template>
    <GuestLayout>
        <Head :title="'Search — ' + (filters.q || 'Products')" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-1 md:grid-cols-4 gap-8">
            <aside class="md:col-span-1">
                <div class="p-5 rounded-2xl bg-white border border-[#EAEAE5] shadow-sm space-y-6">
                    <h3 class="text-sm font-semibold text-[#101114] tracking-tight">Filters</h3>

                    <!-- Price Range -->
                    <div>
                        <label class="block text-xs font-medium text-[#8A8B90] mb-2">Price Range (₱)</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="minPrice"
                                type="number"
                                placeholder="Min"
                                class="w-full h-9 px-2.5 rounded-lg border border-[#E4E4E0] bg-[#FBFBFA] text-sm focus:bg-white focus:border-[#101114]"
                            />
                            <span class="text-[#8A8B90] text-xs">—</span>
                            <input
                                v-model="maxPrice"
                                type="number"
                                placeholder="Max"
                                class="w-full h-9 px-2.5 rounded-lg border border-[#E4E4E0] bg-[#FBFBFA] text-sm focus:border-[#101114]"
                            />
                        </div>
                    </div>

                    <!-- Category Selector (Directly above Minimum Rating) -->
                    <div>
                        <label class="block text-xs font-medium text-[#8A8B90] mb-2">Category</label>
                        <select
                            v-model="selectedCategoryId"
                            class="w-full h-9 px-3 rounded-lg border border-[#E4E4E0] bg-[#FBFBFA] text-sm text-[#101114] focus:bg-white focus:border-[#101114] focus:ring-1 focus:ring-[#101114] transition"
                        >
                            <option value="">All Categories</option>
                            <option v-for="cat in availableCategories" :key="cat.id" :value="String(cat.id)">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Minimum Rating -->
                    <div>
                        <label class="block text-xs font-medium text-[#8A8B90] mb-2">Minimum Rating</label>
                        <select
                            v-model="minRating"
                            class="w-full h-9 px-3 rounded-lg border border-[#E4E4E0] bg-[#FBFBFA] text-sm text-[#101114] focus:bg-white focus:border-[#101114]"
                        >
                            <option value="">Any Rating</option>
                            <option value="4">★ 4.0 & up</option>
                            <option value="3">★ 3.0 & up</option>
                            <option value="2">★ 2.0 & up</option>
                        </select>
                    </div>

                    <button
                        type="button"
                        @click="applyFilters"
                        class="w-full h-10 rounded-xl bg-[#101114] text-white text-sm font-medium hover:bg-black transition shadow-sm"
                    >
                        Apply Filters
                    </button>
                </div>
            </aside>

            <div class="md:col-span-3">
                <div class="flex items-center justify-between mb-6 pb-3 border-b border-[#EAEAE5]">
                    <p class="text-sm font-medium text-[#5F5E5A]">
                        <span class="text-[#101114] font-semibold">{{ results.total }}</span> results{{ filters.q ? ` for "${filters.q}"` : '' }}
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-[#8A8B90]">Sort by:</span>
                        <select
                            v-model="sort"
                            @change="applyFilters"
                            class="h-9 px-3 rounded-lg border border-[#E4E4E0] bg-white text-sm text-[#101114] font-medium"
                        >
                            <option value="relevance">Relevance</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                            <option value="newest">Newest</option>
                            <option value="rating">Highest Rated</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                    <Link
                        v-for="product in results.data"
                        :key="product.id"
                        :href="route('products.show', [product.team.slug, product.slug])"
                        class="group p-3 rounded-2xl bg-white border border-[#EAEAE5] hover:border-[#101114]/30 hover:shadow-md transition-all duration-200"
                    >
                        <div class="aspect-square rounded-xl bg-[#F0F0EC] overflow-hidden mb-3">
                            <img
                                v-if="product.images?.[0]"
                                :src="(product.images[0].path.startsWith('images/') ? '/' : '/storage/') + product.images[0].path"
                                :alt="product.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                        </div>
                        <p class="text-xs text-[#8A8B90] mb-0.5 truncate">{{ product.team?.name }}</p>
                        <p class="text-sm font-semibold text-[#101114] truncate mb-1.5">{{ product.name }}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-[#101114]">₱{{ Number(product.min_price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                            <span class="text-[11px] font-medium text-[#5F5E5A] bg-[#F7F7F5] px-2 py-0.5 rounded-full border border-[#EAEAE5]">★ {{ product.avg_rating }}</span>
                        </div>
                    </Link>
                </div>

                <div v-if="results.data.length === 0" class="text-center py-20 text-[#8A8B90] bg-white rounded-2xl border border-[#EAEAE5]">
                    <svg class="w-12 h-12 mx-auto mb-3 text-[#B0B0AC]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-base font-medium text-[#101114]">No products found</p>
                    <p class="text-xs text-[#8A8B90] mt-1">Try adjusting your filters or search keywords.</p>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
