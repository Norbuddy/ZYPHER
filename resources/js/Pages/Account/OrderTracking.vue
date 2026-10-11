<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import OrderStatusStepper from '@/Components/OrderStatusStepper.vue';
import OrderStatusBadge from '@/Components/OrderStatusBadge.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({ order: Object });

const reviewingItem = ref(null);
const reviewForm = useForm({ order_item_id: null, rating: 5, comment: '', images: [], video: null });
const reviewImagePreviews = ref([]);

const onReviewImagesSelected = (e) => {
    const files = Array.from(e.target.files);
    reviewForm.images = files;
    reviewImagePreviews.value = files.map(f => URL.createObjectURL(f));
};

const onReviewVideoSelected = (e) => {
    reviewForm.video = e.target.files[0] ?? null;
};

const confirmReceipt = (orderShopId) => {
    router.post(route('account.orders.confirm-receipt', orderShopId), {}, { preserveScroll: true });
};

const openReview = (item) => {
    reviewingItem.value = item.id;
    reviewForm.order_item_id = item.id;
};

const submitReview = () => {
    reviewForm.post(route('reviews.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            reviewingItem.value = null;
            reviewImagePreviews.value = [];
        },
    });
};

/*
|--------------------------------------------------------------------------
| Shipment Map (per order_shop, only rendered while "shipped")
|--------------------------------------------------------------------------
*/

const mapElements = ref({});
const maps = {};

const setMapElement = (orderShopId, el) => {
    if (el) mapElements.value[orderShopId] = el;
};

const hasShipmentMapData = (orderShop) => {
    return orderShop.status === 'shipped'
        && Number.isFinite(Number(orderShop.store_latitude))
        && Number.isFinite(Number(orderShop.store_longitude))
        && Number.isFinite(Number(props.order.address?.latitude))
        && Number.isFinite(Number(props.order.address?.longitude));
};

const initMap = (orderShop) => {
    const el = mapElements.value[orderShop.id];
    if (!el || maps[orderShop.id]) return;

    const storeLat = Number(orderShop.store_latitude);
    const storeLng = Number(orderShop.store_longitude);
    const customerLat = Number(props.order.address.latitude);
    const customerLng = Number(props.order.address.longitude);

    const map = L.map(el).setView([storeLat, storeLng], 12);
    maps[orderShop.id] = map;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    L.marker([storeLat, storeLng]).addTo(map).bindPopup(`<strong>${orderShop.team?.name ?? 'Seller'}</strong>`);
    L.marker([customerLat, customerLng]).addTo(map).bindPopup('<strong>Your address</strong>').openPopup();

    const line = L.polyline([[storeLat, storeLng], [customerLat, customerLng]], { color: '#6D5EF8', dashArray: '6 6' }).addTo(map);
    map.fitBounds(line.getBounds(), { padding: [30, 30] });
};

const estimatedArrival = (orderShop) => {
    if (!orderShop.shipped_at || !orderShop.estimated_delivery_minutes) return null;

    const shippedAt = new Date(orderShop.shipped_at);
    const eta = new Date(shippedAt.getTime() + orderShop.estimated_delivery_minutes * 60000);

    return eta.toLocaleString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit',
    });
};

onMounted(async () => {
    await nextTick();
    props.order.order_shops.forEach((orderShop) => {
        if (hasShipmentMapData(orderShop)) initMap(orderShop);
    });
});

onBeforeUnmount(() => {
    Object.values(maps).forEach((map) => map.remove());
});
</script>

