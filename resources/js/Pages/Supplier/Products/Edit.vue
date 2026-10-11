<script setup>
import { Head, router } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { ref, computed } from 'vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({ product: Object, categories: Array });

const form = ref({
    name: props.product.name,
    category_id: props.product.category_id,
    description: props.product.description || '',
    price: props.product.variants?.[0]?.price ?? '',
    images: [],
    sizeStocks: {},
    stock: props.product.stock ?? (props.product.variants ? props.product.variants.reduce((s,v) => s + (v.stock || 0), 0) : 0),
});

const allowedShoesSizes = [36,37,38,39,40,41,42,43,44,45];
const clothingSizes = [['Small', 'S'], ['Medium', 'M'], ['Large', 'L'], ['XL', 'XL'], ['XXL', 'XXL']];

const selectedSizes = ref([]);

// Preload selected sizes and stocks from existing product variants
for (const v of props.product.variants || []) {
    if (v.label && v.label !== 'Default') {
        selectedSizes.value.push(String(v.label));
        form.value.sizeStocks[String(v.label)] = v.stock ?? 0;
    }
}

const selectedCategory = computed(() => props.categories.find(c => c.id === Number(form.value.category_id)));

const isShoeCategory = computed(() => {
    return selectedCategory.value && ['Shoes','Slippers'].includes(selectedCategory.value.name);
});

const isClothingCategory = computed(() => {
    return selectedCategory.value && ['Pants','T-Shirts','Long Sleeves','Jacket'].includes(selectedCategory.value.name);
});

const sizeOptions = computed(() => isShoeCategory.value ? allowedShoesSizes.map(s => String(s)) : (isClothingCategory.value ? clothingSizes.map(s => s[1]) : []));

const updateSizeStocksFromSelection = () => {
    for (const size of selectedSizes.value) {
        if (!(size in form.value.sizeStocks)) form.value.sizeStocks[size] = 0;
    }
    for (const key of Object.keys(form.value.sizeStocks)) {
        if (!selectedSizes.value.includes(key)) delete form.value.sizeStocks[key];
    }
};

const onFiles = (e) => {
    form.value.images = Array.from(e.target.files);
};

const submit = async () => {
    updateSizeStocksFromSelection();

    const fd = new FormData();
    fd.append('_method', 'patch');
    fd.append('name', form.value.name);
    fd.append('category_id', form.value.category_id);
    fd.append('description', form.value.description);
    fd.append('price', form.value.price);
    // If sizes selected, send size_variants[], otherwise send total stock
    if (selectedSizes.value.length > 0) {
        let i = 0;
        for (const size of selectedSizes.value) {
            const s = form.value.sizeStocks[size] ?? 0;
            fd.append(`size_variants[${i}][size]`, size);
            fd.append(`size_variants[${i}][stock]`, String(s));
            i++;
        }
    } else {
        fd.append('stock', form.value.stock);
    }

    form.value.images.forEach((f) => fd.append('images[]', f));

    await router.post(route('supplier.products.update', props.product.id), fd);
};
</script>

<template>
    <div class="space-y-6">
        <Head :title="`Edit: ${props.product.name}`" />

        <div class="mx-auto max-w-3xl">
            <h1 class="text-2xl font-semibold mb-4">Edit Product</h1>

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
                        <input
                            v-model.number="form.stock"
                            type="number"
                            class="mt-1 block w-full rounded border-gray-200"
                        />
                        <p class="text-xs text-gray-500 mt-1">Stock is managed here for single-stock products. For size-based products, manage per-size stock in the Sizes section.</p>
                    </div>
                    <div v-else>
                        <label class="block text-sm font-medium text-gray-700">Total Stock</label>
                        <input
                            :value="props.product.stock ?? (props.product.variants ? props.product.variants.reduce((s,v) => s + (v.stock || 0), 0) : 0)"
                            type="number"
                            disabled
                            class="mt-1 block w-full rounded border-gray-200 bg-gray-100 text-gray-500"
                        />
                        <p class="text-xs text-gray-500 mt-1">Total stock is calculated from sizes and cannot be edited here.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Add Images</label>
                    <input type="file" @change="onFiles" multiple accept="image/*" class="mt-1" />
                </div>

                <div class="pt-4">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</template>
