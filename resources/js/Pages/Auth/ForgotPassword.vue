<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AuthenticationCard from '@/Components/AuthenticationCard.vue'
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

defineProps({
    status: String,
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'), {
        onSuccess: () => {
            router.visit(route('otp.form', {
                email: form.email,
            }))
        },
    })
}
</script>

<template>
    <Head title="Forgot Password" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                Forgot Password
            </h1>

            <p class="mt-2 text-sm text-gray-500 leading-relaxed">
                Enter the email address associated with your account.
                We'll send you a secure 6-digit OTP to reset your password.
            </p>
        </div>

        <div
            v-if="status"
            class="mb-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit">

            <div>
                <InputLabel
                    for="email"
                    value="Email Address"
                />

                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-2 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Enter your email"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.email"
                />
            </div>

            <div class="mt-6">

                <PrimaryButton
                    class="w-full justify-center"
                    :disabled="form.processing"
                    :class="{
                        'opacity-50': form.processing
                    }"
                >
                    {{ form.processing ? 'Sending OTP...' : 'Send OTP' }}
                </PrimaryButton>

            </div>

            <div class="mt-6 text-center">

                <Link
                    :href="route('login')"
                    class="text-sm text-violet-600 hover:text-violet-700 hover:underline"
                >
                    ← Back to Login
                </Link>

            </div>

        </form>

    </AuthenticationCard>
</template>