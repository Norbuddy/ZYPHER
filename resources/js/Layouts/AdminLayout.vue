<script setup>
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import BackLogoutTrapModal from '@/Components/BackLogoutTrapModal.vue';

const page = usePage();
const user = page.props.auth?.user;
const applicationsOpen = ref(false);
const sidebarOpen = ref(false);

const navItems = [
    { label: 'Dashboard', route: 'admin.dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { label: 'Banners', route: 'admin.banners.index', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { label: 'Pop-up Ads', route: 'admin.popup-ads.index', icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' },
    { label: 'Shops & Sellers', route: 'admin.shops.index', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { label: 'Products', route: 'admin.products.index', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { label: 'Users', route: 'admin.users.index', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { label: 'Vouchers', route: 'admin.vouchers.index', icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z' },
    { label: 'Support', route: 'admin.support.index', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
];

const applicationItems = [
    // { label: 'Supplier Applications', route: 'admin.supplier-application.index' },
    { label: 'Seller Applications', route: 'admin.seller-application.index' },
    { label: 'Branch Applications', route: 'admin.branch-application.index' },
];

const isActive = (routeName) => route().current(routeName + '*');
const avatarError = ref(false);

const logout = () => {
    router.post(route('logout'));
};

router.on('navigate', () => {
    sidebarOpen.value = false;
});
</script>

<template>
    <div class="min-h-screen bg-[#F7F7F5] flex flex-col lg:flex-row">
        <!-- DESKTOP FIXED SIDEBAR -->
        <aside class="hidden lg:flex w-64 shrink-0 bg-[#0B0D11] text-white min-h-screen p-5 flex-col border-r border-white/5 sticky top-0 h-screen">
            <!-- LOGO HEADER -->
            <Link :href="route('admin.dashboard')" class="flex items-center gap-2.5 mb-2 px-2 py-1">
                <svg viewBox="0 0 200 130" class="h-6 w-auto fill-white shrink-0">
                    <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                </svg>
                <span class="font-bold tracking-tight text-white text-base">Zypher</span>
            </Link>

            <div class="px-2 mb-6">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40 bg-white/5 px-2 py-0.5 rounded-full border border-white/10">
                    Admin Portal
                </span>
            </div>

            <!-- NAVIGATION ITEMS -->
            <nav class="space-y-1 flex-1 overflow-y-auto pr-1 scrollbar-none">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-200"
                    :class="isActive(item.route)
                        ? 'bg-white text-[#0B0D11] font-semibold shadow-xs'
                        : 'text-white/65 hover:text-white hover:bg-white/5'"
                >
                    <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    <span>{{ item.label }}</span>
                </Link>

                <!-- APPLICATIONS DROPDOWN -->
                <div class="pt-2">
                    <button
                        type="button"
                        @click="applicationsOpen = !applicationsOpen"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium text-white/65 hover:text-white hover:bg-white/5 transition-all duration-200"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Applications</span>
                        </div>
                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-200 opacity-60"
                            :class="applicationsOpen ? 'rotate-180' : ''"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>

                    <div v-if="applicationsOpen" class="mt-1 ml-4 pl-3 border-l border-white/10 space-y-1">
                        <Link
                            v-for="item in applicationItems"
                            :key="item.route"
                            :href="route(item.route)"
                            class="block px-3 py-2 rounded-lg text-xs transition-colors"
                            :class="isActive(item.route) ? 'bg-white/15 text-white font-medium' : 'text-white/50 hover:text-white hover:bg-white/5'"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- FOOTER USER PROFILE -->
            <div class="pt-4 mt-auto border-t border-white/10 space-y-2">
                <div class="flex items-center gap-2.5 p-2 rounded-xl bg-white/5 border border-white/5">
                    <div class="w-8 h-8 rounded-full bg-white text-[#0B0D11] flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden">
                        <img
                            v-if="user?.profile_photo_url && !avatarError"
                            :src="user.profile_photo_url"
                            :alt="user.name"
                            class="w-full h-full object-cover"
                            @error="avatarError = true"
                        />
                        <span v-else>{{ user?.name?.charAt(0) || 'A' }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-white truncate">{{ user?.name }}</p>
                        <p class="text-[10px] text-white/40 truncate">Administrator</p>
                    </div>
                </div>

                <button
                    @click="logout"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-colors cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Sign Out</span>
                </button>
            </div>
        </aside>

        <!-- MOBILE SLIDE-IN OFF-CANVAS SIDEBAR -->
        <transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs z-40 lg:hidden"
                @click="sidebarOpen = false"
            ></div>
        </transition>

        <transition
            enter-active-class="transition-transform duration-200 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-150 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <aside
                v-if="sidebarOpen"
                class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0B0D11] text-white p-5 flex flex-col shadow-2xl lg:hidden overflow-y-auto"
            >
                <div class="flex items-center justify-between mb-4">
                    <Link :href="route('admin.dashboard')" @click="sidebarOpen = false" class="flex items-center gap-2.5">
                        <svg viewBox="0 0 200 130" class="h-6 w-auto fill-white shrink-0">
                            <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                        </svg>
                        <span class="font-bold tracking-tight text-white text-base">Zypher Admin</span>
                    </Link>

                    <button
                        type="button"
                        @click="sidebarOpen = false"
                        class="p-2 text-white/60 hover:text-white rounded-lg"
                        aria-label="Close"
                    >
                        ✕
                    </button>
                </div>

                <nav class="space-y-1 flex-1 overflow-y-auto">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        @click="sidebarOpen = false"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium"
                        :class="isActive(item.route) ? 'bg-white text-[#0B0D11] font-semibold' : 'text-white/70 hover:bg-white/10'"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        <span>{{ item.label }}</span>
                    </Link>

                    <div class="pt-2">
                        <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Applications</p>
                        <Link
                            v-for="item in applicationItems"
                            :key="item.route"
                            :href="route(item.route)"
                            @click="sidebarOpen = false"
                            class="block px-3 py-2 rounded-lg text-xs"
                            :class="isActive(item.route) ? 'bg-white/15 text-white font-medium' : 'text-white/50 hover:bg-white/10'"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </nav>

                <div class="pt-4 mt-auto border-t border-white/10 space-y-2">
                    <button
                        @click="logout"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-rose-400 hover:bg-rose-500/10"
                    >
                        <span>Sign Out</span>
                    </button>
                </div>
            </aside>
        </transition>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white border-b border-[#EAEAE5] px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-30 shadow-2xs">
                <div class="flex items-center gap-3">
                    <!-- Mobile Hamburger -->
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 -ml-2 text-[#101114] hover:bg-[#F7F7F5] rounded-xl transition cursor-pointer"
                        aria-label="Open Sidebar"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <span class="text-xs font-medium text-[#8A8B90]">Admin Panel</span>
                    <span class="text-xs text-[#EAEAE5]">/</span>
                    <span class="text-xs font-semibold text-[#101114]">Management</span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-xs font-medium text-[#101114] bg-[#F7F7F5] px-3 py-1.5 rounded-full border border-[#EAEAE5]">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="hidden sm:inline">System Live</span>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <Banner />
                <slot />
            </main>
        </div>

        <!-- GLOBAL BACK BUTTON LOGOUT TRAP MODAL -->
        <BackLogoutTrapModal />
    </div>
</template>
