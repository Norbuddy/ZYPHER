<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ products: Array });

const destroy = (id) => {
    if (confirm('Remove this product?')) router.delete(route('seller.products.destroy', id));
};

const statusColor = (status) => ({
    draft: 'bg-gray-50 text-gray-600 border-gray-200',
    active: 'bg-green-50 text-green-700 border-green-200',
    archived: 'bg-gray-50 text-gray-500 border-gray-200',
    flagged: 'bg-red-50 text-red-700 border-red-200',
}[status] ?? 'bg-gray-50 text-gray-600 border-gray-200');

const columns = [
    { key: 'name', label: 'Product' },
    { key: 'min_price', label: 'Min Price' },
    { key: 'max_price', label: 'Max Price' },
    { key: 'sold_count', label: 'Sold' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', sortable: false },
];

const imageUrl = (path) => (path.startsWith('images/') ? '/' : '/storage/') + path;
</script>

<template>
    <SellerLayout>
        <Head title="My Products" />

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-[#101114]">My Products</h1>
            <Link :href="route('seller.products.create')" class="h-10 px-4 rounded-lg bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white text-sm font-medium flex items-center">
                + Add Product
            </Link>
        </div>

        <DataTable
            table-id="seller-products"
            :columns="columns"
            :rows="products"
            :search-keys="['name', 'status']"
            csv-filename="products.csv"
        >
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#F0F0EC] overflow-hidden shrink-0">
                        <img v-if="row.image_path" :src="imageUrl(row.image_path)" class="w-full h-full object-cover" />
                    </div>
                    <span class="font-medium">{{ row.name }}</span>
                </div>
            </template>

            <template #cell-min_price="{ value }">₱{{ value }}</template>
            <template #cell-max_price="{ value }">₱{{ value }}</template>

            <template #cell-status="{ value }">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium border" :class="statusColor(value)">
                    {{ value }}
                </span>
            </template>

            <template #cell-actions="{ row }">
                <div class="space-x-3">
                    <Link :href="route('seller.products.edit', row.id)" class="text-xs text-[#6D5EF8] font-medium">Edit</Link>
                    <button @click="destroy(row.id)" class="text-xs text-red-500 font-medium">Delete</button>
                </div>
            </template>
        </DataTable>
    </SellerLayout>
</template>