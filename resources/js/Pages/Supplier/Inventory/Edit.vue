<template>
  <div class="space-y-6">
    <Head title="Update Inventory" />

    <div class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold">Update Inventory</h1>
        <p class="text-sm text-slate-500">Adjust stock levels for this variant.</p>
      </div>

      <form @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <h2 class="font-semibold text-slate-900">Product</h2>
            <p class="mt-1 text-sm text-slate-600">{{ variant.product_name }}</p>
          </div>
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <h2 class="font-semibold text-slate-900">Size</h2>
            <p class="mt-1 text-sm text-slate-600">{{ variant.size }}</p>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3 mt-6">
          <div>
            <label class="block text-sm font-medium text-slate-700">Stock</label>
            <input
              type="number"
              v-model.number="form.stock"
              min="0"
              class="mt-1 block w-full rounded border border-slate-200 px-3 py-2"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Reserved</label>
            <input
              type="number"
              :value="variant.reserved_stock"
              disabled
              class="mt-1 block w-full rounded border border-slate-200 bg-slate-50 px-3 py-2"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Available</label>
            <input
              type="number"
              :value="variant.available_stock"
              disabled
              class="mt-1 block w-full rounded border border-slate-200 bg-slate-50 px-3 py-2"
            />
          </div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <h2 class="font-semibold text-slate-900">Stock Approval</h2>
          <p class="mt-1 text-sm text-slate-600">
            {{ variant.stock_update_status === 'pending' ? 'This stock update is pending approval.' : 'Stock is approved and live.' }}
          </p>
        </div>

        <div class="flex items-center gap-2 pt-4">
          <button type="submit" class="rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
            Save Stock
          </button>
          <Link
            :href="route('supplier.inventory.index')"
            class="rounded border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Back to Inventory
          </Link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';
import { ref } from 'vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({ variant: Object });

const form = ref({
  stock: props.variant.stock,
});

const submit = async () => {
  const fd = new FormData();
  fd.append('_method', 'patch');
  fd.append('stock', form.value.stock);
  await router.post(route('supplier.inventory.update', props.variant.id), fd);
};
</script>
