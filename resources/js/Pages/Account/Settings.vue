<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ addresses: { type: Array, default: () => [] } });

const showForm = ref(false);
const form = useForm({
    full_name: '', phone: '', line1: '', line2: '', city: '', province: '', postal_code: '', is_default: false,
});

const submit = () => {
    form.post(route('account.addresses.store'), {
        onSuccess: () => { showForm.value = false; form.reset(); },
    });
};

const deleteAddress = (id) => {
    if (confirm('Remove this address?')) router.delete(route('account.addresses.destroy', id));
};
</script>

<template>
    <GuestLayout>
        <Head title="Account Settings" />

        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
            <h1 class="text-xl font-semibold text-[#101114] mb-2">Account Settings</h1>
            <p class="text-sm text-[#8A8B90] mb-8">
                Profile info and password are managed via
                <Link :href="route('profile.show')" class="text-[#6D5EF8]">your Jetstream profile page</Link>.
            </p>

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-medium text-[#101114]">Shipping Addresses</h2>
                <button @click="showForm = !showForm" class="text-xs text-[#6D5EF8] font-medium">
                    {{ showForm ? 'Cancel' : '+ Add Address' }}
                </button>
            </div>

            <form v-if="showForm" @submit.prevent="submit" class="mb-6 p-4 border border-[#E4E4E0] rounded-xl space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <input v-model="form.full_name" placeholder="Full name" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                    <input v-model="form.phone" placeholder="Phone" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                </div>
                <input v-model="form.line1" placeholder="Address line 1" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <input v-model="form.line2" placeholder="Address line 2 (optional)" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <div class="grid grid-cols-3 gap-3">
                    <input v-model="form.city" placeholder="City" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                    <input v-model="form.province" placeholder="Province" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                    <input v-model="form.postal_code" placeholder="Postal code" class="h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                </div>
                <label class="flex items-center gap-2 text-sm text-[#5F5E5A]">
                    <input v-model="form.is_default" type="checkbox" /> Set as default
                </label>
                <button type="submit" :disabled="form.processing" class="h-10 px-4 rounded-lg bg-[#101114] text-white text-sm">Save Address</button>
            </form>

            <div v-for="addr in addresses" :key="addr.id" class="flex items-start justify-between p-4 border border-[#E4E4E0] rounded-xl mb-3">
                <div class="text-sm">
                    <p class="font-medium">{{ addr.full_name }} — {{ addr.phone }} <span v-if="addr.is_default" class="text-xs text-[#6D5EF8]">(Default)</span></p>
                    <p class="text-[#8A8B90]">{{ addr.line1 }}, {{ addr.city }}, {{ addr.province }} {{ addr.postal_code }}</p>
                </div>
                <button @click="deleteAddress(addr.id)" class="text-xs text-red-500">Remove</button>
            </div>
        </div>
    </GuestLayout>
</template>
