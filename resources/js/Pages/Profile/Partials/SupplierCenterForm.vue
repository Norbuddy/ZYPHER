<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

import ActionSection from '@/Components/ActionSection.vue'
import DialogModal from '@/Components/DialogModal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const showApplicationModal = ref(false)

const form = useForm({
    business_name: '',
    business_email: '',
    contact_number: '',
    business_address: '',
    reason: '',
    permit: null,
})

const permitFile = ref(null)
const choosePermit = (event) => {
     permitFile.value = event.target.files[0]
     form.permit = event.target.files[0]
}

const submitApplication = () => {
    form.post(route('supplier.apply'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showApplicationModal.value = false
            form.reset()
            permitFile.value = null
        },
    })
}
</script>

<template>
    <ActionSection>
        <template #title>
            Supplier Center
        </template>

        <template #description>
            Apply to become a supplier on Zypher.
        </template>

        <template #content>
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    As a supplier, you can provide products to sellers across the marketplace.
                    Your application will be reviewed by the administrator before approval.
                </p>

                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                    <p class="font-semibold text-yellow-700">
                        Status: Not a Supplier
                    </p>

                    <p class="mt-1 text-sm text-yellow-600">
                        You have not applied as a supplier yet.
                    </p>
                </div>

                <PrimaryButton @click="showApplicationModal = true">
                Apply as Supplier
                </PrimaryButton>
            </div>
        </template>
    </ActionSection>

    <DialogModal :show="showApplicationModal" @close="showApplicationModal = false">
        <template #title>
            Apply as Supplier
        </template>

        <template #content>
            <div class="space-y-4">

                <div>
                    <InputLabel value="Business Name" />
                   <TextInput
                        v-model="form.business_name"
                        class="mt-1 block w-full"
                        placeholder="Enter your business name"
                    />
                </div>

                <div>
                    <InputLabel value="Business Email" />

                    <TextInput
                        v-model="form.business_email"
                        type="email"
                        class="mt-1 block w-full"
                        placeholder="business@email.com"
                    />
                </div>

                <div>
                    <InputLabel value="Contact Number" />
                    <TextInput
                        v-model="form.contact_number"
                        class="mt-1 block w-full"
                        placeholder="09XXXXXXXXX"
                    />
                </div>

                <div>
                    <InputLabel value="Business Address" />
                    <textarea
                        v-model="form.business_address"
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    ></textarea>
                </div>

                <div>
                    <InputLabel value="Reason for Applying" />
                    <textarea
                        v-model="form.reason"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    ></textarea>
                </div>

                <div>
                <InputLabel value="Business Permit / Business Registration" />

                <div class="mt-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center">
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M19.5 14.25v2.25A2.25 2.25 0 0117.25 18.75H6.75A2.25 2.25 0 014.5 16.5v-2.25M16.5 8.25L12 3.75m0 0L7.5 8.25M12 3.75V15"
                        />
                    </svg>

                    <p class="mt-3 text-sm font-medium text-gray-700">
                        Upload your Business Permit
                    </p>

                    <p class="text-xs text-gray-500 mt-1">
                        JPG, PNG or PDF • Maximum 5MB
                    </p>

                    <input
                        type="file"
                        accept=".jpg,.jpeg,.png,.pdf"
                        @change="choosePermit"
                        class="mt-4 block w-full text-sm text-gray-600
                            file:mr-4 file:rounded-md
                            file:border-0 file:bg-indigo-600
                            file:px-4 file:py-2
                            file:text-white
                            hover:file:bg-indigo-700"
                    />
                </div>
                <div
                    v-if="permitFile"
                    class="mt-4 rounded-lg bg-green-50 border border-green-200 p-3"
                >
                    <div class="flex justify-between items-center">

                        <div>
                            <p class="text-sm font-semibold text-green-700">
                                ✔ File Selected
                            </p>

                            <p class="text-sm text-gray-700">
                                {{ permitFile.name }}
                            </p>

                            <p class="text-xs text-gray-500">
                                {{ (permitFile.size / 1024 / 1024).toFixed(2) }} MB
                            </p>
                        </div>

                        <button
                            class="text-red-500 text-sm hover:underline"
                            @click="permitFile = null"
                        >
                            Remove
                        </button>

                    </div>
                </div>
            </div>

                    </div>
        </template>

        <template #footer>
            <SecondaryButton @click="showApplicationModal = false">
                Cancel
            </SecondaryButton>

            <PrimaryButton class="ms-3" @click="submitApplication" :disabled="form.processing">
                {{ form.processing ? 'Submitting...' : 'Submit Application' }}
            </PrimaryButton>
        </template>
    </DialogModal>
</template>