<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    stats: Object,
    charts: {
        type: Object,
        default: () => ({
            revenueByDay: [],
            statusDistribution: [],
            topShops: [],
            userGrowth: [],
        }),
    },
});

const tab = ref('overview');

const maxRevenue = computed(() => Math.max(1, ...props.charts.revenueByDay.map((d) => d.value)));
const maxShop = computed(() => Math.max(1, ...props.charts.topShops.map((d) => d.value)));
const maxUsers = computed(() => Math.max(1, ...props.charts.userGrowth.map((d) => d.value)));
const statusTotal = computed(() => props.charts.statusDistribution.reduce((s, d) => s + d.value, 0) || 1);

function formatMoney(n) {
    return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

/** Build SVG donut arcs */
function donutSlices(items) {
    const total = items.reduce((s, i) => s + i.value, 0) || 1;
    let angle = -90;
    const r = 40;
    const cx = 50;
    const cy = 50;
    return items.map((item) => {
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
            pct: Math.round((item.value / total) * 100),
        };
    });
}

const donut = computed(() => donutSlices(props.charts.statusDistribution.filter((s) => s.value > 0)));

function areaPath(points, max) {
    if (!points.length) return '';
    const w = 280;
    const h = 100;
    const step = points.length > 1 ? w / (points.length - 1) : w;
    const coords = points.map((p, i) => {
        const x = i * step;
        const y = h - (p.value / max) * (h - 8);
        return [x, y];
    });
    let d = `M ${coords[0][0]} ${coords[0][1]}`;
    for (let i = 1; i < coords.length; i++) {
        d += ` L ${coords[i][0]} ${coords[i][1]}`;
    }
    const fill = `${d} L ${coords[coords.length - 1][0]} ${h} L ${coords[0][0]} ${h} Z`;
    return { line: d, fill };
}

const revenueArea = computed(() => areaPath(props.charts.revenueByDay, maxRevenue.value));
const userArea = computed(() => areaPath(props.charts.userGrowth, maxUsers.value));
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-semibold text-[#101114]">Platform Overview</h1>
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

        <!-- KPI cards (shared) -->
        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-[#E4E4E0] bg-white p-5">
                <p class="mb-1 text-xs text-[#8A8B90]">Total Revenue (GMV)</p>
                <p class="text-2xl font-semibold text-[#101114]">₱{{ formatMoney(stats.total_revenue) }}</p>
            </div>
            <div class="rounded-xl border border-[#E4E4E0] bg-white p-5">
                <p class="mb-1 text-xs text-[#8A8B90]">Commission Earned</p>
                <p class="text-2xl font-semibold text-[#101114]">₱{{ formatMoney(stats.total_commission) }}</p>
            </div>
            <div class="rounded-xl border border-[#E4E4E0] bg-white p-5">
                <p class="mb-1 text-xs text-[#8A8B90]">Active Shops</p>
                <p class="text-2xl font-semibold text-[#101114]">{{ stats.active_shops }}</p>
            </div>
            <div class="rounded-xl border border-[#E4E4E0] bg-white p-5">
                <p class="mb-1 text-xs text-[#8A8B90]">Pending Approvals</p>
                <p class="text-2xl font-semibold text-[#101114]">{{ stats.pending_shops }}</p>
            </div>
            <div class="rounded-xl border border-[#E4E4E0] bg-white p-5">
                <p class="mb-1 text-xs text-[#8A8B90]">Total Users</p>
                <p class="text-2xl font-semibold text-[#101114]">{{ stats.total_users }}</p>
            </div>
            <div class="rounded-xl border border-[#E4E4E0] bg-white p-5">
                <p class="mb-1 text-xs text-[#8A8B90]">Total Orders</p>
                <p class="text-2xl font-semibold text-[#101114]">{{ stats.total_orders }}</p>
            </div>
        </div>

        <!-- Overview tab -->
        <div v-show="tab === 'overview'" class="grid gap-6 lg:grid-cols-2">
            <!-- Revenue area chart -->
            <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#101114]">Revenue (Last 7 Days)</h2>
                    <span class="text-xs text-[#8A8B90]">Area</span>
                </div>
                <div class="relative h-36 w-full">
                    <svg viewBox="0 0 280 100" class="h-full w-full" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="revGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#6D5EF8" stop-opacity="0.35" />
                                <stop offset="100%" stop-color="#33D6C8" stop-opacity="0.05" />
                            </linearGradient>
                        </defs>
                        <path :d="revenueArea.fill" fill="url(#revGrad)" />
                        <path :d="revenueArea.line" fill="none" stroke="#6D5EF8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="mt-2 flex justify-between text-[10px] text-[#8A8B90]">
                    <span v-for="d in charts.revenueByDay" :key="d.date">{{ d.label }}</span>
                </div>
            </div>

            <!-- Order status donut -->
            <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#101114]">Orders by Status</h2>
                    <span class="text-xs text-[#8A8B90]">Donut</span>
                </div>
                <div class="flex flex-col items-center gap-4 sm:flex-row">
                    <svg viewBox="0 0 100 100" class="h-40 w-40 shrink-0">
                        <circle cx="50" cy="50" r="40" fill="#F7F7F5" />
                        <path v-for="(s, i) in donut" :key="i" :d="s.d" :fill="s.color" opacity="0.9" />
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

        <!-- Reports tab -->
        <div v-show="tab === 'reports'" class="grid gap-6 lg:grid-cols-2">
            <!-- Top shops bar -->
            <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#101114]">Top Shops by GMV</h2>
                    <span class="text-xs text-[#8A8B90]">Bar</span>
                </div>
                <div v-if="charts.topShops.length" class="space-y-3">
                    <div v-for="shop in charts.topShops" :key="shop.label">
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="truncate font-medium text-[#101114]">{{ shop.label }}</span>
                            <span class="text-[#8A8B90]">₱{{ formatMoney(shop.value) }}</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-[#F0F0EC]">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8]"
                                :style="{ width: Math.max(4, (shop.value / maxShop) * 100) + '%' }"
                            />
                        </div>
                    </div>
                </div>
                <p v-else class="py-10 text-center text-sm text-[#8A8B90]">No completed orders yet.</p>
            </div>

            <!-- User growth area -->
            <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#101114]">User Growth (6 Months)</h2>
                    <span class="text-xs text-[#8A8B90]">Area</span>
                </div>
                <div class="relative h-36 w-full">
                    <svg viewBox="0 0 280 100" class="h-full w-full" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="userGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#33D6C8" stop-opacity="0.4" />
                                <stop offset="100%" stop-color="#33D6C8" stop-opacity="0.02" />
                            </linearGradient>
                        </defs>
                        <path :d="userArea.fill" fill="url(#userGrad)" />
                        <path :d="userArea.line" fill="none" stroke="#33D6C8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="mt-2 flex justify-between text-[10px] text-[#8A8B90]">
                    <span v-for="d in charts.userGrowth" :key="d.label">{{ d.label }}</span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
