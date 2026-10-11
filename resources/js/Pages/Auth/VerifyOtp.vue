<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticationCard from '@/Components/AuthenticationCard.vue'
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue'
import InputError from '@/Components/InputError.vue'

import {
    PinInput,
    PinInputGroup,
    PinInputSlot,
} from '@/Components/ui/pin-input'

import { Button } from '@/Components/ui/button'

const props = defineProps({
    email: String,
})

const form = useForm({
    email: props.email,
    otp: '',
})

function submit() {
    form
        .transform((data) => ({
            ...data,
            otp: Array.isArray(data.otp)
                ? data.otp.join('')
                : data.otp,
        }))
        .post(route('otp.verify'))
}
</script>

<template>
    <Head title="Verify OTP" />

    <AuthenticationCard>

        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="text-center mb-8">

            <h1 class="text-2xl font-bold">
                Verify OTP
            </h1>

            <p class="mt-2 text-sm text-gray-500">
                Enter the 6-digit verification code sent to
            </p>

            <p class="font-semibold mt-1 break-all">
                {{ email }}
            </p>

        </div>

        <form
            class="space-y-6"
            @submit.prevent="submit"
        >

            <div class="flex justify-center">

                <PinInput
                    v-model="form.otp"
                    :length="6"
                >
                    <PinInputGroup class="gap-2">

                        <PinInputSlot
                            v-for="i in 6"
                            :key="i"
                            :index="i - 1"
                            class="h-12 w-12 rounded-lg border"
                        />

                    </PinInputGroup>
                </PinInput>

            </div>

            <InputError
                :message="form.errors.otp"
                class="text-center"
            />

            <Button
                class="w-full"
                type="submit"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Verifying...' : 'Verify OTP' }}
            </Button>

            <div class="text-center text-sm text-gray-500">

                Didn't receive the code?

                <button
                    type="button"
                    class="font-semibold text-violet-600 hover:underline"
                >
                    Resend OTP
                </button>

            </div>

            <div class="text-center">

                <Link
                    :href="route('password.request')"
                    class="text-sm text-violet-600 hover:underline"
                >
                    ← Change Email
                </Link>

            </div>

        </form>

    </AuthenticationCard>
</template>