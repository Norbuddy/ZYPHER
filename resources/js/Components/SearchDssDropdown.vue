<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    suggestions: {
        type: Array,
        default: () => [],
    },
    history: {
        type: Array,
        default: () => [],
    },
    recommendations: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['selectSuggestion', 'selectHistory']);

const recommendationImage = (product) => {
    if (!product?.images?.[0]?.path) {
        return null;
    }
    const path = product.images[0].path;
    return path.startsWith('images/') ? `/${path}` : `/storage/${path}`;
};
</script>

<template>
    <div class="bg-white rounded-2xl border border-[#E4E4E0] shadow-xl overflow-hidden text-left">
        <!-- LOADING -->
        <div v-if="loading" class="px-4 py-4 text-xs text-[#8A8B90] flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-[#6D5EF8]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>Finding recommendations...</span>
        </div>

        <!-- SEARCH SUGGESTIONS -->
        <div v-if="suggestions.length" class="p-3 border-b border-[#EAEAE5]">
            <div class="flex items-center justify-between px-2 mb-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-[#8A8B90]">
                    Suggestions
                </p>
                <span class="text-[10px] text-[#B0B0AC]">
                    Based on similarity
                </span>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="suggestion in suggestions"
                    :key="suggestion"
                    type="button"
                    @mousedown.prevent="emit('selectSuggestion', suggestion)"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[#F7F7F5] border border-[#EAEAE5] text-xs sm:text-sm text-[#101114] hover:border-[#6D5EF8] hover:text-[#6D5EF8] transition"
                >
                    <svg class="w-3.5 h-3.5 text-[#8A8B90]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
                    </svg>
                    {{ suggestion }}
                </button>
            </div>
        </div>

        <!-- RECENT SEARCHES -->
        <div v-if="history.length" class="p-3 border-b border-[#EAEAE5]">
            <div class="flex items-center justify-between px-2 mb-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-[#8A8B90]">
                    Recent Searches
                </p>
                <span class="text-[10px] text-[#B0B0AC]">
                    Your activity
                </span>
            </div>

            <div class="space-y-1">
                <button
                    v-for="item in history"
                    :key="item.id"
                    type="button"
                    @mousedown.prevent="emit('selectHistory', item.query)"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-left hover:bg-[#F7F7F5] transition"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-3.5 h-3.5 text-[#8A8B90] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 2" stroke-linecap="round" />
                        </svg>
                        <span class="text-xs sm:text-sm text-[#101114] truncate">
                            {{ item.query }}
                        </span>
                    </div>

                    <span v-if="item.search_count > 1" class="text-[10px] text-[#8A8B90] shrink-0 ml-3">
                        {{ item.search_count }}×
                    </span>
                </button>
            </div>
        </div>

        <!-- DSS RECOMMENDATIONS -->
        <div v-if="recommendations.length" class="p-3">
            <div class="flex items-center justify-between px-2 mb-2.5">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#8A8B90]">
                        Recommended for You
                    </p>
                    <p class="text-[10px] text-[#B0B0AC] mt-0.5">
                        Based on your shopping activity
                    </p>
                </div>
            </div>

            <div class="space-y-1">
                <Link
                    v-for="product in recommendations"
                    :key="product.id"
                    :href="route('products.show', [product.team.slug, product.slug])"
                    @mousedown.prevent
                    class="flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-[#F7F7F5] transition group"
                >
                    <!-- IMAGE -->
                    <div class="w-11 h-11 rounded-lg bg-[#F0F0EC] overflow-hidden shrink-0 border border-gray-100">
                        <img
                            v-if="recommendationImage(product)"
                            :src="recommendationImage(product)"
                            :alt="product.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-[9px] text-[#8A8B90]">
                            No image
                        </div>
                    </div>

                    <!-- PRODUCT -->
                    <div class="min-w-0 flex-1">
                        <p class="text-xs sm:text-sm font-medium text-[#101114] truncate">
                            {{ product.name }}
                        </p>
                        <p class="text-[10px] text-[#8A8B90] truncate mt-0.5">
                            {{ product.team?.name || 'Zypher Marketplace' }}
                        </p>
                    </div>

                    <!-- PRICE -->
                    <p class="text-xs sm:text-sm font-semibold text-[#101114] whitespace-nowrap">
                        ₱{{ product.min_price }}
                    </p>
                </Link>
            </div>
        </div>

        <!-- EMPTY -->
        <div
            v-if="!loading && !suggestions.length && !history.length && !recommendations.length"
            class="px-5 py-6 text-center"
        >
            <p class="text-xs sm:text-sm text-[#101114] font-medium">
                Search for products
            </p>
            <p class="text-xs text-[#8A8B90] mt-1">
                We'll suggest products based on your interests.
            </p>
        </div>
    </div>
</template>
