<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    paymongoConfigured: { type: Boolean, default: false },
    paymongoTestMode: { type: Boolean, default: true },
    products: Array,
    categories: { type: Array, default: () => [] },
    recentSales: Array,
    branchName: { type: String, default: 'Shop' },
    cashierName: { type: String, default: 'Cashier' },
    flash: { type: Object, default: () => ({}) },
});

const cart = ref([]);
const search = ref('');
const selectedCategory = ref('all');
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
    let list = props.products || [];
    if (selectedCategory.value !== 'all') {
        list = list.filter(p => p.category?.slug === selectedCategory.value || p.category?.name === selectedCategory.value || String(p.category_id) === String(selectedCategory.value));
    }
    if (search.value) {
        const q = search.value.toLowerCase().trim();
        list = list.filter(p => p.name?.toLowerCase().includes(q) || p.variants?.some(v => v.label?.toLowerCase().includes(q) || v.sku?.toLowerCase().includes(q)));
    }
    return list;
});

const addToCart = (product, variant) => {
    const existing = cart.value.find(c => c.product_variant_id === variant.id);
    const availableStock = variant.stock - (variant.reserved_stock ?? 0);
    if (existing) {
        if (existing.quantity < availableStock) {
            existing.quantity++;
        }
    } else {
        cart.value.push({
            product_variant_id: variant.id,
            product_id: product.id,
            name: product.name,
            label: variant.label,
            price: Number(variant.price),
            quantity: 1,
            stock: availableStock,
            image: productImageUrl(product),
        });
    }
};

const incrementCart = (item) => {
    if (item.stock && item.quantity >= item.stock) return;
    item.quantity++;
};

const decrementCart = (item, index) => {
    if (item.quantity > 1) {
        item.quantity--;
    } else {
        removeFromCart(index);
    }
};

const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

const clearCart = () => {
    cart.value = [];
};

const cartTotal = computed(() => cart.value.reduce((sum, i) => sum + (i.price * i.quantity), 0));
const cartItemCount = computed(() => cart.value.reduce((sum, i) => sum + i.quantity, 0));

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
    if (!path) return null;
    if (path.startsWith('images/')) return '/' + path;
    return '/storage/' + path;
};
</script>

