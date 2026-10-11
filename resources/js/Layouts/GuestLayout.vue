<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PopupAdModal from '@/Components/PopupAdModal.vue';
import SearchDssDropdown from '@/Components/SearchDssDropdown.vue';
import BackLogoutTrapModal from '@/Components/BackLogoutTrapModal.vue';

const page = usePage();
const user = page.props.auth?.user;
const allTeams = user?.all_teams || [];
const unreadMessageCount = computed(() => page.props.unreadMessageCount || 0);
const cartItemCount = computed(() => page.props.cartItemCount || 0);

const searchQuery = ref('');
const menuOpen = ref(false); // Desktop avatar dropdown
const mobileMenuOpen = ref(false); // Mobile hamburger drawer
const mobileSearchOpen = ref(false); // Mobile search bar toggle
const avatarError = ref(false);
const mobileAvatarError = ref(false);

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
    const finalQuery = (query ?? searchQuery.value).trim();
    if (!finalQuery) return;

    searchOpen.value = false;
    mobileSearchOpen.value = false;
    mobileMenuOpen.value = false;

    router.get(route('search'), { q: finalQuery });
};

const selectSearchSuggestion = (suggestion) => {
    searchQuery.value = suggestion;
    submitSearch(suggestion);
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

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
    if (mobileMenuOpen.value) {
        mobileSearchOpen.value = false;
        searchOpen.value = false;
    }
};

const toggleMobileSearch = () => {
    mobileSearchOpen.value = !mobileSearchOpen.value;
    if (mobileSearchOpen.value) {
        mobileMenuOpen.value = false;
        openSearchDss();
    } else {
        searchOpen.value = false;
    }
};

const closeAllMobile = () => {
    mobileMenuOpen.value = false;
    mobileSearchOpen.value = false;
    searchOpen.value = false;
    menuOpen.value = false;
};

// Auto-close open menus on Inertia page navigation
router.on('navigate', () => {
    closeAllMobile();
});

