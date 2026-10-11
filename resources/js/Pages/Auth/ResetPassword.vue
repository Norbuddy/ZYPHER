<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticationCard from '@/Components/AuthenticationCard.vue'
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const props = defineProps({
    email: String,
    token: String,
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation')
        },
    })
}
</script>

<template>
    <Head title="Create New Password" />

    <AuthenticationCard>

        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="text-center mb-6">

            <h1 class="text-2xl font-bold">
                Create New Password
            </h1>

            <p class="mt-2 text-sm text-gray-500">
                Your new password must be different from your previous password.
            </p>

        </div>

        <form
            class="space-y-5"
            @submit.prevent="submit"
        >

            <div>

                <InputLabel
                    for="email"
                    value="Email Address"
                />

                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-2 block w-full bg-gray-100"
                    readonly
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.email"
                />

            </div>

            <div>

                <InputLabel
                    for="password"
                    value="New Password"
                />

                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-2 block w-full"
                    placeholder="Enter your new password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password"
                />

            </div>

            <div>

                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-2 block w-full"
                    placeholder="Confirm your password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />

            </div>

            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Updating...' : 'Reset Password' }}
            </PrimaryButton>

        </form>

    </AuthenticationCard>
</template>