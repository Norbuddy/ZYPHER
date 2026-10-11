<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const capabilities = [
    {
        title: 'Multi-shop Marketplace',
        description: 'Browse and buy from many independent sellers and suppliers in one place, each running their own shop under Zypher.',
    },
    {
        title: 'Sell on Zypher',
        description: 'Apply to become a seller, supplier, or branch partner and list your own products for customers to discover.',
    },
    {
        title: 'Secure Checkout & Orders',
        description: 'Add items to your cart, check out, and track your orders from purchase to delivery.',
    },
    {
        title: 'Ratings & Reviews',
        description: 'Read real reviews — including photos and videos from other buyers — before you purchase, and leave your own after checkout.',
    },
    {
        title: 'Vouchers & Promotions',
        description: 'Save on your purchases with vouchers and promotional offers surfaced across the storefront.',
    },
    {
        title: 'Direct Messaging',
        description: 'Chat directly with sellers and suppliers about products, orders, or custom requests.',
    },
    {
        title: 'Help Center',
        description: 'Get support from our team whenever you run into an issue with an order, account, or shop.',
    },
];
</script>

<template>
    <GuestLayout>
        <Head title="About Zypher" />

        <section class="max-w-4xl mx-auto px-4 sm:px-6 py-14">
            <p class="text-[10px] uppercase tracking-[0.22em] font-semibold text-[#8A8B90] mb-3">
                About
            </p>

            <h1 class="font-display text-3xl sm:text-4xl font-bold tracking-tight text-[#101114] mb-4">
                What is Zypher?
            </h1>

            <p class="text-sm sm:text-base text-[#5F5E5A] leading-relaxed mb-10 max-w-2xl">
                Zypher is an online marketplace that connects buyers with independent sellers,
                and branch partners in one storefront. Whether you're looking to buy
                something new or start selling your own products, Zypher gives you the tools to
                do it — browsing, checkout, messaging, and order tracking, all in one place.
            </p>

            <h2 class="text-lg font-semibold text-[#101114] mb-4">What you can do on Zypher</h2>

            <div class="grid sm:grid-cols-2 gap-5 mb-12">
                <div
                    v-for="item in capabilities"
                    :key="item.title"
                    class="bg-white rounded-xl border border-[#E4E4E0] p-5"
                >
                    <p class="text-sm font-semibold text-[#101114] mb-1.5">{{ item.title }}</p>
                    <p class="text-xs text-[#8A8B90] leading-relaxed">{{ item.description }}</p>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>
