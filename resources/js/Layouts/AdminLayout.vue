<script setup>
import { ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';

const page = usePage();
const user = page.props.auth?.user;
const applicationsOpen = ref(false);

const navItems = [
    { label: 'Dashboard', route: 'admin.dashboard' },
    { label: 'Banners', route: 'admin.banners.index' },
    { label: 'Pop-up Ads', route: 'admin.popup-ads.index' },
    { label: 'Shops', route: 'admin.shops.index' },
    { label: 'Products', route: 'admin.products.index' },
    { label: 'Users', route: 'admin.users.index' },
    { label: 'Vouchers', route: 'admin.vouchers.index' },
    { label: 'Support', route: 'admin.support.index' },
];

const applicationItems = [
//     { label: 'Supplier Applications', route: 'admin.supplier-application.index' },
    { label: 'Seller Applications', route: 'admin.seller-application.index' },
    { label: 'Branch Applications', route: 'admin.branch-application.index' },
];

const isActive = (routeName) => route().current(routeName + '*');

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-[#F7F7F5] flex">
        <aside class="w-64 shrink-0 bg-white border-r border-[#EAEAE5] min-h-screen p-6 flex flex-col">
            <Link :href="route('admin.dashboard')" class="flex items-center gap-2 mb-2">
                <svg viewBox="0 0 200 130" class="h-6 w-auto fill-[#101114]">
                    <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                </svg>
                <span class="font-semibold tracking-tight text-[#101114]">Zypher</span>
            </Link>
            <p class="text-xs uppercase tracking-wider text-[#8A8B90] mb-8">Admin Panel</p>

            <nav class="space-y-1 flex-1">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="block px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="isActive(item.route) ? 'bg-[#101114] text-white' : 'text-[#5F5E5A] hover:bg-[#F0F0EC]'"
                >
                    {{ item.label }}
                </Link>

                <div>
                    <button
                        type="button"
                        @click="applicationsOpen = !applicationsOpen"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors text-[#5F5E5A] hover:bg-[#F0F0EC]"
                    >
                        <span>Applications</span>
                        <svg
                            class="w-4 h-4 transition-transform duration-200"
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

                    <div v-if="applicationsOpen" class="mt-1 ml-3 pl-3 border-l border-[#EAEAE5] space-y-1">
                        <Link
                            v-for="item in applicationItems"
                            :key="item.route"
                            :href="route(item.route)"
                            class="block px-3 py-2 rounded-lg text-xs transition-colors"
                            :class="isActive(item.route) ? 'bg-[#101114] text-white' : 'text-[#5F5E5A] hover:bg-[#F0F0EC]'"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </nav>

            <div class="pt-4 border-t border-[#EAEAE5]">
                <div class="flex items-center gap-2 px-3 py-2 mb-1">
                    <div class="w-7 h-7 rounded-full bg-[#6D5EF8]/10 flex items-center justify-center text-xs font-semibold text-[#6D5EF8] shrink-0">
                        {{ user?.name?.charAt(0) }}
                    </div>
                    <span class="text-sm text-[#101114] truncate">{{ user?.name }}</span>
                </div>
                <button @click="logout" class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-500 hover:bg-red-50">
                    Log Out
                </button>
            </div>
        </aside>

        <main class="flex-1 p-8">
            <Banner />

            <slot />
        </main>
    </div>
</template>
