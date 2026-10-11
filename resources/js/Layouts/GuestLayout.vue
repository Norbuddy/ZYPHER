<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PopupAdModal from '@/Components/PopupAdModal.vue';

const page = usePage();
const user = page.props.auth?.user;
const currentTeam = user?.current_team;
const allTeams = user?.all_teams || [];
const unreadMessageCount = computed(() => page.props.unreadMessageCount || 0);
const cartItemCount = computed(() => page.props.cartItemCount || 0);
const searchQuery = ref('');
const menuOpen = ref(false);

const searchOpen = ref(false);
const searchLoading = ref(false);

const searchSuggestions = ref([]);
const searchHistory = ref([]);
const searchRecommendations = ref([]);

let searchTimer = null;

const fetchSearchDss = async () => {
    const query = searchQuery.value.trim();

    searchLoading.value = true;

    try {
        const response = await fetch(
            route('search.suggestions') +
            (query ? `?q=${encodeURIComponent(query)}` : ''),
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        );

        if (!response.ok) {
            throw new Error('Unable to load search suggestions.');
        }

        const data = await response.json();

        searchSuggestions.value = data.suggestions || [];
        searchHistory.value = data.history || [];
        searchRecommendations.value = data.recommendations || [];
    } catch (error) {
        console.error('Search DSS error:', error);

        searchSuggestions.value = [];
        searchHistory.value = [];
        searchRecommendations.value = [];
    } finally {
        searchLoading.value = false;
    }
};

const handleSearchInput = () => {
    searchOpen.value = true;

    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        fetchSearchDss();
    }, 250);
};

const openSearchDss = () => {
    searchOpen.value = true;

    fetchSearchDss();
};

const submitSearch = (query = null) => {
    const finalQuery = (
        query ?? searchQuery.value
    ).trim();

    if (!finalQuery) {
        return;
    }

    searchOpen.value = false;

    router.get(
        route('search'),
        {
            q: finalQuery,
        }
    );
};

const selectSearchSuggestion = (suggestion) => {
    searchQuery.value = suggestion;

    submitSearch(suggestion);
};

const recommendationImage = (product) => {
    if (!product?.images?.[0]?.path) {
        return null;
    }

    const path = product.images[0].path;

    return path.startsWith('images/')
        ? `/${path}`
        : `/storage/${path}`;
};

const selectSearchHistory = (query) => {
    searchQuery.value = query;

    submitSearch(query);
};

const closeSearchDss = () => {
    setTimeout(() => {
        searchOpen.value = false;
    }, 150);
};

