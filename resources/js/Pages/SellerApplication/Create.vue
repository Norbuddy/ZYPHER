<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    existingApplication: { type: Object, default: null },
});

const form = useForm({
    business_name: '',
    business_email: '',
    contact_number: '',
    business_location: '',
    permit: null,
    product_legitimation: null,
    category_ids: [],
    reason: '',
});

const submit = () => {
    form.post(route('seller-application.store'), {
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Become a Seller" />

        <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
            <h1 class="text-2xl font-semibold text-[#101114] mb-2">Become a Seller</h1>
            <p class="text-sm text-[#8A8B90] mb-6">
                Submit your business details for review. Once approved, your shop will be created automatically.
            </p>

            <div
                v-if="existingApplication"
                class="mb-6 rounded-xl border p-4 text-sm"
                :class="{
                    'border-yellow-200 bg-yellow-50 text-yellow-700': existingApplication.status === 'pending',
                    'border-green-200 bg-green-50 text-green-700': existingApplication.status === 'approved',
                    'border-red-200 bg-red-50 text-red-700': existingApplication.status === 'rejected',
                }"
            >
                <p class="font-medium">
                    Your last application is: {{ existingApplication.status }}
                </p>
                <p v-if="existingApplication.status === 'rejected' && existingApplication.admin_note" class="mt-1">
                    Reason: {{ existingApplication.admin_note }}
                </p>
                <p v-if="existingApplication.status === 'pending'" class="mt-1">
                    We'll notify you once an admin reviews your application.
                </p>
            </div>

            <form v-if="!existingApplication || existingApplication.status === 'rejected'" @submit.prevent="submit" class="bg-white p-6 rounded-xl border border-[#E4E4E0] space-y-4">
                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Business Name</label>
                    <input v-model="form.business_name" type="text" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                    <p v-if="form.errors.business_name" class="text-xs text-red-600 mt-1">{{ form.errors.business_name }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Business Email</label>
                    <input v-model="form.business_email" type="email" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                    <p v-if="form.errors.business_email" class="text-xs text-red-600 mt-1">{{ form.errors.business_email }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Contact Number</label>
                    <input v-model="form.contact_number" type="text" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                    <p v-if="form.errors.contact_number" class="text-xs text-red-600 mt-1">{{ form.errors.contact_number }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Business Location</label>
                    <textarea v-model="form.business_location" rows="2" class="w-full px-3 py-2 rounded-lg border border-[#E4E4E0] text-sm"></textarea>
                    <p v-if="form.errors.business_location" class="text-xs text-red-600 mt-1">{{ form.errors.business_location }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Categories you plan to sell</label>
                    <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-[#E4E4E0] rounded-lg p-3">
                        <label v-for="cat in categories" :key="cat.id" class="flex items-center gap-2 text-sm">
                            <input type="checkbox" :value="cat.id" v-model="form.category_ids" />
                            {{ cat.name }}
                        </label>
                    </div>
                    <p v-if="form.errors.category_ids" class="text-xs text-red-600 mt-1">{{ form.errors.category_ids }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Business Permit (image or PDF)</label>
                    <input type="file" accept="image/*,.pdf" @change="form.permit = $event.target.files[0]" class="text-sm" />
                    <p v-if="form.errors.permit" class="text-xs text-red-600 mt-1">{{ form.errors.permit }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Product Legitimation Document (image or PDF)</label>
                    <input type="file" accept="image/*,.pdf" @change="form.product_legitimation = $event.target.files[0]" class="text-sm" />
                    <p v-if="form.errors.product_legitimation" class="text-xs text-red-600 mt-1">{{ form.errors.product_legitimation }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Anything else you'd like us to know (optional)</label>
                    <textarea v-model="form.reason" rows="3" class="w-full px-3 py-2 rounded-lg border border-[#E4E4E0] text-sm"></textarea>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full h-11 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white font-medium text-sm disabled:opacity-50"
                >
                    Submit Application
                </button>
            </form>
        </div>
    </GuestLayout>
</template>