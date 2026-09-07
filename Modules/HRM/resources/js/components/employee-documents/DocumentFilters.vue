<script setup lang="ts">
import { computed } from 'vue';
import SearchInput from '@/components/SearchInput.vue';
import Select from '@/components/Select.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import type { DocumentTypeOption, DocumentStaffOption } from '../../types/employee-documents';

const props = defineProps<{
    search: string;
    selectedStaff: string | number;
    selectedType: string;
    selectedStatus: string;
    expiringSoonOnly: boolean;
    perPage: number;
    staff_members: DocumentStaffOption[];
    document_types: DocumentTypeOption[];
    document_statuses: Array<{ value: string; label: string }>;
    expiringSoonCount: number;
    totalCount: number;
    totalRecords: number;
    hasActiveFilters: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:search', val: string): void;
    (e: 'update:selectedStaff', val: string | number): void;
    (e: 'update:selectedType', val: string): void;
    (e: 'update:selectedStatus', val: string): void;
    (e: 'update:expiringSoonOnly', val: boolean): void;
    (e: 'update:perPage', val: number): void;
    (e: 'filter'): void;
    (e: 'reset'): void;
}>();

const staffOptions = computed(() => [
    { label: 'All Staff Members', value: '' },
    ...props.staff_members.map((s) => ({
        label: `${s.name} (${s.employee_code})`,
        value: s.id,
    })),
]);

const typeOptions = computed(() => [
    { label: 'All Document Types', value: '' },
    ...props.document_types.map((t) => ({
        label: t.label,
        value: t.value,
    })),
]);

const statusOptions = computed(() => [
    { label: 'All Statuses', value: '' },
    ...props.document_statuses.map((s) => ({
        label: s.label,
        value: s.value,
    })),
]);

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

const onSearchInput = (val: string) => {
    emit('update:search', val);
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => emit('filter'), 350);
};

const onStaffChange = (val: string | number) => {
    emit('update:selectedStaff', val);
    emit('filter');
};

const onTypeChange = (val: string | number) => {
    emit('update:selectedType', String(val ?? ''));
    emit('filter');
};

const onStatusChange = (val: string | number) => {
    emit('update:selectedStatus', String(val ?? ''));
    emit('filter');
};

const onPerPageChange = (val: number) => {
    emit('update:perPage', val);
    emit('filter');
};

const toggleExpiringSoon = () => {
    emit('update:expiringSoonOnly', !props.expiringSoonOnly);
    emit('filter');
};
</script>

<template>
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <SearchInput
                    :model-value="search"
                    placeholder="Search by title, doc number, employee..."
                    @update:model-value="onSearchInput"
                />
            </div>

            <!-- Staff Filter -->
            <div>
                <Select
                    :model-value="selectedStaff"
                    :options="staffOptions"
                    placeholder="All Staff Members"
                    searchable
                    @update:model-value="onStaffChange"
                />
            </div>

            <!-- Document Type Filter -->
            <div>
                <Select
                    :model-value="selectedType"
                    :options="typeOptions"
                    placeholder="All Document Types"
                    @update:model-value="onTypeChange"
                />
            </div>

            <!-- Status Filter -->
            <div>
                <Select
                    :model-value="selectedStatus"
                    :options="statusOptions"
                    placeholder="All Statuses"
                    @update:model-value="onStatusChange"
                />
            </div>
        </div>

        <!-- Quick Filter Pill: Expiring Soon -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-100 pt-3 dark:border-zinc-800">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition cursor-pointer"
                    :class="[
                        expiringSoonOnly
                            ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-300 dark:bg-amber-950/80 dark:text-amber-300'
                            : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'
                    ]"
                    @click="toggleExpiringSoon"
                >
                    <span class="h-2 w-2 rounded-full" :class="expiringSoonOnly ? 'bg-amber-500' : 'bg-zinc-400'" />
                    <span>Expiring within 30 days</span>
                    <span v-if="expiringSoonCount > 0" class="ml-1 rounded-full bg-amber-200 px-1.5 py-0.2 text-[10px] font-bold text-amber-900 dark:bg-amber-900 dark:text-amber-200">
                        {{ expiringSoonCount }}
                    </span>
                </button>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 underline cursor-pointer"
                    @click="emit('reset')"
                >
                    Reset filters
                </button>
            </div>

            <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                <span>Showing {{ totalCount }} of {{ totalRecords }} records</span>
                <PerPageSelector
                    :model-value="perPage"
                    :options="[10, 20, 50, 100]"
                    @update:model-value="onPerPageChange"
                />
            </div>
        </div>
    </div>
</template>
