<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    totalRevenue: [String, Number],
    totalOrders: Number,
    unpaidPayout: [String, Number],
    topProducts: Array,
    productInsights: {
        type: Array,
        default: () => [],
    },
});

// Sorted copy for the damage-rate chart (worst first, so problem items surface immediately).
const byDamageRate = computed(() =>
    [...props.productInsights].sort((a, b) => b.damageRate - a.damageRate)
);

const maxDamageRate = computed(() =>
    Math.max(10, ...props.productInsights.map((p) => p.damageRate))
);

function damageColor(rate) {
    if (rate >= 15) return '#DC2626'; // red
    if (rate >= 5) return '#F59E0B'; // amber
    return '#16A34A'; // green
}
</script>

<template>
    <SellerLayout>
        <Head title="Reports" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">Sales Reports</h1>

        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="bg-white p-5 rounded-xl border border-[#E4E4E0]">
                <p class="text-xs text-[#8A8B90] mb-1">Total Revenue (Completed)</p>
                <p class="text-2xl font-semibold">₱{{ totalRevenue }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-[#E4E4E0]">
                <p class="text-xs text-[#8A8B90] mb-1">Completed Orders</p>
                <p class="text-2xl font-semibold">{{ totalOrders }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-[#E4E4E0]">
                <p class="text-xs text-[#8A8B90] mb-1">Unpaid Payout</p>
                <p class="text-2xl font-semibold">₱{{ unpaidPayout }}</p>
            </div>
        </div>

        <!-- Channel Split -->
        <div class="flex items-baseline justify-between mb-3">
            <h2 class="text-sm font-medium text-[#101114]">Channel Split (Online vs. POS)</h2>
            <div class="flex items-center gap-4 text-xs text-[#8A8B90]">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#3B82F6"></span>Online</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#F97316"></span>POS</span>
            </div>
        </div>

        <div v-if="productInsights.length" class="bg-white rounded-xl border border-[#E4E4E0] overflow-hidden mb-8">
            <table class="w-full text-sm">
                <thead class="bg-[#F7F7F5] text-xs text-[#8A8B90] uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Product</th>
                        <th class="text-left px-4 py-3 w-1/2">Where it sells</th>
                        <th class="text-left px-4 py-3">Online</th>
                        <th class="text-left px-4 py-3">POS</th>
                        <th class="text-left px-4 py-3">Best channel</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in productInsights" :key="p.id" class="border-t border-[#EAEAE5]">
                        <td class="px-4 py-3 font-medium text-[#101114]">{{ p.name }}</td>
                        <td class="px-4 py-3">
                            <div v-if="p.totalUnits > 0" class="w-full h-3 rounded-full overflow-hidden flex bg-[#F0F0EC]">
                                <div
                                    class="h-full"
                                    :style="{ width: p.onlinePct + '%', background: '#3B82F6' }"
                                    :title="`Online: ${p.onlinePct}%`"
                                ></div>
                                <div
                                    class="h-full"
                                    :style="{ width: p.posPct + '%', background: '#F97316' }"
                                    :title="`POS: ${p.posPct}%`"
                                ></div>
                            </div>
                            <span v-else class="text-xs text-[#8A8B90]">No sales yet</span>
                        </td>
                        <td class="px-4 py-3 text-[#101114]">{{ p.onlineUnits }} units ({{ p.onlinePct }}%)</td>
                        <td class="px-4 py-3 text-[#101114]">{{ p.posUnits }} units ({{ p.posPct }}%)</td>
                        <td class="px-4 py-3">
                            <span
                                class="px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="{
                                    'bg-blue-50 text-blue-600': p.dominantChannel === 'Online',
                                    'bg-orange-50 text-orange-600': p.dominantChannel === 'POS',
                                    'bg-gray-100 text-gray-500': p.dominantChannel === 'Even split' || p.dominantChannel === 'No sales yet',
                                }"
                            >{{ p.dominantChannel }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="bg-white rounded-xl border border-[#E4E4E0] p-6 text-sm text-[#8A8B90] mb-8">
            No product sales yet — once orders or POS sales come in, the channel split will show up here.
        </div>

        <!-- Damage Rate -->
        <div class="flex items-baseline justify-between mb-3">
            <h2 class="text-sm font-medium text-[#101114]">Damage Rate</h2>
            <p class="text-xs text-[#8A8B90]">Share of completed orders per item that came back as a low-rated (≤2★) review</p>
        </div>

        <div v-if="byDamageRate.length" class="bg-white rounded-xl border border-[#E4E4E0] p-5 mb-8">
            <div class="space-y-3">
                <div v-for="p in byDamageRate" :key="p.id" class="flex items-center gap-3">
                    <div class="w-48 shrink-0 text-sm text-[#101114] truncate" :title="p.name">{{ p.name }}</div>
                    <div class="flex-1 h-5 bg-[#F0F0EC] rounded-md overflow-hidden relative">
                        <div
                            class="h-full rounded-md transition-all"
                            :style="{ width: Math.min(100, (p.damageRate / maxDamageRate) * 100) + '%', background: damageColor(p.damageRate) }"
                        ></div>
                    </div>
                    <div class="w-32 shrink-0 text-xs text-right text-[#101114]">
                        <span class="font-semibold">{{ p.damageRate }}%</span>
                        <span class="text-[#8A8B90]"> ({{ p.complaintCount }}/{{ p.reviewableOrders }})</span>
                    </div>
                </div>
            </div>
            <p class="text-xs text-[#8A8B90] mt-4">
                Rate = low-rated reviews ÷ completed online orders for that item. Items with no online orders yet show 0%.
            </p>
        </div>
        <div v-else class="bg-white rounded-xl border border-[#E4E4E0] p-6 text-sm text-[#8A8B90] mb-8">
            No completed orders yet — damage rate will populate once items have been delivered and reviewed.
        </div>

        <h2 class="text-sm font-medium text-[#101114] mb-3">Top Products</h2>
        <div class="bg-white rounded-xl border border-[#E4E4E0] overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#F7F7F5] text-xs text-[#8A8B90] uppercase">
                    <tr><th class="text-left px-4 py-3">Product</th><th class="text-left px-4 py-3">Units Sold</th></tr>
                </thead>
                <tbody>
                    <tr v-for="p in topProducts" :key="p.id" class="border-t border-[#EAEAE5]">
                        <td class="px-4 py-3">{{ p.name }}</td>
                        <td class="px-4 py-3">{{ p.sold_count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </SellerLayout>
</template>
