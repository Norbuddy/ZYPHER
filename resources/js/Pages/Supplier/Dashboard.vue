<script setup>
import { Head } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { ref, computed } from 'vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({
    stats: Object,
    logs: Array,
    charts: {
        type: Object,
        default: () => ({
            ordersByDay: [],
            statusDistribution: [],
            inventoryMix: [],
        }),
    },
});

const tab = ref('overview');

const maxDayOrders = computed(() => Math.max(1, ...props.charts.ordersByDay.map((d) => d.orders)));
const maxDayRevenue = computed(() => Math.max(1, ...props.charts.ordersByDay.map((d) => d.revenue)));
const statusTotal = computed(() => props.charts.statusDistribution.reduce((s, d) => s + d.value, 0) || 1);

function formatMoney(n) {
    return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function donutSlices(items) {
    const total = items.reduce((s, i) => s + i.value, 0) || 1;
    let angle = -90;
    const r = 40;
    const cx = 50;
    const cy = 50;
    return items
        .filter((i) => i.value > 0)
        .map((item) => {
            const sweep = (item.value / total) * 360;
            const start = angle;
            angle += sweep;
            const large = sweep > 180 ? 1 : 0;
            const startRad = (start * Math.PI) / 180;
            const endRad = (angle * Math.PI) / 180;
            const x1 = cx + r * Math.cos(startRad);
            const y1 = cy + r * Math.sin(startRad);
            const x2 = cx + r * Math.cos(endRad);
            const y2 = cy + r * Math.sin(endRad);
            return {
                ...item,
                d: `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2} Z`,
            };
        });
}

const statusDonut = computed(() => donutSlices(props.charts.statusDistribution));
const inventoryDonut = computed(() => donutSlices(props.charts.inventoryMix));

function areaPath(points, key, max) {
    if (!points.length) return { line: '', fill: '' };
    const w = 280;
    const h = 100;
    const step = points.length > 1 ? w / (points.length - 1) : w;
    const coords = points.map((p, i) => {
        const x = i * step;
        const y = h - ((p[key] || 0) / max) * (h - 8);
        return [x, y];
    });
    let d = `M ${coords[0][0]} ${coords[0][1]}`;
    for (let i = 1; i < coords.length; i++) d += ` L ${coords[i][0]} ${coords[i][1]}`;
    return { line: d, fill: `${d} L ${coords[coords.length - 1][0]} ${h} L ${coords[0][0]} ${h} Z` };
}

const revenueArea = computed(() => areaPath(props.charts.ordersByDay, 'revenue', maxDayRevenue.value));
</script>

<template>
    <div class="space-y-6">
        <Head title="Supplier Center" />

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-semibold text-[#101114]">Supplier Center</h1>
                    <p class="mt-2 text-sm text-[#8A8B90]">Overview of your recent orders and inventory.</p>
                </div>
                <div class="inline-flex rounded-xl border border-[#E4E4E0] bg-white p-1 text-sm">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-1.5 font-medium transition"
                        :class="tab === 'overview' ? 'bg-[#101114] text-white' : 'text-[#5F5E5A] hover:text-[#101114]'"
                        @click="tab = 'overview'"
                    >
                        Overview
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-4 py-1.5 font-medium transition"
                        :class="tab === 'reports' ? 'bg-[#101114] text-white' : 'text-[#5F5E5A] hover:text-[#101114]'"
                        @click="tab = 'reports'"
                    >
                        Reports
                    </button>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="h-40 rounded-2xl border border-gray-200 bg-gray-100 p-6 shadow-inner">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">Total Orders</p>
                        <p class="text-lg font-semibold text-[#101114]">{{ stats.total_orders }}</p>
                    </div>
                    <div v-if="stats.latest_order_shop">
                        <p class="mt-3 text-sm text-gray-600">Latest order from {{ stats.latest_order_shop }}</p>
                    </div>
                    <div v-if="stats.latest_order_products">
                        <p class="text-sm text-gray-600">Product: {{ stats.latest_order_products }}</p>
                    </div>
                </div>

                <div class="h-40 rounded-2xl border border-gray-200 bg-gray-100 p-6 shadow-inner">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500">Total Revenue</p>
                        <p class="text-lg font-semibold text-[#101114]">₱{{ Number(stats.total_revenue || 0).toLocaleString() }}</p>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">Revenue from {{ stats.latest_order_products || '—' }}</p>
                    <p class="text-sm text-gray-600">Shop: {{ stats.latest_order_shop || '—' }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Inventory Summary</p>
                            <p class="mt-2 text-3xl font-semibold text-[#101114]">{{ stats.inventory_products }}</p>
                            <p class="mt-1 text-xs text-gray-500">products</p>
                        </div>
                        <div class="text-right text-xs text-gray-500">
                            <p>Low stock: <span class="font-semibold text-amber-600">{{ stats.low_stock_count }}</span></p>
                            <p class="mt-1">Out of stock: <span class="font-semibold text-rose-600">{{ stats.out_of_stock_count }}</span></p>
                            <p class="mt-2">Value: ₱{{ stats.inventory_value }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overview analytics -->
            <div v-show="tab === 'overview'" class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[#101114]">Revenue (Last 7 Days)</h2>
                        <span class="text-xs text-[#8A8B90]">Area</span>
                    </div>
                    <div class="relative h-36 w-full">
                        <svg viewBox="0 0 280 100" class="h-full w-full" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="supRevGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#6D5EF8" stop-opacity="0.35" />
                                    <stop offset="100%" stop-color="#33D6C8" stop-opacity="0.05" />
                                </linearGradient>
                            </defs>
                            <path :d="revenueArea.fill" fill="url(#supRevGrad)" />
                            <path :d="revenueArea.line" fill="none" stroke="#6D5EF8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="mt-2 flex justify-between text-[10px] text-[#8A8B90]">
                        <span v-for="d in charts.ordersByDay" :key="d.date">{{ d.label }}</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[#101114]">Orders by Status</h2>
                        <span class="text-xs text-[#8A8B90]">Donut</span>
                    </div>
                    <div class="flex flex-col items-center gap-4 sm:flex-row">
                        <svg viewBox="0 0 100 100" class="h-40 w-40 shrink-0">
                            <circle cx="50" cy="50" r="40" fill="#F7F7F5" />
                            <path v-for="(s, i) in statusDonut" :key="i" :d="s.d" :fill="s.color" opacity="0.9" />
                            <circle cx="50" cy="50" r="22" fill="white" />
                            <text x="50" y="52" text-anchor="middle" class="fill-[#101114]" style="font-size: 11px; font-weight: 600">{{ statusTotal }}</text>
                        </svg>
                        <ul class="w-full space-y-2 text-xs">
                            <li v-for="s in charts.statusDistribution" :key="s.label" class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 text-[#5F5E5A]">
                                    <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: s.color }" />
                                    {{ s.label }}
                                </span>
                                <span class="font-medium text-[#101114]">{{ s.value }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Reports analytics -->
            <div v-show="tab === 'reports'" class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[#101114]">Daily Order Volume</h2>
                        <span class="text-xs text-[#8A8B90]">Bar</span>
                    </div>
                    <div class="flex h-40 items-end gap-2">
                        <div
                            v-for="d in charts.ordersByDay"
                            :key="d.date"
                            class="flex flex-1 flex-col items-center gap-1"
                        >
                            <span class="text-[10px] font-medium text-[#8A8B90]">{{ d.orders }}</span>
                            <div
                                class="w-full rounded-t-md bg-gradient-to-t from-[#6D5EF8] to-[#33D6C8]"
                                :style="{ height: Math.max(4, (d.orders / maxDayOrders) * 120) + 'px' }"
                            />
                            <span class="text-[10px] text-[#8A8B90]">{{ d.label }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[#101114]">Inventory Health</h2>
                        <span class="text-xs text-[#8A8B90]">Donut</span>
                    </div>
                    <div class="flex flex-col items-center gap-4 sm:flex-row">
                        <svg viewBox="0 0 100 100" class="h-40 w-40 shrink-0">
                            <circle cx="50" cy="50" r="40" fill="#F7F7F5" />
                            <path v-for="(s, i) in inventoryDonut" :key="i" :d="s.d" :fill="s.color" opacity="0.9" />
                            <circle cx="50" cy="50" r="22" fill="white" />
                            <text x="50" y="52" text-anchor="middle" class="fill-[#101114]" style="font-size: 11px; font-weight: 600">{{ stats.inventory_products }}</text>
                        </svg>
                        <ul class="w-full space-y-2 text-xs">
                            <li v-for="s in charts.inventoryMix" :key="s.label" class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2 text-[#5F5E5A]">
                                    <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: s.color }" />
                                    {{ s.label }}
                                </span>
                                <span class="font-medium text-[#101114]">{{ s.value }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recent Orders & Sales — unchanged section -->
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#101114]">Recent Orders & Sales</h2>
                </div>
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Shop</th>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Quantity</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in logs" :key="row.product_name + row.date_ordered" class="h-16 border-b border-gray-100 text-[#101114]">
                                    <td class="px-4 py-4">{{ row.shop_name }}</td>
                                    <td class="px-4 py-4">{{ row.product_name }}</td>
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2 py-1 text-xs font-semibold uppercase"
                                            :class="{
                                                'bg-emerald-100 text-emerald-800': row.order_status === 'completed',
                                                'bg-blue-100 text-blue-800': row.order_status === 'processing',
                                                'bg-amber-100 text-amber-800': row.order_status === 'accepted',
                                                'bg-gray-100 text-gray-700': row.order_status === 'pending',
                                                'bg-rose-100 text-rose-800': row.order_status === 'rejected',
                                            }"
                                        >
                                            {{ row.order_status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">{{ row.quantity_ordered }}</td>
                                    <td class="px-4 py-4">{{ row.date_ordered }}</td>
                                    <td class="px-4 py-4">₱{{ row.total_amount }}</td>
                                </tr>
                                <tr v-if="logs.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-gray-400">No activity yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
