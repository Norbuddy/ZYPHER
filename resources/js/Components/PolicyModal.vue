<script setup>
import DialogModal from '@/Components/DialogModal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Terms & Policy',
    },
    type: {
        type: String,
        default: 'terms', // 'terms' or 'policy'
    },
});

const emit = defineEmits(['close', 'accept']);
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="2xl">
        <template #title>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-[#6D5EF8]/10 text-[#6D5EF8] flex items-center justify-center font-bold text-sm">
                        §
                    </div>
                    <span class="text-base font-bold text-[#101114]">{{ title }}</span>
                </div>
                <a
                    :href="type === 'policy' ? route('policy.show') : route('terms.show')"
                    target="_blank"
                    class="text-xs text-[#6D5EF8] hover:underline flex items-center gap-1 font-medium mr-2"
                >
                    Open Full Page ↗
                </a>
            </div>
        </template>

        <template #content>
            <div class="max-h-[60vh] overflow-y-auto pr-2 space-y-4 text-xs sm:text-sm text-[#5F5E5A] leading-relaxed">
                <!-- TERMS CONTENT -->
                <div v-if="type === 'terms'" class="space-y-3.5">
                    <p class="text-xs text-[#8A8B90]">
                        Effective Date: October 2026 · Zypher Marketplace Philippines
                    </p>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">1. Agreement to Terms</h4>
                        <p>By signing in, registering, or using Zypher Marketplace, you agree to comply with and be bound by all platform terms, safety guidelines, and policies.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">2. Account Responsibilities</h4>
                        <p>You are solely responsible for maintaining the confidentiality of your account credentials. All orders and activities conducted under your account are deemed authorized by you.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">3. Orders & Payments</h4>
                        <p>All prices are quoted in Philippine Peso (PHP ₱). Zypher supports secure electronic payments (GCash, GrabPay, Credit/Debit cards via PayMongo) as well as Cash on Delivery (COD).</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">4. Buyer & Seller Protection</h4>
                        <p>Counterfeit products and deceptive practices are strictly prohibited. Buyers have the right to request a return or refund in accordance with our return guidelines.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">5. Termination & Suspension</h4>
                        <p>Zypher reserves the right to suspend or terminate accounts that violate platform policies to ensure a safe and trustworthy community for all users.</p>
                    </div>
                </div>

                <!-- PRIVACY POLICY CONTENT -->
                <div v-else class="space-y-3.5">
                    <p class="text-xs text-[#8A8B90]">
                        Compliant with Data Privacy Act of 2012 (RA 10173)
                    </p>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">1. Information We Collect</h4>
                        <p>Zypher collects necessary account details including your name, email, contact telephone number, and delivery addresses required to fulfill orders.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">2. Use of Information</h4>
                        <p>Your data is used solely to process orders, coordinate package deliveries with verified logistics partners, maintain account security, and prevent fraud.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">3. Security & Data Protection</h4>
                        <p>All data transmissions are encrypted using TLS/HTTPS. We never sell, rent, or trade your personal information to third-party advertisers.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">4. User Rights</h4>
                        <p>You may review, update, or edit your personal data at any time through your Profile Settings or by reaching out to Zypher Support.</p>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2 rounded-xl border border-[#E4E4E0] text-xs font-semibold text-[#5F5E5A] hover:bg-[#F7F7F5] transition cursor-pointer"
                >
                    Close
                </button>
                <button
                    type="button"
                    @click="emit('accept'); emit('close');"
                    class="px-5 py-2 rounded-xl bg-[#101114] hover:bg-black text-white text-xs font-bold transition shadow-sm cursor-pointer"
                >
                    I Understand & Agree
                </button>
            </div>
        </template>
    </DialogModal>
</template>
