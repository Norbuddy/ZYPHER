<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import LocationPicker from '@/Components/LocationPicker.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    itemsByShop: Object,
    addresses: Array,
    paymongoTestMode: { type: Boolean, default: true },
    paymongoConfigured: { type: Boolean, default: false },
    paymongoError: { type: String, default: null },
});

const selectedLatitude = ref(null);
const selectedLongitude = ref(null);
const selectedAddressId = ref(props.addresses.find(a => a.is_default)?.id ?? props.addresses[0]?.id ?? null);
const voucherCode = ref('');
const paymentMethod = ref('card');

const form = useForm({
    address_id: selectedAddressId.value,
    platform_voucher_code: '',
    latitude: null,
    longitude: null,
    payment_method: 'card',
});

const selectedAddress = ref(props.addresses.find(a => a.id === selectedAddressId.value) ?? null);

const selectAddress = (address) => {
    selectedAddressId.value = address.id;
    selectedAddress.value = address;
    selectedLatitude.value = address.latitude ? Number(address.latitude) : null;
    selectedLongitude.value = address.longitude ? Number(address.longitude) : null;
};

const selectedAddressQuery = computed(() => {
    if (!selectedAddress.value) return '';
    const addr = selectedAddress.value;
    return [addr.line1, addr.line2, addr.city, addr.province, addr.postal_code, 'Philippines']
        .filter(Boolean)
        .join(', ');
});

const shopSubtotal = (items) =>
    items.reduce((sum, item) => sum + item.variant.price * item.quantity, 0).toFixed(2);

const orderTotal = computed(() => {
    let total = 0;
    for (const shopId in props.itemsByShop) {
        total += props.itemsByShop[shopId].reduce(
            (sum, item) => sum + item.variant.price * item.quantity,
            0
        );
    }
    return total.toFixed(2);
});

const paymentOptions = [
    { value: 'card', label: 'Card', desc: 'Visa / Mastercard' },
    { value: 'gcash', label: 'GCash', desc: 'E-wallet' },
    { value: 'paymaya', label: 'Maya', desc: 'E-wallet' },
    { value: 'grab_pay', label: 'GrabPay', desc: 'E-wallet' },
    { value: 'qrph', label: 'QR Ph', desc: 'Scan to pay' },
];

