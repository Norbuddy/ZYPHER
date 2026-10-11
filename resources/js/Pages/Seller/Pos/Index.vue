<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    paymongoConfigured: { type: Boolean, default: false },
    paymongoTestMode: { type: Boolean, default: true },

    products: Array,
    recentSales: Array,
    branchName: { type: String, default: 'Shop' },
    flash: { type: Object, default: () => ({}) },
});

const cart = ref([]);
const search = ref('');
const processing = ref(false);
const paymentMethod = ref('cash');
const receipt = ref(props.flash?.receipt ?? null);

watch(() => props.flash?.receipt, (value) => {
    if (value) {
        receipt.value = value;
    }
});

const closeReceipt = () => {
    receipt.value = null;
};

const printReceipt = () => {
    window.print();
};

const viewReceipt = (sale) => {
    receipt.value = {
        sale_number: sale.sale_number,
        branch_name: props.branchName,
        items: (sale.items || []).map((item) => ({
            product_name: item.product_name,
            variant_label: item.variant_label,
            quantity: item.quantity,
            unit_price: Number(item.unit_price),
            line_total: Number(item.line_total),
        })),
        total_amount: Number(sale.total_amount),
        payment_method: sale.payment_method,
        created_at: sale.created_at,
    };
};

const filteredProducts = computed(() => {
    if (!search.value) return props.products;
    const q = search.value.toLowerCase();
    return props.products.filter(p => p.name.toLowerCase().includes(q));
});

