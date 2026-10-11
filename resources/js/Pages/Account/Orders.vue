<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ orders: Object });

const productNames = (order) => {
    return order.order_shops
        .flatMap(os => os.items.map(item => item.product_name))
        .filter(Boolean);
};

const formatProducts = (products) => {
    if (!products || products.length === 0) return '—';
    if (products.length <= 2) return products.join(', ');
    return `${products.slice(0, 2).join(', ')} +${products.length - 2} more`;
};

const productImage = (order) => {
    // Support both snake_case and camelCase relation keys from Inertia
    const shops = order.order_shops ?? order.orderShops ?? [];
    const allItems = shops.flatMap(os => os.items ?? []);

    for (const item of allItems) {
        const product = item.variant?.product ?? item.product;
        if (!product) continue;

        const images = product.images ?? [];
        if (!images.length) continue;

        // Prefer primary image, then first
        const primary = images.find(img => img.is_primary) ?? images[0];
        // ProductImage stores path as "images/products/..." under public/
        const path = primary?.path ? String(primary.path).replace(/^\//, '') : null;
        const url = primary?.url
            ?? (path ? (path.startsWith('images/') ? `/${path}` : `/storage/${path}`) : null);
        if (url) return url;
    }
    return null;
};
</script>

<template>
    <GuestLayout>
        <Head title="My Orders" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
            <h1 class="text-xl font-semibold text-[#101114] mb-6">My Orders</h1>

            <div v-if="orders.data.length === 0" class="text-center py-20 text-[#8A8B90]">
                No orders yet. <Link :href="route('home')" class="text-[#6D5EF8]">Start shopping</Link>
            </div>

            <Link
                v-for="order in orders.data"
                :key="order.id"
                :href="route('account.orders.show', order.id)"
                class="block mb-4 border border-[#E4E4E0] rounded-xl p-4 hover:border-[#6D5EF8] transition-colors"
            >
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-14 h-14 rounded-lg overflow-hidden bg-[#F3F3F0] border border-[#E4E4E0] flex-shrink-0">
                            <img
                                v-if="productImage(order)"
                                :src="productImage(order)"
                                alt="Product"
                                class="w-full h-full object-cover"
                                @error="(e) => { e.target.style.display = 'none'; e.target.nextElementSibling?.classList.remove('hidden'); }"
                            />
                            <div
                                class="w-full h-full flex items-center justify-center text-[10px] text-[#8A8B90]"
                                :class="{ hidden: !!productImage(order) }"
                            >
                                Product
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-[#101114] truncate">
                                {{ formatProducts(productNames(order)) }}
                            </p>

                            <p class="text-[11px] text-[#8A8B90] mt-1">
                                {{ order.order_number }}
                            </p>
                        </div>

                    </div>

                    <p class="text-xs text-[#8A8B90] whitespace-nowrap">
                        {{ new Date(order.created_at).toLocaleDateString() }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 mb-2">
                    <OrderStatusBadge v-for="os in order.order_shops" :key="os.id" :status="os.status" />
                </div>
                <p class="text-sm text-[#6D5EF8] font-semibold">₱{{ order.total_amount }}</p>
            </Link>
        </div>
    </GuestLayout>
</template>