<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import DataTable from '@/Components/DataTable.vue'

const props = defineProps({
    vouchers: {
        type: Array,
        default: () => [],
    },
})

const showForm = ref(false)

const form = useForm({
    code: '',
    discount_type: 'percentage',
    discount_value: '',
    min_spend: '',
    max_discount_amount: '',
    usage_limit_total: '',
    usage_limit_per_user: 1,
    starts_at: '',
    expires_at: '',
})

const submit = () => {
    form.post(route('admin.vouchers.store'), {
        onSuccess: () => {
            showForm.value = false
            form.reset()
            form.discount_type = 'percentage'
            form.usage_limit_per_user = 1
        },
    })
}

const formatDiscount = (voucher) => {
    if (voucher.discount_type === 'percentage') {
        return `${voucher.discount_value}%`
    }

    return `₱${Number(voucher.discount_value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`
}

const formatStatus = (status) => {
    if (!status) return '—'

    return status
        .replaceAll('_', ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

const statusClass = (status) => {
    return {
        active:
            'bg-green-50 text-green-700 border-green-200',

        inactive:
            'bg-gray-50 text-gray-600 border-gray-200',

        expired:
            'bg-red-50 text-red-700 border-red-200',

        scheduled:
            'bg-blue-50 text-blue-700 border-blue-200',

        disabled:
            'bg-gray-50 text-gray-600 border-gray-200',
    }[status] ?? 'bg-gray-50 text-gray-600 border-gray-200'
}

const voucherColumns = [
    {
        key: 'code',
        label: 'Code',
    },

    {
        key: 'discount',
        label: 'Discount',
    },

    {
        key: 'min_spend',
        label: 'Min. Spend',
    },

    {
        key: 'usage',
        label: 'Usage',
    },

    {
        key: 'status',
        label: 'Status',
    },
]
</script>

<template>
    <AdminLayout>

        <Head title="Platform Vouchers" />

        <!-- HEADER -->
        <div class="flex items-center justify-between mb-6">

            <div>
                <h1 class="text-xl font-semibold text-[#101114]">
                    Platform Vouchers
                </h1>

                <p class="text-sm text-[#8A8B90] mt-1">
                    Manage promotional vouchers available across the platform.
                </p>
            </div>

            <button
                type="button"
                @click="showForm = !showForm"
                class="h-10 px-4 rounded-lg bg-[#101114] text-white text-sm font-medium hover:bg-[#2b2b2b] transition"
            >
                {{ showForm ? 'Cancel' : '+ Create Voucher' }}
            </button>

        </div>


        <!-- CREATE VOUCHER FORM -->
        <form
            v-if="showForm"
            @submit.prevent="submit"
            class="mb-6 p-5 bg-white border border-[#E4E4E0] rounded-xl space-y-4"
        >

            <div>
                <h2 class="text-sm font-semibold text-[#101114]">
                    Create Platform Voucher
                </h2>

                <p class="text-xs text-[#8A8B90] mt-1">
                    Create a voucher that customers can use on the platform.
                </p>
            </div>


            <!-- CODE + TYPE -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                        Voucher Code
                    </label>

                    <input
                        v-model="form.code"
                        type="text"
                        placeholder="e.g. ZYPHER50"
                        class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm uppercase"
                    />

                    <p
                        v-if="form.errors.code"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ form.errors.code }}
                    </p>
                </div>


                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                        Discount Type
                    </label>

                    <select
                        v-model="form.discount_type"
                        class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                    >
                        <option value="percentage">
                            Percentage %
                        </option>

                        <option value="fixed_amount">
                            Fixed Amount ₱
                        </option>
                    </select>

                    <p
                        v-if="form.errors.discount_type"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ form.errors.discount_type }}
                    </p>
                </div>

            </div>


            <!-- DISCOUNT VALUES -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                        Discount Value
                    </label>

                    <input
                        v-model="form.discount_value"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Discount value"
                        class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                    />

                    <p
                        v-if="form.errors.discount_value"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ form.errors.discount_value }}
                    </p>
                </div>


                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                        Minimum Spend
                    </label>

                    <input
                        v-model="form.min_spend"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Min spend"
                        class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                    />

                    <p
                        v-if="form.errors.min_spend"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ form.errors.min_spend }}
                    </p>
                </div>


                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                        Maximum Discount
                    </label>

                    <input
                        v-model="form.max_discount_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Max discount cap"
                        class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                    />

                    <p
                        v-if="form.errors.max_discount_amount"
                        class="text-xs text-red-500 mt-1"
                    >
                        {{ form.errors.max_discount_amount }}
                    </p>
                </div>

            </div>


            <!-- LIMITS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                        Total Usage Limit
                    </label>

                    <input
                        v-model="form.usage_limit_total"
                        type="number"
                        min="1"
                        placeholder="Unlimited if empty"
                        class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                    />
                </div>


                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                        Usage Limit Per User
                    </label>

                    <input
                        v-model="form.usage_limit_per_user"
                        type="number"
                        min="1"
                        class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                    />
                </div>

            </div>


            <!-- DATES -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                        Starts At
                    </label>

                    <input
                        v-model="form.starts_at"
                        type="datetime-local"
                        class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                    />
                </div>


                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                        Expires At
                    </label>

                    <input
                        v-model="form.expires_at"
                        type="datetime-local"
                        class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                    />
                </div>

            </div>


            <!-- SUBMIT -->
            <div class="flex justify-end pt-1">

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="h-10 px-5 rounded-lg bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white text-sm font-medium disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create Voucher' }}
                </button>

            </div>

        </form>


        <!-- VOUCHERS DATATABLE -->
        <DataTable
            tableId="admin-platform-vouchers"
            :rows="vouchers"
            :columns="voucherColumns"
            :searchable="true"
            search-placeholder="Search vouchers..."
            :paginated="true"
            :per-page="10"
        >

            <!-- CODE -->
            <template #cell-code="{ row }">

                <div class="min-w-[150px]">

                    <p class="font-semibold text-[#101114]">
                        {{ row.code }}
                    </p>

                </div>

            </template>


            <!-- DISCOUNT -->
            <template #cell-discount="{ row }">

                <span class="font-medium text-[#101114]">
                    {{ formatDiscount(row) }}
                </span>

            </template>


            <!-- MIN SPEND -->
            <template #cell-min_spend="{ row }">

                <span class="text-sm text-[#101114]">
                    {{
                        row.min_spend
                            ? `₱${Number(row.min_spend).toLocaleString('en-PH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            })}`
                            : '—'
                    }}
                </span>

            </template>


            <!-- USAGE -->
            <template #cell-usage="{ row }">

                <span class="text-sm text-[#101114]">
                    {{
                        row.usage_limit_total
                            ? `Limited to ${row.usage_limit_total}`
                            : 'Unlimited'
                    }}
                </span>

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

        </DataTable>

    </AdminLayout>
</template>