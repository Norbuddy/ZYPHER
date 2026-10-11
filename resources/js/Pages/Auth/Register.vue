<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Create account" />

    <div class="min-h-screen w-full flex items-center justify-center bg-[#EDEDE8] p-4 sm:p-8">
        <div class="w-full max-w-5xl bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col md:flex-row min-h-[680px]">

            <div class="relative md:w-1/2 bg-[#101114] text-white p-10 sm:p-12 flex flex-col justify-between overflow-hidden">
                <svg class="absolute inset-0 w-full h-full opacity-[0.18]" viewBox="0 0 500 640" preserveAspectRatio="xMidYMid slice">
                    <defs>
                        <linearGradient id="swooshGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6D5EF8" />
                            <stop offset="100%" stop-color="#33D6C8" />
                        </linearGradient>
                    </defs>
                    <g stroke="url(#swooshGrad2)" stroke-width="2.5" fill="none" stroke-linecap="round">
                        <path class="swoosh-line" d="M -50 480 L 200 380 L 150 340 L 480 180" style="animation-delay: 0s" />
                        <path class="swoosh-line" d="M -80 560 L 180 460 L 130 420 L 520 260" style="animation-delay: 1.2s" />
                        <path class="swoosh-line" d="M -30 400 L 220 300 L 170 260 L 460 100" style="animation-delay: 2.4s" />
                    </g>
                </svg>

                <div class="relative z-10">
                    <a href="/" class="inline-flex items-center gap-2">
                        <svg viewBox="0 0 200 130" class="h-8 w-auto fill-white">
                            <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                        </svg>
                        <span class="font-display text-xl tracking-tight">Zypher</span>
                    </a>
                </div>

                <div class="relative z-10 space-y-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-white/50">Join the marketplace</p>
                    <h1 class="font-display text-4xl sm:text-[42px] leading-[1.05] tracking-tight">
                        Start browsing<br />in seconds.
                    </h1>
                    <p class="text-sm text-white/60 max-w-xs leading-relaxed">
                        One account to shop from every seller — and you can start your own shop anytime.
                    </p>
                </div>

                <div class="relative z-10 text-xs text-white/40">
                    &copy; {{ new Date().getFullYear() }} Zypher. All rights reserved.
                </div>
            </div>

            <div class="md:w-1/2 p-10 sm:p-14 flex flex-col justify-center">
                <div class="flex items-center justify-between mb-8">
                    <span class="text-sm text-[#8A8B90]">Already have an account?</span>
                    <Link :href="route('login')" class="text-sm font-medium text-[#101114] hover:text-[#6D5EF8] transition-colors">
                        Sign in
                    </Link>
                </div>

                <h2 class="font-display text-3xl tracking-tight text-[#101114] mb-2">Create account</h2>
                <p class="text-sm text-[#8A8B90] mb-8">Free to join. Takes less than a minute.</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="name" class="block text-xs font-medium text-[#8A8B90] mb-1.5">Full name</label>
                        <input
                            id="name" v-model="form.name" type="text" required autofocus autocomplete="name"
                            class="w-full h-12 px-4 rounded-xl border border-[#E4E4E0] focus:border-[#6D5EF8] focus:ring-2 focus:ring-[#6D5EF8]/20 outline-none transition-all text-sm"
                            placeholder="Juan Dela Cruz"
                        />
                        <p v-if="form.errors.name" class="mt-1.5 text-xs text-[#D85A30]">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-medium text-[#8A8B90] mb-1.5">Email</label>
                        <input
                            id="email" v-model="form.email" type="email" required autocomplete="username"
                            class="w-full h-12 px-4 rounded-xl border border-[#E4E4E0] focus:border-[#6D5EF8] focus:ring-2 focus:ring-[#6D5EF8]/20 outline-none transition-all text-sm"
                            placeholder="name@example.com"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-[#D85A30]">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-medium text-[#8A8B90] mb-1.5">Password</label>
                        <input
                            id="password" v-model="form.password" type="password" required autocomplete="new-password"
                            class="w-full h-12 px-4 rounded-xl border border-[#E4E4E0] focus:border-[#6D5EF8] focus:ring-2 focus:ring-[#6D5EF8]/20 outline-none transition-all text-sm"
                            placeholder="At least 8 characters"
                        />
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-[#D85A30]">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-[#8A8B90] mb-1.5">Confirm password</label>
                        <input
                            id="password_confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password"
                            class="w-full h-12 px-4 rounded-xl border border-[#E4E4E0] focus:border-[#6D5EF8] focus:ring-2 focus:ring-[#6D5EF8]/20 outline-none transition-all text-sm"
                        />
                    </div>

                    <label class="flex items-start gap-2 pt-1 cursor-pointer select-none">
                        <input v-model="form.terms" type="checkbox" required class="w-4 h-4 mt-0.5 rounded border-[#D3D1C7] text-[#6D5EF8] focus:ring-[#6D5EF8]/30" />
                        <span class="text-sm text-[#5F5E5A]">
                            I agree to Zypher's <a href="#" class="underline">Terms</a> and <a href="#" class="underline">Privacy Policy</a>.
                        </span>
                    </label>

                    <button
                        type="submit" :disabled="form.processing"
                        class="w-full h-12 mt-2 rounded-xl font-medium text-sm text-white bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] hover:opacity-90 active:scale-[0.99] transition-all disabled:opacity-60"
                    >
                        {{ form.processing ? 'Creating account…' : 'Create account' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=Inter:wght@400;500&display=swap');
.font-display { font-family: 'Space Grotesk', sans-serif; }
.swoosh-line { stroke-dasharray: 260; stroke-dashoffset: 260; animation: drawSwoosh 3.6s ease-in-out infinite; }
@keyframes drawSwoosh {
    0% { stroke-dashoffset: 260; opacity: 0; } 15% { opacity: 1; }
    50% { stroke-dashoffset: 0; opacity: 1; } 80% { opacity: 0.3; } 100% { stroke-dashoffset: -260; opacity: 0; }
}
@media (prefers-reduced-motion: reduce) { .swoosh-line { animation: none; stroke-dashoffset: 0; opacity: 0.5; } }
</style>
