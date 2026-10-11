<script setup>
import { ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import DataTable from '@/Components/DataTable.vue'
import DialogModal from '@/Components/DialogModal.vue'

const props = defineProps({
    applications: {
        type: Array,
        default: () => [],
    },
})

const selectedApplication = ref(null)

const viewApplication = (application) => {
    selectedApplication.value = application
}

const closeApplication = () => {
    selectedApplication.value = null
}

const approveApplication = () => {
    if (!selectedApplication.value) return

    if (
        !confirm(
            'Approve this seller application? This will create their shop.'
        )
    ) {
        return
    }

    router.patch(
        route(
            'admin.seller-application.approve',
            selectedApplication.value.id
        ),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                selectedApplication.value = null
            },
        }
    )
}

const rejectApplication = () => {
    if (!selectedApplication.value) return

    const note = prompt('Reason for rejection (optional):')

    if (note === null) {
        return
    }

    router.patch(
        route(
            'admin.seller-application.reject',
            selectedApplication.value.id
        ),
        {
            admin_note: note,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                selectedApplication.value = null
            },
        }
    )
}

const statusClass = (status) => {
    return {
        pending:
            'bg-yellow-50 text-yellow-700 border-yellow-200',

        approved:
            'bg-green-50 text-green-700 border-green-200',

        rejected:
            'bg-red-50 text-red-700 border-red-200',
    }[status] ?? 'bg-gray-50 text-gray-600 border-gray-200'
}

const formatStatus = (status) => {
    if (!status) return '—'

    return status
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const formatDate = (date) => {
    if (!date) return '—'

    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}

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
        key: 'created_at',
        label: 'Applied',
    },

    {
        key: 'actions',
        label: 'Actions',
    },
]
</script>

