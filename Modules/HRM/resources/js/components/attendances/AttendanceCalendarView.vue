<script setup lang="ts">
import { ref, computed } from 'vue';
import type { AttendanceItem } from '../../types/attendances';

const props = withDefaults(
    defineProps<{
        attendances?: AttendanceItem[];
    }>(),
    {
        attendances: () => [],
    },
);

const emit = defineEmits<{
    (e: 'select-day', date: string, items: AttendanceItem[]): void;
}>();

const currentYear = ref(new Date().getFullYear());
const currentMonth = ref(new Date().getMonth()); // 0-indexed

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const daysInMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
});

const firstDayWeekday = computed(() => {
    return new Date(currentYear.value, currentMonth.value, 1).getDay(); // 0 = Sun
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

const getRecordsForDate = (day: number): AttendanceItem[] => {
    const m = String(currentMonth.value + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    const dateStr = `${currentYear.value}-${m}-${d}`;
    return props.attendances.filter((a) => a.attendance_date === dateStr);
};

const getStatusAbbr = (statusVal: string): { label: string; class: string } => {
    switch (statusVal) {
        case 'present': return { label: 'P', class: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300' };
        case 'absent': return { label: 'A', class: 'bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300' };
        case 'late': return { label: 'L', class: 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300' };
        case 'half_day': return { label: 'HD', class: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300' };
        case 'on_leave': return { label: 'LV', class: 'bg-purple-100 text-purple-800 dark:bg-purple-950/70 dark:text-purple-300' };
        case 'holiday': return { label: 'H', class: 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300' };
        case 'week_off': return { label: 'W', class: 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' };
        default: return { label: '—', class: 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' };
    }
};

const handleDayClick = (day: number) => {
    const m = String(currentMonth.value + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    const dateStr = `${currentYear.value}-${m}-${d}`;
    emit('select-day', dateStr, getRecordsForDate(day));
};
</script>

<template>
    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <!-- Calendar Controls -->
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                    {{ monthNames[currentMonth] }} {{ currentYear }}
                </h3>
            </div>

            <!-- Month navigation -->
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

        <!-- Legend -->
        <div class="mb-4 flex flex-wrap items-center gap-2 border-b border-zinc-200/80 pb-3 text-[11px] text-slate-600 dark:border-zinc-800 dark:text-zinc-400">
            <span class="font-medium text-slate-700 dark:text-zinc-300">Legend:</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500" /> P: Present</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500" /> A: Absent</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500" /> L: Late</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-indigo-500" /> HD: Half Day</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-purple-500" /> LV: Leave</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-sky-500" /> H: Holiday</span>
            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-zinc-400" /> W: Week Off</span>
        </div>

        <!-- Calendar Weekday Header -->
        <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-slate-500 dark:text-zinc-400 mb-1">
            <span>Sun</span>
            <span>Mon</span>
            <span>Tue</span>
            <span>Wed</span>
            <span>Thu</span>
            <span>Fri</span>
            <span>Sat</span>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-1">
            <!-- Empty offset days -->
            <div
                v-for="offset in firstDayWeekday"
                :key="'offset-' + offset"
                class="min-h-[72px] rounded-lg bg-zinc-50/40 dark:bg-zinc-950/20"
            />

            <!-- Month days -->
            <div
                v-for="day in daysInMonth"
                :key="day"
                class="group min-h-[72px] rounded-lg border border-zinc-200/70 bg-white p-1.5 transition-all hover:border-indigo-300 hover:shadow-xs dark:border-zinc-800 dark:bg-zinc-900/60 dark:hover:border-indigo-800 cursor-pointer flex flex-col justify-between"
                @click="handleDayClick(day)"
            >
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-slate-800 dark:text-zinc-200">
                        {{ day }}
                    </span>
                    <span
                        v-if="getRecordsForDate(day).length > 0"
                        class="text-[10px] text-slate-400"
                    >
                        {{ getRecordsForDate(day).length }} rec
                    </span>
                </div>

                <!-- Status Pills Preview -->
                <div class="mt-1 flex flex-wrap gap-1">
                    <template v-for="rec in getRecordsForDate(day).slice(0, 3)" :key="rec.public_id">
                        <span
                            :class="[
                                'inline-flex items-center justify-center rounded px-1 text-[9px] font-bold',
                                getStatusAbbr(rec.status?.value).class,
                            ]"
                            :title="`${rec.staff?.name || 'Staff'}: ${rec.status?.label}`"
                        >
                            {{ getStatusAbbr(rec.status?.value).label }}
                        </span>
                    </template>
                    <span
                        v-if="getRecordsForDate(day).length > 3"
                        class="text-[9px] text-slate-400 font-medium self-center"
                    >
                        +{{ getRecordsForDate(day).length - 3 }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
