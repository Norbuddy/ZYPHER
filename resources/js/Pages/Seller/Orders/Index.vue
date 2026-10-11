<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, nextTick, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    orderShops: {
        type: Array,
        default: () => [],
    },
});

const shippingDialogFor = ref(null);
const selectedOrder = computed(() =>
    props.orderShops.find(
        order => order.id === shippingDialogFor.value
    ) ?? null
);

const shipForm = useForm({
    courier_name: 'Lalamove',
    tracking_number: '',
    shipping_method: 'Lalamove Delivery',

    store_address: '',
    store_latitude: null,
    store_longitude: null,

    delivery_distance_km: null,
    estimated_delivery_minutes: null,
});

const mapElement = ref(null);

let map = null;
let storeMarker = null;
let customerMarker = null;
let routeLine = null;

const columns = [
    {
        key: 'order_number',
        label: 'Order',
    },
    {
        key: 'customer_name',
        label: 'Customer',
    },
    {
        key: 'subtotal',
        label: 'Total',
    },
    {
        key: 'status',
        label: 'Status',
    },
    {
        key: 'actions',
        label: 'Action',
        sortable: false,
    },
];

const markProcessing = (id) => {
    router.post(
        route('seller.orders.processing', id),
        {},
        {
            preserveScroll: true,
        }
    );
};

const openShipDialog = async (id) => {
    shippingDialogFor.value = id;

    shipForm.reset();

    shipForm.courier_name = 'Lalamove';
    shipForm.shipping_method = 'Lalamove Delivery';

    await nextTick();

    initializeMap();
};

const closeShipDialog = () => {
    destroyMap();
    shippingDialogFor.value = null;
    shipForm.reset();

    shipForm.courier_name = 'Lalamove';
    shipForm.shipping_method = 'Lalamove Delivery';
};

const initializeMap = () => {
    if (!mapElement.value || !selectedOrder.value) {
        return;
    }

    destroyMap();

    const customer = selectedOrder.value.customer_address;

    const customerLat = Number(customer?.latitude);
    const customerLng = Number(customer?.longitude);

    const hasCustomerCoordinates =
        Number.isFinite(customerLat) &&
        Number.isFinite(customerLng);

    const defaultLat = hasCustomerCoordinates
        ? customerLat
        : 14.5995;

    const defaultLng = hasCustomerCoordinates
        ? customerLng
        : 120.9842;

    map = L.map(mapElement.value).setView(
        [defaultLat, defaultLng],
        hasCustomerCoordinates ? 13 : 12
    );

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution:
                '&copy; OpenStreetMap contributors',
        }
    ).addTo(map);

    if (hasCustomerCoordinates) {
        customerMarker = L.marker([
            customerLat,
            customerLng,
        ])
            .addTo(map)
            .bindPopup(
                `<strong>Customer</strong><br>${customer.full_name ?? ''}`
            )
            .openPopup();
    }

    map.on('click', (event) => {
        setStoreLocation(
            event.latlng.lat,
            event.latlng.lng
        );
    });
};

const geocodingStore = ref(false);
const storeGeocodeError = ref('');

