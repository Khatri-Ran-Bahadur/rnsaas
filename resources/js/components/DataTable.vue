<script setup lang="ts" generic="T extends Record<string, any>">
import { ref, computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Dropdown from '@/components/Dropdown.vue';
import ListGridToggle from '@/components/ListGridToggle.vue';
import PerPageSelector from '@/components/PerPageSelector.vue';
import type { PaginatedData } from '@/types/tenancy';

export interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    align?: 'left' | 'center' | 'right';
    width?: string;
    class?: string;
}

const props = withDefaults(
    defineProps<{
        columns: TableColumn[];
        data: T[] | PaginatedData<T>;
        loading?: boolean;
        searchable?: boolean;
        searchPlaceholder?: string;
        searchValue?: string;
        filterable?: boolean;
        perPageOptions?: number[];
        currentPerPage?: number;
        sortBy?: string;
        sortOrder?: 'asc' | 'desc';
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        loading: false,
        searchable: true,
        searchPlaceholder: 'Search records...',
        searchValue: '',
        filterable: false,
        perPageOptions: () => [10, 25, 50, 100],
        currentPerPage: 10,
        sortBy: '',
        sortOrder: 'asc',
        emptyTitle: 'No records found',
        emptyDescription: 'Try adjusting your search query or filters to find what you are looking for.',
    }
);

const emit = defineEmits<{
    (e: 'search', query: string): void;
    (e: 'sort', column: string, order: 'asc' | 'desc'): void;
    (e: 'perPageChange', perPage: number): void;
    (e: 'pageChange', page: number): void;
}>();

const internalSearch = ref(props.searchValue);
const viewMode = ref<'list' | 'grid'>('list');

const isPaginated = computed(() => {
    return props.data && typeof props.data === 'object' && 'data' in props.data;
});

const items = computed<T[]>(() => {
    if (isPaginated.value) {
        return (props.data as PaginatedData<T>).data;
    }
    return (props.data as T[]) || [];
});

const paginationMeta = computed(() => {
    if (isPaginated.value) {
        const paginated = props.data as PaginatedData<T>;
        return {
            from: paginated.from ?? (paginated.total > 0 ? 1 : 0),
            to: paginated.to ?? paginated.data.length,
            total: paginated.total ?? paginated.data.length,
            currentPage: paginated.current_page ?? 1,
            lastPage: paginated.last_page ?? 1,
            links: paginated.links ?? [],
        };
    }
    return {
        from: items.value.length > 0 ? 1 : 0,
        to: items.value.length,
        total: items.value.length,
        currentPage: 1,
        lastPage: 1,
        links: [],
    };
});

const handleSearch = () => {
    emit('search', internalSearch.value);
};

const handleSort = (columnKey: string) => {
    const nextOrder = props.sortBy === columnKey && props.sortOrder === 'asc' ? 'desc' : 'asc';
    emit('sort', columnKey, nextOrder);
};

const selectPerPage = (perPage: number) => {
    emit('perPageChange', perPage);
};

const getAlignmentClass = (align?: 'left' | 'center' | 'right') => {
    switch (align) {
        case 'center':
            return 'text-center';
        case 'right':
            return 'text-right justify-end';
        case 'left':
        default:
            return 'text-left';
    }
};
</script>

