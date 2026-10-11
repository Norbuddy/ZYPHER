<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PolicyModal from '@/Components/PolicyModal.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    terms: false,
});

const showPassword = ref(false);
const showPolicyModal = ref(false);
const policyType = ref('terms');
const policyTitle = computed(() => policyType.value === 'terms' ? 'Terms of Service' : 'Privacy Policy');
const termsError = ref('');

const openPolicy = (type) => {
    policyType.value = type;
    showPolicyModal.value = true;
};

const acceptPolicy = () => {
    form.terms = true;
    termsError.value = '';
};

const submit = () => {
    if (!form.terms) {
        termsError.value = 'Please read and agree to the Terms of Service and Privacy Policy before signing in.';
        return;
    }
    termsError.value = '';
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign in" />

    <div class="min-h-screen w-full flex items-center justify-center bg-[#EDEDE8] p-4 sm:p-8">
        <div class="w-full max-w-5xl bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col md:flex-row min-h-[640px]">

            <!-- Left panel: brand -->
            <div class="relative md:w-1/2 bg-[#101114] text-white p-10 sm:p-12 flex flex-col justify-between overflow-hidden">
                <!-- Signature: animated swoosh motion lines -->
                <svg class="absolute inset-0 w-full h-full opacity-[0.18]" viewBox="0 0 500 640" preserveAspectRatio="xMidYMid slice">
                    <defs>
                        <linearGradient id="swooshGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6D5EF8" />
                            <stop offset="100%" stop-color="#33D6C8" />
                        </linearGradient>
                    </defs>
                    <g stroke="url(#swooshGrad)" stroke-width="2.5" fill="none" stroke-linecap="round">
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
                    <p class="text-xs uppercase tracking-[0.2em] text-white/50">Marketplace, at speed</p>
                    <h1 class="font-display text-4xl sm:text-[42px] leading-[1.05] tracking-tight">
                        Buy, sell,<br />and move fast.
                    </h1>
                    <p class="text-sm text-white/60 max-w-xs leading-relaxed">
                        One account for browsing, buying, and running your own shop on Zypher.
                    </p>
                </div>

                <div class="relative z-10 text-xs text-white/40">
                    &copy; {{ new Date().getFullYear() }} Zypher. All rights reserved.
                </div>
            </div>

            <!-- Right panel: sign in form -->
            <div class="md:w-1/2 p-10 sm:p-14 flex flex-col justify-center">
                <div class="flex items-center justify-between mb-10">
                    <span class="text-sm text-[#8A8B90]">New to Zypher?</span>
                    <Link
                        :href="route('register')"
                        class="text-sm font-medium text-[#101114] hover:text-[#6D5EF8] transition-colors inline-flex items-center gap-1"
                    >
                        Create account
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </Link>
                </div>

                <h2 class="font-display text-3xl tracking-tight text-[#101114] mb-2">Sign in</h2>
                <p class="text-sm text-[#8A8B90] mb-8">Welcome back. Enter your details to continue.</p>

                <div
                    v-if="status"
                    class="mb-6 text-sm font-medium text-[#0F6E56] bg-[#E1F5EE] rounded-lg px-4 py-3"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label for="email" class="block text-xs font-medium text-[#8A8B90] mb-1.5">
                            Email or username
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="text"
                            autocomplete="username"
                            required
                            autofocus
                            class="w-full h-12 px-4 rounded-xl border border-[#E4E4E0] focus:border-[#6D5EF8] focus:ring-2 focus:ring-[#6D5EF8]/20 outline-none transition-all text-sm text-[#101114] placeholder:text-[#B4B2A9]"
                            placeholder="name@example.com"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-[#D85A30]">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-medium text-[#8A8B90]">
                                Password
                            </label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs font-medium text-[#6D5EF8] hover:text-[#33D6C8] transition-colors"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                class="w-full h-12 px-4 pr-11 rounded-xl border border-[#E4E4E0] focus:border-[#6D5EF8] focus:ring-2 focus:ring-[#6D5EF8]/20 outline-none transition-all text-sm text-[#101114] placeholder:text-[#B4B2A9]"
                                placeholder="Enter your password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#B4B2A9] hover:text-[#8A8B90] transition-colors"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z" stroke-linecap="round" stroke-linejoin="round" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.3 20.3 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M1 1l22 22" stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-[#D85A30]">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="w-4 h-4 rounded border-[#D3D1C7] text-[#6D5EF8] focus:ring-[#6D5EF8]/30"
                            />
                            <span class="text-xs text-[#5F5E5A]">Keep me signed in</span>
                        </label>
                    </div>

                    <!-- Mandatory Terms and Privacy Policy Checkbox -->
                    <div class="pt-2 border-t border-[#F0F0EC]">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none">
                            <input
                                v-model="form.terms"
                                type="checkbox"
                                class="w-4 h-4 mt-0.5 rounded border-[#D3D1C7] text-[#6D5EF8] focus:ring-[#6D5EF8]/30"
                                @change="termsError = ''"
                            />
                            <span class="text-xs text-[#5F5E5A] leading-relaxed">
                                I have read and agree to the 
                                <button type="button" @click.prevent="openPolicy('terms')" class="text-[#6D5EF8] font-semibold underline hover:text-[#101114]">Terms of Service</button> 
                                and 
                                <button type="button" @click.prevent="openPolicy('policy')" class="text-[#6D5EF8] font-semibold underline hover:text-[#101114]">Privacy Policy</button> 
                                of Zypher Marketplace.
                            </span>
                        </label>
                        <p v-if="termsError" class="mt-1.5 text-xs text-[#D85A30] font-medium">{{ termsError }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !form.terms"
                        class="w-full h-12 mt-2 rounded-xl font-medium text-sm text-white bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] hover:opacity-90 active:scale-[0.99] transition-all disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span v-if="!form.processing">Sign in</span>
                        <span v-else>Signing in…</span>
                        <svg v-if="!form.processing" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </form>

                <div class="mt-8 text-xs text-[#8A8B90] text-center flex items-center justify-center gap-3">
                    <button type="button" @click.prevent="openPolicy('terms')" class="hover:text-[#101114] hover:underline">
                        Terms of Service
                    </button>
                    <span>·</span>
                    <button type="button" @click.prevent="openPolicy('policy')" class="hover:text-[#101114] hover:underline">
                        Privacy Policy
                    </button>
                </div>
            </div>
        </div>

        <!-- TERMS & POLICY MODAL -->
        <PolicyModal
            :show="showPolicyModal"
            :type="policyType"
            :title="policyTitle"
            @close="showPolicyModal = false"
            @accept="acceptPolicy"
        />
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=Inter:wght@400;500&display=swap');

.font-display {
    font-family: 'Space Grotesk', sans-serif;
}

.swoosh-line {
    stroke-dasharray: 260;
    stroke-dashoffset: 260;
    animation: drawSwoosh 3.6s ease-in-out infinite;
}

@keyframes drawSwoosh {
    0% { stroke-dashoffset: 260; opacity: 0; }
    15% { opacity: 1; }
    50% { stroke-dashoffset: 0; opacity: 1; }
    80% { opacity: 0.3; }
    100% { stroke-dashoffset: -260; opacity: 0; }
}

@media (prefers-reduced-motion: reduce) {
    .swoosh-line {
        animation: none;
        stroke-dashoffset: 0;
        opacity: 0.5;
    }
}
</style>
