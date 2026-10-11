<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({
    layout: SellerLayout
})

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },

    lowStocks: {
        type: Array,
        default: () => [],
    },

    topSelling: {
        type: Array,
        default: () => [],
    },

    supplierInventory: {
        type: Array,
        default: () => [],
    },

    manualEligibleProducts: {
        type: Array,
        default: () => [],
    },

    branchStocks: {
        type: Array,
        default: () => [],
    },

    categories: {
        type: Array,
        default: () => [],
    },

    shopProducts: {
        type: Array,
        default: () => [],
    },
})

const activeTab = ref('stocks')
const isBranchUser = Boolean(usePage().props.auth?.user?.branch_id)

const manualForm = useForm({
    product_variant_id: '',
    quantity: 1,
})

const submitManual = () => {
    manualForm.post(route('seller.inventory.manual.store'), {
        preserveScroll: true,
        onSuccess: () => manualForm.reset('quantity'),
    })
}

const branchItemForm = useForm({
    name: '',
    price: 0,
    stock: 0,
    category_id: '',
    image: null,
})

const importForm = useForm({
    source_product_id: '',
})

const submitBranchItem = () => {
    branchItemForm.post(route('seller.inventory.items.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => branchItemForm.reset(),
    })
}

const onBranchItemImageSelected = (event) => {
    branchItemForm.image = event.target.files[0] ?? null
}

const importShopProduct = () => {
    importForm.post(route('seller.inventory.shop-products.import'), {
        preserveScroll: true,
        onSuccess: () => importForm.reset(),
    })
}

const stockColumns = [
    { key: 'product_name', label: 'Product' },
    { key: 'variant', label: 'Variant' },
    { key: 'sku', label: 'SKU' },
    { key: 'stock', label: 'Stock' },
    { key: 'available_stock', label: 'Available' },
    { key: 'price', label: 'Price' },
    { key: 'source', label: 'Source' },
]
</script>

