<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
});

const mapElement = ref(null);
const showSalesModal = ref(false);
const selectedBranch = ref(null);
const branchSales = ref([]);
const branchSalesChart = ref([]);
const salesLoading = ref(false);
let map = null;

const salesColumns = [
    { key: 'sale_number', label: 'Sale' },
    { key: 'items_summary', label: 'Items', sortable: false },
    { key: 'total_amount', label: 'Total' },
    { key: 'payment_method', label: 'Payment' },
    { key: 'created_at', label: 'Date' },
];

const openSalesModal = async (branch) => {
    selectedBranch.value = branch;
    branchSales.value = [];
    branchSalesChart.value = [];
    showSalesModal.value = true;
    salesLoading.value = true;

    try {
        const response = await axios.get(route('seller.branches.sales', branch.id), {
            headers: { Accept: 'application/json' },
        });

        branchSales.value = (response.data.sales || []).map((sale) => ({
            ...sale,
            items_summary: sale.items.map((item) => `${item.product_name} x${item.quantity}`).join(', '),
        }));
        branchSalesChart.value = response.data.sales_chart || [];
    } finally {
        salesLoading.value = false;
    }
};

const closeSalesModal = () => {
    showSalesModal.value = false;
    selectedBranch.value = null;
};

const hasValidCoordinates = (branch) => {
    const latitude = Number(branch.latitude);
    const longitude = Number(branch.longitude);

    return Number.isFinite(latitude) && Number.isFinite(longitude);
};