<template>
    <GuestLayout>
        <Head :title="'Order ' + order.order_number" />

        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
            <p class="text-xs text-[#8A8B90] mb-1">Order</p>
            <h1 class="text-xl font-semibold text-[#101114] mb-8">{{ order.order_number }}</h1>

            <div v-for="orderShop in order.order_shops" :key="orderShop.id" class="mb-8 border border-[#E4E4E0] rounded-xl overflow-hidden">
                <div class="px-5 py-4 bg-[#F7F7F5] border-b border-[#E4E4E0] flex items-center justify-between">
                    <span class="text-sm font-medium">{{ orderShop.team.name }}</span>
                    <OrderStatusBadge :status="orderShop.status" />
                </div>

                <div class="p-5">
                    <OrderStatusStepper :order-shop="orderShop" />

                    <!-- Shipment map + ETA, only while "shipped" -->
                    <div v-if="orderShop.status === 'shipped'" class="mt-4 pt-4 border-t border-[#EAEAE5]">
                        <div v-if="hasShipmentMapData(orderShop)">
                            <div :ref="el => setMapElement(orderShop.id, el)" class="h-56 w-full rounded-xl overflow-hidden border border-[#E4E4E0]"></div>

                            <div class="flex items-center justify-between mt-3 text-xs text-[#5F5E5A]">
                                <span v-if="orderShop.delivery_distance_km">
                                    📍 {{ Number(orderShop.delivery_distance_km).toFixed(2) }} km away
                                </span>
                                <span v-if="estimatedArrival(orderShop)" class="font-medium text-[#101114]">
                                    Estimated arrival: {{ estimatedArrival(orderShop) }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-[#8A8B90]">
                            Tracking map isn't available for this shipment yet.
                        </p>
                    </div>

                    <div class="mt-4 pt-4 border-t border-[#EAEAE5] space-y-3">
                        <div v-for="item in orderShop.items" :key="item.id" class="flex items-center justify-between text-sm">
                            <div>
                                <p class="font-medium">{{ item.product_name }}</p>
                                <p class="text-xs text-[#8A8B90]">{{ item.variant_label }} × {{ item.quantity }}</p>
                            </div>
                            <button
                                v-if="orderShop.status === 'completed' && !item.review"
                                @click="openReview(item)"
                                class="text-xs text-[#6D5EF8] hover:text-[#33D6C8] font-medium"
                            >
                                Leave a review
                            </button>
                            <span v-else-if="item.review" class="text-xs text-[#8A8B90]">Reviewed ★{{ item.review.rating }}</span>
                        </div>
                    </div>

                                        <div v-if="reviewingItem && orderShop.items.some(i => i.id === reviewingItem)" class="mt-4 p-4 bg-[#F7F7F5] rounded-xl">
                        <div class="flex gap-1 mb-2">
                            <button v-for="n in 5" :key="n" @click="reviewForm.rating = n" type="button" class="text-lg">
                                {{ n <= reviewForm.rating ? '★' : '☆' }}
                            </button>
                        </div>
                        <textarea v-model="reviewForm.comment" rows="2" placeholder="Share your experience..." class="w-full p-2 rounded-lg border border-[#E4E4E0] text-sm mb-2"></textarea>

                        <label class="block text-xs font-medium text-[#8A8B90] mb-1">Photos (up to 5)</label>
                        <input type="file" accept="image/*" multiple @change="onReviewImagesSelected" class="text-xs mb-2" />
                        <div v-if="reviewImagePreviews.length > 0" class="flex gap-2 mb-2">
                            <img v-for="(src, i) in reviewImagePreviews" :key="i" :src="src" class="w-14 h-14 rounded-lg object-cover border border-[#E4E4E0]" />
                        </div>

                        <label class="block text-xs font-medium text-[#8A8B90] mb-1">Video (optional)</label>
                        <input type="file" accept="video/*" @change="onReviewVideoSelected" class="text-xs mb-3" />

                        <button @click="submitReview" :disabled="reviewForm.processing" class="h-9 px-4 rounded-lg bg-[#101114] text-white text-sm disabled:opacity-40">
                            {{ reviewForm.processing ? 'Submitting…' : 'Submit Review' }}
                        </button>
                    </div>

                    <button
                        v-if="orderShop.status === 'shipped'"
                        @click="confirmReceipt(orderShop.id)"
                        class="mt-4 w-full h-10 rounded-lg bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white text-sm font-medium"
                    >
                        Order Received
                    </button>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>