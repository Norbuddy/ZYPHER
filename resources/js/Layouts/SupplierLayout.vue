<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import BackLogoutTrapModal from '@/Components/BackLogoutTrapModal.vue';

const page = usePage();
const supplierName = page.props.auth?.user?.current_team?.name || page.props.auth?.user?.name || 'Supplier Brand';
const notifications = page.props.supplierNotifications || { unread_messages: 0, pending_orders: 0 };
const sidebarOpen = ref(false);

const navItems = [
    {
        label: 'Dashboard',
        route: 'supplier.dashboard',
        icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'
    },
    {
        label: 'Orders',
        route: 'supplier.orders.index',
        badge: notifications.pending_orders,
        badgeClass: 'bg-rose-500 text-white',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'
    },
    {
        label: 'Product Management',
        route: 'supplier.products.index',
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
    },
    {
        label: 'Shipment',
        route: 'supplier.shipment.index',
        icon: 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'
    },
    {
        label: 'Inventory',
        route: 'supplier.inventory.index',
        icon: 'M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16m-8 4v6m-4-3h8'
    },
    {
        label: 'Messages',
        route: 'supplier.messages.index',
        badge: notifications.unread_messages,
        badgeClass: 'bg-amber-300 text-black',
        icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'
    },
    {
        label: 'Teams',
        route: 'supplier.teams.index',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
    },
];

let supplierNotificationTimer = null;

onMounted(() => {
    supplierNotificationTimer = setInterval(() => {
        router.reload({
            preserveScroll: true,
            preserveState: true,
            only: ['supplierNotifications'],
        });
    }, 8000);
});

onUnmounted(() => {
    clearInterval(supplierNotificationTimer);
});

const isActive = (routeName) => route().current(routeName + '*');

const logout = () => {
    router.post(route('logout'));
};

router.on('navigate', () => {
    sidebarOpen.value = false;
});
</script>

<template>
    <div class="min-h-screen bg-[#F8F9FA] flex flex-col lg:flex-row">
        <!-- DESKTOP FIXED SIDEBAR -->
        <aside class="hidden lg:flex w-64 shrink-0 bg-[#0B0D11] text-white min-h-screen p-5 flex-col justify-between border-r border-[#1C2028] sticky top-0 h-screen">
            <div>
                <!-- Brand / Logo -->
                <div class="flex items-center justify-between mb-8 pb-4 border-b border-[#1A1F29]">
                    <Link :href="route('supplier.dashboard')" class="flex items-center gap-2.5">
                        <svg viewBox="0 0 200 130" class="h-6 w-auto fill-white">
                            <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                        </svg>
                        <div>
                            <span class="font-bold text-base tracking-tight text-white block leading-none">Zypher</span>
                            <span class="text-[10px] text-gray-400 font-medium">Supplier Portal</span>
                        </div>
                    </Link>
                </div>

                <!-- Navigation List -->
                <nav class="space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all group"
                        :class="isActive(item.route)
                            ? 'bg-[#1D212B] text-white shadow-xs'
                            : 'text-gray-400 hover:text-white hover:bg-[#141820]'"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            <span>{{ item.label }}</span>
                        </div>
                        <span v-if="item.badge" :class="item.badgeClass" class="text-[10px] font-bold px-2 py-0.5 rounded-full">
                            {{ item.badge }}
                        </span>
                    </Link>
                </nav>
            </div>

            <!-- Footer User Info -->
            <div class="pt-4 border-t border-[#1C2028]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-[#1D212B] text-white flex items-center justify-center text-xs font-bold shrink-0">
                            {{ supplierName.charAt(0) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-white truncate">{{ supplierName }}</p>
                            <p class="text-[10px] text-gray-400 truncate">Supplier</p>
                        </div>
                    </div>
                    <button @click="logout" class="text-gray-400 hover:text-rose-400 p-1.5 transition cursor-pointer" title="Sign out">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- MOBILE SLIDE-IN DRAWER -->
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
                <div class="flex items-center justify-between mb-6">
                    <Link :href="route('supplier.dashboard')" @click="sidebarOpen = false" class="flex items-center gap-2.5">
                        <svg viewBox="0 0 200 130" class="h-6 w-auto fill-white">
                            <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                        </svg>
                        <span class="font-bold text-base text-white">Supplier Portal</span>
                    </Link>
                    <button type="button" @click="sidebarOpen = false" class="p-2 text-white/60">✕</button>
                </div>

                <nav class="space-y-1 flex-1 overflow-y-auto">
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        @click="sidebarOpen = false"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium"
                        :class="isActive(item.route) ? 'bg-[#1D212B] text-white' : 'text-gray-400 hover:bg-[#141820]'"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            <span>{{ item.label }}</span>
                        </div>
                    </Link>
                </nav>

                <div class="pt-4 border-t border-[#1C2028]">
                    <button @click="logout" class="w-full flex items-center justify-center gap-2 text-xs text-rose-400 py-2">
                        Sign Out
                    </button>
                </div>
            </aside>
        </transition>

        <!-- MAIN AREA -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Mobile Top Bar -->
            <header class="h-16 bg-white border-b border-[#EAEAE5] px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 -ml-2 text-[#101114] hover:bg-[#F7F7F5] rounded-xl"
                        aria-label="Open Sidebar"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span class="text-xs font-semibold text-[#101114]">Supplier Portal</span>
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
