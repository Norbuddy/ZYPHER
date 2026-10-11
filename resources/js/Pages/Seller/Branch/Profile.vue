<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <SellerLayout>
        <Head title="Branch Profile" />

        <div class="max-w-3xl">
            <h1 class="text-xl font-semibold text-[#101114]">Branch Profile</h1>
            <p class="mt-1 text-sm text-[#8A8B90]">Keep your account details secure.</p>

            <div class="mt-6 rounded-xl border border-[#E4E4E0] bg-white p-6">
                <UpdateProfileInformationForm :user="$page.props.auth.user" />
            </div>

            <div class="mt-6 rounded-xl border border-[#E4E4E0] bg-white p-6">
                <UpdatePasswordForm />
            </div>
        </div>
    </SellerLayout>
</template>