const submit = () => {
    form.address_id = selectedAddressId.value;
    form.platform_voucher_code = voucherCode.value;
    form.latitude = selectedLatitude.value;
    form.longitude = selectedLongitude.value;
    form.payment_method = paymentMethod.value;
    form.post(route('checkout.store'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Checkout" />

        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
            <h1 class="text-xl font-semibold text-[#101114] mb-6">Checkout</h1>

            <!-- Address -->
            <div class="mb-6 p-4 border border-[#E4E4E0] rounded-xl bg-white">
                <p class="text-sm font-medium mb-3">Shipping Address</p>
                <div v-if="!addresses?.length" class="text-sm text-[#8A8B90]">
                    No addresses yet. Add one in account settings.
                </div>
                <label
                    v-for="addr in addresses"
                    :key="addr.id"
                    class="flex items-start gap-3 py-2 cursor-pointer"
                >
                    <input
                        type="radio"
                        class="mt-1"
                        :value="addr.id"
                        v-model="selectedAddressId"
                        @change="selectAddress(addr)"
                    />
                    <div>
                        <p class="text-sm font-medium text-[#101114]">{{ addr.label || addr.recipient_name || 'Address' }}</p>
                        <p class="text-xs text-[#8A8B90]">
                            {{ [addr.line1, addr.line2, addr.barangay, addr.city, addr.province].filter(Boolean).join(', ') }}
                        </p>
                    </div>
                </label>
            </div>

            <!-- Map -->
            <div class="mb-6 p-4 border border-[#E4E4E0] rounded-xl bg-white">
                <p class="text-sm font-medium mb-1">Delivery Location</p>
                <p class="text-xs text-[#8A8B90] mb-4">Select your exact delivery location on the map.</p>
                <LocationPicker
                    :latitude="selectedLatitude"
                    :longitude="selectedLongitude"
                    :address-query="selectedAddressQuery"
                    @update:latitude="selectedLatitude = $event"
                    @update:longitude="selectedLongitude = $event"
                />
            </div>

            <!-- Cart -->
            <div
                v-for="(items, shopId) in itemsByShop"
                :key="shopId"
                class="mb-4 border border-[#E4E4E0] rounded-xl overflow-hidden bg-white"
            >
                <div class="px-4 py-3 bg-[#F7F7F5] text-sm font-medium border-b border-[#E4E4E0] flex justify-between">
                    <span>{{ items[0].variant.product.team.name }}</span>
                    <span>₱{{ shopSubtotal(items) }}</span>
                </div>
                <div
                    v-for="item in items"
                    :key="item.id"
                    class="px-4 py-3 text-sm flex justify-between border-b border-[#EAEAE5] last:border-0"
                >
                    <span>{{ item.variant.product.name }} ({{ item.variant.label }}) × {{ item.quantity }}</span>
                    <span>₱{{ (item.variant.price * item.quantity).toFixed(2) }}</span>
                </div>
            </div>

            <!-- Voucher -->
            <div class="mb-6 p-4 border border-[#E4E4E0] rounded-xl bg-white">
                <p class="text-sm font-medium mb-3">Platform Voucher</p>
                <input
                    v-model="voucherCode"
                    type="text"
                    placeholder="Enter code"
                    class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                />
            </div>

            <!-- Payment method (PayMongo) -->
            <div class="mb-6 p-4 border border-[#E4E4E0] rounded-xl bg-white">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-[#101114]">Payment Method</p>
                    <span
                        class="text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full"
                        :class="paymongoConfigured
                            ? (paymongoTestMode ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700')
                            : 'bg-gray-100 text-gray-600'"
                    >
                        {{ paymongoConfigured ? (paymongoTestMode ? 'PayMongo Test' : 'PayMongo Live') : 'Demo mode' }}
                    </span>
                </div>
                <p class="text-xs text-[#8A8B90] mb-4">
                    You’ll complete payment securely on PayMongo. Test mode does not charge real money.
                </p>
                <div v-if="paymongoError" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    {{ paymongoError }}
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <button
                        v-for="opt in paymentOptions"
                        :key="opt.value"
                        type="button"
                        class="rounded-xl border px-3 py-3 text-left transition"
                        :class="paymentMethod === opt.value
                            ? 'border-[#6D5EF8] bg-[#F5F3FF] ring-1 ring-[#6D5EF8]'
                            : 'border-[#E4E4E0] hover:border-[#C9C9C4]'"
                        @click="paymentMethod = opt.value"
                    >
                        <p class="text-sm font-medium text-[#101114]">{{ opt.label }}</p>
                        <p class="text-[11px] text-[#8A8B90]">{{ opt.desc }}</p>
                    </button>
                </div>
            </div>

            <!-- Total -->
            <div class="flex items-center justify-between p-4 bg-white border border-[#E4E4E0] rounded-xl mb-6">
                <div>
                    <p class="text-xs text-[#8A8B90]">Order Total</p>
                    <p class="text-xl font-semibold text-[#101114]">₱{{ orderTotal }}</p>
                </div>
            </div>

            <p v-if="form.errors.checkout" class="text-sm text-red-500 mb-4">{{ form.errors.checkout }}</p>

            <button
                type="button"
                @click="submit"
                :disabled="form.processing || !selectedAddressId"
                class="w-full h-12 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white font-medium disabled:opacity-40"
            >
                {{ form.processing ? 'Redirecting to PayMongo…' : 'Pay with PayMongo' }}
            </button>
            <p class="mt-2 text-center text-[11px] text-[#8A8B90]">
                Powered by PayMongo · {{ paymongoTestMode ? 'Test environment (no real charges)' : 'Live payments' }}
            </p>
        </div>
    </GuestLayout>
</template>
