<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import VariantBuilder from '@/Components/VariantBuilder.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ categories: Array, supplierStock: { type: Array, default: () => [] } });

const form = useForm({
    category_id: '',
    name: '',
    description: '',
    publish: true,
    images: [],
    options: [],
    variants: [],
    seller_inventory_id: null,
});

const selectedSupplierStock = ref(null);

const onSupplierStockSelected = (e) => {
    const id = e.target.value;
    if (!id) {
        form.seller_inventory_id = null;
        selectedSupplierStock.value = null;
        return;
    }
    const row = props.supplierStock.find(r => String(r.id) === String(id));
    if (!row) return;

    form.seller_inventory_id = row.id;
    form.name = row.product_name;
    if (row.category_id && props.categories.some(c => c.id === row.category_id)) {
        form.category_id = row.category_id;
    }
    selectedSupplierStock.value = row;
};

// Leaf-only categories, per the "single leaf category" decision
const leafCategories = props.categories.filter(c => !props.categories.some(child => child.parent_id === c.id));

const imagePreviews = ref([]);

const onImagesSelected = (e) => {
    const files = Array.from(e.target.files);
    form.images = files;
    imagePreviews.value = files.map(f => URL.createObjectURL(f));
};

const removeImage = (index) => {
    const files = [...form.images];
    files.splice(index, 1);
    form.images = files;
    imagePreviews.value.splice(index, 1);
};

const onVariantsUpdate = (variants) => {
    // Derive the `options` payload (axis names + unique values) from the variant labels
    const axisMap = {};
    for (const variant of variants) {
        for (const part of variant.option_value_labels) {
            if (!axisMap[part.axis]) axisMap[part.axis] = new Set();
            axisMap[part.axis].add(part.value);
        }
    }
    form.options = Object.entries(axisMap).map(([name, values]) => ({ name, values: Array.from(values) }));
    form.variants = variants;
};

const submit = () => {
    form.post(route('seller.products.store'), { forceFormData: true });
};

const posForm = useForm({
    name: '',
    category_id: '',
    price: null,
    stock: 0,
    images: [],
    seller_inventory_id: null,
});

const posImagePreviews = ref([]);
const selectedPosSupplierStock = ref(null);

const onPosImagesSelected = (e) => {
    const files = Array.from(e.target.files);
    posForm.images = files;
    posImagePreviews.value = files.map(f => URL.createObjectURL(f));
};

const onPosSupplierStockSelected = (e) => {
    const id = e.target.value;
    if (!id) {
        posForm.seller_inventory_id = null;
        selectedPosSupplierStock.value = null;
        return;
    }
    const row = props.supplierStock.find(r => String(r.id) === String(id));
    if (!row) return;

    posForm.seller_inventory_id = row.id;
    posForm.name = row.product_name;
    posForm.stock = row.quantity;
    if (row.category_id && props.categories.some(c => c.id === row.category_id)) {
        posForm.category_id = row.category_id;
    }
    selectedPosSupplierStock.value = row;
};

const submitPos = () => {
    posForm.post(route('seller.products.store.pos'), {
        forceFormData: true,
        onSuccess: () => {
            posForm.reset();
            posImagePreviews.value = [];
            selectedPosSupplierStock.value = null;
        },
    });
};
</script>

