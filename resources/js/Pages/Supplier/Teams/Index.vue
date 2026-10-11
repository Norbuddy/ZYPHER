<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SupplierLayout from '@/Layouts/SupplierLayout.vue';

defineOptions({ layout: SupplierLayout });

const props = defineProps({
    teams: {
        type: Array,
        default: () => [],
    },
    myTeams: {
        type: Array,
        default: () => [],
    },
});

const search = ref('');
const statusFilter = ref('all');
const selectedTeamIds = ref([]);

const filteredTeams = computed(() => {
    return props.teams.filter((team) => {
        const matchesSearch = team.name.toLowerCase().includes(search.value.toLowerCase());
        const matchesStatus = statusFilter.value === 'all'
            || (statusFilter.value === 'active' && team.status === 'approved')
            || (statusFilter.value === 'inactive' && team.status !== 'approved');

        return matchesSearch && matchesStatus;
    });
});

const toggleSelection = (teamId) => {
    if (selectedTeamIds.value.includes(teamId)) {
        selectedTeamIds.value = selectedTeamIds.value.filter((id) => id !== teamId);
    } else {
        selectedTeamIds.value.push(teamId);
    }
};

const supplySelected = () => {
    if (!selectedTeamIds.value.length) return;

    router.post(route('supplier.teams.bulk_supply'), {
        team_ids: selectedTeamIds.value,
    });
};

const supplyTeam = (teamId) => {
    router.post(route('supplier.teams.supply', teamId));
};

const removeTeam = (teamId) => {
    router.delete(route('supplier.teams.remove', teamId));
};
</script>

<template>
    <Head title="Supplier Teams" />

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-[#101114]">Teams</h1>
                <p class="mt-2 text-sm text-gray-500">Choose which seller shops can order from your supplier account.</p>
            </div>
            <button
                @click="supplySelected"
                :disabled="selectedTeamIds.length === 0"
                class="rounded-xl bg-[#101114] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#1f2330] disabled:cursor-not-allowed disabled:bg-slate-300"
            >
                Supply Selected
            </button>
        </div>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 md:grid-cols-[1fr_auto]">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700">Search Teams</label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by shop name"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-[#101114]"
                    />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700">Status</label>
                    <select
                        v-model="statusFilter"
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-[#101114]"
                    >
                        <option value="all">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 space-y-4">
                <template v-if="filteredTeams.length">
                    <div v-for="team in filteredTeams" :key="team.id" class="rounded-2xl border border-gray-200 p-4">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-lg font-semibold text-[#101114]">{{ team.name }}</p>
                                <p class="text-sm text-gray-500">Status: <span class="font-medium text-slate-700">{{ team.status }}</span></p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    v-if="!team.is_supplied"
                                    @click="supplyTeam(team.id)"
                                    class="rounded-xl bg-[#101114] px-4 py-2 text-sm font-medium text-white hover:bg-[#1f2330]"
                                >
                                    Supply Team
                                </button>
                                <button
                                    v-else
                                    @click="removeTeam(team.id)"
                                    class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Remove Supply
                                </button>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                    <input
                                        type="checkbox"
                                        :value="team.id"
                                        v-model="selectedTeamIds"
                                        class="h-4 w-4 rounded border-gray-300 text-[#101114]"
                                    />
                                    Select
                                </label>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500">
                    No teams match your filters.
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-[#101114]">My Teams</h2>
                    <p class="text-sm text-gray-500">Teams you currently supply.</p>
                </div>
            </div>

            <div class="space-y-4">
                <template v-if="props.myTeams.length">
                    <div v-for="team in props.myTeams" :key="team.id" class="rounded-2xl border border-gray-200 p-4 md:flex md:items-center md:justify-between">
                        <div>
                            <p class="text-lg font-semibold text-[#101114]">{{ team.name }}</p>
                            <p class="mt-1 text-sm text-gray-500">Partnership: <span class="font-medium text-slate-700">{{ team.active ? 'Supplying' : 'Inactive' }}</span></p>
                            <p class="text-sm text-gray-500">Date Added: {{ team.added_at }}</p>
                        </div>
                        <button
                            @click="removeTeam(team.id)"
                            class="mt-4 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 md:mt-0"
                        >
                            Remove Supply
                        </button>
                    </div>
                </template>
                <div v-else class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500">
                    No teams are currently supplied.
                </div>
            </div>
        </section>
    </div>
</template>
