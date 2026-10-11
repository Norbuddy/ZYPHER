<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    tableId: { type: String, required: true }, // unique key para sa column visibility persistence
    columns: { type: Array, required: true }, // [{ key, label, sortable = true, visible = true }]
    rows: { type: Array, required: true },
    searchKeys: { type: Array, default: () => [] }, // which row fields to search
    pageSize: { type: Number, default: 10 },
    csvFilename: { type: String, default: 'export.csv' },
});

const search = ref('');
const sortKey = ref(null);
const sortDir = ref('asc');
const page = ref(1);

const storageKey = `datatable-columns-${props.tableId}`;
const savedVisibility = JSON.parse(localStorage.getItem(storageKey) || '{}');

const visibleColumns = ref(
    props.columns.map(c => ({
        ...c,
        visible: savedVisibility[c.key] ?? c.visible ?? true,
    }))
);

const showColumnMenu = ref(false);

const toggleColumn = (key) => {
    const col = visibleColumns.value.find(c => c.key === key);
    if (col) col.visible = !col.visible;

    const state = {};
    visibleColumns.value.forEach(c => { state[c.key] = c.visible; });
    localStorage.setItem(storageKey, JSON.stringify(state));
};

const activeColumns = computed(() => visibleColumns.value.filter(c => c.visible));

const getValue = (row, key) => {
    return key.split('.').reduce((obj, part) => obj?.[part], row);
};

const filteredRows = computed(() => {
    if (!search.value.trim()) return props.rows;
    const q = search.value.toLowerCase();
    const keys = props.searchKeys.length
        ? props.searchKeys
        : activeColumns.value.flatMap(column => column.searchKeys ?? [column.key]);

    return props.rows.filter(row =>
        keys.some(key => String(getValue(row, key) ?? '').toLowerCase().includes(q))
    );
});

const sortedRows = computed(() => {
    if (!sortKey.value) return filteredRows.value;

    return [...filteredRows.value].sort((a, b) => {
        const av = getValue(a, sortKey.value);
        const bv = getValue(b, sortKey.value);

        if (av == null && bv == null) return 0;
        if (av == null) return 1;
        if (bv == null) return -1;

        if (typeof av === 'number' && typeof bv === 'number') {
            return sortDir.value === 'asc' ? av - bv : bv - av;
        }

        return sortDir.value === 'asc'
            ? String(av).localeCompare(String(bv))
            : String(bv).localeCompare(String(av));
    });
});

const totalPages = computed(() => Math.max(1, Math.ceil(sortedRows.value.length / props.pageSize)));

const paginatedRows = computed(() => {
    const start = (page.value - 1) * props.pageSize;
    return sortedRows.value.slice(start, start + props.pageSize);
});

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
    page.value = 1;
};

const goToPage = (p) => {
    if (p >= 1 && p <= totalPages.value) page.value = p;
};

const exportCsv = () => {
    const cols = activeColumns.value;
    const header = cols.map(c => `"${c.label.replace(/"/g, '""')}"`).join(',');

    const lines = sortedRows.value.map(row =>
        cols.map(c => {
            const val = getValue(row, c.key);
            return `"${String(val ?? '').replace(/"/g, '""')}"`;
        }).join(',')
    );

    const csv = [header, ...lines].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = props.csvFilename;
    link.click();
    URL.revokeObjectURL(url);
};
</script>

<template>
    <div class="bg-white rounded-xl border border-[#E4E4E0] overflow-hidden">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-3 p-4 border-b border-[#EAEAE5]">
            <input
                v-model="search"
                type="text"
                placeholder="Search..."
                class="h-9 px-3 rounded-lg border border-[#E4E4E0] text-sm w-56"
                @input="page = 1"
            />

            <div class="flex items-center gap-2">
                <div class="relative">
                    <button
                        type="button"
                        @click="showColumnMenu = !showColumnMenu"
                        class="h-9 px-3 rounded-lg border border-[#E4E4E0] text-xs font-medium text-[#5F5E5A] hover:bg-[#F7F7F5]"
                    >
                        Columns ▾
                    </button>
                    <div
                        v-if="showColumnMenu"
                        class="absolute right-0 mt-1 w-48 bg-white border border-[#E4E4E0] rounded-lg shadow-lg z-10 p-2"
                        @click.outside="showColumnMenu = false"
                    >
                        <label
                            v-for="col in visibleColumns"
                            :key="col.key"
                            class="flex items-center gap-2 text-xs px-2 py-1.5 hover:bg-[#F7F7F5] rounded cursor-pointer"
                        >
                            <input type="checkbox" :checked="col.visible" @change="toggleColumn(col.key)" />
                            {{ col.label }}
                        </label>
                    </div>
                </div>

                <button
                    type="button"
                    @click="exportCsv"
                    class="h-9 px-3 rounded-lg bg-[#101114] text-white text-xs font-medium hover:bg-[#292929]"
                >
                    ⬇ Export CSV
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-[#F7F7F5]">
                    <tr>
                        <th
                            v-for="col in activeColumns"
                            :key="col.key"
                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#5F5E5A] select-none"
                            :class="col.sortable !== false ? 'cursor-pointer hover:text-[#101114]' : ''"
                            @click="col.sortable !== false && toggleSort(col.key)"
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ col.label }}
                                <template v-if="sortKey === col.key">
                                    <span class="text-[10px]">{{ sortDir === 'asc' ? '▲' : '▼' }}</span>
                                </template>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EAEAE5]">
                    <tr v-if="paginatedRows.length === 0">
                        <td :colspan="activeColumns.length" class="px-4 py-10 text-center text-sm text-[#8A8B90]">
                            No results found.
                        </td>
                    </tr>
                    <tr v-for="(row, i) in paginatedRows" :key="row.id ?? i">
                        <td v-for="col in activeColumns" :key="col.key" class="px-4 py-3">
                            <slot :name="`cell-${col.key}`" :row="row" :value="getValue(row, col.key)">
                                {{ getValue(row, col.key) }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-4 py-3 border-t border-[#EAEAE5] text-xs text-[#8A8B90]">
            <span>{{ sortedRows.length }} result(s)</span>
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    @click="goToPage(page - 1)"
                    :disabled="page <= 1"
                    class="w-7 h-7 rounded border border-[#E4E4E0] disabled:opacity-30"
                >
                    ‹
                </button>
                <span class="px-2">{{ page }} / {{ totalPages }}</span>
                <button
                    type="button"
                    @click="goToPage(page + 1)"
                    :disabled="page >= totalPages"
                    class="w-7 h-7 rounded border border-[#E4E4E0] disabled:opacity-30"
                >
                    ›
                </button>
            </div>
        </div>
    </div>
</template>