<template>
    <AdminLayout>

        <Head title="Seller Applications" />

        <div class="space-y-6">

            <!-- HEADER -->
            <div>
                <h1 class="text-xl font-semibold text-[#101114]">
                    Seller Applications
                </h1>

                <p class="mt-1 text-sm text-[#8A8B90]">
                    Review seller applications before approval.
                </p>
            </div>


            <!-- DATA TABLE -->
            <DataTable
                tableId="admin-seller-applications"
                :rows="applications"
                :columns="applicationColumns"
                :searchable="true"
                search-placeholder="Search seller applications..."
                :paginated="true"
                :per-page="10"
            >

                <!-- BUSINESS -->
                <template #cell-business_name="{ row }">

                    <div class="min-w-[220px]">

                        <p class="font-medium text-[#101114]">
                            {{ row.business_name || '—' }}
                        </p>

                        <p
                            v-if="row.business_email"
                            class="text-[11px] text-[#8A8B90] mt-1"
                        >
                            {{ row.business_email }}
                        </p>

                    </div>

                </template>


                <!-- APPLICANT -->
                <template #cell-applicant="{ row }">

                    <div>

                        <p class="text-sm text-[#101114]">
                            {{ row.user?.name || '—' }}
                        </p>

                        <p
                            v-if="row.user?.email"
                            class="text-[11px] text-[#8A8B90] mt-1"
                        >
                            {{ row.user.email }}
                        </p>

                    </div>

                </template>


                <!-- STATUS -->
                <template #cell-status="{ row }">

                    <span
                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase"
                        :class="statusClass(row.status)"
                    >
                        {{ formatStatus(row.status) }}
                    </span>

                </template>


                <!-- APPLIED DATE -->
                <template #cell-created_at="{ row }">

                    <span class="text-sm text-[#101114] whitespace-nowrap">
                        {{ formatDate(row.created_at) }}
                    </span>

                </template>


                <!-- ACTIONS -->
                <template #cell-actions="{ row }">

                    <button
                        type="button"
                        @click="viewApplication(row)"
                        class="rounded-lg border border-[#E4E4E0] px-3 py-1.5 text-xs font-medium text-[#101114] hover:bg-[#F7F7F5] transition"
                    >
                        View
                    </button>

                </template>

            </DataTable>

        </div>


        <!-- SELLER APPLICATION MODAL -->
        <DialogModal
            :show="selectedApplication !== null"
            @close="closeApplication"
        >

            <!-- TITLE -->
            <template #title>
                Seller Application
            </template>


            <!-- CONTENT -->
            <template #content>

                <div
                    v-if="selectedApplication"
                    class="space-y-5"
                >

                    <!-- BUSINESS NAME -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-1">
                            Business Name
                        </p>

                        <p class="font-semibold text-[#101114]">
                            {{ selectedApplication.business_name || '—' }}
                        </p>

                    </div>


                    <!-- APPLICANT -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-1">
                            Applicant
                        </p>

                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.user?.name || '—' }}
                        </p>

                        <p
                            v-if="selectedApplication.user?.email"
                            class="text-xs text-[#8A8B90] mt-1"
                        >
                            {{ selectedApplication.user.email }}
                        </p>

                    </div>


                    <!-- BUSINESS EMAIL -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-1">
                            Business Email
                        </p>

                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.business_email || '—' }}
                        </p>

                    </div>


                    <!-- CONTACT NUMBER -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-1">
                            Contact Number
                        </p>

                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.contact_number || '—' }}
                        </p>

                    </div>


                    <!-- BUSINESS LOCATION -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-1">
                            Business Location
                        </p>

                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.business_location || '—' }}
                        </p>

                    </div>


                    <!-- REQUESTED CATEGORIES -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-1">
                            Requested Categories
                        </p>

                        <p class="text-sm text-[#101114]">
                            {{
                                (
                                    selectedApplication.requested_category_names ||
                                    []
                                ).join(', ') || '—'
                            }}
                        </p>

                    </div>


                    <!-- REASON -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-1">
                            Reason
                        </p>

                        <p class="text-sm text-[#101114] whitespace-pre-line">
                            {{ selectedApplication.reason || '—' }}
                        </p>

                    </div>


                    <!-- STATUS -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-2">
                            Application Status
                        </p>

                        <span
                            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase"
                            :class="
                                statusClass(
                                    selectedApplication.status
                                )
                            "
                        >
                            {{ formatStatus(selectedApplication.status) }}
                        </span>

                    </div>


                    <!-- BUSINESS PERMIT -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-2">
                            Business Permit
                        </p>

                        <a
                            v-if="selectedApplication.permit_path"
                            :href="`/${selectedApplication.permit_path}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded-lg bg-[#101114] px-4 py-2 text-xs font-medium text-white hover:bg-[#2b2b2b] transition"
                        >
                            View Uploaded Permit
                        </a>

                        <p
                            v-else
                            class="text-sm text-[#8A8B90]"
                        >
                            No permit uploaded.
                        </p>

                    </div>


                    <!-- PRODUCT LEGITIMATION -->
                    <div>

                        <p class="text-xs text-[#8A8B90] mb-2">
                            Product Legitimation
                        </p>

                        <a
                            v-if="selectedApplication.product_legitimation_path"
                            :href="`/${selectedApplication.product_legitimation_path}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded-lg bg-[#101114] px-4 py-2 text-xs font-medium text-white hover:bg-[#2b2b2b] transition"
                        >
                            View Uploaded Document
                        </a>

                        <p
                            v-else
                            class="text-sm text-[#8A8B90]"
                        >
                            No document uploaded.
                        </p>

                    </div>


                    <!-- ADMIN NOTE -->
                    <div
                        v-if="selectedApplication.admin_note"
                    >

                        <p class="text-xs text-[#8A8B90] mb-1">
                            Admin Note
                        </p>

                        <div class="rounded-lg bg-[#F7F7F5] p-3">

                            <p class="text-sm text-[#101114] whitespace-pre-line">
                                {{ selectedApplication.admin_note }}
                            </p>

                        </div>

                    </div>

                </div>

            </template>


            <!-- FOOTER -->
            <template #footer>

                <div
                    class="flex w-full items-center justify-between gap-3"
                >

                    <!-- REJECT -->
                    <div>

                        <button
                            v-if="
                                selectedApplication?.status === 'pending'
                            "
                            type="button"
                            @click="rejectApplication"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition"
                        >
                            Reject
                        </button>

                    </div>


                    <!-- RIGHT ACTIONS -->
                    <div class="flex items-center gap-2">

                        <button
                            type="button"
                            @click="closeApplication"
                            class="rounded-lg border border-[#E4E4E0] px-4 py-2 text-sm font-medium text-[#101114] hover:bg-[#F7F7F5] transition"
                        >
                            Close
                        </button>


                        <button
                            v-if="
                                selectedApplication?.status === 'pending'
                            "
                            type="button"
                            @click="approveApplication"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition"
                        >
                            Approve
                        </button>

                    </div>

                </div>

            </template>

        </DialogModal>

    </AdminLayout>
</template>