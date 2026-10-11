<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    shops: Object,
});

const columns = [
    {
        key: 'name',
        label: 'Shop',
    },
    {
        key: 'status',
        label: 'Status',
    },
    {
        key: 'commission_rate',
        label: 'Commission',
    },
    {
        key: 'actions',
        label: 'Action',
        sortable: false,
    },
];

const statusColor = (status) => ({
    pending: 'bg-amber-50 text-amber-700 border-amber-200',
    approved: 'bg-green-50 text-green-700 border-green-200',
    suspended: 'bg-red-50 text-red-700 border-red-200',
    rejected: 'bg-red-50 text-red-700 border-red-200',
}[status] ?? 'bg-gray-50 text-gray-700 border-gray-200');

const approve = (id) =>
    router.post(route('admin.shops.approve', id), {}, {
        preserveScroll: true,
    });

const reject = (id) =>
    router.post(route('admin.shops.reject', id), {}, {
        preserveScroll: true,
    });

const suspend = (id) =>
    router.post(route('admin.shops.suspend', id), {}, {
        preserveScroll: true,
    });
</script>

<template>
    <AdminLayout>
        <Head title="Shops" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">
            Shop Approvals
        </h1>

        <DataTable
            table-id="admin-shops"
            :columns="columns"
            :rows="shops.data"
            :search-keys="[
                'name',
                'status',
            ]"
            :page-size="10"
            csv-filename="admin-shops.csv"
        >
            <template #cell-name="{ row }">
                <span class="font-medium text-[#101114]">
                    {{ row.name }}
                </span>
            </template>

            <template #cell-status="{ row }">
                <span
                    class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium border"
                    :class="statusColor(row.status)"
                >
                    {{ row.status }}
                </span>
            </template>

            <template #cell-commission_rate="{ row }">
                {{ row.commission_rate }}%
            </template>

            <template #cell-actions="{ row }">
                <div class="flex gap-3">
                    <button
                        v-if="row.status === 'pending'"
                        @click="approve(row.id)"
                        class="text-xs text-green-600 font-medium"
                    >
                        Approve
                    </button>

                    <button
                        v-if="row.status === 'pending'"
                        @click="reject(row.id)"
                        class="text-xs text-red-500 font-medium"
                    >
                        Reject
                    </button>

                    <button
                        v-if="row.status === 'approved'"
                        @click="suspend(row.id)"
                        class="text-xs text-red-500 font-medium"
                    >
                        Suspend
                    </button>
                </div>
            </template>
        </DataTable>
    </AdminLayout>
</template>