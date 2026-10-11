<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import DialogModal from '@/Components/DialogModal.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    applications: {
        type: Array,
        default: () => [],
    },
});

const selectedApplication = ref(null);
const mapElement = ref(null);
let map = null;
let marker = null;

const applicationColumns = [
    {
        key: 'branch_name',
        label: 'Branch',
        searchKeys: ['name', 'phone', 'address'],
    },
    {
        key: 'applicant',
        label: 'Applicant',
        searchKeys: ['user.name', 'user.email'],
    },
    {
        key: 'status',
        label: 'Status',
        searchKeys: ['status'],
    },
    {
        key: 'created_at',
        label: 'Submitted',
        searchKeys: ['created_at'],
    },
    {
        key: 'actions',
        label: 'Actions',
        searchKeys: [],
    },
];

const viewApplication = (application) => {
    selectedApplication.value = application;
};

const closeApplication = () => {
    selectedApplication.value = null;
    if (map) {
        map.remove();
        map = null;
        marker = null;
    }
};

const renderBranchMap = async () => {
    if (!selectedApplication.value || !mapElement.value) {
        return;
    }

    await nextTick();

    if (map) {
        map.remove();
        map = null;
    }

    const latitude = Number(selectedApplication.value.latitude);
    const longitude = Number(selectedApplication.value.longitude);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return;
    }

    map = L.map(mapElement.value).setView([latitude, longitude], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    marker = L.marker([latitude, longitude]).addTo(map);
    marker.bindPopup(`<strong>${selectedApplication.value.name || 'Branch'}</strong><br>${selectedApplication.value.address || ''}`);
    marker.openPopup();
};

watch(
    () => selectedApplication.value,
    () => {
        renderBranchMap();
    },
    { flush: 'post' }
);

onBeforeUnmount(() => {
    if (map) {
        map.remove();
    }
});

const approveApplication = () => {
    if (!selectedApplication.value) {
        return;
    }

    if (!confirm('Approve this branch application?')) {
        return;
    }

    router.patch(
        route('admin.branch-application.approve', selectedApplication.value.id),
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

    if (note === null) {
        return;
    }

    router.patch(
        route('admin.branch-application.reject', selectedApplication.value.id),
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

const suspendApplication = () => {
    if (!selectedApplication.value || !confirm('Suspend this branch?')) {
        return;
    }

    router.patch(route('admin.branch-application.suspend', selectedApplication.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { selectedApplication.value = null; },
    });
};

const activateApplication = () => {
    if (!selectedApplication.value || !confirm('Activate this branch?')) {
        return;
    }

    router.patch(route('admin.branch-application.activate', selectedApplication.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { selectedApplication.value = null; },
    });
};

const deleteApplication = () => {
    if (!selectedApplication.value || !confirm('Delete this branch permanently?')) {
        return;
    }

    router.delete(route('admin.branch-application.destroy', selectedApplication.value.id), {
        preserveScroll: true,
        onSuccess: () => { selectedApplication.value = null; },
    });
};

const formatDate = (date) => {
    if (!date) {
        return '—';
    }

    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatDocumentUrl = (path) => {
    if (!path) {
        return '#';
    }

    return `/storage/${path}`;
};
</script>

<template>
    <AdminLayout>
        <Head title="Branch Applications" />

        <div class="space-y-6">
            <div>
                <h1 class="text-xl font-semibold text-[#101114]">
                    Branch Applications
                </h1>
                <p class="mt-1 text-sm text-[#8A8B90]">
                    Manage registered branches and their access.
                </p>
            </div>

            <DataTable
                table-id="admin-branch-applications"
                :columns="applicationColumns"
                :rows="applications"
                :searchable="true"
                search-placeholder="Search branch applications..."
                :paginated="true"
                :per-page="10"
            >
                <template #cell-branch_name="{ row }">
                    <div class="min-w-[220px]">
                        <p class="font-medium text-[#101114]">
                            {{ row.name || '—' }}
                        </p>
                        <p class="text-[11px] text-[#8A8B90] mt-1">
                            {{ row.phone || 'No phone' }}
                        </p>
                    </div>
                </template>

                <template #cell-applicant="{ row }">
                    <span class="text-sm text-[#101114]">
                        {{ row.user?.name || '—' }}
                    </span>
                </template>

                <template #cell-status="{ row }">
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase border"
                        :class="{
                            'bg-green-50 text-green-700 border-green-200': row.status === 'active',
                            'bg-red-50 text-red-700 border-red-200': row.status === 'suspended',
                        }"
                    >
                        {{ row.status || 'pending' }}
                    </span>
                </template>

                <template #cell-created_at="{ row }">
                    <span class="text-sm text-[#101114]">
                        {{ formatDate(row.created_at) }}
                    </span>
                </template>

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

        <DialogModal
            :show="selectedApplication !== null"
            @close="closeApplication"
        >
            <template #title>
                Branch Application
            </template>

            <template #content>
                <div v-if="selectedApplication" class="space-y-5">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Branch Name</p>
                        <p class="font-semibold text-[#101114]">
                            {{ selectedApplication.name || '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 mb-1">Applicant</p>
                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.user?.name || '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 mb-1">Phone</p>
                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.phone || '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 mb-1">Address</p>
                        <p class="text-sm text-[#101114] whitespace-pre-line">
                            {{ selectedApplication.address || '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 mb-1">Coordinates</p>
                        <p class="text-sm text-[#101114]">
                            {{ selectedApplication.latitude || '—' }}, {{ selectedApplication.longitude || '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 mb-2">Branch Location</p>

                        <div
                            v-if="selectedApplication.latitude && selectedApplication.longitude"
                            ref="mapElement"
                            class="h-64 w-full overflow-hidden rounded-xl border border-[#E4E4E0]"
                        ></div>

                        <p v-else class="text-sm text-gray-500">
                            No map coordinates available for this branch.
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 mb-2">BIR Registration Document</p>

                        <a
                            v-if="selectedApplication.bir_document_path"
                            :href="formatDocumentUrl(selectedApplication.bir_document_path)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-600 hover:bg-indigo-100 transition"
                        >
                            View Uploaded Document
                        </a>

                        <span v-else class="text-sm text-gray-500">
                            No document uploaded.
                        </span>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex w-full items-center justify-between">
                    <div>
                        <button
                            v-if="selectedApplication && ['active', 'approved'].includes(selectedApplication.status)"
                            type="button"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition"
                            @click="suspendApplication"
                        >
                            Suspend
                        </button>
                    </div>

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
                            @click="activateApplication"
                        >
                            Activate
                        </button>
                        <button
                            v-if="selectedApplication?.status === 'suspended'"
                            type="button"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition"
                            @click="activateApplication"
                        >
                            Activate
                        </button>
                        <button
                            v-if="selectedApplication"
                            type="button"
                            class="rounded-lg bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800 transition"
                            @click="deleteApplication"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </template>
        </DialogModal>
    </AdminLayout>
</template>
