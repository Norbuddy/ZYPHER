<script setup>
import { ref } from 'vue';

import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import DialogModal from '@/Components/DialogModal.vue';

import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    applications: {
        type: Array,
        default: () => [],
    },
});

const selectedApplication = ref(null);

const applicationColumns = [
    {
        key: 'business_name',
        label: 'Business',
    },
    {
        key: 'applicant',
        label: 'Applicant',
    },
    {
        key: 'status',
        label: 'Status',
    },
    {
        key: 'actions',
        label: 'Actions',
    },
];

const viewApplication = (application) => {
    selectedApplication.value = application;
};

const closeApplication = () => {
    selectedApplication.value = null;
};

const approveApplication = () => {
    if (!selectedApplication.value) {
        return;
    }

    if (!confirm('Approve this supplier application?')) {
        return;
    }

    router.patch(
        route(
            'admin.supplier-application.approve',
            selectedApplication.value.id
        ),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                selectedApplication.value = null;
            },
        }
    );
};

const rejectApplication = () => {
    if (!selectedApplication.value) {
        return;
    }

    const note = prompt('Reason for rejection (optional):');

    router.patch(
        route(
            'admin.supplier-application.reject',
            selectedApplication.value.id
        ),
        {
            admin_note: note,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                selectedApplication.value = null;
            },
        }
    );
};
</script>

<template>
    <AdminLayout>

        <Head title="Supplier Applications" />

        <div class="space-y-6">

            <!-- HEADER -->
            <div>
                <h1 class="text-xl font-semibold text-[#101114]">
                    Supplier Applications
                </h1>

                <p class="text-sm text-[#8A8B90] mt-1">
                    Review supplier applications before approval.
                </p>
            </div>

            <!-- DATATABLE -->
            <DataTable
                table-id="admin-supplier-applications"
                :columns="applicationColumns"
                :rows="applications"
                :searchable="true"
                search-placeholder="Search applications..."
                :paginated="true"
                :per-page="10"
            >

                <!-- BUSINESS -->
                <template #cell-business_name="{ row }">
                    <div class="min-w-[220px]">

                        <p class="font-medium text-[#101114]">
                            {{ row.business_name }}
                        </p>

                        <p class="text-[11px] text-[#8A8B90] mt-1">
                            {{ row.business_email || '—' }}
                        </p>

                    </div>
                </template>

                <!-- APPLICANT -->
                <template #cell-applicant="{ row }">

                    <span class="text-sm text-[#101114]">
                        {{ row.user?.name || '—' }}
                    </span>

                </template>

                <!-- STATUS -->
                <template #cell-status="{ row }">

                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase border"
                        :class="{
                            'bg-yellow-50 text-yellow-700 border-yellow-200':
                                row.status === 'pending',

                            'bg-green-50 text-green-700 border-green-200':
                                row.status === 'approved',

                            'bg-red-50 text-red-700 border-red-200':
                                row.status === 'rejected',

                            'bg-gray-50 text-gray-600 border-gray-200':
                                ![
                                    'pending',
                                    'approved',
                                    'rejected',
                                ].includes(row.status),
                        }"
                    >
                        {{ row.status }}
                    </span>

                </template>

                <!-- ACTIONS -->
                <template #cell-actions="{ row }">

                    <button
                        type="button"
                        @click="viewApplication(row)"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition"
                    >
                        View
                    </button>

                </template>

            </DataTable>

        </div>

        <!-- APPLICATION MODAL -->
        <DialogModal
            :show="selectedApplication !== null"
            @close="closeApplication"
        >

            <!-- TITLE -->
            <template #title>
                Supplier Application
            </template>

            <!-- CONTENT -->
            <template #content>

                <div
                    v-if="selectedApplication"
                    class="space-y-5"
                >

                    <!-- BUSINESS NAME -->
                    <div>
                        <p class="text-xs text-gray-500 mb-1">
                            Business Name
                        </p>

                        <p class="font-semibold text-[#101114]">
                            {{ selectedApplication.business_name }}
                        </p>
                    </div>

                    <!-- APPLICANT -->
                    <div>
                        <p class="text-xs text-gray-500 mb-1">
                            Applicant
                        </p>

                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.user?.name || '—' }}
                        </p>
                    </div>

                    <!-- BUSINESS EMAIL -->
                    <div>
                        <p class="text-xs text-gray-500 mb-1">
                            Business Email
                        </p>

                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.business_email || '—' }}
                        </p>
                    </div>

                    <!-- CONTACT NUMBER -->
                    <div>
                        <p class="text-xs text-gray-500 mb-1">
                            Contact Number
                        </p>

                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.contact_number || '—' }}
                        </p>
                    </div>

                    <!-- BUSINESS ADDRESS -->
                    <div>
                        <p class="text-xs text-gray-500 mb-1">
                            Business Address
                        </p>

                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.business_address || '—' }}
                        </p>
                    </div>

                    <!-- REASON -->
                    <div>
                        <p class="text-xs text-gray-500 mb-1">
                            Reason
                        </p>

                        <p class="text-sm text-[#101114] whitespace-pre-line">
                            {{ selectedApplication.reason || '—' }}
                        </p>
                    </div>

                    <!-- BUSINESS PERMIT -->
                    <div>

                        <p class="text-xs text-gray-500 mb-2">
                            Business Permit
                        </p>

                        <a
                            v-if="selectedApplication.permit_path"
                            :href="`/${selectedApplication.permit_path}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-600 hover:bg-indigo-100 transition"
                        >
                            View Uploaded Permit
                        </a>

                        <span
                            v-else
                            class="text-sm text-gray-500"
                        >
                            No permit uploaded.
                        </span>

                    </div>

                </div>

            </template>

            <!-- FOOTER -->
            <template #footer>

                <div class="flex w-full items-center justify-between">

                    <!-- LEFT -->
                    <div>

                        <button
                            v-if="selectedApplication?.status === 'pending'"
                            type="button"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition"
                            @click="rejectApplication"
                        >
                            Reject
                        </button>

                    </div>

                    <!-- RIGHT -->
                    <div class="flex gap-2">

                        <button
                            type="button"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition"
                            @click="closeApplication"
                        >
                            Close
                        </button>

                        <button
                            v-if="selectedApplication?.status === 'pending'"
                            type="button"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition"
                            @click="approveApplication"
                        >
                            Approve
                        </button>

                    </div>

                </div>

            </template>

        </DialogModal>

    </AdminLayout>
</template>