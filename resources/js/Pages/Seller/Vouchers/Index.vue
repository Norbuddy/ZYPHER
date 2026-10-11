<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ vouchers: Array });

const showForm = ref(false);
const form = useForm({
    code: '', discount_type: 'percentage', discount_value: '', min_spend: '', max_discount_amount: '',
    usage_limit_total: '', usage_limit_per_user: 1, starts_at: '', expires_at: '',
});

const submit = () => {
    form.post(route('seller.vouchers.store'), {
        onSuccess: () => { showForm.value = false; form.reset(); },
    });
};
</script>

<template>
    <SellerLayout>
        <Head title="Vouchers" />

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-[#101114]">Shop Vouchers</h1>
            <button @click="showForm = !showForm" class="h-10 px-4 rounded-lg bg-[#101114] text-white text-sm font-medium">
                {{ showForm ? 'Cancel' : '+ Create Voucher' }}
            </button>
        </div>

        <form v-if="showForm" @submit.prevent="submit" class="mb-6 p-5 bg-white border border-[#E4E4E0] rounded-xl space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <input v-model="form.code" placeholder="Code (e.g. SHOP20)" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <select v-model="form.discount_type" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm">
                    <option value="percentage">Percentage %</option>
                    <option value="fixed_amount">Fixed Amount ₱</option>
                </select>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <input v-model="form.discount_value" type="number" placeholder="Discount value" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <input v-model="form.min_spend" type="number" placeholder="Min spend" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <input v-model="form.max_discount_amount" type="number" placeholder="Max discount cap" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input v-model="form.usage_limit_total" type="number" placeholder="Total usage limit" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <input v-model="form.usage_limit_per_user" type="number" placeholder="Per-user limit" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input v-model="form.starts_at" type="date" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <input v-model="form.expires_at" type="date" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
            </div>
            <button type="submit" :disabled="form.processing" class="h-10 px-4 rounded-lg bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white text-sm font-medium">
                Create Voucher
            </button>
        </form>

        <div class="bg-white rounded-xl border border-[#E4E4E0] overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#F7F7F5] text-xs text-[#8A8B90] uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Code</th>
                        <th class="text-left px-4 py-3">Discount</th>
                        <th class="text-left px-4 py-3">Min Spend</th>
                        <th class="text-left px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="v in vouchers" :key="v.id" class="border-t border-[#EAEAE5]">
                        <td class="px-4 py-3 font-medium">{{ v.code || 'Auto-applied' }}</td>
                        <td class="px-4 py-3">{{ v.discount_type === 'percentage' ? v.discount_value + '%' : '₱' + v.discount_value }}</td>
                        <td class="px-4 py-3">₱{{ v.min_spend }}</td>
                        <td class="px-4 py-3 capitalize">{{ v.status }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </SellerLayout>
</template>
