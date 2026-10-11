<template>
  <div class="space-y-6">
    <Head title="Supplier Inventory" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-600">Total Products</h3>
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ stats.total_products }}</p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-600">Available Stock</h3>
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ stats.available_stock }}</p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-600">Low Stock</h3>
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ stats.low_stock_products }}</p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-600">Out of Stock</h3>
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ stats.out_of_stock_products }}</p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-600">Inventory Value</h3>
        <p class="mt-3 text-3xl font-semibold text-slate-900">₱{{ stats.inventory_value }}</p>
      </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Inventory details</h2>
          <p class="text-sm text-slate-500">Manage each variant stock level from your supplier catalog.</p>
        </div>
      </div>

      <div class="overflow-hidden rounded-lg border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-slate-500">
            <tr>
              <th class="px-4 py-3 text-left font-medium">Product</th>
              <th class="px-4 py-3 text-left font-medium">Size</th>
              <th class="px-4 py-3 text-left font-medium">Category</th>
              <th class="px-4 py-3 text-right font-medium">Stock</th>
              <th class="px-4 py-3 text-right font-medium">Reserved</th>
              <th class="px-4 py-3 text-right font-medium">Available</th>
              <th class="px-4 py-3 text-right font-medium">Price</th>
              <th class="px-4 py-3 text-left font-medium">Status</th>
              <th class="px-4 py-3 text-left font-medium">Approval</th>
              <th class="px-4 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-for="variant in variants" :key="variant.id">
              <td class="px-4 py-3">{{ variant.product_name }}</td>
              <td class="px-4 py-3">{{ variant.size }}</td>
              <td class="px-4 py-3">{{ variant.category }}</td>
              <td class="px-4 py-3 text-right">{{ variant.stock }}</td>
              <td class="px-4 py-3 text-right">{{ variant.reserved_stock }}</td>
              <td class="px-4 py-3 text-right">{{ variant.available_stock }}</td>
              <td class="px-4 py-3 text-right">₱{{ variant.price }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase"
                  :class="{
                    'bg-emerald-100 text-emerald-800': variant.status === 'in_stock',
                    'bg-amber-100 text-amber-800': variant.status === 'low_stock',
                    'bg-rose-100 text-rose-800': variant.status === 'out_of_stock',
                  }"
                >
                  {{ statusLabel(variant.status) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase"
                  :class="{
                    'bg-emerald-100 text-emerald-800': variant.stock_update_status === 'approved',
                    'bg-amber-100 text-amber-800': variant.stock_update_status === 'pending',
                  }"
                >
                  {{ variant.stock_update_status === 'pending' ? 'Pending Approval' : 'Approved' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <Link
                  :href="route('supplier.inventory.edit', variant.id)"
                  class="inline-flex items-center rounded-md bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700"
                >
                  Update
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({
  stats: Object,
  variants: Array,
});

const statusLabel = (status) => {
  switch (status) {
    case 'in_stock':
      return 'In stock';
    case 'low_stock':
      return 'Low stock';
    case 'out_of_stock':
      return 'Out of stock';
    default:
      return 'Unknown';
  }
};
</script>
