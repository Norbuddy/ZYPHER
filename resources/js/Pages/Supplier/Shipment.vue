<script setup>
import { Head, router } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { ref } from 'vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({ orders: Array });
const orders = ref(props.orders || []);

const orderStatusClasses = {
    pending: 'bg-amber-50 text-amber-700 border-amber-200',
    accepted: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    rejected: 'bg-rose-50 text-rose-700 border-rose-200',
    completed: 'bg-blue-50 text-blue-700 border-blue-200',
    cancelled: 'bg-slate-100 text-slate-700 border-slate-200',
};

const shipmentStatusClasses = {
    pending: 'bg-amber-50 text-amber-700 border-amber-200',
    shipped: 'bg-blue-50 text-blue-700 border-blue-200',
    delivered: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    delivery_attempt_unsuccessful: 'bg-rose-50 text-rose-700 border-rose-200',
    cancelled: 'bg-slate-100 text-slate-700 border-slate-200',
};

const formatStatus = (status, fallback = 'Pending') => {
    if (!status) {
        return fallback;
    }

    return {
        pending: 'Pending',
        pending_reservation: 'Pending',
        accepted: 'Accepted',
        rejected: 'Rejected',
        processing: 'Processing',
        completed: 'Completed',
        cancelled: 'Cancelled',
        ready_for_shipment: 'Ready for Shipment',
        shipped: 'Shipped',
        delivered: 'Delivered',
        delivery_attempt_unsuccessful: 'Delivery Attempt Unsuccessful',
    }[status] ?? status
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
};

const formatOrderStatusBadge = (status) => {
    const normalized = status ?? 'pending';
    return orderStatusClasses[normalized] ?? 'bg-slate-100 text-slate-700 border-slate-200';
};

const formatShipmentStatusBadge = (status) => {
    const normalized = status ?? 'pending';
    return shipmentStatusClasses[normalized] ?? 'bg-amber-50 text-amber-700 border-amber-200';
};

const groupShipmentItems = (items) => {
    if (!items || !Array.isArray(items) || items.length === 0) {
        return [];
    }

    const grouped = {};

    items.forEach((item) => {
        const key = `${item.id}::${item.name}`;

        if (!grouped[key]) {
            grouped[key] = {
                name: item.name,
                quantities: [],
                total: 0,
            };
        }

        const quantity = Number(item.quantity || 0);
        const label = item.variant_label ?? item.size ?? item.label ?? null;
        const productGroup = grouped[key];

        if (label) {
            const existing = productGroup.quantities.find((qty) => qty.label === label);
            if (existing) {
                existing.quantity += quantity;
            } else {
                productGroup.quantities.push({ label, quantity });
            }
        } else {
            const existing = productGroup.quantities.find((qty) => qty.label === 'Quantity');
            if (existing) {
                existing.quantity += quantity;
            } else {
                productGroup.quantities.push({ label: 'Quantity', quantity });
            }
        }

        productGroup.total += quantity;
    });

    return Object.values(grouped);
};

const updateStatus = async (orderId, status) => {
    try {
        await router.post(route('supplier.shipment.update_status', orderId), { shipment_status: status });
        const response = await router.get(route('supplier.shipment.index'), { preserveState: true });
        const newOrders = response.props?.orders ?? [];
        orders.value = newOrders;
    } catch (e) {
        console.error(e);
        window.location.reload();
    }
};
</script>

<template>
    <div class="space-y-6">
        <Head title="Shipment" />

        <div class="mx-auto max-w-7xl">
            <div class="mb-4">
                <h1 class="text-2xl font-semibold text-slate-900">Shipments</h1>
                <p class="text-sm text-slate-500">Manage shipping and update shipment state.</p>
            </div>

            <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-6">
                <div class="hidden md:grid md:grid-cols-12 md:items-center md:gap-4 md:rounded-lg md:bg-slate-50/80 md:p-3 md:text-xs md:font-semibold md:uppercase md:tracking-wider md:text-slate-500">
                    <div class="md:col-span-5">Order Details</div>
                    <div class="md:col-span-1">Shop</div>
                    <div class="md:col-span-2">Order Status</div>
                    <div class="md:col-span-2">Shipment Status</div>
                    <div class="md:col-span-2">Actions</div>
                </div>

                <div v-if="orders.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-10 text-center text-sm text-slate-400">
                    No shipments yet.
                </div>

                <div v-else class="mt-3 space-y-3">
                    <div
                        v-for="order in orders"
                        :key="order.id"
                        class="grid grid-cols-1 gap-4 border-b border-slate-100 px-3 py-4 last:border-0 md:grid-cols-12 md:items-center md:rounded-lg md:hover:bg-slate-50/50 md:transition"
                    >
                        <div class="md:col-span-5">
                            <div class="font-mono text-xs font-semibold text-slate-900">{{ order.order_number }}</div>

                            <div v-if="order.items && order.items.length > 0" class="mt-3 space-y-3 text-sm text-slate-700">
                                <template v-for="product in groupShipmentItems(order.items)" :key="product.name">
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ product.name }}</div>
                                        <div class="mt-1 flex flex-wrap gap-2">
                                            <span
                                                v-for="qty in product.quantities"
                                                :key="`${product.name}-${qty.label}`"
                                                class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700"
                                            >
                                                {{ qty.label }} (Qty: {{ qty.quantity }})
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-3 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                Total Quantity: {{ order.total_quantity ?? 0 }}
                            </div>
                        </div>

                        <div class="md:col-span-1">
                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700">
                                {{ order.shop_name }}
                            </span>
                        </div>

                        <div class="md:col-span-2">
                            <span :class="['inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium', formatOrderStatusBadge(order.status)]">
                                {{ formatStatus(order.status, 'Pending') }}
                            </span>
                        </div>

                        <div class="md:col-span-2">
                            <span :class="['inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium', formatShipmentStatusBadge(order.shipment_status)]">
                                {{ formatStatus(order.shipment_status, 'Pending Shipment') }}
                            </span>
                        </div>

                        <div class="md:col-span-2">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-if="order.shipment_status !== 'delivered' && order.shipment_status !== 'shipped'"
                                    @click.prevent="updateStatus(order.id, 'shipped')"
                                    class="rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700"
                                >
                                    Mark Shipped
                                </button>
                                <button
                                    v-if="order.shipment_status !== 'delivered'"
                                    @click.prevent="updateStatus(order.id, 'delivered')"
                                    class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white transition-colors hover:bg-emerald-700"
                                >
                                    Mark Delivered
                                </button>
                                <button
                                    v-if="order.shipment_status !== 'delivered' && order.shipment_status !== 'delivery_attempt_unsuccessful'"
                                    @click.prevent="updateStatus(order.id, 'delivery_attempt_unsuccessful')"
                                    class="rounded-lg bg-amber-600 px-2.5 py-1.5 text-xs font-medium text-white transition-colors hover:bg-amber-700"
                                >
                                    Failed Attempt
                                </button>
                                <button
                                    v-if="order.shipment_status !== 'delivered' && order.shipment_status !== 'cancelled'"
                                    @click.prevent="updateStatus(order.id, 'cancelled')"
                                    class="rounded-lg border border-rose-200 px-2.5 py-1.5 text-xs font-medium text-rose-600 transition-colors hover:bg-rose-50"
                                >
                                    Cancel Shipment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
