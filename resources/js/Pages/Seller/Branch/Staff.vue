<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showForm = ref(false);
const selectedStaff = ref(null);
const actionMenuStaff = ref(null);
const props = defineProps({
    branch: Object,
    staff: {
        type: Array,
        default: () => [],
    },
    sales: {
        type: Array,
        default: () => [],
    },
});
const form = useForm({
    name: '',
    email: '',
    contact_number: '',
});

const submit = () => {
    form.post(route('branch.staff.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const toggleSuspension = (member) => {
    actionMenuStaff.value = null;
    router.patch(route('branch.staff.suspension', member.id), {
        is_suspended: !member.is_suspended,
    }, { preserveScroll: true });
};

const deleteStaff = (member) => {
    actionMenuStaff.value = null;
    if (!window.confirm(`Delete ${member.name}'s account? This cannot be undone.`)) {
        return;
    }

    router.delete(route('branch.staff.destroy', member.id), { preserveScroll: true });
};

const salesFor = (member) => props.sales.filter((sale) => sale.user_id === member.id);

const viewSales = (member) => {
    selectedStaff.value = member;
    actionMenuStaff.value = null;
};

const toggleActionMenu = (member) => {
    actionMenuStaff.value = actionMenuStaff.value?.id === member.id ? null : member;
};
</script>

<template>
    <SellerLayout>
        <Head title="Branch Staff" />

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-semibold text-[#101114]">Branch Staff</h1>
                <p class="mt-1 text-sm text-[#8A8B90]">Manage cashiers for this branch.</p>
            </div>
            <button type="button" @click="showForm = !showForm" class="rounded-lg bg-[#101114] px-4 py-2.5 text-sm font-medium text-white">
                Add Cashier / Staff
            </button>
        </div>

        <form v-if="showForm" @submit.prevent="submit" class="mb-6 rounded-xl border border-[#E4E4E0] bg-white p-6 space-y-4">
            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Cashier Name</label>
                <input v-model="form.name" required class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Email / Username</label>
                <input v-model="form.email" type="email" required class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <p class="text-xs text-[#8A8B90] mt-1">This email receives the credentials. A unique login alias is generated if it is already in use.</p>
                <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Contact Number</label>
                <input v-model="form.contact_number" required class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <p v-if="form.errors.contact_number" class="text-xs text-red-500 mt-1">{{ form.errors.contact_number }}</p>
            </div>
            <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#101114] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50">
                {{ form.processing ? 'Creating...' : 'Create Cashier' }}
            </button>
        </form>

        <div class="mb-6 overflow-visible rounded-xl border border-[#E4E4E0] bg-white">
            <div v-if="!staff.length" class="p-6 text-sm text-[#8A8B90]">No cashiers have been added yet.</div>
            <table v-else class="w-full text-left text-sm">
                <thead class="border-b border-[#E4E4E0] text-xs uppercase text-[#8A8B90]">
                    <tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Email</th><th class="px-5 py-3">Contact</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Sales</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Actions</th></tr>
                </thead>
                <tbody>
                    <tr v-for="member in staff" :key="member.id" class="border-b border-[#F0F0ED] last:border-0">
                        <td class="px-5 py-4 font-medium">{{ member.name }}</td>
                        <td class="px-5 py-4">{{ member.email }}</td>
                        <td class="px-5 py-4">{{ member.contact_number }}</td>
                        <td class="px-5 py-4 capitalize">{{ member.role.replace('_', ' ') }}</td>
                        <td class="px-5 py-4">
                            <div>{{ member.sales_count }} sale{{ member.sales_count === 1 ? '' : 's' }}</div>
                            <div class="text-xs text-[#8A8B90]">₱{{ Number(member.sales_total).toFixed(2) }}</div>
                        </td>
                        <td class="px-5 py-4">
                            <span :class="member.is_suspended ? 'text-red-600' : 'text-green-600'" class="font-medium">
                                {{ member.is_suspended ? 'Suspended' : 'Active' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="relative inline-block text-left">
                                <button type="button" @click="toggleActionMenu(member)" class="rounded-lg border border-[#E4E4E0] px-3 py-1.5 text-xs font-medium">
                                    Actions
                                </button>
                                <div v-if="actionMenuStaff?.id === member.id" class="absolute right-0 z-10 mt-2 w-40 rounded-lg border border-[#E4E4E0] bg-white p-1 shadow-lg">
                                    <button type="button" @click="viewSales(member)" class="block w-full rounded-md px-3 py-2 text-left text-xs hover:bg-[#F7F7F5]">View Sales</button>
                                    <button type="button" @click="toggleSuspension(member)" class="block w-full rounded-md px-3 py-2 text-left text-xs hover:bg-[#F7F7F5]">{{ member.is_suspended ? 'Reactivate' : 'Suspend' }}</button>
                                    <button type="button" @click="deleteStaff(member)" class="block w-full rounded-md px-3 py-2 text-left text-xs text-red-600 hover:bg-red-50">Delete</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="selectedStaff" class="overflow-hidden rounded-xl border border-[#E4E4E0] bg-white">
            <div class="flex items-center justify-between border-b border-[#E4E4E0] px-5 py-4">
                <div>
                    <h2 class="font-semibold text-[#101114]">Sales by {{ selectedStaff.name }}</h2>
                    <p class="mt-1 text-xs text-[#8A8B90]">{{ selectedStaff.sales_count }} transaction{{ selectedStaff.sales_count === 1 ? '' : 's' }} · ₱{{ Number(selectedStaff.sales_total).toFixed(2) }} total</p>
                </div>
                <button type="button" @click="selectedStaff = null" class="text-sm text-[#8A8B90] hover:text-[#101114]">Close</button>
            </div>
            <div v-if="!salesFor(selectedStaff).length" class="p-5 text-sm text-[#8A8B90]">No sales recorded for this cashier yet.</div>
            <div v-else class="divide-y divide-[#F0F0ED]">
                <div v-for="sale in salesFor(selectedStaff)" :key="sale.id" class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-medium">{{ sale.sale_number }}</p>
                        <p class="text-xs text-[#8A8B90]">{{ sale.created_at }} · {{ sale.payment_method }}</p>
                        <p class="mt-1 text-xs text-[#5F5E5A]">{{ sale.items.map((item) => `${item.product_name} x${item.quantity}`).join(', ') }}</p>
                    </div>
                    <span class="font-semibold">₱{{ Number(sale.total_amount).toFixed(2) }}</span>
                </div>
            </div>
        </div>
    </SellerLayout>
</template>
