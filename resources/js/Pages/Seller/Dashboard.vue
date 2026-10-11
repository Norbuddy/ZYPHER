<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const showBranchModal = ref(false);
const isBranchUser = ['branch_user', 'branch_manager', 'cashier'].includes(usePage().props.auth?.user?.role);
const mapElement = ref(null);
const regionOptions = ref([]);
const provinceOptions = ref([]);
const cityOptions = ref([]);
const barangayOptions = ref([]);
const selectedRegionCode = ref('');
const selectedProvinceCode = ref('');
const selectedCityCode = ref('');
const locationError = ref('');
const isCheckingLocation = ref(false);
let lastValidLocation = { lat: 14.5995, lng: 120.9842 };
let locationValidationRequest = 0;
let map = null;
let marker = null;

const form = useForm({
    name: '',
    phone: '',
    region: '',
    province: '',
    city: '',
    barangay: '',
    address: '',
    latitude: 14.5995,
    longitude: 120.9842,
    bir_document: null,
    email: '',
    branch_role: 'manager',
});

const fetchPSGCData = async (url) => {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Unable to fetch PSGC data.');
    }

    return response.json();
};

const loadRegions = async () => {
    try {
        regionOptions.value = await fetchPSGCData('https://psgc.gitlab.io/api/regions/');
    } catch (error) {
        console.error(error);
        regionOptions.value = [];
    }
};

const loadProvinces = async (regionCode) => {
    if (!regionCode) {
        provinceOptions.value = [];
        return;
    }

    try {
        provinceOptions.value = await fetchPSGCData(`https://psgc.gitlab.io/api/regions/${regionCode}/provinces/`);
    } catch (error) {
        console.error(error);
        provinceOptions.value = [];
    }
};

const loadCities = async (provinceCode) => {
    if (!provinceCode) {
        cityOptions.value = [];
        return;
    }

    try {
        cityOptions.value = await fetchPSGCData(`https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`);
    } catch (error) {
        console.error(error);
        cityOptions.value = [];
    }
};

const loadBarangays = async (cityCode) => {
    if (!cityCode) {
        barangayOptions.value = [];
        return;
    }

    try {
        barangayOptions.value = await fetchPSGCData(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
    } catch (error) {
        console.error(error);
        barangayOptions.value = [];
    }
};

const updateMarkerLocation = (lat, lng, zoom = 15) => {
    const nextLat = Number(lat);
    const nextLng = Number(lng);

    form.latitude = Number(nextLat.toFixed(7));
    form.longitude = Number(nextLng.toFixed(7));
    lastValidLocation = { lat: nextLat, lng: nextLng };
    locationError.value = '';

    if (!map) {
        return;
    }

    if (marker) {
        marker.setLatLng([nextLat, nextLng]);
    } else {
        marker = L.marker([nextLat, nextLng], { draggable: true }).addTo(map);
    }

    map.flyTo([nextLat, nextLng], zoom, {
        animate: true,
        duration: 1.2,
    });
};

const hasNearbyWaterFeature = async (lat, lng) => {
    const query = `[out:json][timeout:5];(
        nwr(around:100,${lat},${lng})["natural"~"^(water|wetland|coastline)$"];
        nwr(around:100,${lat},${lng})["waterway"];
        nwr(around:100,${lat},${lng})["landuse"="reservoir"];
        nwr(around:100,${lat},${lng})["boundary"="maritime"];
        nwr(around:100,${lat},${lng})["seamark:type"];
    );out ids;`;

    const endpoints = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
    ];
    const checks = await Promise.all(endpoints.map(async (endpoint) => {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 1500);

        try {
            const response = await fetch(
                `${endpoint}?data=${encodeURIComponent(query)}`,
                { signal: controller.signal },
            );

            if (!response.ok) {
                return null;
            }

            const result = await response.json();
            return (result.elements ?? []).length > 0;
        } catch (error) {
            return null;
        } finally {
            clearTimeout(timeout);
        }
    }));

    if (checks.every((result) => result === null)) {
        return null;
    }

    return checks.some(Boolean);
};

