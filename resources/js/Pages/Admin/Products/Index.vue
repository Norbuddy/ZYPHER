<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    products: Object,
});

const flag = (id) => {
    router.post(
        route('admin.products.flag', id),
        {},
        {
            preserveScroll: true,
        }
    );
};

const unflag = (id) => {
    router.post(
        route('admin.products.unflag', id),
        {},
        {
            preserveScroll: true,
        }
    );
};

const productRows = computed(() => {
    return (props.products?.data ?? []).map((product) => ({
        ...product,
        shop_name: product.team?.name ?? '—',
    }));
});

const columns = [
    {
        key: 'name',
        label: 'Product',
    },
    {
        key: 'shop_name',
        label: 'Shop',
    },
    {
        key: 'status',
        label: 'Status',
    },
    {
        key: 'actions',
        label: 'Action',
        sortable: false,
    },
];

const statusClass = (status) => {
    return {
        active:
            'bg-green-50 text-green-700 border-green-200',

        flagged:
            'bg-red-50 text-red-700 border-red-200',

        draft:
            'bg-gray-50 text-gray-600 border-gray-200',

        inactive:
            'bg-gray-50 text-gray-600 border-gray-200',

        archived:
            'bg-gray-50 text-gray-600 border-gray-200',
    }[status] ?? 'bg-gray-50 text-gray-600 border-gray-200';
};

const formatStatus = (status) => {
    if (!status) {
        return '—';
    }

    return status
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};
</script>

<template>
    <AdminLayout>
        <Head title="Products" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">
            Product Moderation
        </h1>

        <DataTable
            table-id="admin-products"
            :columns="columns"
            :rows="productRows"
            :search-keys="[
                'name',
                'shop_name',
                'status',
            ]"
            :page-size="10"
            csv-filename="admin-products.csv"
        >
            <!-- PRODUCT -->
            <template #cell-name="{ row }">
                <div class="min-w-[220px]">
                    <p class="font-medium text-[#101114]">
                        {{ row.name }}
                    </p>

                    <p class="text-[11px] text-[#8A8B90] mt-1">
                        Product #{{ row.id }}
                    </p>
                </div>
            </template>

            <!-- SHOP -->
            <template #cell-shop_name="{ row }">
                <span class="text-sm text-[#101114]">
                    {{ row.shop_name }}
                </span>
            </template>

            <!-- STATUS -->
            <template #cell-status="{ row }">
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase border"
                    :class="statusClass(row.status)"
                >
                    {{ formatStatus(row.status) }}
                </span>
            </template>

            <!-- ACTION -->
            <template #cell-actions="{ row }">
                <button
                    v-if="row.status !== 'flagged'"
                    type="button"
                    @click="flag(row.id)"
                    class="text-xs text-red-500 font-medium hover:text-red-700 transition"
                >
                    Flag
                </button>

                <button
                    v-else
                    type="button"
                    @click="unflag(row.id)"
                    class="text-xs text-green-600 font-medium hover:text-green-700 transition"
                >
                    Restore
                </button>
            </template>
        </DataTable>
    </AdminLayout>
</template>