<template>
<div class="p-8">

    <h1 class="text-3xl font-bold mb-6">
        Inventory Dashboard
    </h1>

    <div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200">
        <button type="button" @click="activeTab = 'stocks'" class="border-b-2 px-4 py-3 text-sm font-medium" :class="activeTab === 'stocks' ? 'border-[#101114] text-[#101114]' : 'border-transparent text-gray-500'">
            Branch Stock
        </button>
        <button type="button" @click="activeTab = 'add-stock'" class="border-b-2 px-4 py-3 text-sm font-medium" :class="activeTab === 'add-stock' ? 'border-[#101114] text-[#101114]' : 'border-transparent text-gray-500'">
            Add Stock
        </button>
        <button v-if="isBranchUser" type="button" @click="activeTab = 'add-item'" class="border-b-2 px-4 py-3 text-sm font-medium" :class="activeTab === 'add-item' ? 'border-[#101114] text-[#101114]' : 'border-transparent text-gray-500'">
            Add Branch Item
        </button>
        <button v-if="isBranchUser" type="button" @click="activeTab = 'import-item'" class="border-b-2 px-4 py-3 text-sm font-medium" :class="activeTab === 'import-item' ? 'border-[#101114] text-[#101114]' : 'border-transparent text-gray-500'">
            Add Shop Item
        </button>
    </div>

    <div v-if="activeTab === 'stocks'" class="mb-6">
        <DataTable
            table-id="branch-stock"
            :columns="stockColumns"
            :rows="props.branchStocks"
            :search-keys="['product_name', 'variant', 'sku', 'source']"
            csv-filename="branch-stock.csv"
        >
            <template #cell-price="{ value }">₱{{ Number(value).toFixed(2) }}</template>
            <template #cell-stock="{ value }"><span class="font-semibold">{{ value }}</span></template>
            <template #cell-available_stock="{ value }"><span :class="value <= 5 ? 'text-orange-600' : 'text-green-600'" class="font-semibold">{{ value }}</span></template>
        </DataTable>
    </div>

    <div v-if="activeTab === 'add-stock'" class="mb-6 rounded-xl border bg-white p-6 shadow">
        <h2 class="text-xl font-semibold">Add Stock from Shop</h2>
        <p class="mt-1 text-sm text-gray-500">Add stock to an item already assigned to this branch.</p>
        <form @submit.prevent="submitManual" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium">Shop Product</label>
                <select v-model="manualForm.product_variant_id" class="w-full rounded-lg border px-3 py-2" required>
                    <option value="">Select a branch item</option>
                    <option v-for="product in props.manualEligibleProducts" :key="product.id" :value="product.id">{{ product.name }}</option>
                </select>
                <p v-if="manualForm.errors.product_variant_id" class="mt-1 text-xs text-red-500">{{ manualForm.errors.product_variant_id }}</p>
            </div>
            <div class="sm:w-32">
                <label class="mb-1 block text-sm font-medium">Quantity</label>
                <input v-model.number="manualForm.quantity" type="number" min="1" class="w-full rounded-lg border px-3 py-2" required />
            </div>
            <button type="submit" :disabled="manualForm.processing" class="rounded-lg bg-[#101114] px-5 py-2 text-sm font-medium text-white disabled:opacity-50">{{ manualForm.processing ? 'Adding...' : 'Add Stock' }}</button>
        </form>
    </div>

    <div v-if="activeTab === 'add-item' && isBranchUser" class="mb-6 rounded-xl border bg-white p-6 shadow">
        <h2 class="text-xl font-semibold">Add Branch Item</h2>
        <p class="mt-1 text-sm text-gray-500">Create an item owned by this branch. It stays separate from the main shop catalog.</p>
        <form @submit.prevent="submitBranchItem" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Item Name</label>
                <input v-model="branchItemForm.name" type="text" class="w-full rounded-lg border px-3 py-2" required />
                <p v-if="branchItemForm.errors.name" class="mt-1 text-xs text-red-500">{{ branchItemForm.errors.name }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Category</label>
                <select v-model="branchItemForm.category_id" class="w-full rounded-lg border px-3 py-2" required>
                    <option value="">Select a category</option>
                    <option v-for="category in props.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Price</label>
                <input v-model.number="branchItemForm.price" type="number" min="0" step="0.01" class="w-full rounded-lg border px-3 py-2" required />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Starting Stock</label>
                <input v-model.number="branchItemForm.stock" type="number" min="0" class="w-full rounded-lg border px-3 py-2" required />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Photo</label>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border px-3 py-2" @change="onBranchItemImageSelected" />
                <p v-if="branchItemForm.errors.image" class="mt-1 text-xs text-red-500">{{ branchItemForm.errors.image }}</p>
            </div>
            <div class="flex items-end">
                <button type="submit" :disabled="branchItemForm.processing" class="rounded-lg bg-[#101114] px-5 py-2 text-sm font-medium text-white disabled:opacity-50">{{ branchItemForm.processing ? 'Creating...' : 'Create Branch Item' }}</button>
            </div>
        </form>
    </div>

    <div v-if="activeTab === 'import-item' && isBranchUser" class="mb-6 rounded-xl border bg-white p-6 shadow">
        <h2 class="text-xl font-semibold">Add Shop Item</h2>
        <p class="mt-1 text-sm text-gray-500">Copy a main-shop product into this branch. Its products and stock will be independent.</p>
        <form @submit.prevent="importShopProduct" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium">Main Shop Product</label>
                <select v-model="importForm.source_product_id" class="w-full rounded-lg border px-3 py-2" required>
                    <option value="">Select a product</option>
                    <option v-for="product in props.shopProducts" :key="product.id" :value="product.id">{{ product.name }}</option>
                </select>
                <p v-if="importForm.errors.source_product_id" class="mt-1 text-xs text-red-500">{{ importForm.errors.source_product_id }}</p>
            </div>
            <button type="submit" :disabled="importForm.processing" class="rounded-lg bg-[#101114] px-5 py-2 text-sm font-medium text-white disabled:opacity-50">
                {{ importForm.processing ? 'Adding...' : 'Add to Branch' }}
            </button>
        </form>
        <p v-if="!props.shopProducts.length" class="mt-3 text-sm text-gray-500">All main-shop products are already in this branch.</p>
    </div>

    <div class="grid grid-cols-12 gap-6">

        <!-- LEFT COLUMN -->
        <div class="col-span-4">

            <div class="grid grid-rows-4 gap-6 h-full">

                <div class="bg-white rounded-xl shadow border p-6">
                    <h2 class="text-lg font-semibold text-gray-500">
                        Total Products
                    </h2>

                    <p class="text-5xl font-bold mt-5">
                        {{ props.stats.total_products }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow border p-6">
                    <h2 class="text-lg font-semibold text-orange-600">
                        Low Stock Items
                    </h2>

                    <p class="text-5xl font-bold mt-5">
                        {{ props.stats.low_stock }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow border p-6">
                    <h2 class="text-lg font-semibold text-green-600">
                        In Stock
                    </h2>

                    <p class="text-5xl font-bold mt-5">
                        {{ props.stats.in_stock }}
                    </p>
                </div>

                <div class="bg-white rounded-xl shadow border p-6">
                    <h2 class="text-lg font-semibold text-red-600">
                        Out of Stock
                    </h2>

                    <p class="text-5xl font-bold mt-5">
                        {{ props.stats.out_of_stock }}
                    </p>
                </div>

            </div>

        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-span-8">

            <div class="grid grid-rows-2 gap-6 h-full">

                <!-- LOW STOCK -->
                <div class="bg-white rounded-xl shadow border p-6">

                    <h2 class="text-2xl font-bold mb-4">
                        🚨 Low Stocks Alert
                    </h2>

                    <table class="w-full">

                        <thead class="border-b">

                            <tr class="text-left">

                                <th class="pb-3">Product</th>

                                <th class="pb-3">Variant</th>

                                <th class="pb-3">Stock</th>

                            </tr>

                        </thead>

                    <tbody>

                <tr v-if="props.lowStocks.length === 0">
                    <td colspan="3" class="py-8 text-center text-gray-400">
            
                        No low stock items.
                    </td>
                </tr>

                <tr v-for="item in props.lowStocks" :key="item.sku" class="border-b last:border-0">
            <td class="py-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#F0F0EC] overflow-hidden shrink-0">
                        <img
                            v-if="item.image_path"
                            :src="(item.image_path.startsWith('images/') ? '/' : '/storage/') + item.image_path"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    <span>{{ item.product }}</span>
                </div>
            </td>

            <td>
                {{ item.variant }}
            </td>

            <td class="font-semibold text-orange-600">
                {{ item.stock }}
            </td>
            </tr>
        </tbody>
                    </table>

                </div>

                <!-- TOP SELLING -->
                <div class="bg-white rounded-xl shadow border p-6">

                    <h2 class="text-2xl font-bold mb-4">
                        🏆 Top Selling Products
                    </h2>

                    <table class="w-full">

                        <thead class="border-b">

                            <tr class="text-left">

                                <th class="pb-3">Product</th>

                                <th class="pb-3">Sold</th>

                                <th class="pb-3">Revenue</th>

                            </tr>

                        </thead>

                        <tbody>

    <tr v-if="props.topSelling.length === 0">
        <td colspan="3" class="py-8 text-center text-gray-400">
            No sales yet.
        </td>
    </tr>

    <tr
        v-for="item in props.topSelling"
        :key="item.product_name"
        class="border-b last:border-0"
    >
        <td class="py-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-[#F0F0EC] overflow-hidden shrink-0">
                    <img
                        v-if="item.image_path"
                        :src="(item.image_path.startsWith('images/') ? '/' : '/storage/') + item.image_path"
                        class="w-full h-full object-cover"
                    />
                </div>
                <span>{{ item.product_name }}</span>
            </div>
        </td>

        <td>
            {{ item.sold }}
        </td>

        <td class="font-semibold text-green-600">
            ₱{{ Number(item.revenue).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) }}
        </td>
    </tr>

</tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>
</div>

    <!-- <div class="mt-6 grid grid-cols-12 gap-6">
        <div class="col-span-8 bg-white rounded-xl shadow border p-6">
            <h2 class="text-2xl font-bold mb-4">📦 Ordered Products Inventory</h2>
            <p class="text-sm text-gray-500 mb-4">Stock received from supplier orders and manual entries. This is separate from your storefront stock.</p>
            <table class="w-full">
                <thead class="border-b">
                    <tr class="text-left">
                        <th class="pb-3">Product</th>
                        <th class="pb-3">Quantity</th>
                        <th class="pb-3">Source</th>
                        <th class="pb-3">Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="props.supplierInventory.length === 0">
                        <td colspan="4" class="py-8 text-center text-gray-400">No supplier-received inventory yet.</td>
                    </tr>
                    <tr v-for="row in props.supplierInventory" :key="row.id" class="border-b last:border-0">
                        <td class="py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-[#F0F0EC] overflow-hidden shrink-0">
                                    <img
                                        v-if="row.image_path"
                                        :src="(row.image_path.startsWith('images/') ? '/' : '/storage/') + row.image_path"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <span>{{ row.product_name }}</span>
                            </div>
                        </td>
                        <td class="font-semibold">{{ row.quantity }}</td>
                        <td>
                            <span :class="row.source_type === 'supplier' ? 'text-indigo-600' : 'text-gray-500'" class="text-xs font-medium uppercase">
                                {{ row.source_type }}
                            </span>
                        </td>
                        <td class="text-sm text-gray-500">{{ row.updated_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div> -->
</template>