<template>
    <SellerLayout>
        <Head title="Point of Sale" />

        <!-- Top Bar: Cashier badge & status -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-gray-200/80 pb-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0F172A] text-white shadow-sm">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900 tracking-tight">Point of Sale (POS)</h1>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span class="font-medium text-gray-700">{{ branchName }}</span>
                        <span>·</span>
                        <span>Terminal 01</span>
                    </div>
                </div>
            </div>

            <!-- Cashier Identity Card -->
            <div class="flex items-center gap-3 rounded-xl border border-gray-200/80 bg-white px-3.5 py-1.5 shadow-sm">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                    {{ cashierName?.charAt(0) || 'C' }}
                </div>
                <div class="text-left text-xs">
                    <p class="font-semibold text-gray-900">{{ cashierName }}</p>
                    <p class="text-[11px] text-gray-500">Cashier / Staff</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Side: Products Catalog (8 Cols) -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-4">
                <!-- Search Box with F1 shortcut badge -->
                <div class="relative flex items-center">
                    <svg class="pointer-events-none absolute left-3.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Scan barcode or search product..."
                        class="w-full h-11 pl-10 pr-12 rounded-xl border border-gray-200/90 bg-white text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#0F172A] focus:ring-1 focus:ring-[#0F172A] shadow-sm transition"
                    />
                    <kbd class="pointer-events-none absolute right-3 hidden sm:inline-flex items-center rounded border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-semibold text-gray-400">
                        F1
                    </kbd>
                </div>

                <!-- Category Filter Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                    <button
                        type="button"
                        @click="selectedCategory = 'all'"
                        class="shrink-0 px-4 py-2 rounded-xl text-xs font-medium transition-all"
                        :class="selectedCategory === 'all'
                            ? 'bg-[#0F172A] text-white shadow-sm font-semibold'
                            : 'bg-white text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-gray-200/80'"
                    >
                        All
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        @click="selectedCategory = cat.slug"
                        class="shrink-0 px-4 py-2 rounded-xl text-xs font-medium transition-all"
                        :class="selectedCategory === cat.slug
                            ? 'bg-[#0F172A] text-white shadow-sm font-semibold'
                            : 'bg-white text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-gray-200/80'"
                    >
                        {{ cat.name }}
                    </button>
                </div>

                <!-- Products Grid -->
                <div v-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white p-12 text-center">
                    <svg class="h-10 w-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="text-sm font-medium text-gray-600">No products found</p>
                    <p class="text-xs text-gray-400 mt-0.5">Try searching for a different keyword or category</p>
                </div>

                <div v-else class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5">
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="group flex flex-col justify-between rounded-2xl border border-gray-200/80 bg-white p-3 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-md hover:border-gray-300 transition-all duration-150"
                    >
                        <div>
                            <div class="aspect-square rounded-xl bg-gray-50 overflow-hidden mb-2.5 border border-gray-100 relative">
                                <img
                                    v-if="productImageUrl(product)"
                                    :src="productImageUrl(product)"
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex flex-col items-center justify-center text-xs text-gray-400 gap-1"
                                >
                                    <svg class="h-6 w-6 stroke-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>No Image</span>
                                </div>
                            </div>
                            <p class="text-xs font-semibold text-gray-900 line-clamp-1 mb-1" :title="product.name">{{ product.name }}</p>
                            <p v-if="product.category" class="text-[11px] text-gray-400 mb-2 truncate">{{ product.category.name }}</p>
                        </div>

                        <!-- Variants Selector / Quick Add -->
                        <div class="space-y-1.5 pt-2 border-t border-gray-100">
                            <div
                                v-for="variant in product.variants"
                                :key="variant.id"
                                class="flex items-center justify-between text-xs"
                            >
                                <div class="min-w-0 pr-1">
                                    <span class="block font-medium text-gray-900">₱{{ Number(variant.price).toLocaleString() }}</span>
                                    <span class="block text-[10px] text-gray-400 truncate">{{ variant.label || 'Default' }}</span>
                                </div>
                                <button
                                    type="button"
                                    @click="addToCart(product, variant)"
                                    :disabled="(variant.stock - (variant.reserved_stock ?? 0)) <= 0"
                                    class="shrink-0 px-2.5 py-1 rounded-lg bg-[#0F172A] hover:bg-black text-white text-xs font-medium disabled:opacity-30 disabled:hover:bg-[#0F172A] transition"
                                >
                                    + Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Cart (3) Panel (4-5 Cols) -->
            <div class="lg:col-span-5 xl:col-span-4">
                <div class="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-sm sticky top-6">
                    <div class="flex items-center justify-between pb-3.5 border-b border-gray-100 mb-3.5">
                        <div class="flex items-center gap-2">
                            <h2 class="font-bold text-gray-900 text-base">Cart</h2>
                            <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-800">
                                {{ cartItemCount }}
                            </span>
                        </div>
                        <button
                            v-if="cart.length > 0"
                            type="button"
                            @click="clearCart"
                            class="text-xs font-medium text-rose-500 hover:text-rose-700 transition"
                        >
                            Clear
                        </button>
                    </div>

                    <!-- Cart Item List -->
                    <div v-if="cart.length === 0" class="flex flex-col items-center justify-center py-12 text-center text-gray-400">
                        <svg class="h-10 w-10 text-gray-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <p class="text-sm font-medium text-gray-500">Cart is empty</p>
                        <p class="text-xs text-gray-400 mt-0.5">Select items from catalog to start sale</p>
                    </div>

                    <div v-else class="space-y-3 max-h-[340px] overflow-y-auto pr-1">
                        <div
                            v-for="(item, i) in cart"
                            :key="item.product_variant_id"
                            class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 bg-gray-50/50 p-2.5"
                        >
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <div class="h-10 w-10 shrink-0 rounded-lg bg-gray-200 overflow-hidden border border-gray-100">
                                    <img v-if="item.image" :src="item.image" :alt="item.name" class="h-full w-full object-cover" />
                                    <div v-else class="h-full w-full flex items-center justify-center text-[9px] text-gray-400">Item</div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-xs text-gray-900 truncate">{{ item.name }}</p>
                                    <p class="text-[10px] text-gray-400">{{ item.label }} · ₱{{ item.price.toLocaleString() }}</p>
                                </div>
                            </div>

                            <!-- Stepper & Price -->
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="inline-flex items-center rounded-lg border border-gray-200 bg-white">
                                    <button
                                        type="button"
                                        @click="decrementCart(item, i)"
                                        class="px-2 py-0.5 text-xs text-gray-600 hover:bg-gray-100 rounded-l-lg"
                                    >
                                        -
                                    </button>
                                    <span class="px-2 text-xs font-semibold text-gray-900">{{ item.quantity }}</span>
                                    <button
                                        type="button"
                                        @click="incrementCart(item)"
                                        :disabled="item.stock && item.quantity >= item.stock"
                                        class="px-2 py-0.5 text-xs text-gray-600 hover:bg-gray-100 rounded-r-lg disabled:opacity-30"
                                    >
                                        +
                                    </button>
                                </div>
                                <div class="text-right w-16">
                                    <p class="text-xs font-bold text-gray-900">₱{{ (item.price * item.quantity).toFixed(2) }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click="removeFromCart(i)"
                                    class="text-gray-400 hover:text-rose-500 text-xs px-1"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="mt-4 pt-4 border-t border-gray-100 space-y-2 text-xs">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal</span>
                            <span class="font-medium text-gray-700">₱{{ cartTotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Discount</span>
                            <span class="font-medium text-gray-700">₱0.00</span>
                        </div>
                        <div class="flex justify-between items-baseline pt-2 border-t border-gray-200/80 text-sm">
                            <span class="font-bold text-gray-900">Total</span>
                            <span class="text-xl font-extrabold text-gray-900">₱{{ cartTotal.toFixed(2) }}</span>
                        </div>
                    </div>

                    <!-- Payment Method Toggle -->
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-700 mb-2">Payment Method</p>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                @click="paymentMethod = 'cash'"
                                class="flex flex-col items-center justify-center py-2.5 rounded-xl border text-xs font-medium transition"
                                :class="paymentMethod === 'cash'
                                    ? 'border-[#0F172A] bg-slate-900 text-white shadow-sm'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                            >
                                <span class="font-bold text-sm">₱</span>
                                <span class="text-[11px] mt-0.5">Cash</span>
                            </button>
                            <button
                                type="button"
                                @click="paymentMethod = 'atm'"
                                class="flex flex-col items-center justify-center py-2.5 rounded-xl border text-xs font-medium transition"
                                :class="paymentMethod === 'atm'
                                    ? 'border-[#0F172A] bg-slate-900 text-white shadow-sm'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                            >
                                <span class="text-sm">💳</span>
                                <span class="text-[11px] mt-0.5">Card</span>
                            </button>
                            <button
                                type="button"
                                @click="paymentMethod = 'ewallet'"
                                class="flex flex-col items-center justify-center py-2.5 rounded-xl border text-xs font-medium transition"
                                :class="paymentMethod === 'ewallet'
                                    ? 'border-[#0F172A] bg-slate-900 text-white shadow-sm'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                            >
                                <span class="text-sm">📱</span>
                                <span class="text-[11px] mt-0.5">QR / E-wallet</span>
                            </button>
                        </div>
                    </div>

                    <!-- Error Flash -->
                    <div v-if="$page.props.errors?.payment || $page.props.errors?.checkout" class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-700">
                        {{ $page.props.errors.payment || $page.props.errors.checkout }}
                    </div>

                    <!-- Checkout Button -->
                    <button
                        type="button"
                        @click="checkout"
                        :disabled="cart.length === 0 || processing"
                        class="mt-4 w-full h-12 rounded-xl bg-[#0F172A] hover:bg-black text-white font-semibold text-sm flex items-center justify-center gap-2 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed transition duration-150"
                    >
                        <span>{{ processing ? 'Processing...' : (paymentMethod === 'cash' ? 'Complete Payment' : 'Pay with PayMongo') }}</span>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Sales Section -->
        <div class="mt-10">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-gray-900 text-base">Recent POS Transactions</h2>
                <span class="text-xs text-gray-400">Latest 10 sales</span>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50/80 border-b border-gray-200/80 uppercase text-gray-500 font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Receipt / Sale #</th>
                                <th class="px-4 py-3">Items</th>
                                <th class="px-4 py-3">Payment</th>
                                <th class="px-4 py-3">Total Amount</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="sale in recentSales" :key="sale.id" class="hover:bg-gray-50/60 transition">
                                <td class="px-4 py-3.5 font-medium text-gray-900">{{ sale.sale_number }}</td>
                                <td class="px-4 py-3.5 text-gray-500">{{ sale.items?.length || 0 }} item(s)</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase bg-slate-100 text-slate-700">
                                        {{ sale.payment_method }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 font-bold text-gray-900">₱{{ Number(sale.total_amount).toFixed(2) }}</td>
                                <td class="px-4 py-3.5 text-right">
                                    <button
                                        type="button"
                                        @click="viewReceipt(sale)"
                                        class="rounded-lg border border-gray-200 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition"
                                    >
                                        View Receipt
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!recentSales || recentSales.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">No transactions recorded yet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Receipt Modal -->
        <div v-if="receipt" class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="closeReceipt">
            <div id="pos-receipt" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-gray-200">
                <div class="text-center">
                    <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#0F172A] text-white mb-2">
                        <svg viewBox="0 0 200 130" class="h-5 w-auto fill-white">
                            <path d="M155 12 L45 12 L58 34 L47 55 L138 55 L20 88 L8 108 L118 108 L105 86 L116 65 L25 65 L143 32 Z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-extrabold text-gray-900">{{ receipt.branch_name }}</h2>
                    <p class="text-xs text-gray-500 font-medium">Official Sales Receipt</p>
                    <p class="mt-2 text-xs text-gray-400 font-mono">{{ receipt.sale_number }} · {{ receipt.created_at }}</p>
                </div>

                <div class="my-5 border-y border-dashed border-gray-200 py-3.5">
                    <div v-for="item in receipt.items" :key="`${item.product_name}-${item.variant_label}`" class="mb-2.5 flex justify-between gap-4 text-xs last:mb-0">
                        <div>
                            <p class="font-semibold text-gray-900">{{ item.product_name }}</p>
                            <p class="text-[11px] text-gray-500">{{ item.variant_label || 'Default' }} · {{ item.quantity }} × ₱{{ Number(item.unit_price).toFixed(2) }}</p>
                        </div>
                        <span class="font-bold text-gray-900">₱{{ Number(item.line_total).toFixed(2) }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-gray-600">
                    <span>Payment Method</span>
                    <span class="font-semibold uppercase text-gray-900">{{ receipt.payment_method }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-100">
                    <span>Total Amount</span>
                    <span class="text-xl">₱{{ Number(receipt.total_amount).toFixed(2) }}</span>
                </div>

                <div class="mt-6 flex gap-2 print:hidden">
                    <button type="button" @click="closeReceipt" class="flex-1 rounded-xl border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">Close</button>
                    <button type="button" @click="printReceipt" class="flex-1 rounded-xl bg-[#0F172A] hover:bg-black px-4 py-2 text-xs font-semibold text-white transition">Print Receipt</button>
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