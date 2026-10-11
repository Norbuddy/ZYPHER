<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({ products: Object });

const confirmingDelete = ref(false);
const productToDelete = ref(null);

const requestDelete = (product) => {
    productToDelete.value = product;
    confirmingDelete.value = true;
};

const remove = async () => {
    if (!productToDelete.value) return;

    await router.delete(route('supplier.products.destroy', productToDelete.value.id));
    confirmingDelete.value = false;
    window.location.reload();
};
</script>

<template>
    <div class="space-y-6">
        <Head title="Product Management" />

        <div class="mx-auto max-w-6xl">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-2xl font-semibold">Products</h1>
                    <p class="text-sm text-gray-500">Create and manage your products.</p>
                </div>
                <div>
                    <Link :href="route('supplier.products.create')" class="px-4 py-2 bg-indigo-600 text-white rounded">Add Product</Link>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-gray-600 uppercase text-xs">
                                <th class="px-4 py-3">Image</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Size</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products.data" :key="product.id" class="border-b border-gray-100">
                                <td class="px-4 py-4">
                                    <img v-if="product.images && product.images.length" :src="product.images[0].url" class="h-12 w-12 object-cover rounded" />
                                    <div v-else class="h-12 w-12 bg-gray-100 rounded"></div>
                                </td>
                                <td class="px-4 py-4">{{ product.name }}</td>
                                <td class="px-4 py-4">{{ product.variants?.[0]?.label ?? product.variants?.[0]?.sku ?? '—' }}</td>
                                <td class="px-4 py-4">₱{{ product.min_price ?? '0.00' }}</td>
                                <td class="px-4 py-4">{{ product.stock ?? (product.variants ? product.variants.reduce((s,v) => s + (v.stock || 0), 0) : 0) }}</td>
                                <td class="px-4 py-4">{{ product.category?.name ?? '—' }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex gap-2">
                                        <Link :href="route('supplier.products.edit', product.id)" class="px-3 py-1 rounded bg-blue-600 text-white text-xs">Edit</Link>
                                        <button @click.prevent="requestDelete(product)" class="px-3 py-1 rounded bg-red-500 text-white text-xs">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!products.data || products.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400">No products yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ConfirmationModal :show="confirmingDelete" @close="confirmingDelete = false">
            <template #title>
                Delete Product
            </template>

            <template #content>
                Are you sure you want to delete this product? This action cannot be undone.
            </template>

            <template #footer>
                <SecondaryButton @click="confirmingDelete = false">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    @click="remove"
                >
                    Delete Product
                </DangerButton>
            </template>
        </ConfirmationModal>
    </div>
</template>
