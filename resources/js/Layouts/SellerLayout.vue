<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router, useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import BackLogoutTrapModal from '@/Components/BackLogoutTrapModal.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const team = computed(() => page.props.auth?.user?.current_team);
const isBranchUser = computed(() => ['branch_user', 'branch_manager', 'cashier'].includes(user.value?.role));
const isCashier = computed(() => user.value?.role === 'cashier');
const isBranchManager = computed(() => ['branch_user', 'branch_manager'].includes(user.value?.role)
    && (user.value?.role === 'branch_manager' || user.value?.branch_role === 'manager'));

const dashboardOpen = ref(false);
const sidebarOpen = ref(false);
const avatarError = ref(false);

const showEditShopModal = ref(false);
const logoPreview = ref(null);
const logoInput = ref(null);

const shopForm = useForm({
    name: '',
    description: '',
    logo: null,
});

const openEditShopModal = () => {
    shopForm.clearErrors();
    shopForm.name = team.value?.name || '';
    shopForm.description = team.value?.description || '';
    shopForm.logo = null;
    logoPreview.value = null;
    showEditShopModal.value = true;
};

const closeEditShopModal = () => {
    showEditShopModal.value = false;
};

const handleLogoChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    logoPreview.value = URL.createObjectURL(file);
    shopForm.logo = file;

    // Auto-compress and scale image to max 600x600 to prevent 413 Request Entity Too Large
    try {
        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const MAX_SIZE = 600;
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > MAX_SIZE) {
                        height = Math.round((height * MAX_SIZE) / width);
                        width = MAX_SIZE;
                    }
                } else {
                    if (height > MAX_SIZE) {
                        width = Math.round((width * MAX_SIZE) / height);
                        height = MAX_SIZE;
                    }
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (blob) {
                        const cleanName = (file.name || 'store_logo').replace(/\.[^/.]+$/, '') + '.jpg';
                        shopForm.logo = new File([blob], cleanName, {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });
                    }
                }, 'image/jpeg', 0.85);
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    } catch (err) {
        shopForm.logo = file;
    }
};

const triggerLogoInput = () => {
    logoInput.value?.click();
};

const saveShopProfile = () => {
    shopForm.post(route('seller.shop.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showEditShopModal.value = false;
            logoPreview.value = null;
        },
    });
};

