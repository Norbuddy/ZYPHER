<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SellerLayout from '@/Layouts/SellerLayout.vue';

defineOptions({ layout: SellerLayout });

const props = defineProps({
  suppliers: { type: Array, default: () => [] },
  selectedSupplier: { type: Object, default: null },
  catalog: { type: Array, default: () => [] },
  analytics: {
    type: Object,
    default: () => ({
      total_purchases: 0,
      top_supplier_name: 'N/A',
      total_spend: 0,
    }),
  },
  recentOrders: { type: Array, default: () => [] },
});

const selectedSupplierId = ref(props.selectedSupplier?.id ?? null);
const selectedProducts = ref([]);
const showModal = ref(false);
const orderItems = ref([]);
const paymentMethod = ref('card');
const isPaying = ref(false);
const notification = ref({ show: false, message: '', type: 'danger' });
let notificationTimeout = null;

const paymentOptions = [
  { value: 'card', label: 'Card', desc: 'Visa / Mastercard' },
  { value: 'gcash', label: 'GCash', desc: 'E-wallet' },
  { value: 'paymaya', label: 'Maya', desc: 'E-wallet' },
  { value: 'grab_pay', label: 'GrabPay', desc: 'E-wallet' },
  { value: 'qrph', label: 'QR Ph', desc: 'Scan to pay' },
];

const showNotification = (message, type = 'danger') => {
  if (notificationTimeout) {
    window.clearTimeout(notificationTimeout);
  }

  notification.value = {
    show: true,
    message,
    type,
  };

  notificationTimeout = window.setTimeout(() => {
    notification.value.show = false;
  }, 4000);
};

const closeNotification = () => {
  notification.value.show = false;
};

const getProductVariants = (product) => {
  return Array.isArray(product?.variants) ? product.variants : [];
};

