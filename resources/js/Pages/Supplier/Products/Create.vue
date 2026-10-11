<script setup>
import { Head, router } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { ref, computed } from 'vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({ categories: Array });

const form = ref({
    name: '',
    category_id: null,
    description: '',
    price: '',
    stock: '',
    images: [],
    sizeStocks: {}, // map size => stock
});

const allowedShoesSizes = [36,37,38,39,40,41,42,43,44,45];
const clothingSizes = [['Small', 'S'], ['Medium', 'M'], ['Large', 'L'], ['XL', 'XL'], ['XXL', 'XXL']];

const selectedSizes = ref([]);

const selectedCategory = computed(() => props.categories.find(c => c.id === Number(form.value.category_id)));

const isShoeCategory = computed(() => {
    return selectedCategory.value && ['Shoes','Slippers'].includes(selectedCategory.value.name);
});

const isClothingCategory = computed(() => {
    return selectedCategory.value && ['Pants','T-Shirts','Long Sleeves','Jacket'].includes(selectedCategory.value.name);
});

const sizeOptions = computed(() => isShoeCategory.value ? allowedShoesSizes.map(s => String(s)) : (isClothingCategory.value ? clothingSizes.map(s => s[1]) : []));

const updateSizeStocksFromSelection = () => {
    // ensure there's an entry for each selected size
    for (const size of selectedSizes.value) {
        if (!(size in form.value.sizeStocks)) form.value.sizeStocks[size] = 0;
    }
    // remove stocks for unselected sizes
    for (const key of Object.keys(form.value.sizeStocks)) {
        if (!selectedSizes.value.includes(key)) delete form.value.sizeStocks[key];
    }
};

const onFiles = (e) => {
    form.value.images = Array.from(e.target.files);
};

const submit = async () => {
    updateSizeStocksFromSelection();

    const payload = {
        name: form.value.name,
        category_id: form.value.category_id,
        description: form.value.description,
        price: form.value.price,
        images: form.value.images,
    };

    if (selectedSizes.value.length > 0) {
        payload.size_variants = selectedSizes.value.map((size) => ({
            size,
            stock: form.value.sizeStocks[size] ?? 0,
        }));
    } else {
        payload.stock = form.value.stock;
    }

    router.post(route('supplier.products.store'), payload, {
        forceFormData: true,
    });
};
</script>

<template>
    <div class="space-y-6">
        <Head title="Add Product" />

        <div class="mx-auto max-w-3xl">
            <h1 class="text-2xl font-semibold mb-4">Add Product</h1>

            <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input v-model="form.name" class="mt-1 block w-full rounded border-gray-200" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select v-model="form.category_id" class="mt-1 block w-full rounded border-gray-200">
                        <option :value="null">Select</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <div v-if="isShoeCategory || isClothingCategory" class="pt-2">
                    <h3 class="text-sm font-medium text-gray-700">Available Sizes</h3>
                    <div class="mt-2 grid grid-cols-4 gap-2">
                        <label v-for="opt in sizeOptions" :key="opt" class="flex items-center gap-2">
                            <input type="checkbox" :value="opt" v-model="selectedSizes" @change="updateSizeStocksFromSelection" />
                            <span class="text-sm">{{ opt }}</span>
                        </label>
                    </div>

                    <div v-if="selectedSizes.length > 0" class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700">Stock per Size</h4>
                        <div class="mt-2 space-y-2">
                            <div v-for="s in selectedSizes" :key="s" class="flex items-center gap-4">
                                <div class="w-32 font-medium">{{ s }}</div>
                                <input type="number" v-model.number="form.sizeStocks[s]" min="0" class="w-32 rounded border-gray-200" />
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea v-model="form.description" class="mt-1 block w-full rounded border-gray-200"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input v-model="form.price" type="number" step="0.01" class="mt-1 block w-full rounded border-gray-200" />
                    </div>
                    <div v-if="!isShoeCategory && !isClothingCategory">
                        <label class="block text-sm font-medium text-gray-700">Stock</label>
                        <input v-model="form.stock" type="number" class="mt-1 block w-full rounded border-gray-200" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Images</label>
                    <input type="file" @change="onFiles" multiple accept="image/*" class="mt-1" />
                </div>

                <div class="pt-4">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</template>
