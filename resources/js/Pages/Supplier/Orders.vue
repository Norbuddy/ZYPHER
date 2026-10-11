<script setup>
import { Head, router } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { ref, computed } from 'vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({ orders: Array });

const orders = computed(() => props.orders || []);
const notification = ref({ show: false, message: '', type: 'danger' });
let notificationTimeout = null;

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

const updateStatus = async (orderId, action) => {
    try {
        const routeName = `supplier.orders.${action}`;
        const url = route(routeName, { order: orderId });
        await router.post(url, {}, { preserveState: true, preserveScroll: true });
    } catch (e) {
        console.error(e);
        let message = e?.response?.data?.message
            || e?.response?.data?.errors?.order?.[0]
            || e?.response?.data?.errors?.items?.[0]
            || e?.response?.data?.errors?.status?.[0]
            || e?.message
            || 'Unable to update order status.';
        if (typeof message === 'object') {
            message = JSON.stringify(message);
        }
        showNotification(message);
    }
};


const startConversation = async (teamId) => {
    try {
        await router.post(route('supplier.messages.start'), { team_id: teamId });
    } catch (e) {
        console.error(e);
        showNotification('Unable to start conversation.');
    }
};

const humanStatus = (status) => {
    if (!status) {
        return 'Pending';
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
    }[status] ?? status.split('_').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
};

const getOrderStatus = (order) => {
    if (order?.status === 'accepted') {
        return 'Accepted';
    }

    if (order?.status === 'rejected') {
        return 'Rejected';
    }

    if (order?.status === 'completed') {
        return 'Completed';
    }

    if (order?.shipment_status === 'shipped') {
        return 'Shipped';
    }

    if (order?.shipment_status === 'delivered') {
        return 'Delivered';
    }

    if (order?.shipment_status === 'delivery_attempt_unsuccessful') {
        return 'Delivery Attempt Unsuccessful';
    }

    if (order?.shipment_status === 'cancelled') {
        return 'Cancelled';
    }

    if (order?.shipment_status === 'pending') {
        return 'Pending';
    }

    return humanStatus(order?.status);
};

const groupOrderItems = (items) => {
    if (!items || items.length === 0) {
        return [];
    }

    const groups = {};

    items.forEach((item) => {
        const key = `${item.id}::${item.name}`;

        if (!groups[key]) {
            groups[key] = {
                name: item.name,
                image_url: item.image_url ?? item.imageUrl ?? null,
                quantities: [],
                total: 0,
            };
        }

        const label = item.variant_label ?? null;
        const quantity = Number(item.quantity || 0);

        if (label) {
            const existing = groups[key].quantities.find((q) => q.label === label);
            if (existing) {
                existing.quantity += quantity;
            } else {
                groups[key].quantities.push({ label, quantity });
            }
        } else {
            groups[key].quantities.push({ label: 'Quantity', quantity, isSimple: true });
        }

        groups[key].total += quantity;
    });

    return Object.values(groups);
};
</script>

<template>
    <div class="space-y-6">
        <Head title="Supplier Orders" />

        <div class="mx-auto max-w-6xl">
            <div class="mb-4">
                <h1 class="text-2xl font-semibold">Incoming Purchase Orders</h1>
                <p class="text-sm text-gray-500">Review and manage orders from connected shops.</p>
            </div>

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
                    <button @click.prevent="closeNotification" class="rounded-full p-1 text-slate-500 transition hover:bg-white/70 hover:text-slate-700">
                        <span class="text-lg leading-none">×</span>
                    </button>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-gray-600 uppercase text-xs">
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Shop Name</th>
                                <th class="px-4 py-3">Order ID</th>
                                <th class="px-4 py-3">Quantity</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Date Ordered</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in orders" :key="order.id" class="border-b border-gray-100">
                                <td class="px-4 py-4 align-top">
                                    <div class="font-semibold text-slate-900">{{ order.products || '—' }}</div>
                                    <div v-if="order.items && order.items.length > 0" class="mt-2 text-sm text-gray-500">
                                        <template v-for="group in groupOrderItems(order.items)" :key="group.name">
                                            <div class="mt-3">
                                                <div class="flex items-center gap-3">
                                        <img v-if="group.image_url" :src="group.image_url" class="h-10 w-10 rounded-lg object-cover" />
                                        <div class="font-medium text-slate-900">{{ group.name }}</div>
                                    </div>
                                                <div v-if="group.quantities.some(q => q.label !== 'Quantity')" class="mt-2 space-y-1">
                                                    <div class="text-xs uppercase tracking-wide text-gray-400">Sizes Ordered</div>
                                                    <div v-for="q in group.quantities" :key="q.label" class="text-sm leading-6">
                                                        {{ q.label }} = {{ q.quantity }}
                                                    </div>
                                                </div>
                                                <div v-else class="text-sm leading-6">
                                                    Quantity: {{ group.total }}
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="mt-3 text-sm font-medium text-gray-700">Total Quantity: {{ order.quantity }}</div>
                                </td>
                                <td class="px-4 py-4">{{ order.shop_name }}</td>
                                <td class="px-4 py-4">{{ order.order_number }}</td>
                                <td class="px-4 py-4">{{ order.quantity }}</td>
                                <td class="px-4 py-4">₱{{ order.total_amount }}</td>
                                <td class="px-4 py-4">{{ order.date_ordered }}</td>
                                <td class="px-4 py-4">{{ getOrderStatus(order) }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            v-if="['pending', 'pending_reservation'].includes(order.status)"
                                            @click.stop.prevent="updateStatus(order.id, 'accept')"
                                            class="inline-flex cursor-pointer px-3 py-1 rounded bg-green-500 text-white text-xs pointer-events-auto relative z-10"
                                            style="position: relative; z-index: 10;"
                                        >
                                            Accept
                                        </button>
                                        <button
                                            type="button"
                                            v-if="['pending', 'pending_reservation'].includes(order.status)"
                                            @click.stop.prevent="updateStatus(order.id, 'reject')"
                                            class="inline-flex cursor-pointer px-3 py-1 rounded bg-red-500 text-white text-xs pointer-events-auto relative z-10"
                                            style="position: relative; z-index: 10;"
                                        >
                                            Reject
                                        </button>
                                        <button
                                            type="button"
                                            @click.stop.prevent="startConversation(order.team_id)"
                                            class="inline-flex cursor-pointer px-3 py-1 rounded bg-blue-600 text-white text-xs"
                                        >
                                            Start Conversation
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="orders.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-gray-400">No orders found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