<template>
    <div class="space-y-4">
        <!-- Top Toolbar -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <!-- Search Bar -->
            <div class="flex flex-1 items-center gap-2 max-w-lg">
                <div v-if="searchable" class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="internalSearch"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full rounded-lg border border-zinc-200/90 bg-white py-2 pl-9 pr-4 text-sm text-zinc-900 placeholder-zinc-400 transition-all duration-150 shadow-2xs hover:border-zinc-300 focus:border-primary-500 focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 dark:border-zinc-800 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500 dark:hover:border-zinc-700 dark:focus:border-primary-500"
                        @keyup.enter="handleSearch"
                    />
                </div>

                <button
                    v-if="searchable"
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-xs shadow-primary-500/25 transition-all duration-150 hover:bg-primary-700 active:scale-[0.98]"
                    @click="handleSearch"
                >
                    Search
                </button>

                <slot name="search-addons" />
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-2 self-end sm:self-auto">
                <!-- Layout Mode Toggle -->
                <ListGridToggle v-model="viewMode" />

                <!-- Per Page Selector -->
                <PerPageSelector
                    :model-value="currentPerPage"
                    :options="perPageOptions"
                    @change="selectPerPage"
                />

                <!-- Filters -->
                <slot name="filters">
                    <button
                        v-if="filterable"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200/90 bg-white px-3 py-2 text-xs font-medium text-zinc-700 shadow-2xs transition-colors hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        <svg class="h-3.5 w-3.5 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>Filters</span>
                    </button>
                </slot>

                <slot name="actions" />
            </div>
        </div>

        <!-- Modern Table Card Container -->
        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-[0_1px_3px_0_rgba(0,0,0,0.04),0_1px_2px_-1px_rgba(0,0,0,0.04)] dark:border-zinc-800/80 dark:bg-zinc-900 dark:shadow-none">
            <!-- Loading State -->
            <div v-if="loading" class="p-8 space-y-4">
                <div v-for="i in 5" :key="i" class="flex items-center gap-4 animate-pulse">
                    <div class="h-10 w-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 shrink-0" />
                    <div class="flex-1 space-y-2">
                        <div class="h-4 w-1/4 rounded bg-zinc-200 dark:bg-zinc-800" />
                        <div class="h-3 w-1/2 rounded bg-zinc-100 dark:bg-zinc-800/60" />
                    </div>
                    <div class="h-6 w-16 rounded-full bg-zinc-200 dark:bg-zinc-800" />
                </div>
            </div>

            <!-- Grid View (when viewMode === 'grid') -->
            <div v-else-if="items.length > 0 && viewMode === 'grid'" class="p-6 bg-zinc-50/30 dark:bg-zinc-950/30">
                <slot name="grid" :items="items">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        <div
                            v-for="(item, idx) in items"
                            :key="item.id ?? idx"
                            class="flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-2xs hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 transition-all"
                        >
                            <slot name="grid-card" :item="item" :index="idx">
                                <div>
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 font-bold text-sm text-zinc-800 uppercase dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200/80 dark:border-zinc-700">
                                            {{ (item.name || item.title || 'D').substring(0, 2) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-sm text-zinc-900 dark:text-white truncate">
                                                {{ item.name || item.title || `Item #${item.id}` }}
                                            </h3>
                                            <p v-if="item.slug || item.public_id || item.email" class="text-xs text-zinc-400 truncate font-mono">
                                                {{ item.slug || item.public_id || item.email }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-300">
                                        <div v-for="col in columns.filter(c => c.key !== 'actions' && c.key !== columns[0]?.key).slice(0, 3)" :key="col.key">
                                            <span class="text-zinc-400 dark:text-zinc-500 font-medium text-[11px] block">{{ col.label }}</span>
                                            <slot :name="`cell(${col.key})`" :item="item" :value="item[col.key]">
                                                <span>{{ item[col.key] ?? '—' }}</span>
                                            </slot>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="$slots['cell(actions)']" class="mt-4 pt-3 border-t border-dashed border-zinc-200 dark:border-zinc-800 flex items-center justify-end">
                                    <slot name="cell(actions)" :item="item" />
                                </div>
                            </slot>
                        </div>
                    </div>
                </slot>
            </div>

            <!-- Table View (when viewMode === 'list') -->
            <div v-else-if="items.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-300">
                    <thead class="border-b border-zinc-100 bg-zinc-50/60 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800/80 dark:bg-zinc-900/60 dark:text-zinc-400">
                        <tr>
                            <th
                                v-for="col in columns"
                                :key="col.key"
                                scope="col"
                                :class="[
                                    'px-6 py-3.5 select-none',
                                    col.class || '',
                                    col.sortable ? 'cursor-pointer hover:text-zinc-900 dark:hover:text-white transition-colors' : '',
                                    getAlignmentClass(col.align),
                                ]"
                                :style="col.width ? { width: col.width } : undefined"
                                @click="col.sortable ? handleSort(col.key) : undefined"
                            >
                                <div class="inline-flex items-center gap-1.5">
                                    <span>{{ col.label }}</span>
                                    <span v-if="col.sortable" class="inline-flex flex-col text-zinc-400 dark:text-zinc-500">
                                        <svg
                                            v-if="sortBy === col.key && sortOrder === 'asc'"
                                            class="h-3.5 w-3.5 text-zinc-900 dark:text-white"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                        </svg>
                                        <svg
                                            v-else-if="sortBy === col.key && sortOrder === 'desc'"
                                            class="h-3.5 w-3.5 text-zinc-900 dark:text-white"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg
                                            v-else
                                            class="h-3 w-3 opacity-60"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                        </svg>
                                    </span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
                        <tr
                            v-for="(item, idx) in items"
                            :key="item.id ?? idx"
                            class="transition-colors duration-150 hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40"
                        >
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                :class="[
                                    'px-6 py-4',
                                    col.class || '',
                                    getAlignmentClass(col.align),
                                ]"
                            >
                                <slot
                                    :name="`cell(${col.key})`"
                                    :item="item"
                                    :value="item[col.key]"
                                    :index="idx"
                                >
                                    {{ item[col.key] ?? '—' }}
                                </slot>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-else class="p-8">
                <slot name="empty">
                    <EmptyState
                        :title="emptyTitle"
                        :description="emptyDescription"
                    >
                        <template #actions>
                            <slot name="empty-actions" />
                        </template>
                    </EmptyState>
                </slot>
            </div>

            <!-- Pagination Footer -->
            <div
                v-if="paginationMeta.total > 0"
                class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-zinc-100 bg-white px-6 py-4 dark:border-zinc-800/80 dark:bg-zinc-900"
            >
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Showing
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ paginationMeta.from }}</span>
                    to
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ paginationMeta.to }}</span>
                    of
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ paginationMeta.total }}</span>
                    results
                </p>

                <!-- Navigation Controls -->
                <slot name="pagination">
                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            :disabled="paginationMeta.currentPage <= 1"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-200/90 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 disabled:opacity-40 disabled:cursor-not-allowed dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            @click="emit('pageChange', paginationMeta.currentPage - 1)"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Previous</span>
                        </button>

                        <button
                            v-for="page in paginationMeta.lastPage"
                            :key="page"
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                paginationMeta.currentPage === page
                                    ? 'bg-primary-600 text-white font-bold shadow-xs shadow-primary-500/25'
                                    : 'border border-zinc-200/90 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800',
                            ]"
                            @click="emit('pageChange', page)"
                        >
                            {{ page }}
                        </button>

                        <button
                            type="button"
                            :disabled="paginationMeta.currentPage >= paginationMeta.lastPage"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-200/90 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 disabled:opacity-40 disabled:cursor-not-allowed dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            @click="emit('pageChange', paginationMeta.currentPage + 1)"
                        >
                            <span>Next</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </slot>
            </div>
        </div>
    </div>
</template>
