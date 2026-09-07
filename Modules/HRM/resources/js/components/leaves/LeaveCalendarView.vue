<script setup lang="ts">
import { ref, computed } from 'vue';
import type { LeaveItem } from '../../types/leaves';

const props = withDefaults(
    defineProps<{
        leaves?: LeaveItem[];
    }>(),
    {
        leaves: () => [],
    },
);

const emit = defineEmits<{
    (e: 'inspect', item: LeaveItem): void;
}>();

const currentYear = ref(new Date().getFullYear());
const currentMonth = ref(new Date().getMonth());

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const daysInMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
});

const firstDayWeekday = computed(() => {
    return new Date(currentYear.value, currentMonth.value, 1).getDay();
});

const prevMonth = () => {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
};

const nextMonth = () => {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
};

const getLeavesForDate = (day: number): LeaveItem[] => {
    const m = String(currentMonth.value + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    const dateStr = `${currentYear.value}-${m}-${d}`;
    return props.leaves.filter((l) => dateStr >= l.start_date && dateStr <= l.end_date);
};
</script>

<template>
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <!-- Calendar Header -->
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                {{ monthNames[currentMonth] }} {{ currentYear }}
            </h3>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-slate-500 hover:bg-zinc-100 hover:text-slate-700 dark:text-zinc-400 dark:hover:bg-zinc-800 transition-colors"
                    @click="prevMonth"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-slate-500 hover:bg-zinc-100 hover:text-slate-700 dark:text-zinc-400 dark:hover:bg-zinc-800 transition-colors"
                    @click="nextMonth"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Weekday Row -->
        <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-slate-500 dark:text-zinc-400 mb-1">
            <span>Sun</span>
            <span>Mon</span>
            <span>Tue</span>
            <span>Wed</span>
            <span>Thu</span>
            <span>Fri</span>
            <span>Sat</span>
        </div>

        <!-- Days Grid -->
        <div class="grid grid-cols-7 gap-1">
            <div
                v-for="offset in firstDayWeekday"
                :key="'offset-' + offset"
                class="min-h-[80px] rounded-lg bg-zinc-50/40 dark:bg-zinc-950/20"
            />

            <div
                v-for="day in daysInMonth"
                :key="day"
                class="min-h-[80px] rounded-lg border border-zinc-200/70 bg-white p-1.5 dark:border-zinc-800 dark:bg-zinc-900/60 flex flex-col justify-between"
            >
                <div class="text-xs font-medium text-slate-800 dark:text-zinc-200">
                    {{ day }}
                </div>

                <div class="mt-1 space-y-1">
                    <div
                        v-for="l in getLeavesForDate(day).slice(0, 2)"
                        :key="l.public_id"
                        :class="[
                            'truncate rounded px-1.5 py-0.5 text-[10px] font-semibold cursor-pointer transition-opacity hover:opacity-80',
                            l.status?.value === 'approved'
                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300'
                                : 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300',
                        ]"
                        @click="emit('inspect', l)"
                    >
                        {{ l.staff?.name || 'Staff' }} ({{ l.leave_type?.label }})
                    </div>
                    <span
                        v-if="getLeavesForDate(day).length > 2"
                        class="text-[9px] text-slate-400 block font-medium"
                    >
                        +{{ getLeavesForDate(day).length - 2 }} more
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
