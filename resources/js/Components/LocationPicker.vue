<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    latitude: {
        type: [Number, String],
        default: null,
    },
    longitude: {
        type: [Number, String],
        default: null,
    },
    addressQuery: {
        type: String,
        default: '',
    },
});

const geocoding = ref(false);
const geocodeError = ref('');
const validationError = ref('');

const emit = defineEmits([
    'update:latitude',
    'update:longitude',
]);

const mapElement = ref(null);

let map = null;
let marker = null;

// Approximate bounding box for the Philippines (mainland + major islands)
const PH_BOUNDS = L.latLngBounds(
    [4.5, 116.0],   // SW
    [21.5, 127.0]   // NE
);

const defaultLat = Number(props.latitude) || 14.5995;
const defaultLng = Number(props.longitude) || 120.9842;

/**
 * Validate that a coordinate is inside the Philippines and is not open water.
 * Uses Nominatim reverse geocoding (countrycodes=ph + reject water features).
 */
const validateLocation = async (lat, lng) => {
    // Hard bounds check first (instant)
    if (!PH_BOUNDS.contains([lat, lng])) {
        return {
            valid: false,
            message: 'Location is outside the Philippines. Please choose a delivery point within the country.',
        };
    }

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=14&addressdetails=1&countrycodes=ph`,
            {
                headers: {
                    'Accept-Language': 'en',
                },
            }
        );
        const data = await response.json();

        if (!data || data.error) {
            return {
                valid: false,
                message: 'Could not verify this location. Please choose a different pin.',
            };
        }

        // Reject pure water / ocean / sea features
        const category = (data.category || '').toLowerCase();
        const type = (data.type || '').toLowerCase();
        const displayName = (data.display_name || '').toLowerCase();

        const isWater =
            category === 'water' ||
            (category === 'natural' && (type === 'water' || type === 'bay' || type === 'strait')) ||
            type === 'ocean' ||
            type === 'sea' ||
            displayName.includes('philippine sea') ||
            displayName.includes('south china sea') ||
            displayName.includes('celebes sea') ||
            displayName.includes('sulu sea');

        if (isWater) {
            return {
                valid: false,
                message: 'Cannot drop a pin in the ocean or open water. Please select a land location.',
            };
        }

        // Must resolve to the Philippines
        const countryCode = data.address?.country_code?.toLowerCase();
        if (countryCode && countryCode !== 'ph') {
            return {
                valid: false,
                message: 'Location is outside the Philippines. Please choose a delivery point within the country.',
            };
        }

        return { valid: true, message: '' };
    } catch (e) {
        // Network failure – still allow the pin but warn
        console.warn('Reverse geocode failed', e);
        return { valid: true, message: '' };
    }
};

const setLocation = async (lat, lng, skipValidation = false) => {
    if (!map) return;

    validationError.value = '';

    if (!skipValidation) {
        geocoding.value = true;
        const result = await validateLocation(lat, lng);
        geocoding.value = false;

        if (!result.valid) {
            validationError.value = result.message;
            // Do not move the marker / emit invalid coordinates
            return;
        }
    }

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], {
            draggable: true,
        }).addTo(map);

        marker.on('dragend', async (e) => {
            const pos = e.target.getLatLng();
            await setLocation(pos.lat, pos.lng);
        });
    }

    map.setView([lat, lng], Math.max(map.getZoom(), 14));

    emit('update:latitude', lat);
    emit('update:longitude', lng);
};

const geocodeAddress = async () => {
    if (!props.addressQuery) {
        geocodeError.value = 'Please select a shipping address first.';
        return;
    }

    geocoding.value = true;
    geocodeError.value = '';
    validationError.value = '';

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ph&q=${encodeURIComponent(props.addressQuery)}`
        );
        const results = await response.json();

        if (!results.length) {
            geocodeError.value = 'Could not find that address on the map. Try adjusting the pin manually.';
            return;
        }

        // Address geocode is already constrained to PH, so skip extra validation
        await setLocation(parseFloat(results[0].lat), parseFloat(results[0].lon), true);
    } catch (e) {
        geocodeError.value = 'Something went wrong while locating your address.';
    } finally {
        geocoding.value = false;
    }
};