const fullNavItems = [
    { label: 'POS', route: 'seller.pos.index', icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
    { label: 'Products', route: 'seller.products.index', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { label: 'Messages', route: 'seller.messages.index', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
    { label: 'Vouchers', route: 'seller.vouchers.index', icon: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z' },
    { label: 'Reports', route: 'seller.reports.index', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { label: 'Inventory', route: 'seller.inventory.index', icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' },
    // { label: 'Supplier', route: 'seller.supplier.index', icon: 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0' },
];

const navItems = computed(() => {
    if (isCashier.value) {
        return fullNavItems.filter((item) => item.route === 'seller.pos.index');
    }
    if (isBranchUser.value) {
        return fullNavItems.filter((item) => ['seller.pos.index', 'seller.inventory.index'].includes(item.route));
    }
    return fullNavItems;
});

const dashboardRoutes = computed(() => {
    if (isBranchUser.value) {
        return [{ label: 'Overview', route: 'seller.dashboard' }];
    }
    return [
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
});

const isActive = (routeName) => {
    return route().current(routeName + '*');
};

const isDashboardActive = () => {
    return route().current('seller.dashboard*') || route().current('seller.branches*') || route().current('seller.orders*');
};

const goToDashboard = (item) => {
    dashboardOpen.value = true;
    sidebarOpen.value = false;

    if (item.params) {
        router.get(route(item.route), item.params, { preserveScroll: true, preserveState: true });
    } else {
        router.get(route(item.route), {}, { preserveScroll: true, preserveState: true });
    }
};

const logout = () => {
    router.post(route('logout'));
};

router.on('navigate', () => {
    sidebarOpen.value = false;
});
</script>

<template>
    <div class="min-h-screen bg-[#F7F7F5] flex flex-col lg:flex-row">
        <!-- DESKTOP STICKY SIDEBAR -->
        <aside class="hidden lg:flex w-64 shrink-0 bg-[#0B0D11] text-white min-h-screen p-5 flex-col border-r border-white/5 sticky top-0 h-screen">
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

            <!-- SHOP NAME CHIP (CLICKABLE TO EDIT STORE PROFILE) -->
            <button
                type="button"
                @click="openEditShopModal"
                class="w-full text-left px-3 py-2.5 rounded-xl bg-white/5 border border-white/5 mb-6 hover:bg-white/10 hover:border-white/20 transition-all group select-none cursor-pointer"
                title="Click to edit store profile & logo"
            >
                <div class="flex items-center justify-between">
                    <p class="text-[10px] uppercase tracking-wider text-white/40 font-medium">Store</p>
                    <span class="text-[10px] text-white/40 group-hover:text-white flex items-center gap-1 transition-colors">
                        Edit ✎
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-1.5">
                    <div class="w-6 h-6 rounded-full bg-white/20 text-white flex items-center justify-center text-[10px] font-bold overflow-hidden shrink-0">
                        <img
                            v-if="team?.logo_path"
                            :src="(team.logo_path.startsWith('images/') || team.logo_path.startsWith('http') ? (team.logo_path.startsWith('http') ? team.logo_path : '/' + team.logo_path) : '/storage/' + team.logo_path)"
                            class="w-full h-full object-cover"
                        />
                        <span v-else>{{ team?.name?.charAt(0) || 'S' }}</span>
                    </div>
                    <p class="text-xs font-semibold text-white truncate flex-1">
                        {{ team?.name || 'Main Shop' }}
                    </p>
                </div>
            </button>

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

                    <div v-if="dashboardOpen" class="mt-1 ml-4 pl-3 border-l border-white/10 space-y-1">
                        <button
                            v-for="item in dashboardRoutes"
                            :key="item.route"
                            type="button"
                            @click="goToDashboard(item)"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-colors"
                            :class="isActive(item.route) ? 'bg-white/15 text-white font-medium' : 'text-white/50 hover:text-white hover:bg-white/5'"
                        >
                            {{ item.label }}
                        </button>
                    </div>
                </div>

                <!-- NAV ITEMS -->
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
                    <div class="w-8 h-8 rounded-full bg-white text-[#0B0D11] flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden">
                        <img
                            v-if="user?.profile_photo_url && !avatarError"
                            :src="user.profile_photo_url"
                            :alt="user.name"
                            class="w-full h-full object-cover"
                            @error="avatarError = true"
                        />
                        <span v-else>{{ user?.name?.charAt(0) || 'S' }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-white truncate">{{ user?.name }}</p>
                        <p class="text-[10px] text-white/40 truncate">{{ isCashier ? 'Cashier' : (isBranchUser ? 'Branch Staff' : 'Merchant Owner') }}</p>
                    </div>
                </div>

                <button
                    @click="logout"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-colors cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Log Out</span>
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
                    <Link :href="route(isCashier ? 'seller.pos.index' : 'seller.dashboard')" @click="sidebarOpen = false" class="flex items-center gap-2.5">
                        <svg viewBox="0 0 200 130" class="h-6 w-auto fill-white shrink-0">
                            <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                        </svg>
                        <span class="font-bold tracking-tight text-white text-base">Zypher Seller</span>
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

                <div class="px-2 mb-4">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40 bg-white/5 px-2 py-0.5 rounded-full border border-white/10">
                        {{ isBranchUser ? 'Branch Portal' : 'Seller Portal' }}
                    </span>
                </div>

                <!-- NAVIGATION -->
                <nav class="space-y-1 flex-1 overflow-y-auto">
                    <div v-if="!isCashier">
                        <button
                            type="button"
                            @click="dashboardOpen = !dashboardOpen"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-medium"
                            :class="isDashboardActive() ? 'bg-white text-[#0B0D11] font-semibold' : 'text-white/70 hover:bg-white/10'"
                        >
                            <span>Dashboard</span>
                            <span class="text-xs">{{ dashboardOpen ? '▲' : '▼' }}</span>
                        </button>
                        <div v-if="dashboardOpen" class="mt-1 ml-4 pl-3 border-l border-white/10 space-y-1">
                            <button
                                v-for="item in dashboardRoutes"
                                :key="item.route"
                                type="button"
                                @click="goToDashboard(item)"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs"
                                :class="isActive(item.route) ? 'bg-white/15 text-white font-medium' : 'text-white/50'"
                            >
                                {{ item.label }}
                            </button>
                        </div>
                    </div>

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
                </nav>

                <div class="pt-4 mt-auto border-t border-white/10 space-y-2">
                    <Link
                        v-if="!isBranchUser"
                        :href="route('home')"
                        @click="sidebarOpen = false"
                        class="block px-3 py-2 rounded-xl text-xs text-white/60 hover:text-white"
                    >
                        &larr; Back to Storefront
                    </Link>
                    <button
                        @click="logout"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-rose-400 hover:bg-rose-500/10"
                    >
                        <span>Log Out</span>
                    </button>
                </div>
            </aside>
        </transition>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- TOP BAR -->
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

                    <span class="text-xs font-medium text-[#8A8B90] truncate max-w-[150px] sm:max-w-none">{{ team?.name || 'Seller Center' }}</span>
                    <span class="text-xs text-[#EAEAE5]">/</span>
                    <span class="text-xs font-semibold text-[#101114]">Management Portal</span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-xs font-medium text-[#101114] bg-[#F7F7F5] px-3 py-1.5 rounded-full border border-[#EAEAE5]">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="hidden sm:inline">Shop Active</span>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <slot />
            </main>
        </div>

        <!-- EDIT SHOP PROFILE MODAL -->
        <DialogModal :show="showEditShopModal" @close="closeEditShopModal" max-width="lg">
            <template #title>
                <div class="flex items-center justify-between">
                    <span class="text-base font-bold text-[#101114]">Edit Store Profile</span>
                    <a
                        v-if="team?.slug || team?.id"
                        :href="route('shops.show', team.slug || team.id)"
                        target="_blank"
                        class="text-xs text-[#6D5EF8] hover:underline flex items-center gap-1 font-medium"
                    >
                        View Public Profile ↗
                    </a>
                </div>
            </template>

            <template #content>
                <form @submit.prevent="saveShopProfile" class="space-y-4">
                    <!-- STORE LOGO -->
                    <div>
                        <label class="block text-xs font-bold text-[#101114] uppercase tracking-wider mb-2">Store Logo</label>
                        <input
                            ref="logoInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleLogoChange"
                        />

                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-[#0F1115] text-white flex items-center justify-center font-bold text-lg overflow-hidden border border-[#EAEAE5] shadow-xs shrink-0">
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    class="w-full h-full object-cover"
                                />
                                <img
                                    v-else-if="team?.logo_path"
                                    :src="(team.logo_path.startsWith('images/') || team.logo_path.startsWith('http') ? (team.logo_path.startsWith('http') ? team.logo_path : '/' + team.logo_path) : '/storage/' + team.logo_path)"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else>{{ (shopForm.name || team?.name || 'S').charAt(0) }}</span>
                            </div>

                            <div>
                                <button
                                    type="button"
                                    @click="triggerLogoInput"
                                    class="px-3 py-1.5 rounded-xl border border-[#E4E4E0] hover:border-[#101114] text-xs font-semibold text-[#101114] bg-white transition shadow-2xs"
                                >
                                    Change Logo
                                </button>
                                <p class="text-[11px] text-[#8A8B90] mt-1">Recommended square format, JPG/PNG (max 2MB)</p>
                            </div>
                        </div>
                        <p v-if="shopForm.errors.logo" class="text-xs text-rose-600 mt-1">{{ shopForm.errors.logo }}</p>
                    </div>

                    <!-- STORE NAME -->
                    <div>
                        <label class="block text-xs font-bold text-[#101114] uppercase tracking-wider mb-2">Store Name</label>
                        <input
                            v-model="shopForm.name"
                            type="text"
                            required
                            class="w-full rounded-xl border border-[#E4E4E0] px-3.5 py-2.5 text-xs text-[#101114] focus:border-[#101114] focus:ring-0 outline-none"
                            placeholder="Enter store name..."
                        />
                        <p v-if="shopForm.errors.name" class="text-xs text-rose-600 mt-1">{{ shopForm.errors.name }}</p>
                    </div>

                    <!-- STORE DESCRIPTION -->
                    <div>
                        <label class="block text-xs font-bold text-[#101114] uppercase tracking-wider mb-2">Store Description & Tagline</label>
                        <textarea
                            v-model="shopForm.description"
                            rows="3"
                            class="w-full rounded-xl border border-[#E4E4E0] p-3 text-xs text-[#101114] focus:border-[#101114] focus:ring-0 outline-none"
                            placeholder="Briefly describe your shop, specialties, and location..."
                        ></textarea>
                        <p v-if="shopForm.errors.description" class="text-xs text-rose-600 mt-1">{{ shopForm.errors.description }}</p>
                    </div>
                </form>
            </template>

            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="closeEditShopModal"
                        class="rounded-xl border border-[#E4E4E0] px-4 py-2 text-xs font-semibold text-[#5F5E5A] hover:bg-[#F7F7F5] transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="saveShopProfile"
                        :disabled="shopForm.processing"
                        class="rounded-xl bg-[#101114] hover:bg-black px-5 py-2 text-xs font-bold text-white transition disabled:opacity-50"
                    >
                        {{ shopForm.processing ? 'Saving...' : 'Save Store Details' }}
                    </button>
                </div>
            </template>
        </DialogModal>

        <!-- GLOBAL BACK BUTTON LOGOUT TRAP MODAL -->
        <BackLogoutTrapModal />
    </div>
</template>