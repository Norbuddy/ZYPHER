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
                        <h4 class="font-bold text-[#101114] mb-1">1. Pagtanggap sa Kasunduan (Agreement to Terms)</h4>
                        <p>Sa pamamagitan ng pag-login, pag-register, o paggamit sa Zypher Marketplace, sumasang-ayon ka sa mga patakaran, safety guidelines, at legal terms ng system.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">2. Responsibilidad sa Account (Account Responsibilities)</h4>
                        <p>Ikaw ang may buong pananagutan sa pagpapanatiling ligtas ng iyong email at password. Ang anumang order o transaksyon gamit ang iyong account ay ituturing na opisyal at pinahintulutan mo.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">3. Transaksyon at Pagbabayad (Orders & Payments)</h4>
                        <p>Lahat ng presyo ay nakasaad sa Philippine Peso (PHP ₱). Suportado ng system ang secure electronic payments (GCash, GrabPay, Cards via PayMongo) at Cash on Delivery (COD).</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">4. Proteksyon sa Mamimili (Buyer & Seller Protection)</h4>
                        <p>Ipinagbabawal ang mga pekeng produkto at panlilinlang. Ang bawat mamimili ay may karapatang mag-request ng return o refund sakaling may depekto ang natanggap na produkto.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">5. Pagkansela at Pagwawakas (Termination)</h4>
                        <p>May karapatan ang Zypher na i-suspend o tanggalin ang sinumang lumalabag sa mga patakarang ito upang mapanatiling ligtas ang komunidad.</p>
                    </div>
                </div>

                <!-- PRIVACY POLICY CONTENT -->
                <div v-else class="space-y-3.5">
                    <p class="text-xs text-[#8A8B90]">
                        Compliant with Data Privacy Act of 2012 (RA 10173)
                    </p>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">1. Impormasyong Kinokolekta (Data Collected)</h4>
                        <p>Kinokolekta lamang ng Zypher ang kinakailangang impormasyon gaya ng pangalan, email, contact number, at delivery address para sa pagproseso ng mga order.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">2. Paggamit ng Impormasyon (Use of Information)</h4>
                        <p>Ginagamit ang iyong datos upang maipadala nang maayos ang iyong mga binili, maprotektahan ang iyong session, at maiwasan ang mga panloloko sa system.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">3. Seguridad at Encryption (Security Measures)</h4>
                        <p>Naka-encrypt (TLS/HTTPS) at protektado ang lahat ng sensitibong impormasyon. Hindi kailanman ibinebenta ang iyong personal na impormasyon sa mga third-party advertiser.</p>
                    </div>

                    <div>
                        <h4 class="font-bold text-[#101114] mb-1">4. Iyong mga Karapatan (User Rights)</h4>
                        <p>Maaari mong baguhin o i-update ang iyong personal data anumang oras sa pamamagitan ng Profile Settings o makipag-ugnayan sa Zypher Support.</p>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2 rounded-xl border border-[#E4E4E0] text-xs font-semibold text-[#5F5E5A] hover:bg-[#F7F7F5] transition"
                >
                    Isara (Close)
                </button>
                <button
                    type="button"
                    @click="emit('accept'); emit('close');"
                    class="px-5 py-2 rounded-xl bg-[#101114] hover:bg-black text-white text-xs font-bold transition shadow-sm"
                >
                    Naiintindihan at Sumasang-ayon Ako
                </button>
            </div>
        </template>
    </DialogModal>
</template>
