<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ payouts: Array });
</script>

<template>
    <AdminLayout>
        <Head title="Payouts" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">Seller Payouts</h1>
        <p class="text-sm text-[#8A8B90] mb-6">Amounts owed to each seller from completed, unpaid orders.</p>

        <div class="bg-white rounded-xl border border-[#E4E4E0] overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#F7F7F5] text-xs text-[#8A8B90] uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Shop</th>
                        <th class="text-left px-4 py-3">Orders</th>
                        <th class="text-left px-4 py-3">Total Owed</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in payouts" :key="p.team.id" class="border-t border-[#EAEAE5]">
                        <td class="px-4 py-3 font-medium">{{ p.team.name }}</td>
                        <td class="px-4 py-3">{{ p.order_count }}</td>
                        <td class="px-4 py-3 text-[#6D5EF8] font-semibold">₱{{ p.total_owed }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-[#B4B2A9] mt-4">
            Note: this view aggregates by shop. To mark individual order_shops as paid, extend this table with a per-order breakdown and wire up the "Mark Paid" action already available on the PayoutController.
        </p>
    </AdminLayout>
</template>