const useCurrentLocation = () => {
    validationError.value = '';
    geocodeError.value = '';

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            await setLocation(
                position.coords.latitude,
                position.coords.longitude
            );
        },
        () => {
            alert('Unable to get your current location.');
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
        }
    );
};

onMounted(() => {
    map = L.map(mapElement.value, {
        maxBounds: PH_BOUNDS.pad(0.05),
        maxBoundsViscosity: 0.9,
        minZoom: 5,
    }).setView(
        [defaultLat, defaultLng],
        props.latitude ? 16 : 6
    );

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution: '&copy; OpenStreetMap contributors',
            noWrap: true,
        }
    ).addTo(map);

    // Visual boundary rectangle (subtle)
    L.rectangle(PH_BOUNDS, {
        color: '#6D5EF8',
        weight: 1,
        fill: false,
        dashArray: '4 6',
        opacity: 0.5,
    }).addTo(map);

    if (props.latitude && props.longitude) {
        const lat = Number(props.latitude);
        const lng = Number(props.longitude);
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', async (e) => {
            const pos = e.target.getLatLng();
            await setLocation(pos.lat, pos.lng);
        });
        emit('update:latitude', lat);
        emit('update:longitude', lng);
    }

    map.on('click', async (event) => {
        await setLocation(event.latlng.lat, event.latlng.lng);
    });
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
});

// Keep external prop changes in sync (e.g. address change)
watch(
    () => [props.latitude, props.longitude],
    ([lat, lng]) => {
        if (lat != null && lng != null && map) {
            const nLat = Number(lat);
            const nLng = Number(lng);
            if (marker) {
                marker.setLatLng([nLat, nLng]);
            } else {
                marker = L.marker([nLat, nLng], { draggable: true }).addTo(map);
                marker.on('dragend', async (e) => {
                    const pos = e.target.getLatLng();
                    await setLocation(pos.lat, pos.lng);
                });
            }
        }
    }
);
</script>

<template>
    <div class="space-y-3">
        <div
            ref="mapElement"
            class="w-full h-80 rounded-xl overflow-hidden border border-[#E4E4E0]"
        ></div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
                type="button"
                @click="geocodeAddress"
                :disabled="geocoding || !addressQuery"
                class="h-10 rounded-lg border border-[#E4E4E0] text-sm font-medium hover:bg-[#F7F7F5] disabled:opacity-40"
            >
                {{ geocoding ? 'Locating…' : '📮 Locate My Shipping Address' }}
            </button>

            <button
                type="button"
                @click="useCurrentLocation"
                :disabled="geocoding"
                class="h-10 rounded-lg border border-[#E4E4E0] text-sm font-medium hover:bg-[#F7F7F5] disabled:opacity-40"
            >
                📍 Use My Current Location
            </button>
        </div>

        <p v-if="geocodeError" class="text-xs text-red-500">{{ geocodeError }}</p>
        <p v-if="validationError" class="text-xs text-red-500 font-medium">{{ validationError }}</p>

        <div
            v-if="latitude && longitude"
            class="text-xs text-[#8A8B90] bg-[#F7F7F5] rounded-lg p-3"
        >
            <p>
                Latitude:
                <span class="font-medium text-[#101114]">
                    {{ Number(latitude).toFixed(7) }}
                </span>
            </p>
            <p>
                Longitude:
                <span class="font-medium text-[#101114]">
                    {{ Number(longitude).toFixed(7) }}
                </span>
            </p>
        </div>

        <p v-else class="text-xs text-[#8A8B90]">
            Click anywhere on the map (within the Philippines) to select your exact delivery location.
            Pins in the ocean or outside the country are not allowed.
        </p>
    </div>
</template>
