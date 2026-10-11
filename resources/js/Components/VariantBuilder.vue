<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] }, // resulting variants array (v-model)
});
const emit = defineEmits(['update:modelValue']);

// Each axis: { name: 'Color', values: ['Red', 'Blue'] }
const axes = ref([{ name: '', values: [''] }]);

const addAxis = () => axes.value.push({ name: '', values: [''] });
const removeAxis = (i) => axes.value.splice(i, 1);
const addValue = (axisIndex) => axes.value[axisIndex].values.push('');
const removeValue = (axisIndex, valueIndex) => axes.value[axisIndex].values.splice(valueIndex, 1);

// Cartesian product of all axis values -> generates every combination
const combinations = computed(() => {
    const cleanAxes = axes.value
        .filter(a => a.name.trim())
        .map(a => ({ name: a.name.trim(), values: a.values.map(v => v.trim()).filter(Boolean) }))
        .filter(a => a.values.length > 0);

    if (cleanAxes.length === 0) return [];

    let result = [[]];
    for (const axis of cleanAxes) {
        const next = [];
        for (const combo of result) {
            for (const value of axis.values) {
                next.push([...combo, { axis: axis.name, value }]);
            }
        }
        result = next;
    }

    return result.map(combo => ({
        label: combo.map(c => c.value).join(' / '),
        parts: combo,
    }));
});

// variantData keyed by label, so edits (price/stock) persist across recomputation
const variantData = ref({});

watch(combinations, (newCombos) => {
    const next = {};
    for (const combo of newCombos) {
        next[combo.label] = variantData.value[combo.label] || { price: '', stock: '', sku: '' };
    }
    variantData.value = next;
    emitVariants();
}, { deep: true });

const emitVariants = () => {
    const variants = combinations.value.map(combo => ({
        label: combo.label,
        price: variantData.value[combo.label]?.price || 0,
        stock: variantData.value[combo.label]?.stock || 0,
        sku: variantData.value[combo.label]?.sku || '',
        option_value_labels: combo.parts, // frontend needs to resolve these to real IDs after axes are saved server-side, or send raw labels if your endpoint creates them inline
    }));
    emit('update:modelValue', variants);
};

const bulkPrice = ref('');
const bulkStock = ref('');
const applyBulk = () => {
    for (const key in variantData.value) {
        if (bulkPrice.value) variantData.value[key].price = bulkPrice.value;
        if (bulkStock.value) variantData.value[key].stock = bulkStock.value;
    }
    emitVariants();
};
</script>

<template>
    <div class="space-y-6">
        <div>
            <h3 class="text-sm font-medium text-[#101114] mb-1">Variation Options</h3>
            <p class="text-xs text-[#8A8B90] mb-4">Add as many attributes as needed (e.g. Color, Size, Material). Combinations are generated automatically below.</p>

            <div v-for="(axis, axisIndex) in axes" :key="axisIndex" class="mb-4 p-4 border border-[#E4E4E0] rounded-xl">
                <div class="flex items-center gap-2 mb-3">
                    <input
                        v-model="axis.name"
                        type="text"
                        placeholder="Option name (e.g. Color)"
                        class="flex-1 h-9 px-3 rounded-lg border border-[#E4E4E0] text-sm focus:border-[#6D5EF8] outline-none"
                    />
                    <button type="button" @click="removeAxis(axisIndex)" class="text-xs text-red-500 hover:text-red-700 px-2">
                        Remove
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <input
                        v-for="(val, valIndex) in axis.values"
                        :key="valIndex"
                        v-model="axis.values[valIndex]"
                        type="text"
                        placeholder="Value"
                        class="h-8 px-3 rounded-lg border border-[#E4E4E0] text-sm w-28 focus:border-[#6D5EF8] outline-none"
                    />
                    <button type="button" @click="addValue(axisIndex)" class="h-8 px-3 rounded-lg border border-dashed border-[#B4B2A9] text-xs text-[#8A8B90] hover:border-[#6D5EF8] hover:text-[#6D5EF8]">
                        + Add value
                    </button>
                </div>
            </div>

            <button type="button" @click="addAxis" class="text-sm font-medium text-[#6D5EF8] hover:text-[#33D6C8]">
                + Add another option
            </button>
        </div>

        <div v-if="combinations.length > 0">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-[#101114]">
                    Variant Combinations ({{ combinations.length }})
                </h3>
                <div class="flex items-center gap-2">
                    <input v-model="bulkPrice" type="number" placeholder="Bulk price" class="h-8 w-28 px-2 rounded-lg border border-[#E4E4E0] text-xs" />
                    <input v-model="bulkStock" type="number" placeholder="Bulk stock" class="h-8 w-24 px-2 rounded-lg border border-[#E4E4E0] text-xs" />
                    <button type="button" @click="applyBulk" class="h-8 px-3 rounded-lg bg-[#101114] text-white text-xs">Apply to all</button>
                </div>
            </div>

            <div class="border border-[#E4E4E0] rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-[#F7F7F5] text-xs text-[#8A8B90] uppercase">
                        <tr>
                            <th class="text-left px-4 py-2">Combination</th>
                            <th class="text-left px-4 py-2 w-32">Price (₱)</th>
                            <th class="text-left px-4 py-2 w-28">Stock</th>
                            <th class="text-left px-4 py-2 w-40">SKU</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="combo in combinations" :key="combo.label" class="border-t border-[#EAEAE5]">
                            <td class="px-4 py-2 font-medium">{{ combo.label }}</td>
                            <td class="px-4 py-2">
                                <input
                                    v-model="variantData[combo.label].price"
                                    @input="emitVariants"
                                    type="number" step="0.01" min="0"
                                    class="h-8 w-full px-2 rounded-lg border border-[#E4E4E0] text-sm"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <input
                                    v-model="variantData[combo.label].stock"
                                    @input="emitVariants"
                                    type="number" min="0"
                                    class="h-8 w-full px-2 rounded-lg border border-[#E4E4E0] text-sm"
                                />
                            </td>
                            <td class="px-4 py-2">
                                <input
                                    v-model="variantData[combo.label].sku"
                                    @input="emitVariants"
                                    type="text" placeholder="auto"
                                    class="h-8 w-full px-2 rounded-lg border border-[#E4E4E0] text-sm"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