const tryGeocode = async (query) => {
    const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ph&q=${encodeURIComponent(query)}`
    );
    return response.json();
};

const geocodeStoreAddress = async () => {
    if (!shipForm.store_address) {
        storeGeocodeError.value = 'Please type your store address first.';
        return;
    }

    geocodingStore.value = true;
    storeGeocodeError.value = '';

    try {
        const query = shipForm.store_address.includes('Philippines')
            ? shipForm.store_address
            : `${shipForm.store_address}, Philippines`;

        const parts = query.split(',').map(p => p.trim()).filter(Boolean);
        let results = [];

        for (let i = 0; i < parts.length; i++) {
            results = await tryGeocode(parts.slice(i).join(', '));
            if (results.length) break;
        }

        if (!results.length) {
            storeGeocodeError.value = 'Could not find that address. Try clicking your location on the map instead.';
            return;
        }

        setStoreLocation(parseFloat(results[0].lat), parseFloat(results[0].lon));

        if (map) {
            map.setView([parseFloat(results[0].lat), parseFloat(results[0].lon)], 15);
        }
    } catch (e) {
        storeGeocodeError.value = 'Something went wrong while locating your address.';
    } finally {
        geocodingStore.value = false;
    }
};

const setStoreLocation = (
    latitude,
    longitude
) => {
    if (!map) return;

    shipForm.store_latitude = latitude;
    shipForm.store_longitude = longitude;

    if (storeMarker) {
        storeMarker.setLatLng([
            latitude,
            longitude,
        ]);
    } else {
        storeMarker = L.marker([
            latitude,
            longitude,
        ])
            .addTo(map)
            .bindPopup('<strong>Your Store</strong>');
    }

    calculateDelivery();
};

const calculateDelivery = () => {
    if (
        !selectedOrder.value?.customer_address
    ) {
        return;
    }

    const customerLat = Number(
        selectedOrder.value.customer_address.latitude
    );

    const customerLng = Number(
        selectedOrder.value.customer_address.longitude
    );

    const storeLat = Number(
        shipForm.store_latitude
    );

    const storeLng = Number(
        shipForm.store_longitude
    );

    if (
        !Number.isFinite(customerLat) ||
        !Number.isFinite(customerLng) ||
        !Number.isFinite(storeLat) ||
        !Number.isFinite(storeLng)
    ) {
        return;
    }

    const distance = haversineDistance(
        storeLat,
        storeLng,
        customerLat,
        customerLng
    );

    shipForm.delivery_distance_km =
        Number(distance.toFixed(2));

    /*
     * Approximate city delivery speed.
     * This is NOT live Lalamove ETA yet.
     */
    const averageSpeedKmh = 20;

    const minutes = Math.max(
        15,
        Math.ceil(
            (distance / averageSpeedKmh) * 60
        )
    );

    shipForm.estimated_delivery_minutes =
        minutes;

    drawRouteLine(
        storeLat,
        storeLng,
        customerLat,
        customerLng
    );
};

const haversineDistance = (
    lat1,
    lon1,
    lat2,
    lon2
) => {
    const earthRadius = 6371;

    const dLat =
        toRadians(lat2 - lat1);

    const dLon =
        toRadians(lon2 - lon1);

    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRadians(lat1)) *
        Math.cos(toRadians(lat2)) *
        Math.sin(dLon / 2) ** 2;

    return (
        earthRadius *
        2 *
        Math.atan2(
            Math.sqrt(a),
            Math.sqrt(1 - a)
        )
    );
};

const toRadians = (degrees) =>
    degrees * Math.PI / 180;

const drawRouteLine = (
    storeLat,
    storeLng,
    customerLat,
    customerLng
) => {
    if (!map) return;

    if (routeLine) {
        map.removeLayer(routeLine);
    }

    routeLine = L.polyline(
        [
            [storeLat, storeLng],
            [customerLat, customerLng],
        ],
        {
            color: '#6D5EF8',
            weight: 4,
            dashArray: '8, 8',
        }
    ).addTo(map);

    const bounds = L.latLngBounds([
        [storeLat, storeLng],
        [customerLat, customerLng],
    ]);

    map.fitBounds(
        bounds,
        {
            padding: [40, 40],
        }
    );
};

const formattedAddress = computed(() => {
    const address =
        selectedOrder.value?.customer_address;

    if (!address) {
        return 'Customer address unavailable';
    }

    return [
        address.line1,
        address.line2,
        address.city,
        address.province,
        address.postal_code,
    ]
        .filter(Boolean)
        .join(', ');
});

const formattedEta = computed(() => {
    const minutes =
        Number(
            shipForm.estimated_delivery_minutes
        );

    if (!minutes) {
        return '—';
    }

    if (minutes < 60) {
        return `~${minutes} minutes`;
    }

    const hours =
        Math.floor(minutes / 60);

    const remaining =
        minutes % 60;

    if (!remaining) {
        return `~${hours} hour${hours > 1 ? 's' : ''}`;
    }

    return `~${hours}h ${remaining}m`;
});

const submitShip = () => {
    if (!shipForm.store_address) {
        alert(
            'Please enter your store address.'
        );
        return;
    }

    if (
        !shipForm.store_latitude ||
        !shipForm.store_longitude
    ) {
        alert(
            'Please select your store location on the map.'
        );
        return;
    }

    if (
        !shipForm.delivery_distance_km ||
        !shipForm.estimated_delivery_minutes
    ) {
        calculateDelivery();
    }

    shipForm.post(
        route(
            'seller.orders.shipped',
            shippingDialogFor.value
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                closeShipDialog();
            },
        }
    );
};

onBeforeUnmount(() => {
    destroyMap();
});

const destroyMap = () => {
    if (map) {
        map.remove();
        map = null;
    }

    storeMarker = null;
    customerMarker = null;
    routeLine = null;
};
</script>

<template>
    <SellerLayout>
        <Head title="Orders" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">Orders to Fulfill</h1>

        <DataTable
            table-id="seller-orders"
            :columns="columns"
            :rows="orderShops"
            :search-keys="['order_number', 'customer_name', 'status']"
            csv-filename="orders.csv"
        >
        <template #cell-order_number="{ row }">
                <div class="flex items-center gap-3 min-w-[240px]">
                    <div class="w-12 h-12 rounded-lg overflow-hidden bg-[#F3F3F0] border border-[#E4E4E0] flex-shrink-0">
                        <img
                            v-if="row.product_image_url"
                            :src="row.product_image_url"
                            :alt="row.product_names?.[0] || 'Product'"
                            class="w-full h-full object-cover"
                        />

                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center text-[9px] text-[#8A8B90]"
                        >
                            No image
                        </div>
                    </div>

                    <!-- ORDER DETAILS -->
                    <div class="min-w-0">
                        <p class="font-medium text-[#101114] truncate">
                            {{ row.product_names?.length
                                ? row.product_names.slice(0, 2).join(', ')
                                : '—'
                            }}

                            <span
                                v-if="row.product_names?.length > 2"
                                class="text-[#8A8B90]"
                            >
                                +{{ row.product_names.length - 2 }} more
                            </span>
                        </p>

                        <p class="text-[11px] text-[#8A8B90] mt-1">
                            {{ row.order_number }}
                        </p>
                    </div>

                </div>
            </template>

            <template #cell-subtotal="{ value }">₱{{ value }}</template>

            <template #cell-status="{ value }">
                <OrderStatusBadge :status="value" />
            </template>

            <template #cell-actions="{ row }">
                <button v-if="row.status === 'pending_payment'" @click="markProcessing(row.id)" class="text-xs text-[#6D5EF8] font-medium">
                    Mark Processing
                </button>
                <button v-else-if="row.status === 'processing'" @click="openShipDialog(row.id)" class="text-xs text-[#6D5EF8] font-medium">
                    Mark Shipped
                </button>
                <span v-else class="text-xs text-[#8A8B90]">—</span>
            </template>
        </DataTable>

        <!-- Ship Dialog -->
<div
    v-if="shippingDialogFor"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    @click.self="closeShipDialog"
>
    <div
        class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl"
    >

        <!-- HEADER -->
        <div class="p-6 border-b border-[#EAEAE5]">
            <div class="flex items-start justify-between gap-4">

                <div>
                    <p class="text-xs font-semibold text-[#8A8B90] uppercase tracking-wide">
                        Delivery Setup
                    </p>

                    <h3 class="text-lg font-semibold text-[#101114] mt-1">
                        Mark Order as Shipped
                    </h3>

                    <p class="text-xs text-[#8A8B90] mt-1">
                        {{ selectedOrder?.order_number }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="closeShipDialog"
                    class="w-8 h-8 rounded-lg hover:bg-gray-100 text-[#8A8B90]"
                >
                    ✕
                </button>

            </div>
        </div>


        <!-- BODY -->
        <div class="p-6 space-y-5">

            <!-- COURIER -->
            <div>
                <label class="block text-xs font-semibold text-[#8A8B90] mb-2">
                    Courier
                </label>

                <div
                    class="w-full h-11 px-4 rounded-lg border border-[#E4E4E0] bg-[#F7F7F5] flex items-center justify-between"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-base">
                            🚚
                        </span>

                        <span class="text-sm font-semibold text-[#101114]">
                            Lalamove
                        </span>
                    </div>

                    <span
                        class="text-[10px] font-bold uppercase px-2 py-1 rounded-full bg-gray-200 text-gray-600"
                    >
                        Fixed
                    </span>
                </div>

                <p class="text-[11px] text-[#8A8B90] mt-1">
                    Lalamove is the designated courier for Zypher deliveries.
                </p>
            </div>


            <!-- STORE ADDRESS -->
            <div>
                <label class="block text-xs font-semibold text-[#8A8B90] mb-2">
                    Store / Pickup Address
                </label>
                
                <div class="flex gap-2">
                    <input
                    v-model="shipForm.store_address"
                    type="text"
                    placeholder="Enter your store address..."
                    class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"
                        />
                    <button
                        type="button"
                        @click="geocodeStoreAddress"
                        :disabled="geocodingStore"
                        class="px-3 h-10 rounded-lg bg-[#101114] text-white text-xs font-medium whitespace-nowrap disabled:opacity-40"
                    >
                        {{ geocodingStore ? 'Locating…' : '📍 Locate' }}
                    </button>
                </div>
                <p v-if="storeGeocodeError" class="text-xs text-red-500 mt-1">{{ storeGeocodeError }}</p>
                                

                <p class="text-[11px] text-[#8A8B90] mt-1">
                    Click your exact store location on the map below.
                </p>
            </div>


            <!-- CUSTOMER ADDRESS -->
            <div>
                <label class="block text-xs font-semibold text-[#8A8B90] mb-2">
                    Customer Delivery Address
                </label>

                <div class="rounded-lg bg-[#F7F7F5] border border-[#EAEAE5] p-3">
                    <p class="text-sm font-medium text-[#101114]">
                        {{ selectedOrder?.customer_address?.full_name || selectedOrder?.customer_name }}
                    </p>

                    <p class="text-xs text-[#8A8B90] mt-1">
                        {{ formattedAddress }}
                    </p>
                </div>
            </div>


            <!-- MAP -->
            <div>
                <div class="flex items-center justify-between mb-2">

                    <label class="text-xs font-semibold text-[#8A8B90]">
                        Delivery Map
                    </label>

                    <span
                        v-if="shipForm.store_latitude"
                        class="text-[10px] text-[#8A8B90]"
                    >
                        Store location selected
                    </span>

                </div>

                <div
                    ref="mapElement"
                    class="w-full h-80 rounded-xl overflow-hidden border border-[#E4E4E0]"
                ></div>

                <p class="text-[11px] text-[#8A8B90] mt-2">
                    📍 Click the map to set the store/pickup location.
                </p>
            </div>


            <!-- DELIVERY SUMMARY -->
            <div
                class="grid grid-cols-2 gap-3"
            >

                <div
                    class="rounded-xl border border-indigo-100 bg-indigo-50 p-4"
                >
                    <p class="text-[10px] uppercase font-semibold text-indigo-600">
                        Distance
                    </p>

                    <p class="text-xl font-bold text-indigo-700 mt-1">
                        {{
                            shipForm.delivery_distance_km
                                ? `${Number(shipForm.delivery_distance_km).toFixed(2)} km`
                                : '—'
                        }}
                    </p>

                    <p class="text-[10px] text-indigo-500 mt-1">
                        Store → Customer
                    </p>
                </div>


                <div
                    class="rounded-xl border border-emerald-100 bg-emerald-50 p-4"
                >
                    <p class="text-[10px] uppercase font-semibold text-emerald-600">
                        Estimated Delivery
                    </p>

                    <p class="text-xl font-bold text-emerald-700 mt-1">
                        {{ formattedEta }}
                    </p>

                    <p class="text-[10px] text-emerald-500 mt-1">
                        Approximate travel time
                    </p>
                </div>

            </div>


            <!-- TRACKING -->
            <div>
                <label class="block text-xs font-semibold text-[#8A8B90] mb-2">
                    Tracking Number
                    <span class="font-normal">
                        (optional)
                    </span>
                </label>

                <input
                    v-model="shipForm.tracking_number"
                    type="text"
                    placeholder="Enter Lalamove tracking/reference number..."
                    class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm"
                />
            </div>

        </div>


        <!-- FOOTER -->
        <div
            class="p-6 border-t border-[#EAEAE5] flex gap-3"
        >

            <button
                type="button"
                @click="closeShipDialog"
                class="flex-1 h-11 rounded-xl border border-[#E4E4E0] text-sm font-medium hover:bg-[#F7F7F5]"
            >
                Cancel
            </button>

            <button
                type="button"
                @click="submitShip"
                :disabled="shipForm.processing"
                class="flex-1 h-11 rounded-xl bg-[#101114] text-white text-sm font-semibold hover:bg-[#2B2B2B] disabled:opacity-50"
            >
                {{
                    shipForm.processing
                        ? 'Saving...'
                        : 'Confirm Shipment'
                }}
            </button>

        </div>

    </div>
</div>
    </SellerLayout>
</template>