const makeOrderItem = (product, variant = null, quantity = 1) => {
  const itemVariant = variant ?? getProductVariants(product)[0] ?? null;

  return {
    uid: `${product.id}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    id: product.id,
    name: product.name,
    quantity,
    unitPrice: Number(product.min_price ?? 0),
    total: Number(product.min_price ?? 0) * quantity,
    available_stock: Number(itemVariant?.stock ?? product.available_stock ?? 0),
    reserved_stock: Number(itemVariant?.reserved_stock ?? 0),
    variantId: itemVariant?.id ?? null,
    variant_label: itemVariant?.label ?? itemVariant?.size ?? null,
  };
};

const syncItemVariant = (item) => {
  const product = props.catalog.find((candidate) => candidate.id === item.id);
  const variants = getProductVariants(product);
  const chosenVariant = variants.find((variant) => String(variant.id) === String(item.variantId))
    ?? variants[0]
    ?? null;

  if (!chosenVariant) {
    item.variantId = null;
    item.variant_label = null;
    item.available_stock = Number(item.available_stock ?? 0);
    item.reserved_stock = Number(item.reserved_stock ?? 0);
    return;
  }

  item.variantId = chosenVariant.id;
  item.variant_label = chosenVariant.label ?? chosenVariant.size ?? null;
  item.available_stock = Number(chosenVariant.stock ?? chosenVariant.available_stock ?? 0);
  item.reserved_stock = Number(chosenVariant.reserved_stock ?? 0);
};

const supplierProducts = computed(() => {
  if (!selectedSupplierId.value) {
    return [];
  }

  return props.catalog.filter((product) => product.supplier_id === selectedSupplierId.value);
});

const toggleProduct = (product) => {
  const exists = selectedProducts.value.some((item) => item.id === product.id);

  if (exists) {
    selectedProducts.value = selectedProducts.value.filter((item) => item.id !== product.id);
    return;
  }

  selectedProducts.value = [...selectedProducts.value, product];
};

const addToOrder = () => {
  selectedProducts.value.forEach((product) => {
    orderItems.value.push(makeOrderItem(product));
  });

  selectedProducts.value = [];
  showModal.value = false;
};

const updateQuantity = (item, value) => {
  const qty = Math.max(1, Number(value) || 1);
  item.quantity = qty;
  item.total = qty * item.unitPrice;
};

const productVariantsForItem = (item) => {
  const product = props.catalog.find((candidate) => candidate.id === item.id);
  return getProductVariants(product);
};

const getNextVariantForItem = (item) => {
  const variants = productVariantsForItem(item);
  if (!variants.length) {
    return null;
  }

  const usedVariantIds = new Set(
    orderItems.value
      .filter((row) => row.id === item.id && row.uid !== item.uid)
      .map((row) => row.variantId)
      .filter(Boolean),
  );

  return variants.find((variant) => !usedVariantIds.has(variant.id)) ?? variants[0] ?? null;
};

const addSizeLine = (item) => {
  const nextVariant = getNextVariantForItem(item);
  if (!nextVariant) {
    return;
  }

  orderItems.value.push(makeOrderItem(
    props.catalog.find((product) => product.id === item.id),
    nextVariant,
    1,
  ));
};

const removeItem = (itemUid) => {
  orderItems.value = orderItems.value.filter((item) => item.uid !== itemUid);
};

const clearOrder = () => {
  orderItems.value = [];
};

const orderShipmentLabel = (order) => {
  if (order.status === 'rejected') {
    return 'rejected';
  }

  if (order.shipment_status) {
    return order.shipment_status;
  }

  return 'pending';
};

const orderShipmentClass = (order) => {
  if (order.status === 'rejected' || order.shipment_status === 'cancelled') {
    return 'rounded-full bg-red-100 px-3 py-1 text-xs uppercase tracking-wide text-red-700';
  }

  if (order.shipment_status === 'shipped' || order.shipment_status === 'delivered') {
    return 'rounded-full bg-emerald-100 px-3 py-1 text-xs uppercase tracking-wide text-emerald-700';
  }

  if (!order.shipment_status) {
    return 'rounded-full bg-amber-100 px-3 py-1 text-xs uppercase tracking-wide text-amber-800';
  }

  return 'rounded-full bg-slate-100 px-3 py-1 text-xs uppercase tracking-wide text-slate-600';
};

const formatCurrency = (amount) => {
  return Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const hasOutOfStockOrder = computed(() => {
  return orderItems.value.some((item) => Number(item.quantity ?? 0) > Number(item.available_stock ?? 0) + Number(item.reserved_stock ?? 0));
});

const hasVariantSelection = (item) => {
  return productVariantsForItem(item).length > 0;
};

const reserveProduct = (product, quantity = 1) => {
  if (!selectedSupplierId.value) {
    return;
  }

  router.post(route('seller.supplier.products.reserve', product.id), { quantity }, {
    onSuccess: () => {
      showNotification(`Reserved ${quantity} unit${quantity > 1 ? 's' : ''} of ${product.name}.`, 'success');
      product.available_stock = Math.max(0, Number(product.available_stock ?? 0) - quantity);
      product.reserved_stock = Number(product.reserved_stock ?? 0) + quantity;

      const orderItem = orderItems.value.find((item) => item.id === product.id);
      if (orderItem) {
        orderItem.available_stock = Math.max(0, Number(orderItem.available_stock ?? 0) - quantity);
        orderItem.reserved_stock = Number(orderItem.reserved_stock ?? 0) + quantity;
      }
    },
    onError: (errors) => {
      showNotification(errors?.items ?? 'Unable to reserve this product.');
    },
  });
};

const needsReservation = (item) => {
  return Number(item.quantity ?? 0) > Number(item.available_stock ?? 0) + Number(item.reserved_stock ?? 0);
};

const reserveQuantity = (item) => {
  const shortage = Number(item.quantity ?? 1) - (Number(item.available_stock ?? 0) + Number(item.reserved_stock ?? 0));
  return Math.max(0, Math.min(Number(item.available_stock ?? 0), shortage));
};

const reserveOrderItem = (item) => {
  if (!selectedSupplierId.value) {
    return;
  }

  const quantity = reserveQuantity(item);
  if (quantity <= 0) {
    return;
  }

  router.post(route('seller.supplier.products.reserve', item.id), { quantity }, {
    onSuccess: () => {
      showNotification(`Reserved ${quantity} unit${quantity > 1 ? 's' : ''} for ${item.name}.`, 'success');
      item.available_stock = Math.max(0, Number(item.available_stock ?? 0) - quantity);
      item.reserved_stock = Number(item.reserved_stock ?? 0) + quantity;
    },
    onError: (errors) => {
      showNotification(errors?.items ?? 'Unable to reserve this item.');
    },
  });
};

const createOrder = () => {
  if (orderItems.value.length === 0 || !selectedSupplierId.value) {
    return;
  }

  if (hasOutOfStockOrder.value) {
    showNotification('One or more selected products exceed available stock and must be reserved before ordering.', 'warning');
    return;
  }

  isPaying.value = true;

  router.post(route('seller.supplier.orders.store'), {
    supplier_id: selectedSupplierId.value,
    items: orderItems.value,
    payment_method: paymentMethod.value,
  }, {
    onSuccess: () => {
      orderItems.value = [];
      isPaying.value = false;
    },
    onError: () => {
      isPaying.value = false;
    },
    onFinish: () => {
      isPaying.value = false;
    },
  });
};
const reorderFromPrevious = (order) => {
  selectedSupplierId.value = order.supplier_id;

  const items = Array.isArray(order.items) ? order.items : [];

  const newItems = items.map((prevItem) => {
    const product = props.catalog.find((p) => p.id === prevItem.id);
    if (!product) {
      return { ...prevItem, uid: `${prevItem.id}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}` };
    }
    const matchedVariant = getProductVariants(product).find((v) => String(v.id) === String(prevItem.variantId));
    return makeOrderItem(product, matchedVariant, prevItem.quantity);
  });

  orderItems.value = newItems;
  showNotification(`Loaded ${newItems.length} item(s) from order #${order.order_number}. Review and submit below.`, 'success');

  document.getElementById('order-summary')?.scrollIntoView({ behavior: 'smooth' });
};
const confirmReceived = (order) => {
  if (!confirm(`Confirm that you received order #${order.order_number}? This will add the items to your inventory.`)) {
    return;
  }

  router.post(route('seller.supplier.orders.confirm-received', order.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showNotification('Order confirmed as received. Inventory updated.', 'success');
    },
    onError: () => {
      showNotification('Unable to confirm this order. It may have already been processed.', 'error');
    },
  });
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-[#101114]">Supplier Management</h1>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-6">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Choose Supplier</label>
                <div>
                    <select v-model="selectedSupplierId" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-[#101114]" :disabled="props.suppliers.length === 0">
                        <option :value="null" disabled>Select supplier</option>
                        <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">
                            {{ supplier.name }}
                        </option>
                    </select>
                </div>
                <div v-if="props.suppliers.length === 0" class="mt-3 rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">
                    No suppliers are configured yet. Please add a supplier record in the admin panel first.
                </div>
                </div>

                <div class="mb-6">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-[#101114]">Product Catalog</h2>
                        <span class="text-sm text-gray-500">{{ supplierProducts.length }} items</span>
                    </div>
                    <div class="max-h-[320px] space-y-3 overflow-y-auto pr-1">
                        <div v-if="supplierProducts.length === 0" class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">
                            No catalog products available for this supplier.
                        </div>
                        <div v-for="product in supplierProducts" :key="product.id" class="flex items-center gap-4 rounded-xl border border-gray-200 p-4">
                            <img v-if="product.images?.length" :src="product.images[0].url" class="h-16 w-16 rounded-lg object-cover" />
                            <div v-else class="flex h-16 w-16 items-center justify-center rounded-lg bg-gray-100 text-xs text-gray-400">No image</div>
                            <div class="flex-1">
                                <p class="font-semibold text-[#101114]">{{ product.name }}</p>
                                <p class="text-sm text-gray-500">SKU / ID: {{ product.id }}</p>
                                <p class="text-sm text-gray-500">Available Stock: {{ product.available_stock ?? 0 }}</p>
                                <p v-if="product.reserved_stock > 0" class="text-sm text-amber-600">Reserved: {{ product.reserved_stock }}</p>
                                <p v-if="product.available_stock <= 0" class="text-sm font-medium text-red-600">Out of stock — reserve first</p>
                                <p class="text-sm text-gray-500">Supplier Price: ₱{{ Number(product.min_price ?? 0).toLocaleString() }}</p>
                            </div>
                            <button
                                v-if="product.available_stock <= 0"
                                @click="reserveProduct(product, 1)"
                                class="rounded-xl bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                            >
                                Reserve
                            </button>
                        </div>
                    </div>
                </div>

                <button @click="showModal = true" class="rounded-xl bg-[#101114] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#1f2330]">
                    Choose Product
                </button>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-[#101114]">Supplier Analytics</h2>
                </div>
                <div class="space-y-3">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Total Purchases</p>
                        <p class="mt-1 text-2xl font-semibold text-[#101114]">{{ analytics.total_purchases }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Top Supplier</p>
                        <p class="mt-1 text-xl font-semibold text-[#101114]">{{ analytics.top_supplier_name }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm text-gray-500">Total Spend</p>
                        <p class="mt-1 text-2xl font-semibold text-[#101114]">₱{{ Number(analytics.total_spend ?? 0).toLocaleString() }}</p>
                    </div>
                </div>
            </section>
        </div>

        <section id="order-summary" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div
                v-if="notification.show"
                :class="[
                    'pointer-events-auto fixed right-5 top-5 z-[60] w-[min(420px,calc(100vw-2rem))] rounded-2xl border px-4 py-3 shadow-lg shadow-slate-900/10 transition-all duration-300 ease-out',
                    notification.type === 'success'
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                        : notification.type === 'warning'
                            ? 'border-amber-200 bg-amber-50 text-amber-800'
                            : 'border-red-200 bg-red-50 text-red-800'
                ]"
            >
                <div class="flex items-start gap-3">
                    <div
                        :class="[
                            'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-base font-semibold',
                            notification.type === 'success'
                                ? 'bg-emerald-100 text-emerald-700'
                                : notification.type === 'warning'
                                    ? 'bg-amber-100 text-amber-700'
                                    : 'bg-red-100 text-red-700'
                        ]"
                    >
                        <span v-if="notification.type === 'success'">✓</span>
                        <span v-else-if="notification.type === 'warning'">!</span>
                        <span v-else>×</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-[#101114]">
                            {{ notification.type === 'success' ? 'Success' : notification.type === 'warning' ? 'Notice' : 'Error' }}
                        </p>
                        <p class="mt-1 text-sm leading-5 text-slate-700">{{ notification.message }}</p>
                    </div>
                    <button @click="closeNotification" class="rounded-full p-1 text-slate-500 transition hover:bg-white/70 hover:text-slate-700">
                        <span class="text-lg leading-none">×</span>
                    </button>
                </div>
            </div>

            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-[#101114]">Create Orders</h2>
                <div class="space-x-2">
                    <button @click="clearOrder" class="rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Clear Order</button>
                    <button
                        @click="createOrder"
                        :disabled="orderItems.length === 0 || !selectedSupplierId || isPaying"
                        class="rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        {{ isPaying ? 'Redirecting to PayMongo…' : 'Pay with PayMongo' }}
                    </button>
                </div>
            </div>

            <!-- Payment method selection -->
            <div v-if="orderItems.length > 0" class="mb-4 rounded-xl border border-[#E4E4E0] bg-white p-4">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-medium text-[#101114]">Payment Method</p>
                    <span class="text-[11px] font-medium text-[#6D5EF8]">PayMongo</span>
                </div>
                <p class="mb-3 text-xs text-[#8A8B90]">You’ll complete payment securely on PayMongo for this restock order.</p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-5">
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
                    <div v-if="hasOutOfStockOrder" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        One or more selected products exceed available stock. The order will automatically reserve the shortage when submitted.
                    </div>
            <div class="space-y-3">
                <div v-for="item in orderItems" :key="item.uid" class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 md:flex-row md:items-center md:justify-between">
                    <div class="min-w-[220px] flex-1">
                        <p class="font-semibold text-[#101114]">{{ item.name }}</p>
                        <p class="text-sm text-gray-500">Unit Price: ₱{{ Number(item.unitPrice).toLocaleString() }}</p>
                        <p class="text-sm text-gray-500">Available: {{ item.available_stock ?? 0 }}</p>
                        <p v-if="item.reserved_stock > 0" class="text-sm text-amber-600">Reserved: {{ item.reserved_stock }}</p>
                        <div v-if="productVariantsForItem(item).length > 0" class="mt-2">
                            <label class="block text-xs font-medium uppercase tracking-wide text-gray-500">Size</label>
                            <select
                                v-model="item.variantId"
                                @change="syncItemVariant(item)"
                                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            >
                                <option v-for="variant in productVariantsForItem(item)" :key="variant.id" :value="variant.id">
                                    {{ variant.label ?? variant.size ?? `Variant #${variant.id}` }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-gray-500">Qty</label>
                        <input v-model.number="item.quantity" type="number" min="1" class="w-20 rounded-lg border border-gray-300 px-3 py-2 text-sm" @input="updateQuantity(item, $event.target.value)" />
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="font-semibold text-[#101114]">₱{{ Number(item.total).toLocaleString() }}</p>
                    </div>
                    <div class="flex flex-col gap-2 md:items-end">
                        <button
                            v-if="getNextVariantForItem(item)"
                            @click="addSizeLine(item)"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Add Size
                        </button>
                        <button
                            v-if="needsReservation(item) && reserveQuantity(item) > 0"
                            @click="reserveOrderItem(item)"
                            class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                        >
                            Reserve {{ reserveQuantity(item) }}
                        </button>
                        <button
                            v-else-if="needsReservation(item)"
                            disabled
                            class="rounded-lg border border-gray-300 bg-slate-100 px-3 py-2 text-sm text-slate-500 cursor-not-allowed"
                        >
                            Out of stock
                        </button>
                        <button @click="removeItem(item.uid)" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50">Remove</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-[#101114]">Order Tracking</h2>
                    <p class="text-sm text-gray-500">Track shipment progress and view tracking numbers for recent supplier orders.</p>
                </div>
            </div>

            <div class="space-y-4">
                <div v-if="props.recentOrders.length === 0" class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500">
                    No tracked orders yet. Create an order above and check back once the supplier updates shipment details.
                </div>
                <div v-else class="grid gap-4 xl:grid-cols-2">
                    <div v-for="order in props.recentOrders.slice(0, 6)" :key="order.id" class="rounded-2xl border border-gray-200 p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Order #{{ order.order_number }}</p>
                                <p class="mt-1 font-semibold text-[#101114]">{{ order.shop_name }}</p>
                            </div>
                            <span :class="orderShipmentClass(order)">{{ orderShipmentLabel(order) }}</span>
                        </div>
                        <div class="mt-4 text-sm text-gray-600 space-y-2">
                          <p><span class="font-medium text-slate-900">Status:</span> {{ order.status }}</p>
                          <p><span class="font-medium text-slate-900">Items:</span> {{ order.products || '—' }}</p>
                          <p><span class="font-medium text-slate-900">Total:</span> ₱{{ formatCurrency(order.total_amount) }}</p>
                      </div>
                      <button
                          v-if="order.shipment_status === 'delivered' && order.status !== 'completed'"
                          @click="confirmReceived(order)"
                          class="mt-3 w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                      >
                          ✓ Confirm Received
                      </button>
                      <p v-else-if="order.status === 'completed'" class="mt-3 text-center text-sm font-medium text-emerald-600">
                          ✓ Received — added to inventory
                      </p>
                      <button
                          @click="reorderFromPrevious(order)"
                          class="mt-3 w-full rounded-lg border border-[#6D5EF8] px-3 py-2 text-sm font-medium text-[#6D5EF8] hover:bg-[#6D5EF8]/10"
                      >
                          ↻ Reorder
                      </button>
                    </div>
                </div>
            </div>
        </section>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#101114]">Select Products</h3>
                    <button @click="showModal = false" class="text-sm text-gray-500">Close</button>
                </div>
                <div class="max-h-[360px] space-y-3 overflow-y-auto pr-1">
                    <div v-for="product in supplierProducts" :key="product.id" class="flex items-center justify-between rounded-xl border border-gray-200 p-4">
                        <div>
                            <p class="font-semibold text-[#101114]">{{ product.name }}</p>
                            <p class="text-sm text-gray-500">{{ product.id }}</p>
                            <p class="text-sm text-gray-500">Available: {{ product.available_stock ?? 0 }}</p>
                            <p v-if="product.reserved_stock > 0" class="text-sm text-amber-600">Reserved: {{ product.reserved_stock }}</p>
                            <p v-if="product.available_stock <= 0" class="text-sm font-medium text-red-600">Out of stock — reserve first</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                v-if="product.available_stock <= 0"
                                @click="reserveProduct(product, 1)"
                                class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700"
                            >
                                Reserve
                            </button>
                            <button
                                v-else
                                @click="toggleProduct(product)"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                {{ selectedProducts.some((item) => item.id === product.id) ? 'Selected' : 'Select' }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-600">Cancel</button>
                    <button @click="addToOrder" class="rounded-xl bg-[#101114] px-4 py-2 text-sm font-medium text-white">Add to Order</button>
                </div>
            </div>
        </div>
    </div>
</template>
