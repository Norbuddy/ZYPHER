<script setup>
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth?.user;
const team = user?.current_team;
const isBranchUser = ['branch_user', 'branch_manager', 'cashier'].includes(user?.role);
const isCashier = user?.role === 'cashier';
const isBranchManager = ['branch_user', 'branch_manager'].includes(user?.role)
    && (user?.role === 'branch_manager' || user?.branch_role === 'manager');

const dashboardOpen = ref(false);

const fullNavItems = [
    { label: 'POS', route: 'seller.pos.index', icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
    { label: 'Products', route: 'seller.products.index', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { label: 'Messages', route: 'seller.messages.index', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
    { label: 'Vouchers', route: 'seller.vouchers.index', icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z' },
    { label: 'Reports', route: 'seller.reports.index', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { label: 'Inventory', route: 'seller.inventory.index', icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' },
    // { label: 'Supplier', route: 'seller.supplier.index', icon: 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' },
];

const navItems = isCashier
    ? fullNavItems.filter((item) => item.route === 'seller.pos.index')
    : isBranchUser
        ? fullNavItems.filter((item) => ['seller.pos.index', 'seller.inventory.index'].includes(item.route))
    : fullNavItems;

const dashboardRoutes = isBranchUser
    ? [{ label: 'Overview', route: 'seller.dashboard' }]
    : [
        { label: 'Overview', route: 'seller.dashboard' },
        ...(page.props.branches && page.props.branches.length > 0 ? [{
            label: 'Branches',
            route: 'seller.branches.index',
        }] : []),
        {
            label: 'All Orders',
            route: 'seller.orders.index',
        },
    ];

const isActive = (routeName) => {
    return route().current(routeName + '*');
};

const isDashboardActive = () => {
    return route().current('seller.dashboard*') || route().current('seller.branches*') || route().current('seller.orders*');
};

const goToDashboard = (item) => {
    dashboardOpen.value = true;

    if (item.params) {
        router.get(
            route(item.route),
            item.params,
            {
                preserveScroll: true,
                preserveState: true,
            }
        );
    } else {
        router.get(
            route(item.route),
            {},
            {
                preserveScroll: true,
                preserveState: true,
            }
        );
    }
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-[#F7F7F5] flex">
        <!-- SLEEK DARK SIDEBAR -->
        <aside class="w-64 shrink-0 bg-[#0B0D11] text-white min-h-screen p-5 flex flex-col border-r border-white/5">
            <!-- LOGO -->
            <Link
                :href="route(isCashier ? 'seller.pos.index' : 'seller.dashboard')"
                class="flex items-center gap-2.5 mb-2 px-2 py-1"
            >
                <svg viewBox="0 0 200 130" class="h-6 w-auto fill-white shrink-0">
                    <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                </svg>
                <span class="font-bold tracking-tight text-white text-base">Zypher</span>
            </Link>

            <!-- PORTAL BADGE -->
            <div class="px-2 mb-4">
                <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40 bg-white/5 px-2 py-0.5 rounded-full border border-white/10">
                    {{ isBranchUser ? 'Branch Portal' : 'Seller Portal' }}
                </span>
            </div>

            <!-- SHOP NAME CHIP -->
            <div class="px-3 py-2 rounded-xl bg-white/5 border border-white/5 mb-6">
                <p class="text-[10px] uppercase tracking-wider text-white/40 font-medium">Store</p>
                <p class="text-xs font-semibold text-white truncate mt-0.5">
                    {{ team?.name || 'Main Shop' }}
                </p>
            </div>

            <!-- NAVIGATION -->
            <nav class="space-y-1 flex-1 overflow-y-auto pr-1 scrollbar-none">
                <!-- DASHBOARD DROPDOWN -->
                <div v-if="!isCashier">
                    <button
                        type="button"
                        @click="dashboardOpen = !dashboardOpen"
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-200"
                        :class="isDashboardActive()
                            ? 'bg-white text-[#0B0D11] font-semibold shadow-xs'
                            : 'text-white/65 hover:text-white hover:bg-white/5'"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span>Dashboard</span>
                        </div>

                        <svg
                            class="w-3.5 h-3.5 transition-transform duration-200 opacity-60"
                            :class="dashboardOpen ? 'rotate-180' : ''"
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

                    <div
                        v-if="dashboardOpen"
                        class="mt-1 ml-4 pl-3 border-l border-white/10 space-y-1"
                    >
                        <button
                            v-for="item in dashboardRoutes"
                            :key="item.label"
                            type="button"
                            @click="goToDashboard(item)"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors"
                            :class="!item.params && !page.url.includes('?source=')
                                ? 'text-white bg-white/15 font-medium'
                                : 'text-white/50 hover:text-white hover:bg-white/5'"
                        >
                            {{ item.label }}
                        </button>
                    </div>
                </div>

                <!-- STAFF FOR BRANCH MANAGER -->
                <Link
                    v-if="isBranchManager"
                    :href="route('branch.staff.index')"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-200"
                    :class="isActive('branch.staff')
                        ? 'bg-white text-[#0B0D11] font-semibold shadow-xs'
                        : 'text-white/65 hover:text-white hover:bg-white/5'"
                >
                    <svg class="w-4 h-4 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Staff</span>
                </Link>

                <!-- OTHER NAV ITEMS -->
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
            </nav>

            <!-- FOOTER PROFILE & ACTIONS -->
            <div class="pt-4 mt-auto border-t border-white/10 space-y-2">
                <Link
                    v-if="isBranchUser && !isCashier"
                    :href="route('branch.profile')"
                    class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-white/60 hover:text-white hover:bg-white/5 transition"
                >
                    <span>Branch Profile</span>
                </Link>

                <Link
                    v-if="!isBranchUser"
                    :href="route('home')"
                    class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-white/60 hover:text-white hover:bg-white/5 transition"
                >
                    <span>&larr; Back to Storefront</span>
                </Link>

                <div class="flex items-center gap-2.5 p-2 rounded-xl bg-white/5 border border-white/5">
                    <div class="w-8 h-8 rounded-full bg-white text-[#0B0D11] flex items-center justify-center text-xs font-bold shrink-0">
                        {{ user?.name?.charAt(0) || 'S' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-white truncate">{{ user?.name }}</p>
                        <p class="text-[10px] text-white/40 truncate">{{ isCashier ? 'Cashier' : (isBranchUser ? 'Branch Staff' : 'Merchant Owner') }}</p>
                    </div>
                </div>

                <button
                    @click="logout"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Log Out</span>
                </button>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- TOP BAR -->
            <header class="h-16 bg-white border-b border-[#EAEAE5] px-8 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium text-[#8A8B90]">{{ team?.name || 'Seller Center' }}</span>
                    <span class="text-xs text-[#EAEAE5]">/</span>
                    <span class="text-xs font-semibold text-[#101114]">Management Portal</span>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 text-xs font-medium text-[#101114] bg-[#F7F7F5] px-3 py-1.5 rounded-full border border-[#EAEAE5]">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Shop Active</span>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-8 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>