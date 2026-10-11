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
    { label: 'POS', route: 'seller.pos.index' },
    { label: 'Products', route: 'seller.products.index' },
    { label: 'Messages', route: 'seller.messages.index' },
    { label: 'Vouchers', route: 'seller.vouchers.index' },
    { label: 'Reports', route: 'seller.reports.index' },
    { label: 'Inventory', route: 'seller.inventory.index' },
    // { label: 'Supplier', route: 'seller.supplier.index' },
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
    return route().current('seller.dashboard*');
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

        <!-- SIDEBAR -->
        <aside class="w-64 shrink-0 bg-[#101114] text-white min-h-screen p-6 flex flex-col">

            <!-- LOGO -->
            <Link
                :href="route(isCashier ? 'seller.pos.index' : 'seller.dashboard')"
                class="flex items-center gap-2 mb-10"
            >
                <svg
                    viewBox="0 0 200 130"
                    class="h-6 w-auto fill-white"
                >
                    <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                </svg>

                <span class="font-semibold tracking-tight">
                    Zypher
                </span>
            </Link>

            <!-- SELLER CENTER -->
            <p class="text-xs uppercase tracking-wider text-white/40 mb-3">
                Seller Center
            </p>

            <p class="text-sm font-medium mb-8 truncate">
                {{ team?.name }}
            </p>

            <!-- NAVIGATION -->
            <nav class="space-y-1 flex-1">

                <!-- DASHBOARD DROPDOWN -->
                <div v-if="!isCashier">

                    <!-- Dashboard Button -->
                    <button
                        type="button"
                        @click="dashboardOpen = !dashboardOpen"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors"
                        :class="
                            isDashboardActive()
                                ? 'bg-white/10 text-white'
                                : 'text-white/60 hover:text-white hover:bg-white/5'
                        "
                    >
                        <span>
                            Dashboard
                        </span>

                        <svg
                            class="w-4 h-4 transition-transform duration-200"
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

                    <!-- Dashboard Dropdown -->
                    <div
                        v-if="dashboardOpen"
                        class="mt-1 ml-3 pl-3 border-l border-white/10 space-y-1"
                    >

                        <button
                            v-for="item in dashboardRoutes"
                            :key="item.label"
                            type="button"
                            @click="goToDashboard(item)"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors"
                            :class="
                                !item.params && !page.url.includes('?source=')
                                    ? 'text-white bg-white/5'
                                    : 'text-white/50 hover:text-white hover:bg-white/5'
                            "
                        >
                            {{ item.label }}
                        </button>

                    </div>
                </div>

                <Link
                    v-if="isBranchManager"
                    :href="route('branch.staff.index')"
                    class="block px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="isActive('branch.staff') ? 'bg-white/10 text-white' : 'text-white/60 hover:text-white hover:bg-white/5'"
                >
                    Staff
                </Link>

                <!-- OTHER NAV ITEMS -->
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="block px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="
                        isActive(item.route)
                            ? 'bg-white/10 text-white'
                            : 'text-white/60 hover:text-white hover:bg-white/5'
                    "
                >
                    {{ item.label }}
                </Link>

            </nav>

            <!-- BOTTOM -->
            <div class="pt-4 border-t border-white/10 space-y-1">

                <Link
                    v-if="isBranchUser && !isCashier"
                    :href="route('branch.profile')"
                    class="block px-3 py-2 rounded-lg text-sm text-white/60 hover:text-white hover:bg-white/5"
                >
                    Profile
                </Link>

                <Link
                    v-if="!isBranchUser"
                    :href="route('home')"
                    class="block px-3 py-2 rounded-lg text-sm text-white/60 hover:text-white hover:bg-white/5"
                >
                    &larr; Back to Storefront
                </Link>

                <button
                    @click="logout"
                    class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-400 hover:bg-white/5"
                >
                    Log Out
                </button>

            </div>

        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex-1 p-8">
            <slot />
        </main>

    </div>
</template>