// A "shop" is any owned team that isn't the auto-created personal team.
// We check across ALL owned teams, not just the current one, so switching
// teams doesn't hide the seller toggle if the user owns a shop elsewhere.
const ownedShop = computed(() => allTeams.find(t => !t.personal_team) || null);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-[#F7F7F5]">
        <header class="sticky top-0 z-40 bg-black border-b border-[#EAEAE5]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center gap-6">
                <Link :href="route('home')" class="flex items-center gap-2 shrink-0 text-white">
                    <svg viewBox="0 0 200 130" class="h-7 w-auto fill-[#FFFF]">
                        <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                    </svg>
                    <span class="font-display text-lg font-semibold tracking-tight">Zypher</span>
                </Link>

                <form
                    @submit.prevent="submitSearch()"
                    @focusin="openSearchDss"
                    @focusout="closeSearchDss"
                    class="flex-1 max-w-2xl"
                >
                    <div class="relative">

                        <!-- SEARCH INPUT -->
                        <input
                            v-model="searchQuery"
                            @input="handleSearchInput"
                            type="text"
                            placeholder="Search products, shops..."
                            autocomplete="off"
                            class="w-full h-11 pl-4 pr-11 rounded-xl border border-[#E4E4E0] focus:border-[#6D5EF8] focus:ring-2 focus:ring-[#6D5EF8]/20 outline-none text-sm"
                        />

                        <!-- SEARCH ICON -->
                        <button
                            type="submit"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8A8B90]"
                        >
                            <svg
                                class="w-5 h-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle cx="11" cy="11" r="7" />
                                <path
                                    d="M21 21l-4.35-4.35"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>


                        <!-- DSS DROPDOWN -->
                        <div
                            v-if="searchOpen"
                            class="absolute left-0 right-0 top-[calc(100%+8px)] bg-white rounded-2xl border border-[#E4E4E0] shadow-xl overflow-hidden z-50"
                        >

                            <!-- LOADING -->
                            <div
                                v-if="searchLoading"
                                class="px-4 py-4 text-xs text-[#8A8B90]"
                            >
                                Finding recommendations...
                            </div>


                            <!-- SEARCH SUGGESTIONS -->
                            <div
                                v-if="searchSuggestions.length"
                                class="p-3 border-b border-[#EAEAE5]"
                            >
                                <div class="flex items-center justify-between px-2 mb-2">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-[#8A8B90]"
                                    >
                                        Suggestions
                                    </p>

                                    <span class="text-[10px] text-[#B0B0AC]">
                                        Based on similarity
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="suggestion in searchSuggestions"
                                        :key="suggestion"
                                        type="button"
                                        @mousedown.prevent="selectSearchSuggestion(suggestion)"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-[#F7F7F5] border border-[#EAEAE5] text-sm text-[#101114] hover:border-[#6D5EF8] hover:text-[#6D5EF8] transition"
                                    >
                                        <svg
                                            class="w-4 h-4 text-[#8A8B90]"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="11" cy="11" r="7" />
                                            <path
                                                d="M21 21l-4.35-4.35"
                                                stroke-linecap="round"
                                            />
                                        </svg>

                                        {{ suggestion }}
                                    </button>
                                </div>
                            </div>

                            <!-- RECENT SEARCHES -->
                            <div
                                v-if="searchHistory.length"
                                class="p-3 border-b border-[#EAEAE5]"
                            >
                                <div class="flex items-center justify-between px-2 mb-2">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-wider text-[#8A8B90]"
                                    >
                                        Recent Searches
                                    </p>

                                    <span class="text-[10px] text-[#B0B0AC]">
                                        Your activity
                                    </span>
                                </div>

                                <div class="space-y-1">
                                    <button
                                        v-for="item in searchHistory"
                                        :key="item.id"
                                        type="button"
                                        @mousedown.prevent="selectSearchHistory(item.query)"
                                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-left hover:bg-[#F7F7F5] transition"
                                    >
                                        <div class="flex items-center gap-3 min-w-0">
                                            <svg
                                                class="w-4 h-4 text-[#8A8B90] shrink-0"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <circle cx="12" cy="12" r="9" />
                                                <path
                                                    d="M12 7v5l3 2"
                                                    stroke-linecap="round"
                                                />
                                            </svg>

                                            <span class="text-sm text-[#101114] truncate">
                                                {{ item.query }}
                                            </span>
                                        </div>

                                        <span
                                            v-if="item.search_count > 1"
                                            class="text-[10px] text-[#8A8B90] shrink-0 ml-3"
                                        >
                                            {{ item.search_count }}×
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- DSS RECOMMENDATIONS -->
                            <div
                                v-if="searchRecommendations.length"
                                class="p-3"
                            >
                                <div class="flex items-center justify-between px-2 mb-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p
                                                class="text-[10px] font-bold uppercase tracking-wider text-[#8A8B90]"
                                            >
                                                Recommended for You
                                            </p>
                                        </div>

                                        <p class="text-[10px] text-[#B0B0AC] mt-0.5">
                                            Based on your shopping activity
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        @mousedown.prevent
                                        class="text-[10px] font-semibold text-[#6D5EF8] hover:underline"
                                    >
                                        See all ˅
                                    </button>
                                </div>

                                <div class="space-y-1">
                                    <Link
                                        v-for="product in searchRecommendations"
                                        :key="product.id"
                                        :href="
                                            route(
                                                'products.show',
                                                [
                                                    product.team.slug,
                                                    product.slug
                                                ]
                                            )
                                        "
                                        @mousedown.prevent
                                        class="flex items-center gap-3 px-2 py-2.5 rounded-xl hover:bg-[#F7F7F5] transition group"
                                    >
                                        <!-- IMAGE -->
                                        <div
                                            class="w-12 h-12 rounded-lg bg-[#F0F0EC] overflow-hidden shrink-0"
                                        >
                                            <img
                                                v-if="recommendationImage(product)"
                                                :src="recommendationImage(product)"
                                                :alt="product.name"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                            />

                                            <div
                                                v-else
                                                class="w-full h-full flex items-center justify-center text-[9px] text-[#8A8B90]"
                                            >
                                                No image
                                            </div>
                                        </div>

                                        <!-- PRODUCT -->
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-sm font-medium text-[#101114] truncate"
                                            >
                                                {{ product.name }}
                                            </p>

                                            <p
                                                class="text-[10px] text-[#8A8B90] truncate mt-0.5"
                                            >
                                                {{ product.team?.name || 'Zypher Marketplace' }}
                                            </p>
                                        </div>

                                        <!-- PRICE -->
                                        <p
                                            class="text-sm font-semibold text-[#101114] whitespace-nowrap"
                                        >
                                            ₱{{ product.min_price }}
                                        </p>
                                    </Link>
                                </div>
                            </div>


                            <!-- EMPTY -->
                            <div
                                v-if="
                                    !searchLoading &&
                                    !searchSuggestions.length &&
                                    !searchHistory.length &&
                                    !searchRecommendations.length
                                "
                                class="px-5 py-6 text-center"
                            >
                                <p class="text-sm text-[#101114]">
                                    Search for products
                                </p>

                                <p class="text-xs text-[#8A8B90] mt-1">
                                    We'll suggest products based on your interests.
                                </p>
                            </div>

                        </div>
                    </div>
                </form>

                <nav class="flex items-center gap-3 shrink-0">
                    <template v-if="user">
                        <Link
                            v-if="ownedShop"
                            :href="route('seller.dashboard')"
                            class="text-sm font-medium px-3 py-1.5 rounded-lg border border-[#E4E4E0] text-[#FFFF] hover:border-[#6D5EF8] hover:text-[#6D5EF8] transition-colors"
                        >
                            Seller Center
                        </Link>
                        <Link
                            v-else
                            :href="route('seller-application.create')"
                            class="text-sm font-medium px-3 py-1.5 rounded-lg bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white hover:opacity-90 transition-opacity"
                        >
                            Become a Seller
                        </Link>

                        <Link :href="route('messages.index')" class="relative p-2 text-[#FFFF] hover:text-[#6D5EF8]">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span v-if="unreadMessageCount" class="absolute -top-1 -end-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-[#6D5EF8] text-[10px] text-white px-1.5">
                                {{ unreadMessageCount }}
                            </span>
                        </Link>

                        <Link :href="route('cart.index')" class="relative p-2 text-[#FFFF] hover:text-[#6D5EF8]">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" />
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                                <span v-if="cartItemCount" class="absolute -top-1 -end-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] rounded-full bg-[#6D5EF8] text-[10px] text-white px-1.5">
                                    {{ cartItemCount }}
                                </span>
                        </Link>
                        <Link :href="route('account.orders.index')" class="text-sm font-medium text-[#FFFF] hover:text-[#6D5EF8]">
                            My Orders
                        </Link>

                        <!-- Account dropdown -->
                        <div class="relative">
                            <button
                                @click="menuOpen = !menuOpen"
                                class="w-8 h-8 rounded-full bg-[#6D5EF8]/10 flex items-center justify-center text-xs font-semibold text-[#6D5EF8]"
                            >
                                {{ user.name?.charAt(0) }}
                            </button>

                            <div v-if="menuOpen" class="fixed inset-0 z-10" @click="menuOpen = false"></div>

                            <div
                                v-if="menuOpen"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-[#EAEAE5] py-1 z-20"
                            >
                                <div class="px-4 py-2 border-b border-[#EAEAE5]">
                                    <p class="text-sm font-medium text-[#101114] truncate">{{ user.name }}</p>
                                    <p class="text-xs text-[#8A8B90] truncate">{{ user.email }}</p>
                                </div>
                                <Link :href="route('account.settings')" class="block px-4 py-2 text-sm text-[#5F5E5A] hover:bg-[#F7F7F5]">
                                    My Address
                                </Link>
                                <Link :href="route('profile.show')" class="block px-4 py-2 text-sm text-[#5F5E5A] hover:bg-[#F7F7F5]">
                                    Manage Profile
                                </Link>
                                <Link v-if="ownedShop" :href="route('seller.dashboard')" class="block px-4 py-2 text-sm text-[#5F5E5A] hover:bg-[#F7F7F5]">
                                    Seller Center
                                </Link>
                                <Link :href="route('support.index')" class="block px-4 py-2 text-sm text-[#5F5E5A] hover:bg-[#F7F7F5]">
                                    Help / Support Chat
                                </Link>
                                <button @click="logout" class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-red-50">
                                    Log Out
                                </button>
                            </div>
                        </div>
                    </template>
                    <template v-else>

    <Link
        :href="route('login', {
            redirect: route('seller-application.create')
        })"
        class="text-sm font-medium px-3 py-2 rounded-lg border border-[#E4E4E0]
               hover:border-[#6D5EF8] hover:text-[#6D5EF8] text-white"
    >
        Become a Seller
    </Link>

    <Link
        :href="route('login')"
        class="text-sm font-medium text-[#FFFF] hover:text-[#6D5EF8]"
    >
        Login
    </Link>

    <Link
        :href="route('register')"
        class="text-sm font-medium bg-[#101114] text-white px-4 py-2 rounded-lg hover:bg-[#6D5EF8]"
    >
        Sign Up
    </Link>

</template>
                </nav>
            </div>
        </header>

        <main>
            <slot />
        </main>

        <footer class="mt-16 border-t border-[#EAEAE5] py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#8A8B90]">
                <p>&copy; {{ new Date().getFullYear() }} Zypher Marketplace.</p>

                <nav class="flex items-center gap-4">
                    <Link :href="route('about')" class="hover:text-[#101114]">About</Link>
                </nav>
            </div>
        </footer>

        <PopupAdModal />
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600&display=swap');
.font-display { font-family: 'Space Grotesk', sans-serif; }
</style>
