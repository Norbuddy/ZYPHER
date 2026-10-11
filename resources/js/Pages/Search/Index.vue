<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ results: Object, filters: Object });

const minPrice = ref(props.filters.min_price || '');
const maxPrice = ref(props.filters.max_price || '');
const minRating = ref(props.filters.min_rating || '');
const sort = ref(props.filters.sort || 'relevance');

const applyFilters = () => {
    router.get(route('search'), {
        q: props.filters.q,
        category_id: props.filters.category_id,
        min_price: minPrice.value,
        max_price: maxPrice.value,
        min_rating: minRating.value,
        sort: sort.value,
    }, { preserveState: true });
};
</script>

<template>
    <GuestLayout>
        <Head :title="'Search — ' + (filters.q || '')" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-1 md:grid-cols-4 gap-8">
            <aside class="md:col-span-1 space-y-6">
                <div>
                    <p class="text-xs font-medium text-[#8A8B90] mb-2">Price Range</p>
                    <div class="flex items-center gap-2">
                        <input v-model="minPrice" type="number" placeholder="Min" class="w-full h-9 px-2 rounded-lg border border-[#E4E4E0] text-sm" />
                        <span class="text-[#8A8B90]">—</span>
                        <input v-model="maxPrice" type="number" placeholder="Max" class="w-full h-9 px-2 rounded-lg border border-[#E4E4E0] text-sm" />
                    </div>
                </div>

                <div>
                    <p class="text-xs font-medium text-[#8A8B90] mb-2">Minimum Rating</p>
                    <select v-model="minRating" class="w-full h-9 px-2 rounded-lg border border-[#E4E4E0] text-sm">
                        <option value="">Any</option>
                        <option value="4">4★ & up</option>
                        <option value="3">3★ & up</option>
                        <option value="2">2★ & up</option>
                    </select>
                </div>

                <button @click="applyFilters" class="w-full h-10 rounded-lg bg-[#101114] text-white text-sm font-medium">
                    Apply Filters
                </button>
            </aside>

            <div class="md:col-span-3">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-[#8A8B90]">{{ results.total }} results{{ filters.q ? ` for "${filters.q}"` : '' }}</p>
                    <select v-model="sort" @change="applyFilters" class="h-9 px-2 rounded-lg border border-[#E4E4E0] text-sm">
                        <option value="relevance">Relevance</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="newest">Newest</option>
                        <option value="rating">Highest Rated</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <Link
                        v-for="product in results.data"
                        :key="product.id"
                        :href="route('products.show', [product.team.slug, product.slug])"
                        class="group"
                    >
                        <div class="aspect-square rounded-xl bg-[#F0F0EC] overflow-hidden mb-2">
                            <img
                                v-if="product.images?.[0]"
                                :src="(product.images[0].path.startsWith('images/') ? '/' : '/storage/') + product.images[0].path"
                                :alt="product.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                            />
                        </div>
                        <p class="text-sm font-medium text-[#101114] truncate">{{ product.name }}</p>
                        <p class="text-sm text-[#6D5EF8] font-semibold">₱{{ product.min_price }}</p>
                        <p class="text-xs text-[#8A8B90]">★ {{ product.avg_rating }} · {{ product.sold_count }} sold</p>
                    </Link>
                </div>

                <div v-if="results.data.length === 0" class="text-center py-20 text-[#8A8B90]">
                    No products found. Try adjusting your filters.
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
