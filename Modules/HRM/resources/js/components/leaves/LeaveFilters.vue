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
        leaveType?: string | null;
        status?: string | null;
        fromDate?: string | null;
        toDate?: string | null;
        staffMembers?: StaffOption[];
    }>(),
    {
        search: '',
        staffId: null,
        leaveType: null,
        status: null,
        fromDate: null,
        toDate: null,
        staffMembers: () => [],
    },
);

const emit = defineEmits<{
    (e: 'update:search', val: string): void;
    (e: 'update:staffId', val: number | string | null): void;
    (e: 'update:leaveType', val: string | null): void;
    (e: 'update:status', val: string | null): void;
    (e: 'update:fromDate', val: string | null): void;
    (e: 'update:toDate', val: string | null): void;
    (e: 'reset'): void;
}>();

const localSearch = ref(props.search);
const localStaff = ref(props.staffId ? String(props.staffId) : '');
const localType = ref(props.leaveType ?? '');
const localStatus = ref(props.status ?? '');
const localFrom = ref(props.fromDate ?? '');
const localTo = ref(props.toDate ?? '');

watch(() => props.search, (v) => { localSearch.value = v; });
watch(() => props.staffId, (v) => { localStaff.value = v ? String(v) : ''; });
watch(() => props.leaveType, (v) => { localType.value = v ?? ''; });
watch(() => props.status, (v) => { localStatus.value = v ?? ''; });
watch(() => props.fromDate, (v) => { localFrom.value = v ?? ''; });
watch(() => props.toDate, (v) => { localTo.value = v ?? ''; });

const leaveTypeOptions = [
    { value: '', label: 'All Leave Types' },
    { value: 'annual', label: 'Annual Leave' },
    { value: 'sick', label: 'Sick Leave' },
    { value: 'casual', label: 'Casual Leave' },
    { value: 'unpaid', label: 'Unpaid Leave' },
    { value: 'maternity', label: 'Maternity Leave' },
    { value: 'paternity', label: 'Paternity Leave' },
    { value: 'other', label: 'Other Leave' },
];

const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
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

const handleTypeChange = (val: string | number) => {
    localType.value = String(val);
    emit('update:leaveType', val ? String(val) : null);
};

const handleStatusChange = (val: string | number) => {
    localStatus.value = String(val);
    emit('update:status', val ? String(val) : null);
};

const handleFromDateChange = (val: string) => {
    localFrom.value = val;
    emit('update:fromDate', val || null);
};

const handleToDateChange = (val: string) => {
    localTo.value = val;
    emit('update:toDate', val || null);
};

const handleReset = () => {
    localSearch.value = '';
    localStaff.value = '';
    localType.value = '';
    localStatus.value = '';
    localFrom.value = '';
    localTo.value = '';
    emit('reset');
};
</script>

<template>
    <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <!-- Search Input -->
        <div class="lg:col-span-2">
            <SearchInput
                :model-value="localSearch"
                placeholder="Search employee, code, reason..."
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

        <!-- Type Filter -->
        <div>
            <Select
                :model-value="localType"
                :options="leaveTypeOptions"
                placeholder="All Leave Types"
                @update:model-value="handleTypeChange"
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

        <!-- Date Filter & Reset -->
        <div class="flex items-center gap-2">
            <div class="flex-1 min-w-0">
                <DatePicker
                    :model-value="localFrom"
                    placeholder="From Date"
                    @update:model-value="handleFromDateChange"
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
