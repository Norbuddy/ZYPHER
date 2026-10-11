<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({ orderShops: Object });

const refund = (id) => {
    if (confirm('Force refund this order shop?')) router.post(route('admin.orders.refund', id), {}, { preserveScroll: true });
};
</script>

<template>
    <AdminLayout>
        <Head title="Orders" />

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-[#101114]">All Orders</h1>
            <a
                :href="route('admin.orders.export')"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#E4E4E0] bg-white text-xs font-medium text-[#101114] hover:bg-[#F7F7F5] transition shadow-sm"
            >
                <span>📥</span> Export CSV
            </a>
        </div>

        <div class="bg-white rounded-xl border border-[#E4E4E0] overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#F7F7F5] text-xs text-[#8A8B90] uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Order</th>
                        <th class="text-left px-4 py-3">Shop</th>
                        <th class="text-left px-4 py-3">Customer</th>
                        <th class="text-left px-4 py-3">Total</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="os in orderShops.data" :key="os.id" class="border-t border-[#EAEAE5]">
                        <td class="px-4 py-3 font-medium">{{ os.order.order_number }}</td>
                        <td class="px-4 py-3">{{ os.team?.name }}</td>
                        <td class="px-4 py-3">{{ os.order.user?.name }}</td>
                        <td class="px-4 py-3">₱{{ os.subtotal }}</td>
                        <td class="px-4 py-3"><OrderStatusBadge :status="os.status" /></td>
                        <td class="px-4 py-3">
                            <button v-if="os.status === 'refund_requested'" @click="refund(os.id)" class="text-xs text-red-500 font-medium">
                                Approve Refund
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