<template>
    <SellerLayout>
        <Head title="Add Product" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">Add New Product</h1>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 space-y-6">
            <!-- <div v-if="props.supplierStock.length > 0" class="bg-indigo-50 border border-indigo-200 p-6 rounded-xl space-y-2">
                <label class="block text-xs font-medium text-indigo-700 mb-1.5">Publish from Supplier Stock (optional)</label>
                <select @change="onSupplierStockSelected" class="w-full h-11 px-3 rounded-lg border border-indigo-200 text-sm">
                    <option value="">— Create a fresh listing instead —</option>
                    <option v-for="row in props.supplierStock" :key="row.id" :value="row.id">
                        {{ row.product_name }} ({{ row.quantity }} units received)
                    </option>
                </select>
                <p v-if="selectedSupplierStock" class="text-xs text-indigo-700">
                    You have {{ selectedSupplierStock.quantity }} unit(s) available from this supplier delivery. Set your variant stock below to at most this amount — set your own retail price.
                </p>
            </div> -->

            <div class="bg-white p-6 rounded-xl border border-[#E4E4E0] space-y-4">
                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Product Name</label>
                    <input v-model="form.name" type="text" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                    <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Category (leaf-level)</label>
                    <select v-model="form.category_id" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm">
                        <option value="">Select category</option>
                        <option v-for="cat in leafCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                    <p v-if="form.errors.category_id" class="text-xs text-red-500 mt-1">{{ form.errors.category_id }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Description</label>
                    <textarea v-model="form.description" rows="4" class="w-full p-3 rounded-lg border border-[#E4E4E0] text-sm"></textarea>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl border border-[#E4E4E0]">
                <label class="block text-xs font-medium text-[#8A8B90] mb-2">Product Images</label>
                <p class="text-xs text-[#B4B2A9] mb-3">First image becomes the thumbnail shown in listings. Up to 8 images, 5MB each.</p>

                <input
                    type="file"
                    accept="image/*"
                    multiple
                    @change="onImagesSelected"
                    class="block w-full text-sm text-[#5F5E5A] file:mr-4 file:h-9 file:px-4 file:rounded-lg file:border-0 file:bg-[#101114] file:text-white file:text-sm file:cursor-pointer cursor-pointer"
                />
                <p v-if="form.errors.images" class="text-xs text-red-500 mt-1">{{ form.errors.images }}</p>

                <div v-if="imagePreviews.length > 0" class="grid grid-cols-4 sm:grid-cols-6 gap-3 mt-4">
                    <div v-for="(src, i) in imagePreviews" :key="i" class="relative aspect-square rounded-lg overflow-hidden border border-[#E4E4E0]">
                        <img :src="src" class="w-full h-full object-cover" />
                        <span v-if="i === 0" class="absolute top-1 left-1 text-[10px] bg-[#101114] text-white px-1.5 py-0.5 rounded">Primary</span>
                        <button
                            type="button"
                            @click="removeImage(i)"
                            class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/60 text-white text-xs flex items-center justify-center"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl border border-[#E4E4E0]">
                <VariantBuilder @update:modelValue="onVariantsUpdate" />
                <p v-if="form.errors.variants" class="text-xs text-red-500 mt-2">{{ form.errors.variants }}</p>
            </div>

            <div class="bg-white p-6 rounded-xl border border-[#E4E4E0] flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-[#101114]">Publish immediately</p>
                    <p class="text-xs text-[#8A8B90]">If off, this saves as a draft — hidden from the storefront until you publish it later.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input v-model="form.publish" type="checkbox" class="sr-only peer" />
                    <div class="w-10 h-6 bg-[#E4E4E0] peer-checked:bg-[#6D5EF8] rounded-full transition-colors"></div>
                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4"></div>
                </label>
            </div>

            <button
                @click="submit"
                :disabled="form.processing"
                class="h-11 px-6 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white font-medium text-sm disabled:opacity-40"
            >
                {{ form.processing ? 'Saving…' : (form.publish ? 'Publish Product' : 'Save as Draft') }}
            </button>
        </div>

        <div class="bg-white p-6 rounded-xl border border-[#E4E4E0] space-y-4 lg:sticky lg:top-6">
           <div>
                <p class="text-sm font-semibold text-[#101114]">Add Item for Physical Store (POS)</p>
                <p class="text-xs text-[#8A8B90] mt-1">For walk-in-only items. Won't show on the online storefront.</p>
            </div>

            <!-- <div v-if="props.supplierStock.length > 0">
                <label class="block text-xs font-medium text-indigo-700 mb-1.5">From Supplier Delivery (optional)</label>
                <select @change="onPosSupplierStockSelected" class="w-full h-10 px-3 rounded-lg border border-indigo-200 text-sm">
                    <option value="">— Add a fresh POS item instead —</option>
                    <option v-for="row in props.supplierStock" :key="row.id" :value="row.id">
                        {{ row.product_name }} ({{ row.quantity }} units)
                    </option>
                </select>
                <p v-if="selectedPosSupplierStock" class="text-xs text-indigo-700 mt-1">
                    {{ selectedPosSupplierStock.quantity }} unit(s) available from this delivery.
                </p>
            </div> -->

            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Product Name</label>
                <input v-model="posForm.name" type="text" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <p v-if="posForm.errors.name" class="text-xs text-red-500 mt-1">{{ posForm.errors.name }}</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Category</label>
                <select v-model="posForm.category_id" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm">
                    <option value="">Select category</option>
                    <option v-for="cat in leafCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
                <p v-if="posForm.errors.category_id" class="text-xs text-red-500 mt-1">{{ posForm.errors.category_id }}</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Price</label>
                <input v-model.number="posForm.price" type="number" step="0.01" min="0" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <p v-if="posForm.errors.price" class="text-xs text-red-500 mt-1">{{ posForm.errors.price }}</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Stock</label>
                <input v-model.number="posForm.stock" type="number" min="0" class="w-full h-10 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
                <p v-if="posForm.errors.stock" class="text-xs text-red-500 mt-1">{{ posForm.errors.stock }}</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Product Image</label>
                <input type="file" accept="image/*" multiple @change="onPosImagesSelected" class="text-xs" />
                <p v-if="posForm.errors.images" class="text-xs text-red-500 mt-1">{{ posForm.errors.images }}</p>
                <div v-if="posImagePreviews.length > 0" class="grid grid-cols-4 gap-2 mt-2">
                    <img v-for="(src, i) in posImagePreviews" :key="i" :src="src" class="aspect-square rounded-lg object-cover border border-[#E4E4E0]" />
                </div>
            </div>

            <button
                @click="submitPos"
                :disabled="posForm.processing"
                class="w-full h-10 rounded-lg bg-[#101114] text-white text-sm font-medium disabled:opacity-40"
            >
                {{ posForm.processing ? 'Saving…' : 'Add to POS' }}
            </button>
        </div>
        </div>
    </SellerLayout>
</template>
