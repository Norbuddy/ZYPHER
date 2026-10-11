<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import Banner from '@/Components/Banner.vue';

const page = usePage();
const supplierName = page.props.auth?.user?.current_team?.name || page.props.auth?.user?.name || 'Supplier Brand';
const notifications = page.props.supplierNotifications || { unread_messages: 0, pending_orders: 0 };

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
</script>

<template>
    <div class="min-h-screen bg-[#F8F9FA] flex flex-col md:flex-row">
        <!-- Dark Sidebar matching Mockup -->
        <aside class="w-full md:w-64 shrink-0 bg-[#0B0D11] text-white min-h-screen md:min-h-screen p-5 flex flex-col justify-between border-r border-[#1C2028]">
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
                            ? 'bg-white/10 text-white font-semibold shadow-sm'
                            : 'text-gray-400 hover:text-white hover:bg-white/5'"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 transition" :class="isActive(item.route) ? 'text-white' : 'text-gray-400 group-hover:text-white'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon" />
                            </svg>
                            <span>{{ item.label }}</span>
                        </div>
                        <span
                            v-if="item.badge > 0"
                            :class="`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold ${item.badgeClass}`"
                        >
                            {{ item.badge }}
                        </span>
                    </Link>
                </nav>
            </div>

            <!-- Profile & Logout Footer -->
            <div class="pt-4 border-t border-[#1A1F29] space-y-3">
                <div class="flex items-center gap-3 px-2 py-1.5 rounded-xl bg-white/5 border border-white/5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-white uppercase">
                        {{ supplierName?.charAt(0) || 'S' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-white truncate">{{ supplierName }}</p>
                        <p class="text-[10px] text-gray-400">Supplier Account</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="logout"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Log Out</span>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 md:p-8 overflow-y-auto">
            <Banner />
            <slot />
        </main>
    </div>
</template>
