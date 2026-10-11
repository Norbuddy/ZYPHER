<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: SellerLayout });

const props = defineProps({
    branch: { type: Object, required: true },
    sales: { type: Array, default: () => [] },
});

const columns = [
    { key: 'sale_number', label: 'Sale' },
    { key: 'items_summary', label: 'Items', sortable: false },
    { key: 'total_amount', label: 'Total' },
    { key: 'payment_method', label: 'Payment' },
    { key: 'created_at', label: 'Date' },
];

const rows = props.sales.map((sale) => ({
    ...sale,
    items_summary: sale.items.map((item) => `${item.product_name} x${item.quantity}`).join(', '),
}));
</script>

<template>
    <SellerLayout>
        <Head :title="`${branch.name} Sales`" />

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-[#101114]">{{ branch.name }} Sales</h1>
                <p class="mt-1 text-sm text-[#8A8B90]">POS sales recorded for this branch.</p>
            </div>
            <Link :href="route('seller.branches.index')" class="rounded-lg border border-[#E4E4E0] px-4 py-2 text-sm font-medium">
                Back to Branches
            </Link>
        </div>

        <DataTable
            table-id="branch-sales"
            :columns="columns"
            :rows="rows"
            :search-keys="['sale_number', 'items_summary', 'payment_method']"
            csv-filename="branch-sales.csv"
        >
            <template #cell-total_amount="{ value }">₱{{ Number(value).toFixed(2) }}</template>
            <template #cell-payment_method="{ value }">{{ value.replace('_', ' ').toUpperCase() }}</template>
        </DataTable>
    </SellerLayout>
</template>