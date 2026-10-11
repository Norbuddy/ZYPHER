<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import Banner from '@/Components/Banner.vue';

const page = usePage();
const supplierName = page.props.auth?.user?.current_team?.name || page.props.auth?.user?.name || 'Supplier Brand';
const notifications = page.props.supplierNotifications || { unread_messages: 0, pending_orders: 0 };

const navItems = [
    { label: 'Dashboard', route: 'supplier.dashboard' },
    { label: 'Orders', route: 'supplier.orders.index', badge: notifications.pending_orders, badgeClass: 'bg-rose-500 text-white' },
    { label: 'Product Management', route: 'supplier.products.index' },
    { label: 'Shipment', route: 'supplier.shipment.index' },
    { label: 'Inventory', route: 'supplier.inventory.index' },
    { label: 'Messages', route: 'supplier.messages.index', badge: notifications.unread_messages, badgeClass: 'bg-amber-300 text-black' },
    { label: 'Teams', route: 'supplier.teams.index' },
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
    <div class="min-h-screen bg-[#F7F7F5] flex flex-col md:flex-row">
        <aside class="w-full md:w-64 shrink-0 bg-[#101114] text-white min-h-screen md:min-h-screen p-6 flex flex-col">
            <Link :href="route('supplier.dashboard')" class="flex items-center gap-2 mb-10">
                <svg viewBox="0 0 200 130" class="h-6 w-auto fill-white">
                    <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                </svg>
                <span class="font-semibold tracking-tight">Zypher</span>
            </Link>

            <p class="text-xs uppercase tracking-wider text-white/40 mb-3">Supplier Center</p>
            <p class="text-sm font-semibold mb-8 truncate">{{ supplierName }}</p>

            <nav class="space-y-1 flex-1">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="block px-3 py-3 rounded-md text-sm transition-colors"
                    :class="isActive(item.route) ? 'bg-white/10 text-white pl-4' : 'text-white/60 hover:text-white hover:bg-white/5'"
                >
                    <div class="flex items-center justify-between gap-3">
                        <span>{{ item.label }}</span>
                        <span
                            v-if="item.badge > 0"
                            :class="`inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ${item.badgeClass}`"
                        >
                            {{ item.badge }}
                        </span>
                    </div>
                </Link>
            </nav>

            <div class="pt-4 border-t border-white/10 space-y-2">
                <div class="px-3 py-2 rounded-lg bg-white/5 text-xs uppercase tracking-wide text-white/50">
                    Logged in
                </div>
                <div class="block px-3 py-2 rounded-lg text-sm text-white/80 bg-white/5">
                    {{ supplierName }}
                </div>
                <button @click="logout" class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-400 hover:bg-white/5">
                    Log Out
                </button>
            </div>
        </aside>

        <main class="flex-1 p-6 md:p-8">
            <Banner />
            <slot />
        </main>
    </div>
</template>
