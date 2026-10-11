<script setup>
import SellerLayout from '@/Layouts/SellerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ product: Object, categories: Array });

const leafCategories = props.categories.filter(c => !props.categories.some(child => child.parent_id === c.id));

const form = useForm({
    category_id: props.product.category_id,
    name: props.product.name,
    description: props.product.description,
    status: props.product.status,
});

const submit = () => form.patch(route('seller.products.update', props.product.id));
</script>

<template>
    <SellerLayout>
        <Head :title="'Edit ' + product.name" />

        <h1 class="text-xl font-semibold text-[#101114] mb-6">Edit Product</h1>

        <div class="max-w-2xl bg-white p-6 rounded-xl border border-[#E4E4E0] space-y-4">
            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Product Name</label>
                <input v-model="form.name" type="text" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm" />
            </div>

            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Category</label>
                <select v-model="form.category_id" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm">
                    <option v-for="cat in leafCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Description</label>
                <textarea v-model="form.description" rows="4" class="w-full p-3 rounded-lg border border-[#E4E4E0] text-sm"></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-[#8A8B90] mb-1.5">Status</label>
                <select v-model="form.status" class="w-full h-11 px-3 rounded-lg border border-[#E4E4E0] text-sm">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div class="pt-2 border-t border-[#EAEAE5]">
                <p class="text-xs font-medium text-[#8A8B90] mb-2">Variants ({{ product.variants.length }})</p>
                <div v-for="v in product.variants" :key="v.id" class="flex justify-between text-sm py-1.5 border-b border-[#EAEAE5] last:border-0">
                    <span>{{ v.label || v.sku }}</span>
                    <span>₱{{ v.price }} · {{ v.stock }} in stock</span>
                </div>
                <p class="text-xs text-[#B4B2A9] mt-2">Editing individual variant price/stock is a fast-follow — for now, adjust directly in your DB admin or extend this form.</p>
            </div>

            <button @click="submit" :disabled="form.processing" class="h-11 px-6 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white text-sm font-medium">
                {{ form.processing ? 'Saving…' : 'Save Changes' }}
            </button>
        </div>
    </SellerLayout>
</template>