// A "shop" is any owned team that isn't the auto-created personal team.
const ownedShop = computed(() => allTeams.find(t => !t.personal_team) || null);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-[#F7F7F5] flex flex-col selection:bg-[#6D5EF8] selection:text-white">
        <!-- GLOBAL STICKY HEADER -->
        <header class="sticky top-0 z-40 bg-black border-b border-white/10 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6">
                <!-- Top Nav Bar -->
                <div class="h-16 flex items-center justify-between gap-3 sm:gap-6">
                    
                    <!-- BRAND LOGO -->
                    <Link :href="route('home')" class="flex items-center gap-2 shrink-0 text-white select-none group">
                        <svg viewBox="0 0 200 130" class="h-7 w-auto fill-white group-hover:scale-105 transition-transform">
                            <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                        </svg>
                        <span class="font-display text-lg font-bold tracking-tight">Zypher</span>
                    </Link>

                    <!-- DESKTOP SEARCH FORM (visible on md+) -->
                    <form
                        @submit.prevent="submitSearch()"
                        @focusin="openSearchDss"
                        @focusout="closeSearchDss"
                        class="hidden md:block flex-1 max-w-xl lg:max-w-2xl relative"
                    >
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                @input="handleSearchInput"
                                type="text"
                                placeholder="Search products, shops..."
                                autocomplete="off"
                                class="w-full h-11 pl-4 pr-11 rounded-xl bg-white/10 hover:bg-white/15 focus:bg-white text-white focus:text-[#101114] placeholder-white/50 focus:placeholder-gray-400 border border-white/15 focus:border-[#6D5EF8] focus:ring-2 focus:ring-[#6D5EF8]/20 outline-none text-sm transition-all"
                            />
                            <button
                                type="submit"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-white/60 hover:text-white transition-colors"
                                aria-label="Search"
                            >
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="7" />
                                    <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
                                </svg>
                            </button>

                            <!-- DESKTOP DSS DROPDOWN -->
                            <div
                                v-if="searchOpen && !mobileSearchOpen"
                                class="absolute left-0 right-0 top-[calc(100%+8px)] z-50 max-h-[70vh] overflow-y-auto"
                            >
                                <SearchDssDropdown
                                    :loading="searchLoading"
                                    :suggestions="searchSuggestions"
                                    :history="searchHistory"
                                    :recommendations="searchRecommendations"
                                    @select-suggestion="selectSearchSuggestion"
                                    @select-history="selectSearchHistory"
                                />
                            </div>
                        </div>
                    </form>

                    <!-- DESKTOP NAV ACTIONS (visible on md+) -->
                    <nav class="hidden md:flex items-center gap-3 shrink-0">
                        <template v-if="user">
                            <Link
                                v-if="ownedShop"
                                :href="route('seller.dashboard')"
                                class="text-sm font-medium px-3.5 py-2 rounded-xl border border-white/20 text-white hover:border-[#6D5EF8] hover:text-[#6D5EF8] transition-colors"
                            >
                                Seller Center
                            </Link>
                            <Link
                                v-else
                                :href="route('seller-application.create')"
                                class="text-sm font-semibold px-3.5 py-2 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white hover:opacity-90 transition-opacity shadow-sm"
                            >
                                Become a Seller
                            </Link>

                            <!-- Messages Icon -->
                            <Link
                                :href="route('messages.index')"
                                class="relative p-2.5 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition-all"
                                title="Messages"
                            >
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span
                                    v-if="unreadMessageCount"
                                    class="absolute 1 top-1 right-1 inline-flex items-center justify-center h-4.5 min-w-[1.125rem] rounded-full bg-[#6D5EF8] text-[10px] font-bold text-white px-1 shadow-sm"
                                >
                                    {{ unreadMessageCount }}
                                </span>
                            </Link>

                            <!-- Cart Icon -->
                            <Link
                                :href="route('cart.index')"
                                class="relative p-2.5 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition-all"
                                title="Cart"
                            >
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" />
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span
                                    v-if="cartItemCount"
                                    class="absolute top-1 right-1 inline-flex items-center justify-center h-4.5 min-w-[1.125rem] rounded-full bg-[#6D5EF8] text-[10px] font-bold text-white px-1 shadow-sm"
                                >
                                    {{ cartItemCount }}
                                </span>
                            </Link>

                            <Link
                                :href="route('account.orders.index')"
                                class="text-sm font-medium text-white/90 hover:text-white hover:underline transition-colors px-1"
                            >
                                My Orders
                            </Link>

                            <!-- Desktop Account Dropdown -->
                            <div class="relative ml-1">
                                <button
                                    type="button"
                                    @click="menuOpen = !menuOpen"
                                    class="w-9 h-9 rounded-full bg-[#6D5EF8] text-white flex items-center justify-center text-xs font-bold hover:ring-2 hover:ring-white/30 transition-all cursor-pointer overflow-hidden"
                                    aria-label="User menu"
                                >
                                    <img
                                        v-if="user.profile_photo_url && !avatarError"
                                        :src="user.profile_photo_url"
                                        :alt="user.name"
                                        class="w-full h-full object-cover"
                                        @error="avatarError = true"
                                    />
                                    <span v-else>{{ user.name?.charAt(0) }}</span>
                                </button>

                                <div v-if="menuOpen" class="fixed inset-0 z-10" @click="menuOpen = false"></div>

                                <transition
                                    enter-active-class="transition duration-150 ease-out"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition duration-100 ease-in"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <div
                                        v-if="menuOpen"
                                        class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-xl border border-[#EAEAE5] py-1.5 z-20 overflow-hidden"
                                    >
                                        <div class="px-4 py-2.5 border-b border-[#EAEAE5]">
                                            <p class="text-xs font-bold text-[#101114] truncate">{{ user.name }}</p>
                                            <p class="text-[11px] text-[#8A8B90] truncate">{{ user.email }}</p>
                                        </div>
                                        <Link :href="route('account.settings')" class="block px-4 py-2 text-xs font-medium text-[#5F5E5A] hover:bg-[#F7F7F5] hover:text-[#101114]">
                                            📍 My Address
                                        </Link>
                                        <Link :href="route('profile.show')" class="block px-4 py-2 text-xs font-medium text-[#5F5E5A] hover:bg-[#F7F7F5] hover:text-[#101114]">
                                            👤 Manage Profile
                                        </Link>
                                        <Link v-if="ownedShop" :href="route('seller.dashboard')" class="block px-4 py-2 text-xs font-medium text-[#5F5E5A] hover:bg-[#F7F7F5] hover:text-[#101114]">
                                            🏪 Seller Center
                                        </Link>
                                        <Link :href="route('support.index')" class="block px-4 py-2 text-xs font-medium text-[#5F5E5A] hover:bg-[#F7F7F5] hover:text-[#101114]">
                                            🎧 Help / Support Chat
                                        </Link>
                                        <div class="border-t border-[#EAEAE5] mt-1 pt-1">
                                            <button @click="logout" class="w-full text-left px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                                Log Out
                                            </button>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                        </template>

                        <!-- Guest Mode (Not logged in) -->
                        <template v-else>
                            <Link
                                :href="route('login', { redirect: route('seller-application.create') })"
                                class="text-sm font-medium px-3.5 py-2 rounded-xl border border-white/20 text-white hover:border-[#6D5EF8] hover:text-[#6D5EF8] transition-colors"
                            >
                                Become a Seller
                            </Link>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-white hover:text-[#6D5EF8] px-2 transition-colors"
                            >
                                Login
                            </Link>
                            <Link
                                :href="route('register')"
                                class="text-sm font-semibold bg-white text-[#101114] px-4 py-2 rounded-xl hover:bg-gray-100 transition-colors shadow-sm"
                            >
                                Sign Up
                            </Link>
                        </template>
                    </nav>

                    <!-- MOBILE COMPACT HEADER CONTROLS (visible on mobile < 768px) -->
                    <div class="flex md:hidden items-center gap-1 sm:gap-2 shrink-0">
                        <!-- Mobile Search Toggle -->
                        <button
                            type="button"
                            @click="toggleMobileSearch"
                            class="p-2 text-white hover:text-[#6D5EF8] rounded-xl transition-colors cursor-pointer"
                            :class="mobileSearchOpen ? 'text-[#6D5EF8] bg-white/10' : ''"
                            aria-label="Toggle Search"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7" />
                                <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
                            </svg>
                        </button>

                        <!-- Mobile Messages (if authenticated) -->
                        <Link
                            v-if="user"
                            :href="route('messages.index')"
                            class="relative p-2 text-white hover:text-[#6D5EF8] rounded-xl transition-colors"
                            aria-label="Messages"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span
                                v-if="unreadMessageCount"
                                class="absolute top-1 right-1 inline-flex items-center justify-center h-4 min-w-[1rem] rounded-full bg-[#6D5EF8] text-[9px] font-bold text-white px-1"
                            >
                                {{ unreadMessageCount }}
                            </span>
                        </Link>

                        <!-- Mobile Cart -->
                        <Link
                            :href="route('cart.index')"
                            class="relative p-2 text-white hover:text-[#6D5EF8] rounded-xl transition-colors"
                            aria-label="Cart"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" />
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span
                                v-if="cartItemCount"
                                class="absolute top-1 right-1 inline-flex items-center justify-center h-4 min-w-[1rem] rounded-full bg-[#6D5EF8] text-[9px] font-bold text-white px-1"
                            >
                                {{ cartItemCount }}
                            </span>
                        </Link>

                        <!-- Mobile Hamburger Button -->
                        <button
                            type="button"
                            @click="toggleMobileMenu"
                            class="p-2 text-white hover:text-[#6D5EF8] rounded-xl transition-colors cursor-pointer"
                            :class="mobileMenuOpen ? 'text-[#6D5EF8] bg-white/10' : ''"
                            aria-label="Toggle Menu"
                        >
                            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- MOBILE EXPANDABLE SEARCH BAR -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div v-if="mobileSearchOpen" class="md:hidden pb-3 pt-1">
                        <form @submit.prevent="submitSearch()" class="relative">
                            <input
                                v-model="searchQuery"
                                @input="handleSearchInput"
                                type="text"
                                placeholder="Search products, shops..."
                                autocomplete="off"
                                autofocus
                                class="w-full h-11 pl-4 pr-11 rounded-xl bg-white text-[#101114] placeholder-gray-400 border border-gray-200 focus:border-[#6D5EF8] focus:ring-2 focus:ring-[#6D5EF8]/20 outline-none text-sm shadow-lg"
                            />
                            <button
                                type="submit"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-black p-1"
                                aria-label="Submit search"
                            >
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="7" />
                                    <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
                                </svg>
                            </button>
                        </form>

                        <!-- MOBILE DSS DROPDOWN -->
                        <div
                            v-if="searchOpen && mobileSearchOpen"
                            class="mt-2 max-h-[60vh] overflow-y-auto"
                        >
                            <SearchDssDropdown
                                :loading="searchLoading"
                                :suggestions="searchSuggestions"
                                :history="searchHistory"
                                :recommendations="searchRecommendations"
                                @select-suggestion="selectSearchSuggestion"
                                @select-history="selectSearchHistory"
                            />
                        </div>
                    </div>
                </transition>
            </div>

            <!-- MOBILE SLIDE-DOWN DRAWER MENU -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-3"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-3"
            >
                <div
                    v-if="mobileMenuOpen"
                    class="md:hidden bg-[#0F1115] border-t border-white/10 px-4 py-5 space-y-4 max-h-[calc(100vh-4.5rem)] overflow-y-auto shadow-2xl"
                >
                    <!-- Authenticated User Banner -->
                    <div v-if="user" class="p-3.5 rounded-2xl bg-white/5 border border-white/10 flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full bg-[#6D5EF8] text-white flex items-center justify-center font-bold text-sm shrink-0 overflow-hidden">
                            <img
                                v-if="user.profile_photo_url && !mobileAvatarError"
                                :src="user.profile_photo_url"
                                :alt="user.name"
                                class="w-full h-full object-cover"
                                @error="mobileAvatarError = true"
                            />
                            <span v-else>{{ user.name?.charAt(0) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-white truncate">{{ user.name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ user.email }}</p>
                        </div>
                    </div>

                    <!-- Seller Action -->
                    <div>
                        <Link
                            v-if="ownedShop"
                            :href="route('seller.dashboard')"
                            @click="closeAllMobile"
                            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl border border-white/20 text-white font-medium text-sm hover:border-[#6D5EF8] transition"
                        >
                            <span>🏪 Seller Center</span>
                        </Link>
                        <Link
                            v-else-if="user"
                            :href="route('seller-application.create')"
                            @click="closeAllMobile"
                            class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white font-semibold text-sm shadow-md"
                        >
                            <span>🚀 Become a Seller</span>
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="space-y-1 pt-1">
                        <template v-if="user">
                            <Link
                                :href="route('account.orders.index')"
                                @click="closeAllMobile"
                                class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium text-gray-200 hover:bg-white/10 hover:text-white transition"
                            >
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span>My Orders</span>
                            </Link>

                            <Link
                                :href="route('cart.index')"
                                @click="closeAllMobile"
                                class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium text-gray-200 hover:bg-white/10 hover:text-white transition"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                                    </svg>
                                    <span>Shopping Cart</span>
                                </div>
                                <span v-if="cartItemCount" class="text-xs bg-[#6D5EF8] text-white font-bold px-2 py-0.5 rounded-full">
                                    {{ cartItemCount }}
                                </span>
                            </Link>

                            <Link
                                :href="route('messages.index')"
                                @click="closeAllMobile"
                                class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium text-gray-200 hover:bg-white/10 hover:text-white transition"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span>Messages</span>
                                </div>
                                <span v-if="unreadMessageCount" class="text-xs bg-[#6D5EF8] text-white font-bold px-2 py-0.5 rounded-full">
                                    {{ unreadMessageCount }}
                                </span>
                            </Link>

                            <Link
                                :href="route('account.settings')"
                                @click="closeAllMobile"
                                class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium text-gray-200 hover:bg-white/10 hover:text-white transition"
                            >
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <span>My Address</span>
                            </Link>

                            <Link
                                :href="route('profile.show')"
                                @click="closeAllMobile"
                                class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium text-gray-200 hover:bg-white/10 hover:text-white transition"
                            >
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Manage Profile</span>
                            </Link>

                            <Link
                                :href="route('support.index')"
                                @click="closeAllMobile"
                                class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium text-gray-200 hover:bg-white/10 hover:text-white transition"
                            >
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span>Support / Help Center</span>
                            </Link>
                        </template>

                        <!-- Guest Mode Links -->
                        <template v-else>
                            <div class="grid grid-cols-2 gap-2.5 pb-2">
                                <Link
                                    :href="route('login')"
                                    @click="closeAllMobile"
                                    class="flex items-center justify-center py-3 rounded-xl border border-white/20 text-white font-semibold text-sm hover:border-[#6D5EF8] transition"
                                >
                                    Log In
                                </Link>
                                <Link
                                    :href="route('register')"
                                    @click="closeAllMobile"
                                    class="flex items-center justify-center py-3 rounded-xl bg-white text-[#101114] font-semibold text-sm hover:bg-gray-100 transition shadow-sm"
                                >
                                    Sign Up
                                </Link>
                            </div>

                            <Link
                                :href="route('login', { redirect: route('seller-application.create') })"
                                @click="closeAllMobile"
                                class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white font-semibold text-sm shadow-md"
                            >
                                <span>🚀 Become a Seller</span>
                            </Link>
                        </template>

                        <Link
                            :href="route('about')"
                            @click="closeAllMobile"
                            class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium text-gray-400 hover:text-white transition"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>About Zypher</span>
                        </Link>
                    </div>

                    <!-- Logout Button for Authenticated Users -->
                    <div v-if="user" class="pt-2 border-t border-white/10">
                        <button
                            type="button"
                            @click="logout"
                            class="w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-semibold text-rose-400 hover:bg-rose-500/10 transition cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Log Out</span>
                        </button>
                    </div>
                </div>
            </transition>
        </header>

        <!-- Backdrop overlay when mobile drawer is open -->
        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 top-16 bg-black/60 backdrop-blur-xs z-30 md:hidden"
            @click="closeAllMobile"
        ></div>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 w-full overflow-x-hidden">
            <slot />
        </main>

        <!-- FOOTER -->
        <footer class="mt-auto border-t border-[#EAEAE5] py-8 bg-white/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#8A8B90]">
                <p>&copy; {{ new Date().getFullYear() }} Zypher Marketplace. All rights reserved.</p>

                <nav class="flex items-center gap-5">
                    <Link :href="route('about')" class="hover:text-[#101114] transition-colors">About</Link>
                    <Link :href="route('support.index')" class="hover:text-[#101114] transition-colors">Help & Support</Link>
                </nav>
            </div>
        </footer>

        <!-- POPUP AD MODAL -->
        <PopupAdModal />

        <!-- GLOBAL BACK BUTTON LOGOUT TRAP MODAL -->
        <BackLogoutTrapModal />
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600&display=swap');
.font-display { font-family: 'Space Grotesk', sans-serif; }
</style>
