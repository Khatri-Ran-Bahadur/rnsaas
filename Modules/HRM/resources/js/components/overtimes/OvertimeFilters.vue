<script setup lang="ts">
import { computed } from 'vue';
import SearchInput from '@/components/SearchInput.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import type { OvertimeOption } from '../../types/overtimes';

const props = defineProps<{
    search: string;
    staffId: number | string;
    typeFilter: string;
    statusFilter: string;
    fromDate: string;
    toDate: string;
    perPage: number;
    staff_members: Array<{ id: number; public_id: string; name: string; employee_code: string }>;
    types: OvertimeOption[];
    statuses: OvertimeOption[];
    totalCount: number;
    hasActiveFilters: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:search', val: string): void;
    (e: 'update:staffId', val: number | string): void;
    (e: 'update:typeFilter', val: string): void;
    (e: 'update:statusFilter', val: string): void;
    (e: 'update:fromDate', val: string): void;
    (e: 'update:toDate', val: string): void;
    (e: 'update:perPage', val: number): void;
    (e: 'filter'): void;
    (e: 'reset'): void;
}>();

const staffOptions = computed(() => [
    { label: 'All Staff Members', value: '' },
    ...(props.staff_members || []).map((s) => ({
        label: `${s.name} (${s.employee_code})`,
        value: s.id,
    })),
]);

const typeOptions = computed(() => [
    { label: 'All Overtime Types', value: '' },
    ...(props.types || []).map((t) => ({
        label: t.label,
        value: t.value,
    })),
]);

const statusOptions = computed(() => [
    { label: 'All Statuses', value: '' },
    ...(props.statuses || []).map((s) => ({
        label: s.label,
        value: s.value,
    })),
]);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const onSearchInput = (val: string) => {
    emit('update:search', val);
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => emit('filter'), 300);
};

const onStaffChange = (val: string | number) => {
    emit('update:staffId', val);
    emit('filter');
};

const onTypeChange = (val: string | number) => {
    emit('update:typeFilter', String(val ?? ''));
    emit('filter');
};

const onStatusChange = (val: string | number) => {
    emit('update:statusFilter', String(val ?? ''));
    emit('filter');
};

const onFromDateChange = (val: string) => {
    emit('update:fromDate', val);
    emit('filter');
};

const onToDateChange = (val: string) => {
    emit('update:toDate', val);
    emit('filter');
};

const onPerPageChange = (val: number) => {
    emit('update:perPage', val);
    emit('filter');
};
</script>

<template>
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <SearchInput
                    :model-value="search"
                    placeholder="Search employee or reason..."
                    class="w-full"
                    @update:model-value="onSearchInput"
                />
            </div>

            <div>
                <Select
                    :model-value="staffId"
                    :options="staffOptions"
                    placeholder="All Staff Members"
                    searchable
                    @update:model-value="onStaffChange"
                />
            </div>

            <div>
                <Select
                    :model-value="typeFilter"
                    :options="typeOptions"
                    placeholder="All Overtime Types"
                    @update:model-value="onTypeChange"
                />
            </div>

            <div>
                <Select
                    :model-value="statusFilter"
                    :options="statusOptions"
                    placeholder="All Statuses"
                    @update:model-value="onStatusChange"
                />
            </div>
        </div>

        <!-- Date range row and PerPage -->
        <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-zinc-400 font-medium">Date Range:</span>
                <div class="w-36">
                    <DatePicker
                        :model-value="fromDate"
                        placeholder="From Date"
                        @update:model-value="onFromDateChange"
                    />
                </div>
                <span class="text-zinc-400">to</span>
                <div class="w-36">
                    <DatePicker
                        :model-value="toDate"
                        placeholder="To Date"
                        @update:model-value="onToDateChange"
                    />
                </div>
                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 cursor-pointer"
                    @click="emit('reset')"
                >
                    Reset Filters
                </button>
            </div>

            <div class="flex items-center gap-2 text-zinc-500">
                <span>Showing {{ totalCount }} records</span>
                <PerPageSelector :model-value="perPage" :options="[10, 20, 50]" @update:model-value="onPerPageChange" />
            </div>
        </div>
    </div>
</template>