const addToCart = (product, variant) => {
    const existing = cart.value.find(c => c.product_variant_id === variant.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({
            product_variant_id: variant.id,
            name: product.name,
            label: variant.label,
            price: Number(variant.price),
            quantity: 1,
            stock: variant.stock - (variant.reserved_stock ?? 0),
        });
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const cartTotal = computed(() => cart.value.reduce((sum, i) => sum + (i.price * i.quantity), 0));

const checkout = () => {
    if (cart.value.length === 0) return;
    processing.value = true;

    router.post(route('seller.pos.store'), {
        items: cart.value.map(i => ({ product_variant_id: i.product_variant_id, quantity: i.quantity })),
        payment_method: paymentMethod.value,
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            const url = page?.props?.flash?.paymongo_checkout_url
                || usePage().props.flash?.paymongo_checkout_url;
            if (url) {
                window.location.href = url;
                return;
            }
            cart.value = [];
            paymentMethod.value = 'cash';
        },
        onFinish: () => { processing.value = false; },
    });
};

onMounted(() => {
    const url = usePage().props.flash?.paymongo_checkout_url;
    if (url) {
        window.location.href = url;
    }
});

const productImageUrl = (product) => {
    const path = product.images?.[0]?.path;

    if (!path) {
        return null;
    }

    // Old image paths
    if (path.startsWith('images/')) {
        return '/' + path;
    }

    // Laravel storage images
    return '/storage/' + path;
};
</script>

<template>
    <SellerLayout>
        <Head title="Point of Sale" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">Point of Sale</h1>

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2">
                <input v-model="search" type="text" placeholder="Search products…" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm mb-4" />

                <div class="grid grid-cols-3 gap-3">
                    <div v-for="product in filteredProducts" :key="product.id" class="bg-white rounded-xl border border-[#E4E4E0] p-3">
                        <div class="aspect-square rounded-lg bg-[#F0F0EC] overflow-hidden mb-2">
                            <img
    v-if="productImageUrl(product)"
    :src="productImageUrl(product)"
    :alt="product.name"
    class="w-full h-full object-cover"
/>

<div
    v-else
    class="w-full h-full flex items-center justify-center text-xs text-[#8A8B90]"
>
    No Image
</div>
                        </div>
                        <p class="text-sm font-medium text-[#101114] mb-2 truncate">{{ product.name }}</p>
                        <div v-for="variant in product.variants" :key="variant.id" class="flex items-center justify-between text-xs mb-1">
                            <span class="text-[#8A8B90]">{{ variant.label || 'Default' }} — ₱{{ variant.price }}</span>
                            <button
                                @click="addToCart(product, variant)"
                                :disabled="(variant.stock - (variant.reserved_stock ?? 0)) <= 0"
                                class="px-2 py-1 rounded bg-[#101114] text-white disabled:opacity-30"
                            >
                                Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-[#E4E4E0] p-4 h-fit">
                <h2 class="font-semibold mb-3">Current Sale</h2>

                <div v-if="cart.length === 0" class="text-sm text-[#8A8B90] text-center py-6">No items yet.</div>

                <div v-for="(item, i) in cart" :key="i" class="flex items-center justify-between text-sm mb-2 pb-2 border-b border-[#EAEAE5]">
                    <div>
                        <p class="font-medium">{{ item.name }}</p>
                        <p class="text-xs text-[#8A8B90]">{{ item.label }} x{{ item.quantity }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>₱{{ (item.price * item.quantity).toFixed(2) }}</span>
                        <button @click="removeFromCart(i)" class="text-red-500 text-xs">✕</button>
                    </div>
                </div>

                <div class="flex items-center justify-between font-semibold mt-4 mb-4">
                    <span>Total</span>
                    <span>₱{{ cartTotal.toFixed(2) }}</span>
                </div>

                <p class="text-xs font-medium text-[#101114] mb-2">Payment Method</p>
                <div class="flex justify-center gap-3 mb-4">
                    <button
                        type="button"
                        @click="paymentMethod = 'cash'"
                        class="w-16 h-16 rounded-xl border-2 flex flex-col items-center justify-center gap-1"
                        :class="paymentMethod === 'cash' ? 'border-lime-400 bg-lime-50' : 'border-gray-200'"
                    >
                        <span class="text-lg">₱</span>
                        <span class="text-[10px] font-medium">Cash</span>
                    </button>
                    <button
                        type="button"
                        @click="paymentMethod = 'ewallet'"
                        class="w-16 h-16 rounded-xl border-2 flex flex-col items-center justify-center gap-1"
                        :class="paymentMethod === 'ewallet' ? 'border-lime-400 bg-lime-50' : 'border-gray-200'"
                    >
                        <span class="text-lg">📱</span>
                        <span class="text-[10px] font-medium">E-wallet</span>
                    </button>
                    <button
                        type="button"
                        @click="paymentMethod = 'atm'"
                        class="w-16 h-16 rounded-xl border-2 flex flex-col items-center justify-center gap-1"
                        :class="paymentMethod === 'atm' ? 'border-lime-400 bg-lime-50' : 'border-gray-200'"
                    >
                        <span class="text-lg">💳</span>
                        <span class="text-[10px] font-medium">ATM</span>
                    </button>
                </div>

                <button
                    @click="checkout"
                    :disabled="cart.length === 0 || processing"
                    class="w-full h-11 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white font-medium text-sm disabled:opacity-40"
                >
                    {{ processing ? 'Processing…' : (paymentMethod === 'cash' ? 'Complete Sale (Cash)' : 'Pay with PayMongo') }}
                </button>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="font-semibold mb-3">Recent Sales</h2>
            <div class="bg-white rounded-xl border border-[#E4E4E0] divide-y divide-[#EAEAE5]">
                <div v-for="sale in recentSales" :key="sale.id" class="p-4 flex items-center justify-between gap-3 text-sm">
                    <span>{{ sale.sale_number }}</span>
                    <span class="text-[#8A8B90]">{{ sale.items.length }} item(s)</span>
                    <span class="text-[10px] uppercase font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ sale.payment_method }}</span>
                    <span class="font-medium">₱{{ Number(sale.total_amount).toFixed(2) }}</span>
                    <button type="button" @click="viewReceipt(sale)" class="rounded-lg border border-[#E4E4E0] px-3 py-1.5 text-xs font-medium text-[#101114] hover:bg-[#F7F7F5]">
                        View receipt
                    </button>
                </div>
            </div>
        </div>

        <div v-if="receipt" class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/50 p-4" @click.self="closeReceipt">
            <div id="pos-receipt" class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
                <div class="text-center">
                    <h2 class="text-xl font-bold text-[#101114]">{{ receipt.branch_name }}</h2>
                    <p class="mt-1 text-xs text-[#8A8B90]">Sales Receipt</p>
                    <p class="mt-3 text-xs text-[#8A8B90]">{{ receipt.sale_number }} · {{ receipt.created_at }}</p>
                </div>

                <div class="my-5 border-y border-dashed border-[#D8D8D2] py-3">
                    <div v-for="item in receipt.items" :key="`${item.product_name}-${item.variant_label}`" class="mb-3 flex justify-between gap-4 text-sm last:mb-0">
                        <div>
                            <p class="font-medium">{{ item.product_name }}</p>
                            <p class="text-xs text-[#8A8B90]">{{ item.variant_label || 'Default' }} · {{ item.quantity }} × ₱{{ Number(item.unit_price).toFixed(2) }}</p>
                        </div>
                        <span class="font-medium">₱{{ Number(item.line_total).toFixed(2) }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <span>Payment</span>
                    <span class="uppercase">{{ receipt.payment_method }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between text-lg font-bold">
                    <span>Total</span>
                    <span>₱{{ Number(receipt.total_amount).toFixed(2) }}</span>
                </div>

                <div class="mt-6 flex gap-2 print:hidden">
                    <button type="button" @click="closeReceipt" class="flex-1 rounded-lg border border-[#E4E4E0] px-4 py-2 text-sm font-medium">Close</button>
                    <button type="button" @click="printReceipt" class="flex-1 rounded-lg bg-[#101114] px-4 py-2 text-sm font-medium text-white">Print Receipt</button>
                </div>
            </div>
        </div>
    </SellerLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    #pos-receipt,
    #pos-receipt * {
        visibility: visible;
    }

    #pos-receipt {
        position: absolute;
        inset: 0;
        max-width: none;
        box-shadow: none;
    }
}
</style>