<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({ payouts: Array });

const markPaid = (team) => {
    if (!confirm(`Mark all completed payouts as settled for ${team.name}?`)) {
        return;
    }

    router.post(route('admin.payouts.team.mark-paid', team.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Payouts" />

        <h1 class="text-xl font-semibold text-[#101114] mb-2">Seller Payouts</h1>
        <p class="text-sm text-[#8A8B90] mb-6">Amounts owed to each seller from completed, unpaid orders.</p>

        <div class="bg-white rounded-xl border border-[#E4E4E0] overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#F7F7F5] text-xs text-[#8A8B90] uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Shop</th>
                        <th class="text-left px-4 py-3">Orders</th>
                        <th class="text-left px-4 py-3">Total Owed</th>
                        <th class="text-right px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in payouts" :key="p.team.id" class="border-t border-[#EAEAE5]">
                        <td class="px-4 py-3 font-medium">{{ p.team.name }}</td>
                        <td class="px-4 py-3">{{ p.order_count }}</td>
                        <td class="px-4 py-3 text-[#6D5EF8] font-semibold">₱{{ Number(p.total_owed).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 bg-[#101114] hover:bg-[#2b2b2b] text-white text-xs font-medium rounded-lg transition"
                                @click="markPaid(p.team)"
                            >
                                Mark as Paid
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!payouts || payouts.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-[#8A8B90]">
                            No pending seller payouts at this time.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-xs text-[#8A8B90] mt-4">
            Marking a shop's payouts as paid updates all completed orders to settled and triggers an email notification to the shop owner.
        </p>
    </AdminLayout>
</template>