const validateMapLocation = async (lat, lng) => {
    const requestId = ++locationValidationRequest;
    isCheckingLocation.value = true;
    locationError.value = '';

    try {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 5000);
        const reverseResponsePromise = fetch(
            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`,
            { signal: controller.signal },
        );
        const coarseResponsePromise = fetch(
            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=10&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`,
            { signal: controller.signal },
        );
        const waterCheckPromise = hasNearbyWaterFeature(lat, lng);
        const [response, coarseResponse, nearbyWaterFeature] = await Promise.all([
            reverseResponsePromise,
            coarseResponsePromise,
            waterCheckPromise,
        ]);
        clearTimeout(timeout);

        if (!response.ok) {
            throw new Error('Unable to verify this location.');
        }

        const result = await response.json();
        const coarseResult = coarseResponse.ok ? await coarseResponse.json() : {};
        const address = result.address ?? {};
        const blockedTypes = [
            'bay', 'coastline', 'desert', 'forest', 'grassland', 'lake', 'marsh',
            'nature_reserve', 'ocean', 'reservoir', 'river', 'sea', 'scrub',
            'water', 'wetland', 'wood', 'dock', 'harbour', 'jetty', 'marina',
            'pier', 'port', 'quay', 'slipway', 'riverbank', 'stream', 'canal', 'drain',
        ];
        const marineName = `${result.name ?? ''} ${result.display_name ?? ''}`.toLowerCase();
        const coarseMarineName = `${coarseResult.name ?? ''} ${coarseResult.display_name ?? ''}`.toLowerCase();
        const marineTypes = ['bay', 'coastline', 'harbour', 'harbor', 'ocean', 'pier', 'port', 'quay', 'sea', 'water'];
        const isBodyOfWater = result.class === 'water'
            || result.category === 'water'
            || blockedTypes.includes(result.type)
            || blockedTypes.includes(result.addresstype)
            || blockedTypes.includes(address.water)
            || Boolean(address.waterway)
            || Boolean(address.natural)
            || ['reservoir', 'water', 'wetland'].includes(address.landuse)
            || /\b(manila bay|bay|dock|harbour|harbor|jetty|marina|pier|port|quay|river|riverbank|stream|canal|drain|sea|lake|ocean|water)\b/.test(marineName)
            || marineTypes.includes(coarseResult.type)
            || marineTypes.includes(coarseResult.addresstype)
            || /\b(bay|harbour|harbor|ocean|sea|water)\b/.test(coarseMarineName);
        const populatedPlaceTypes = [
            'apartments', 'building', 'commercial', 'house', 'industrial', 'locality',
            'neighbourhood', 'place', 'quarter', 'residential', 'retail', 'road',
            'suburb', 'town', 'village',
        ];
        const hasPopulatedAddress = [
            'amenity', 'building', 'city', 'city_district', 'commercial',
            'county', 'house_number', 'industrial', 'municipality', 'neighbourhood',
            'office', 'quarter', 'road', 'shop', 'suburb', 'town', 'village', 'hamlet',
            'locality', 'residential',
        ].some((key) => Boolean(address[key])) || populatedPlaceTypes.includes(result.type);
        const isPhilippines = address.country_code?.toLowerCase() === 'ph';

        if (requestId !== locationValidationRequest) {
            return false;
        }

        if (requestId !== locationValidationRequest) {
            return false;
        }

        if (isBodyOfWater || nearbyWaterFeature) {
            locationError.value = 'Please choose a location on land, not a body of water.';
            return false;
        }

        if (!isPhilippines || !hasPopulatedAddress) {
            locationError.value = 'Please choose a populated land location in the Philippines.';
            return false;
        }

        updateMarkerLocation(lat, lng);
        return true;
    } catch (error) {
        if (requestId === locationValidationRequest) {
            locationError.value = 'This location could not be verified. Please choose another pin.';
        }

        return false;
    } finally {
        if (requestId === locationValidationRequest) {
            isCheckingLocation.value = false;
        }
    }
};

