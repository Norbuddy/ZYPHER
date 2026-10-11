<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ itemsByShop: Object });

const grandTotal = computed(() => {
    let total = 0;
    for (const shopId in props.itemsByShop) {
        for (const item of props.itemsByShop[shopId]) {
            total += item.variant.price * item.quantity;
        }
    }
    return total.toFixed(2);
});

const updateQty = (itemId, quantity) => {
    if (quantity < 1) return;
    router.patch(route('cart.update', itemId), { quantity }, { preserveScroll: true });
};

const removeItem = (itemId) => {
    router.delete(route('cart.destroy', itemId), { preserveScroll: true });
};

console.log(props.itemsByShop)
</script>

<template>
    <GuestLayout>
        <Head title="Cart" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
            <h1 class="text-xl font-semibold text-[#101114] mb-6">Your Cart</h1>

            <div v-if="Object.keys(itemsByShop).length === 0" class="text-center py-20 text-[#8A8B90]">
                Your cart is empty. <Link :href="route('home')" class="text-[#6D5EF8]">Start shopping</Link>
            </div>

            <div v-for="(items, shopId) in itemsByShop" :key="shopId" class="mb-6 border border-[#E4E4E0] rounded-xl overflow-hidden">
                <div class="px-4 py-3 bg-[#F7F7F5] text-sm font-medium border-b border-[#E4E4E0]">
                    {{ items[0].variant.product.team.name }}
                </div>

                <div v-for="item in items" :key="item.id" class="flex items-center gap-4 px-4 py-4 border-b border-[#EAEAE5] last:border-0">
                <div class="w-16 h-16 rounded-lg overflow-hidden bg-[#F0F0EC] shrink-0">
                <img v-if="item.variant.product.images?.length" :src="(item.variant.product.images[0].path.startsWith('images/') ? '/' : '/storage/') + item.variant.product.images[0].path" class="w-full h-full object-cover"/>
                 <div v-else class="w-full h-full bg-[#F0F0EC]"></div></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ item.variant.product.name }}</p>
                        <p class="text-xs text-[#8A8B90]">{{ item.variant.label }}</p>
                        <p class="text-sm text-[#6D5EF8] font-semibold mt-1">₱{{ item.variant.price }}</p>
                    </div>
                    <div class="flex items-center border border-[#E4E4E0] rounded-lg">
                        <button @click="updateQty(item.id, item.quantity - 1)" class="w-8 h-8 text-[#5F5E5A]">−</button>
                        <span class="w-10 text-center text-sm">{{ item.quantity }}</span>
                        <button @click="updateQty(item.id, item.quantity + 1)" class="w-8 h-8 text-[#5F5E5A]">+</button>
                    </div>
                    <button @click="removeItem(item.id)" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                </div>
            </div>

            <div v-if="Object.keys(itemsByShop).length > 0" class="flex items-center justify-between mt-8 p-4 bg-white border border-[#E4E4E0] rounded-xl">
                <div>
                    <p class="text-xs text-[#8A8B90]">Total</p>
                    <p class="text-xl font-semibold text-[#101114]">₱{{ grandTotal }}</p>
                </div>
                <Link :href="route('checkout.index')" class="h-11 px-6 rounded-xl bg-gradient-to-r from-[#6D5EF8] to-[#33D6C8] text-white font-medium text-sm flex items-center">
                    Proceed to Checkout
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
