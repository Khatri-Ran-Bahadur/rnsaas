<script setup lang="ts">
import SearchInput from '@/components/SearchInput.vue';
import Select from '@/components/Select.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';

const props = defineProps<{
    search: string;
    selectedActive: string;
    perPage: number;
    totalCount: number;
}>();

const emit = defineEmits<{
    (e: 'update:search', val: string): void;
    (e: 'update:selectedActive', val: string): void;
    (e: 'update:perPage', val: number): void;
    (e: 'filter'): void;
}>();

const activeOptions = [
    { label: 'All Statuses', value: '' },
    { label: 'Active Only', value: '1' },
    { label: 'Inactive Only', value: '0' },
];

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const onSearch = (val: string) => {
    emit('update:search', val);
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => emit('filter'), 350);
};

const onActiveChange = (val: string | number) => {
    emit('update:selectedActive', String(val ?? ''));
    emit('filter');
};

const onPerPageChange = (val: number) => {
    emit('update:perPage', val);
    emit('filter');
};
</script>

<template>
    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
            <div class="w-full sm:max-w-xs">
                <SearchInput :model-value="search" placeholder="Search by name or code..." @update:model-value="onSearch" />
            </div>
            <div class="w-full sm:w-44">
                <Select
                    :model-value="selectedActive"
                    :options="activeOptions"
                    placeholder="All Statuses"
                    @update:model-value="onActiveChange"
                />
            </div>
        </div>

        <div class="flex items-center gap-2 text-xs text-zinc-500">
            <span>Showing {{ totalCount }} shifts</span>
            <PerPageSelector :model-value="perPage" :options="[10, 20, 50]" @update:model-value="onPerPageChange" />
        </div>
    </div>
</template>