const syncMapToAdministrativeArea = async () => {
    if (!map) {
        return;
    }

    const query = [form.barangay, form.city, form.province, form.region, 'Philippines']
        .filter(Boolean)
        .join(', ');

    if (!query) {
        return;
    }

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ph&q=${encodeURIComponent(query)}`
        );

        const results = await response.json();

        if (!results.length) {
            return;
        }

        const lat = parseFloat(results[0].lat);
        const lng = parseFloat(results[0].lon);
        const zoom = form.barangay ? 17 : form.city ? 14 : 12;

        const accepted = await validateMapLocation(lat, lng);

        if (accepted && map) {
            map.flyTo([lat, lng], zoom, {
                animate: true,
                duration: 1.2,
            });
        }
    } catch (error) {
        console.error('Unable to geocode administrative area:', error);
    }
};

const initBranchMap = async () => {
    await nextTick();

    if (!mapElement.value) {
        return;
    }

    if (map) {
        map.remove();
        map = null;
    }

    map = L.map(mapElement.value).setView([form.latitude, form.longitude], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    marker = L.marker([form.latitude, form.longitude], { draggable: true }).addTo(map);
    lastValidLocation = { lat: form.latitude, lng: form.longitude };

    marker.on('dragend', async (event) => {
        const { lat, lng } = event.target.getLatLng();
        const accepted = await validateMapLocation(lat, lng);

        if (!accepted && marker) {
            marker.setLatLng([lastValidLocation.lat, lastValidLocation.lng]);
        }
    });

    map.on('click', async (event) => {
        const { lat, lng } = event.latlng;
        const accepted = await validateMapLocation(lat, lng);

        if (accepted && marker) {
            marker.setLatLng([lat, lng]);
        }
    });
};

const handleRegionChange = async (event) => {
    const selectedCode = event.target.value;
    const selectedRegion = regionOptions.value.find((item) => item.code === selectedCode);

    selectedRegionCode.value = selectedCode;
    form.region = selectedRegion ? selectedRegion.name : '';
    form.province = '';
    form.city = '';
    form.barangay = '';
    selectedProvinceCode.value = '';
    selectedCityCode.value = '';
    provinceOptions.value = [];
    cityOptions.value = [];
    barangayOptions.value = [];

    await loadProvinces(selectedCode);
};

const handleProvinceChange = async (event) => {
    const selectedCode = event.target.value;
    const selectedProvince = provinceOptions.value.find((item) => item.code === selectedCode);

    selectedProvinceCode.value = selectedCode;
    form.province = selectedProvince ? selectedProvince.name : '';
    form.city = '';
    form.barangay = '';
    selectedCityCode.value = '';
    cityOptions.value = [];
    barangayOptions.value = [];

    await loadCities(selectedCode);
};

const handleCityChange = async (event) => {
    const selectedCode = event.target.value;
    const selectedCity = cityOptions.value.find((item) => item.code === selectedCode);

    selectedCityCode.value = selectedCode;
    form.city = selectedCity ? selectedCity.name : '';
    form.barangay = '';
    barangayOptions.value = [];

    await loadBarangays(selectedCode);
    await syncMapToAdministrativeArea();
};

const handleBarangayChange = async (event) => {
    const selectedBarangay = event.target.value;
    form.barangay = selectedBarangay;
    await syncMapToAdministrativeArea();
};

const onBirDocumentChange = (event) => {
    const file = event.target.files?.[0] ?? null;

    if (!file) {
        form.bir_document = null;
        return;
    }

    const valid = ['image/png', 'image/jpg', 'image/jpeg', 'application/pdf'].includes(file.type)
        || /\.(png|jpe?g|pdf)$/i.test(file.name);

    if (!valid) {
        form.setError('bir_document', 'Only PNG, JPG, JPEG, and PDF files are allowed.');
        event.target.value = '';
        form.bir_document = null;
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        form.setError('bir_document', 'The file must be 5MB or smaller.');
        event.target.value = '';
        form.bir_document = null;
        return;
    }

    form.clearErrors('bir_document');
    form.bir_document = file;
};

const closeBranchModal = () => {
    showBranchModal.value = false;
    form.reset();
    locationError.value = '';
    isCheckingLocation.value = false;
    selectedRegionCode.value = '';
    selectedProvinceCode.value = '';
    selectedCityCode.value = '';
    regionOptions.value = [];
    provinceOptions.value = [];
    cityOptions.value = [];
    barangayOptions.value = [];
    if (map) {
        map.remove();
        map = null;
    }
};

const submitBranch = async () => {
    form.clearErrors();

    const requiredFields = {
        email: form.email,
        branch_role: form.branch_role,
        name: form.name,
        phone: form.phone,
        region: form.region,
        province: form.province,
        city: form.city,
        barangay: form.barangay,
        address: form.address,
        bir_document: form.bir_document,
        latitude: form.latitude,
        longitude: form.longitude,
    };
    const missingFields = Object.entries(requiredFields)
        .filter(([, value]) => value === null || value === undefined || value === '')
        .map(([field]) => field);

    if (missingFields.length > 0) {
        form.setError('form', 'Please complete all required fields before saving the branch.');
        return;
    }

    const validLocation = await validateMapLocation(
        Number(form.latitude),
        Number(form.longitude),
    );

    if (!validLocation || locationError.value) {
        form.setError('location', locationError.value);
        return;
    }

    form.post(route('seller.branches.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            closeBranchModal();
        },
    });
};

onMounted(() => {
    if (showBranchModal.value) {
        initBranchMap();
    }
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
    }
});

const props = defineProps({
    stats: Object,
    supplierLabel: String,
    recentOrdersAndSales: {
        type: Array,
        default: () => [],
    },
    charts: {
        type: Object,
        default: () => ({
            revenueByDay: [],
            ordersByDay: [],
            statusDistribution: [],
            channelSplit: [],
        }),
    },
});

const analyticsTab = ref('overview');

const maxRevenue = computed(() => Math.max(1, ...(props.charts.revenueByDay || []).map((d) => d.value)));
const maxOrders = computed(() => Math.max(1, ...(props.charts.ordersByDay || []).map((d) => d.value)));
const statusTotal = computed(() => (props.charts.statusDistribution || []).reduce((s, d) => s + d.value, 0) || 1);

function donutSlices(items) {
    const list = (items || []).filter((i) => i.value > 0);
    const total = list.reduce((s, i) => s + i.value, 0) || 1;
    let angle = -90;
    const r = 40, cx = 50, cy = 50;
    return list.map((item) => {
        const sweep = (item.value / total) * 360;
        const start = angle;
        angle += sweep;
        const large = sweep > 180 ? 1 : 0;
        const startRad = (start * Math.PI) / 180;
        const endRad = (angle * Math.PI) / 180;
        const x1 = cx + r * Math.cos(startRad);
        const y1 = cy + r * Math.sin(startRad);
        const x2 = cx + r * Math.cos(endRad);
        const y2 = cy + r * Math.sin(endRad);
        return { ...item, d: `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2} Z` };
    });
}

const statusDonut = computed(() => donutSlices(props.charts.statusDistribution));

function areaPath(points, max) {
    if (!points?.length) return { line: '', fill: '' };
    const w = 280, h = 100;
    const step = points.length > 1 ? w / (points.length - 1) : w;
    const coords = points.map((p, i) => [i * step, h - (p.value / max) * (h - 8)]);
    let d = `M ${coords[0][0]} ${coords[0][1]}`;
    for (let i = 1; i < coords.length; i++) d += ` L ${coords[i][0]} ${coords[i][1]}`;
    return { line: d, fill: `${d} L ${coords[coords.length - 1][0]} ${h} L ${coords[0][0]} ${h} Z` };
}

const revenueArea = computed(() => areaPath(props.charts.revenueByDay, maxRevenue.value));


const showAddCashModal = ref(false);
const addCashAmount = ref(null);
const addCashNote = ref('');
const addingCash = ref(false);

const submitAddCash = () => {
    if (!addCashAmount.value || addCashAmount.value <= 0) return;

    addingCash.value = true;
    router.post(route('seller.cash.add'), {
        amount: addCashAmount.value,
        note: addCashNote.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAddCashModal.value = false;
            addCashAmount.value = null;
            addCashNote.value = '';
        },
        onFinish: () => { addingCash.value = false; },
    });
};

const showPayModal = ref(false);
const paymentMethod = ref('cash');
const payAmount = ref(0);
const phoneNumber = ref('');
const processing = ref(false);

const openPayModal = () => {
    payAmount.value = props.stats.accounts_payable;
    paymentMethod.value = 'cash';
    phoneNumber.value = '';
    showPayModal.value = true;
};

const closePayModal = () => {
    showPayModal.value = false;
};

const submitPayment = () => {
    if (paymentMethod.value === 'ewallet') {
        alert('E-wallet payments are coming soon.');
        return;
    }

    processing.value = true;
    router.post(route('seller.supplier.pay-outstanding'), { method: 'cash' }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            showPayModal.value = false;
        },
    });
};

const formatDateTime = (date) => {
    if (!date) return '—';

    return new Date(date).toLocaleString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const formatProducts = (products) => {
    if (!products || products.length === 0) {
        return '—';
    }

    if (products.length <= 2) {
        return products.join(', ');
    }

    return `${products.slice(0, 2).join(', ')} +${products.length - 2} more`;
};

const formatPaymentMethod = (method) => {
    if (!method) return '';

    return method.replace('_', ' ').toUpperCase();
};

const recentOrdersColumns = [
    { key: 'product_names', label: 'Product / Order' },
    { key: 'customer_name', label: 'Customer' },
    { key: 'ordered_at', label: 'Date & Time' },
    { key: 'source_label', label: 'Source' },
    { key: 'status', label: 'Status' },
    { key: 'total', label: 'Total' },
];
</script>

<template>
    <SellerLayout>
        <Head title="Seller Dashboard" />

        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <h1 class="text-xl font-semibold text-[#101114]">Dashboard</h1>
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex rounded-xl border border-[#E4E4E0] bg-white p-1 text-sm">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-1.5 font-medium transition"
                        :class="analyticsTab === 'overview' ? 'bg-[#101114] text-white' : 'text-[#5F5E5A] hover:text-[#101114]'"
                        @click="analyticsTab = 'overview'"
                    >
                        Overview
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-4 py-1.5 font-medium transition"
                        :class="analyticsTab === 'reports' ? 'bg-[#101114] text-white' : 'text-[#5F5E5A] hover:text-[#101114]'"
                        @click="analyticsTab = 'reports'"
                    >
                        Reports
                    </button>
                </div>
                <button
                    type="button"
                    v-if="!isBranchUser"
                    @click="showBranchModal = true; initBranchMap(); loadRegions();"
                    class="rounded-lg bg-[#101114] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#1f2330]"
                >
                    Add Branch
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-if="!isBranchUser" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-500 mb-1">Pending Orders</p>
                <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ stats.pending_orders }}</p>
            </div>
            <div v-if="!isBranchUser" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-500 mb-1">Processing</p>
                <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ stats.processing_orders }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-500 mb-1">Active Products</p>
                <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ stats.active_products }}</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-500 mb-1">Gross Revenue</p>
                <p class="text-2xl font-bold text-gray-900 tracking-tight">₱{{ stats.gross_revenue.toLocaleString() }}</p>
            </div>
            <div v-if="!isBranchUser" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-500 mb-1">COGS</p>
                <p class="text-2xl font-bold text-gray-900 tracking-tight">₱{{ stats.cogs.toLocaleString() }}</p>
            </div>
            <div v-if="!isBranchUser" class="bg-emerald-50/50 p-5 rounded-2xl border border-emerald-200 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-md transition">
                <p class="text-xs font-semibold text-emerald-800 mb-1">Gross Profit</p>
                <p class="text-2xl font-bold text-emerald-700 tracking-tight">₱{{ stats.gross_profit.toLocaleString() }}</p>
            </div>
            <div v-if="!isBranchUser" class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-[0_2px_8px_rgba(0,0,0,0.03)] hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-500 mb-1">Net Payout (after commission)</p>
                <p class="text-2xl font-bold text-gray-900 tracking-tight">₱{{ stats.net_payout.toLocaleString() }}</p>
            </div>
            <!-- <div v-if="!isBranchUser" class="bg-white p-5 rounded-xl border border-amber-200 bg-amber-50 flex items-center justify-between">
                <div>
                    <p class="text-xs text-amber-700 mb-1">Accounts Payable (owed to supplier)</p>
                    <p class="text-2xl font-semibold text-amber-700">₱{{ stats.accounts_payable.toLocaleString() }}</p>
                </div>
                <button
                    v-if="stats.accounts_payable > 0"
                    @click="openPayModal"
                    class="rounded-lg bg-lime-400 hover:bg-lime-500 px-3 py-2 text-xs font-bold text-[#101114] whitespace-nowrap"
                >
                    PAY SUPPLIER
                </button>
            </div> -->
            <div v-if="!isBranchUser" class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-200 col-span-2 flex items-center justify-between shadow-[0_2px_8px_rgba(0,0,0,0.03)]">
                <div>
                    <p class="text-xs font-semibold text-indigo-800 mb-1">Cash on Hand</p>
                    <p class="text-2xl font-bold text-indigo-900 tracking-tight">₱{{ stats.cash_on_hand.toLocaleString() }}</p>
                </div>
                <button
                    @click="showAddCashModal = true"
                    class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition"
                >
                    + ADD CASH
                </button>
            </div>
        </div>

        <!-- Analytics: Overview -->
        <div v-show="analyticsTab === 'overview'" class="grid gap-6 lg:grid-cols-2 mb-8">
            <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#101114]">Revenue (Last 7 Days)</h2>
                    <span class="text-xs text-[#8A8B90]">Area</span>
                </div>
                <div class="relative h-36 w-full">
                    <svg viewBox="0 0 280 100" class="h-full w-full" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="sellerRevGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#6D5EF8" stop-opacity="0.35" />
                                <stop offset="100%" stop-color="#33D6C8" stop-opacity="0.05" />
                            </linearGradient>
                        </defs>
                        <path :d="revenueArea.fill" fill="url(#sellerRevGrad)" />
                        <path :d="revenueArea.line" fill="none" stroke="#6D5EF8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="mt-2 flex justify-between text-[10px] text-[#8A8B90]">
                    <span v-for="d in charts.revenueByDay" :key="d.date">{{ d.label }}</span>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#101114]">Orders by Status</h2>
                    <span class="text-xs text-[#8A8B90]">Donut</span>
                </div>
                <div class="flex flex-col items-center gap-4 sm:flex-row">
                    <svg viewBox="0 0 100 100" class="h-40 w-40 shrink-0">
                        <circle cx="50" cy="50" r="40" fill="#F7F7F5" />
                        <path v-for="(s, i) in statusDonut" :key="i" :d="s.d" :fill="s.color" opacity="0.9" />
                        <circle cx="50" cy="50" r="22" fill="white" />
                        <text x="50" y="52" text-anchor="middle" class="fill-[#101114]" style="font-size: 11px; font-weight: 600">{{ statusTotal }}</text>
                    </svg>
                    <ul class="w-full space-y-2 text-xs">
                        <li v-for="s in charts.statusDistribution" :key="s.label" class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2 text-[#5F5E5A]">
                                <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: s.color }" />
                                {{ s.label }}
                            </span>
                            <span class="font-medium text-[#101114]">{{ s.value }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Analytics: Reports -->
        <div v-show="analyticsTab === 'reports'" class="grid gap-6 lg:grid-cols-2 mb-8">
            <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#101114]">Daily Order Volume</h2>
                    <span class="text-xs text-[#8A8B90]">Bar</span>
                </div>
                <div class="flex h-40 items-end gap-2">
                    <div
                        v-for="d in charts.ordersByDay"
                        :key="d.date"
                        class="flex flex-1 flex-col items-center gap-1"
                    >
                        <span class="text-[10px] font-medium text-[#8A8B90]">{{ d.value }}</span>
                        <div
                            class="w-full rounded-t-md bg-gradient-to-t from-[#6D5EF8] to-[#33D6C8]"
                            :style="{ height: Math.max(4, (d.value / maxOrders) * 120) + 'px' }"
                        />
                        <span class="text-[10px] text-[#8A8B90]">{{ d.label }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E4E4E0] bg-white p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-[#101114]">Channel Split (Online vs POS)</h2>
                    <span class="text-xs text-[#8A8B90]">Bar</span>
                </div>
                <div class="space-y-4 pt-2">
                    <div v-for="ch in charts.channelSplit" :key="ch.label">
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2 font-medium text-[#101114]">
                                <span class="h-2.5 w-2.5 rounded-sm" :style="{ background: ch.color }" />
                                {{ ch.label }}
                            </span>
                            <span class="text-[#8A8B90]">{{ ch.value }}% <span v-if="ch.count != null">({{ ch.count }})</span></span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-[#F0F0EC]">
                            <div class="h-full rounded-full" :style="{ width: Math.max(2, ch.value) + '%', background: ch.color }" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders & POS Sales -->
<div class="mt-6">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h2 class="text-lg font-semibold text-[#101114]">
                {{ isBranchUser ? 'Branch POS Sales' : 'Recent Orders & Sales' }}
            </h2>

            <p class="text-xs text-[#8A8B90] mt-1">
                {{ isBranchUser ? 'Sales recorded at this branch' : 'Online orders and physical store POS sales' }}
            </p>
        </div>
    </div>

        <DataTable
        table-id="seller-dashboard-orders"
        :columns="recentOrdersColumns"
        :rows="recentOrdersAndSales"
        :search-keys="['product_names', 'customer_name', 'reference', 'status']"
        csv-filename="recent-orders-and-sales.csv"
    >
        <template #cell-product_names="{ row }">
            <div class="min-w-[220px]">
                <p class="font-medium text-[#101114]">{{ formatProducts(row.product_names) }}</p>
                <p class="text-[11px] text-[#8A8B90] mt-1">{{ row.reference }}</p>
                <p class="text-[11px] text-[#8A8B90]">
                    {{ row.items_count }} {{ row.items_count === 1 ? 'item' : 'items' }}
                </p>
            </div>
        </template>

        <template #cell-ordered_at="{ value }">
            {{ formatDateTime(value) }}
        </template>

        <template #cell-source_label="{ row }">
            <span
                class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase"
                :class="row.source === 'pos' ? 'bg-indigo-100 text-indigo-700' : 'bg-purple-100 text-purple-700'"
            >
                {{ row.source_label }}
            </span>
            <p v-if="row.source === 'pos'" class="text-[10px] text-[#8A8B90] mt-1 uppercase">
                {{ formatPaymentMethod(row.payment_method) }}
            </p>
        </template>

        <template #cell-status="{ row }">
            <span
                v-if="row.source === 'pos'"
                class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 text-[10px] font-semibold uppercase"
            >
                Paid
            </span>
            <span
                v-else
                class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase"
                :class="{
                    'bg-yellow-100 text-yellow-700': row.status === 'pending_payment',
                    'bg-blue-100 text-blue-700': row.status === 'processing',
                    'bg-purple-100 text-purple-700': row.status === 'shipped',
                    'bg-emerald-100 text-emerald-700': row.status === 'completed',
                    'bg-red-100 text-red-700': row.status === 'cancelled',
                    'bg-gray-100 text-gray-600': !['pending_payment','processing','shipped','completed','cancelled'].includes(row.status),
                }"
            >
                {{ row.status?.replace('_', ' ') }}
            </span>
        </template>

        <template #cell-total="{ value }">
            <span class="font-semibold text-[#101114]">
                ₱{{ Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </span>
        </template>
    </DataTable>
</div>
        
        <!-- Add Branch Modal -->
        <div v-if="showBranchModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="closeBranchModal">
            <div class="bg-white rounded-2xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-[#101114]">Add Branch</h2>
                    <p class="text-sm text-[#8A8B90] mt-1">Create a branch and upload your BIR registration document.</p>
                </div>

                <form @submit.prevent="submitBranch" novalidate class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 border-b border-[#E4E4E0] pb-2">
                            <h3 class="text-sm font-semibold text-[#101114]">Branch Account Credentials</h3>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Branch Username / Email</label>
                            <input v-model="form.email" type="email" required class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                            <p class="text-xs text-[#8A8B90] mt-1">This email receives the credentials. A unique login alias is generated if it is already in use.</p>
                            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Account Role</label>
                            <select v-model="form.branch_role" required class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm">
                                <option value="manager">Branch Manager</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Branch Name</label>
                            <input v-model="form.name" type="text" required class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Contact Number</label>
                            <input v-model="form.phone" type="text" required class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                            <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1">{{ form.errors.phone }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Region</label>
                            <select v-model="selectedRegionCode" @change="handleRegionChange" required class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm">
                                <option value="">Select a region</option>
                                <option v-for="region in regionOptions" :key="region.code" :value="region.code">
                                    {{ region.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Province</label>
                            <select v-model="selectedProvinceCode" @change="handleProvinceChange" :disabled="!selectedRegionCode" :required="Boolean(selectedRegionCode)" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm disabled:opacity-50 disabled:bg-gray-50">
                                <option value="">Select a province</option>
                                <option v-for="province in provinceOptions" :key="province.code" :value="province.code">
                                    {{ province.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">City / Municipality</label>
                            <select v-model="selectedCityCode" @change="handleCityChange" :disabled="!selectedProvinceCode" :required="Boolean(selectedProvinceCode)" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm disabled:opacity-50 disabled:bg-gray-50">
                                <option value="">Select a city</option>
                                <option v-for="city in cityOptions" :key="city.code" :value="city.code">
                                    {{ city.name }}
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Barangay</label>
                            <select v-model="form.barangay" @change="handleBarangayChange" :disabled="!selectedCityCode" :required="Boolean(selectedCityCode)" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm disabled:opacity-50 disabled:bg-gray-50">
                                <option value="">Select a barangay</option>
                                <option v-for="barangay in barangayOptions" :key="barangay.code" :value="barangay.name">
                                    {{ barangay.name }}
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Physical Address</label>
                            <textarea v-model="form.address" rows="3" required class="w-full px-3 py-2 rounded-lg border border-[#E4E4E0] text-sm"></textarea>
                            <p v-if="form.errors.address" class="text-xs text-red-500 mt-1">{{ form.errors.address }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">BIR Document</label>
                            <input type="file" accept=".pdf,.png,.jpg,.jpeg" required @change="onBirDocumentChange" class="w-full text-sm" />
                            <p class="text-xs text-[#8A8B90] mt-1">Accepted: PDF, JPG, JPEG, PNG · Max 5MB</p>
                            <p v-if="form.errors.bir_document" class="text-xs text-red-500 mt-1">{{ form.errors.bir_document }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Map Pin Location</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Latitude</label>
                                    <input v-model.number="form.latitude" type="number" step="0.0000001" required readonly class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm bg-gray-50" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Longitude</label>
                                    <input v-model.number="form.longitude" type="number" step="0.0000001" required readonly class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm bg-gray-50" />
                                </div>
                            </div>
                            <div ref="mapElement" class="w-full h-48 rounded-xl overflow-hidden border border-[#E4E4E0]"></div>
                            <p v-if="locationError" class="text-xs text-red-500 mt-1">{{ locationError }}</p>
                            <p v-else class="text-xs text-[#8A8B90] mt-1">The pin must be on populated land in the Philippines.</p>
                        </div>
                    </div>

                    <p v-if="form.errors.form" class="text-xs text-red-500">{{ form.errors.form }}</p>

                    <div class="flex gap-2 pt-2">
                        <button type="button" @click="closeBranchModal" class="flex-1 h-11 rounded-xl border border-[#E4E4E0] text-sm font-medium">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing || isCheckingLocation" class="flex-1 h-11 rounded-xl bg-[#101114] text-white text-sm font-medium disabled:opacity-50">
                            {{ isCheckingLocation ? 'Checking pin...' : form.processing ? 'Saving...' : 'Save Branch' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Cash Modal -->
        <div v-if="showAddCashModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="showAddCashModal = false">
            <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
                <h2 class="text-lg font-semibold text-[#101114] mb-1">Add Cash</h2>
                <p class="text-sm text-[#8A8B90] mb-4">Record extra cash you're putting into the business (e.g. owner capital, cash top-up).</p>

                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Amount</label>
                <input
                    v-model.number="addCashAmount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm mb-4"
                    placeholder="0.00"
                />

                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Note (optional)</label>
                <input
                    v-model="addCashNote"
                    type="text"
                    class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm mb-4"
                    placeholder="e.g. Owner top-up"
                />

                <div class="flex gap-2">
                    <button @click="showAddCashModal = false" class="flex-1 h-11 rounded-xl border border-[#E4E4E0] text-sm font-medium">
                        Cancel
                    </button>
                    <button
                        @click="submitAddCash"
                        :disabled="addingCash || !addCashAmount"
                        class="flex-1 h-11 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium disabled:opacity-50"
                    >
                        {{ addingCash ? 'Adding…' : 'Add' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Pay Supplier Modal -->
        <!-- <div v-if="showPayModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="closePayModal">
            <div class="bg-white rounded-2xl p-8 w-full max-w-md text-center">
                <div class="w-16 h-16 rounded-full bg-[#101114] flex items-center justify-center mx-auto mb-4">
                    <span class="text-white text-2xl font-bold">Z</span>
                </div>
                <h2 class="text-lg font-semibold text-[#101114] mb-1">Zypher x {{ supplierLabel }}</h2>
                <p class="text-sm text-[#8A8B90] mb-2">The total amount you have to pay is:</p>
                <p class="text-4xl font-bold text-[#101114] mb-6">₱{{ Number(stats.accounts_payable).toFixed(2) }}</p>

                <p class="text-sm font-medium text-[#101114] mb-3">Choose your payment process:</p>
                <div class="flex justify-center gap-4 mb-6">
                    <button
                        type="button"
                        @click="paymentMethod = 'cash'"
                        class="w-20 h-20 rounded-xl border-2 flex flex-col items-center justify-center gap-1"
                        :class="paymentMethod === 'cash' ? 'border-lime-400 bg-lime-50' : 'border-gray-200'"
                    >
                        <span class="text-xl">₱</span>
                        <span class="text-xs font-medium">Cash</span>
                    </button>
                    <button
                        type="button"
                        @click="paymentMethod = 'ewallet'"
                        class="w-20 h-20 rounded-xl border-2 flex flex-col items-center justify-center gap-1"
                        :class="paymentMethod === 'ewallet' ? 'border-lime-400 bg-lime-50' : 'border-gray-200'"
                    >
                        <span class="text-xl">📱</span>
                        <span class="text-xs font-medium">E-wallet</span>
                    </button>
                </div>

                <div class="mb-6">
                    <input
                        v-if="paymentMethod === 'cash'"
                        v-model.number="payAmount"
                        type="number"
                        step="0.01"
                        class="w-full text-center rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        placeholder="Enter amount"
                    />
                    <input
                        v-else
                        v-model="phoneNumber"
                        type="text"
                        class="w-full text-center rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        placeholder="Enter Phone number"
                    />
                </div>

                <button
                    type="button"
                    @click="submitPayment"
                    :disabled="processing"
                    class="w-full rounded-xl bg-[#101114] hover:bg-[#2b2b2b] px-4 py-3 text-sm font-bold text-white disabled:opacity-50"
                >
                    PAY SUPPLIER
                </button>
            </div>
        </div> -->
    </SellerLayout>
</template>