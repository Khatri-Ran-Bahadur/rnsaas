<script setup lang="ts">
import { ref, watch } from 'vue';
import SearchInput from '@/components/SearchInput.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';

interface StaffOption {
    id: number;
    public_id: string;
    name: string;
    employee_code: string;
}

const props = withDefaults(
    defineProps<{
        search?: string;
        staffId?: number | string | null;
        status?: string | null;
        source?: string | null;
        date?: string | null;
        staffMembers?: StaffOption[];
    }>(),
    {
        search: '',
        staffId: null,
        status: null,
        source: null,
        date: null,
        staffMembers: () => [],
    },
);

const emit = defineEmits<{
    (e: 'update:search', val: string): void;
    (e: 'update:staffId', val: number | string | null): void;
    (e: 'update:status', val: string | null): void;
    (e: 'update:source', val: string | null): void;
    (e: 'update:date', val: string | null): void;
    (e: 'reset'): void;
}>();

const localSearch = ref(props.search);
const localStaff = ref(props.staffId ? String(props.staffId) : '');
const localStatus = ref(props.status ?? '');
const localSource = ref(props.source ?? '');
const localDate = ref(props.date ?? '');

watch(() => props.search, (val) => { localSearch.value = val; });
watch(() => props.staffId, (val) => { localStaff.value = val ? String(val) : ''; });
watch(() => props.status, (val) => { localStatus.value = val ?? ''; });
watch(() => props.source, (val) => { localSource.value = val ?? ''; });
watch(() => props.date, (val) => { localDate.value = val ?? ''; });

const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'present', label: 'Present' },
    { value: 'absent', label: 'Absent' },
    { value: 'late', label: 'Late' },
    { value: 'half_day', label: 'Half Day' },
    { value: 'on_leave', label: 'On Leave' },
    { value: 'holiday', label: 'Holiday' },
    { value: 'week_off', label: 'Week Off' },
];

const sourceOptions = [
    { value: '', label: 'All Sources' },
    { value: 'manual', label: 'Manual' },
    { value: 'web', label: 'Web' },
    { value: 'mobile', label: 'Mobile' },
    { value: 'biometric', label: 'Biometric' },
    { value: 'import', label: 'Import' },
];

const staffSelectOptions = [
    { value: '', label: 'All Staff' },
    ...props.staffMembers.map((s) => ({
        value: String(s.id),
        label: `${s.name} (${s.employee_code})`,
    })),
];

const handleSearch = (val: string) => {
    localSearch.value = val;
    emit('update:search', val);
};

const handleStaffChange = (val: string | number) => {
    localStaff.value = String(val);
    emit('update:staffId', val ? Number(val) : null);
};

const handleStatusChange = (val: string | number) => {
    localStatus.value = String(val);
    emit('update:status', val ? String(val) : null);
};

const handleSourceChange = (val: string | number) => {
    localSource.value = String(val);
    emit('update:source', val ? String(val) : null);
};

const handleDateChange = (val: string) => {
    localDate.value = val;
    emit('update:date', val || null);
};

const handleReset = () => {
    localSearch.value = '';
    localStaff.value = '';
    localStatus.value = '';
    localSource.value = '';
    localDate.value = '';
    emit('reset');
};
</script>

<template>
    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <!-- Search Input -->
        <div class="lg:col-span-2">
            <SearchInput
                :model-value="localSearch"
                placeholder="Search staff, code, notes..."
                @update:model-value="handleSearch"
            />
        </div>

        <!-- Staff Filter -->
        <div>
            <Select
                :model-value="localStaff"
                :options="staffSelectOptions"
                placeholder="All Staff"
                @update:model-value="handleStaffChange"
            />
        </div>

        <!-- Status Filter -->
        <div>
            <Select
                :model-value="localStatus"
                :options="statusOptions"
                placeholder="All Statuses"
                @update:model-value="handleStatusChange"
            />
        </div>

        <!-- Source Filter -->
        <div>
            <Select
                :model-value="localSource"
                :options="sourceOptions"
                placeholder="All Sources"
                @update:model-value="handleSourceChange"
            />
        </div>

        <!-- Date Picker & Reset -->
        <div class="flex items-center gap-2">
            <div class="flex-1 min-w-0">
                <DatePicker
                    :model-value="localDate"
                    placeholder="Pick Date"
                    @update:model-value="handleDateChange"
                />
            </div>

            <button
                type="button"
                class="rounded-lg border border-zinc-200 bg-white px-2.5 py-2 text-xs font-medium text-slate-600 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 shrink-0 transition-colors"
                title="Reset Filters"
                @click="handleReset"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>
</template>