onMounted(() => {
    if (!mapElement.value) {
        return;
    }

    map = L.map(mapElement.value).setView([14.5995, 120.9842], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const validBranches = props.branches.filter(hasValidCoordinates);

    if (validBranches.length) {
        const points = validBranches.map((branch) => [Number(branch.latitude), Number(branch.longitude)]);
        const bounds = L.latLngBounds(points);

        validBranches.forEach((branch) => {
            const marker = L.marker([
                Number(branch.latitude),
                Number(branch.longitude),
            ]).addTo(map);

            marker.bindPopup(`
                <div style="min-width: 180px;">
                    <strong>${branch.name}</strong><br>
                    ${branch.address || 'No address provided'}<br>
                    <small>${branch.status || 'pending'}</small>
                </div>
            `);
        });

        map.fitBounds(bounds.pad(0.35));
    }
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <SellerLayout>
        <Head title="Branches" />

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-[#101114]">Branches</h1>
                <p class="text-sm text-[#8A8B90] mt-1">Manage your stores and branch registration documents.</p>
            </div>
        </div>

        <div class="mb-6 rounded-xl border border-[#E4E4E0] bg-white p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-[#101114]">Branch Locations</h2>
                <span class="text-xs text-[#8A8B90]">{{ branches.length }} total</span>
            </div>
            <div ref="mapElement" class="h-[360px] w-full overflow-hidden rounded-xl border border-[#EAEAE5]"></div>
        </div>

        <div class="bg-white rounded-xl border border-[#E4E4E0] overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#F7F7F5] text-xs text-[#8A8B90] uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Branch</th>
                        <th class="text-left px-4 py-3">Contact</th>
                        <th class="text-left px-4 py-3">Address</th>
                        <th class="text-left px-4 py-3">Location</th>
                        <th class="text-left px-4 py-3">Sales</th>
                        <th class="text-left px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="branch in branches" :key="branch.id" class="border-t border-[#EAEAE5] align-top">
                        <td class="px-4 py-3">
                            <p class="font-medium text-[#101114]">{{ branch.name }}</p>
                            <p class="text-[11px] text-[#8A8B90] mt-1">Added {{ branch.created_at }}</p>
                        </td>
                        <td class="px-4 py-3">{{ branch.phone }}</td>
                        <td class="px-4 py-3 max-w-[220px]">{{ branch.address }}</td>
                        <td class="px-4 py-3">
                            <span v-if="branch.latitude && branch.longitude" class="text-xs text-[#8A8B90]">
                                {{ Number(branch.latitude).toFixed(4) }}, {{ Number(branch.longitude).toFixed(4) }}
                            </span>
                            <span v-else class="text-xs text-[#8A8B90]">Not set</span>
                        </td>
                        <td class="px-4 py-3">
                            <button
                                v-if="branch.bir_document_path"
                                type="button"
                                @click="openSalesModal(branch)"
                                class="text-xs font-medium text-[#6D5EF8] underline"
                            >
                                View sales
                            </button>
                            <span v-else class="text-xs text-[#8A8B90]">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase"
                                :class="{
                                    'bg-yellow-100 text-yellow-700': branch.status === 'pending',
                                    'bg-emerald-100 text-emerald-700': branch.status === 'approved',
                                    'bg-red-100 text-red-700': branch.status === 'rejected',
                                    'bg-slate-100 text-slate-700': !['pending','approved','rejected'].includes(branch.status),
                                }"
                            >
                                {{ branch.status || 'pending' }}
                            </span>
                        </td>
                    </tr>

                    <tr v-if="!branches.length">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-[#8A8B90]">
                            No branches added yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showSalesModal" class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/40 p-4" @click.self="closeSalesModal">
            <div class="relative z-[1001] max-h-[85vh] w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-[#EAEAE5] p-5">
                    <div>
                        <h2 class="text-lg font-semibold text-[#101114]">{{ selectedBranch?.name }} Sales</h2>
                        <p class="mt-1 text-sm text-[#8A8B90]">POS sales recorded for this branch.</p>
                    </div>
                    <button type="button" @click="closeSalesModal" class="rounded-lg border border-[#E4E4E0] px-3 py-2 text-sm">Close</button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-5">
                    <p v-if="salesLoading" class="py-10 text-center text-sm text-[#8A8B90]">Loading sales...</p>
                    <div v-else class="mb-6 rounded-xl border border-[#E4E4E0] p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-[#101114]">Daily Sales</h3>
                            <span v-if="branchSalesChart.length" class="text-xs text-[#8A8B90]">{{ branchSalesChart.length }} day(s)</span>
                        </div>
                        <div v-if="branchSalesChart.length" class="flex h-48 items-end gap-2 overflow-x-auto border-b border-[#EAEAE5] px-2 pt-4">
                            <div v-for="day in branchSalesChart" :key="day.date" class="flex h-full min-w-12 flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-[10px] font-medium text-[#5F5E5A]">₱{{ Number(day.total).toLocaleString() }}</span>
                                <div
                                    class="w-full max-w-10 rounded-t-md bg-[#6D5EF8] transition-all"
                                    :style="{ height: `${Math.max(8, (day.total / Math.max(...branchSalesChart.map((item) => Number(item.total)), 1)) * 125)}px` }"
                                    :title="`${day.date}: ₱${Number(day.total).toFixed(2)} (${day.sales_count} sale(s))`"
                                ></div>
                                <span class="text-[10px] text-[#8A8B90]">{{ day.date }}</span>
                            </div>
                        </div>
                        <p v-else class="py-10 text-center text-sm text-[#8A8B90]">No sales data for this branch yet.</p>
                    </div>
                    <DataTable
                        v-if="!salesLoading"
                        table-id="branch-sales-modal"
                        :columns="salesColumns"
                        :rows="branchSales"
                        :search-keys="['sale_number', 'items_summary', 'payment_method']"
                        csv-filename="branch-sales.csv"
                    >
                        <template #cell-total_amount="{ value }">₱{{ Number(value).toFixed(2) }}</template>
                        <template #cell-payment_method="{ value }">{{ value.replace('_', ' ').toUpperCase() }}</template>
                    </DataTable>
                </div>
            </div>
        </div>
    </SellerLayout>
</template>
