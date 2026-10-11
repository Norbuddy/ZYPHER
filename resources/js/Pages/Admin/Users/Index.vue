<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    users: Object,
});

const suspend = (id) => {
    if (confirm('Suspend this user?')) {
        router.post(
            route('admin.users.suspend', id),
            {},
            {
                preserveScroll: true,
            }
        );
    }
};

const userRows = computed(() => {
    return (props.users?.data ?? []).map((user) => ({
        ...user,
        role: user.is_admin ? 'Admin' : 'Customer',
        joined: user.created_at
            ? new Date(user.created_at).toLocaleDateString('en-PH', {
                  month: 'short',
                  day: 'numeric',
                  year: 'numeric',
              })
            : '—',
    }));
});

const columns = [
    {
        key: 'name',
        label: 'Name',
    },
    {
        key: 'email',
        label: 'Email',
    },
    {
        key: 'role',
        label: 'Role',
    },
    {
        key: 'joined',
        label: 'Joined',
    },
    {
        key: 'actions',
        label: 'Action',
        sortable: false,
    },
];

const roleClass = (role) => {
    if (role === 'Admin') {
        return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    }

    return 'bg-gray-50 text-gray-600 border-gray-200';
};
</script>

<template>
    <AdminLayout>
        <Head title="Users" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">
            Users
        </h1>

        <DataTable
            table-id="admin-users"
            :columns="columns"
            :rows="userRows"
            :search-keys="[
                'name',
                'email',
                'role',
            ]"
            :page-size="10"
            csv-filename="admin-users.csv"
        >
            <!-- NAME -->
            <template #cell-name="{ row }">
                <div class="min-w-[180px]">
                    <p class="font-medium text-[#101114]">
                        {{ row.name }}
                    </p>

                    <p class="text-[11px] text-[#8A8B90] mt-1">
                        User #{{ row.id }}
                    </p>
                </div>
            </template>

            <!-- EMAIL -->
            <template #cell-email="{ row }">
                <span class="text-sm text-[#101114]">
                    {{ row.email }}
                </span>
            </template>

            <!-- ROLE -->
            <template #cell-role="{ row }">
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase border"
                    :class="roleClass(row.role)"
                >
                    {{ row.role }}
                </span>
            </template>

            <!-- JOINED -->
            <template #cell-joined="{ row }">
                <span class="text-sm text-[#101114] whitespace-nowrap">
                    {{ row.joined }}
                </span>
            </template>

            <!-- ACTION -->
            <template #cell-actions="{ row }">
                <button
                    v-if="!row.is_admin"
                    type="button"
                    @click="suspend(row.id)"
                    class="text-xs text-red-500 font-medium hover:text-red-700 transition"
                >
                    Suspend
                </button>

                <span
                    v-else
                    class="text-xs text-[#8A8B90]"
                >
                    —
                </span>
            </template>
        </DataTable>
    </AdminLayout>
</template>