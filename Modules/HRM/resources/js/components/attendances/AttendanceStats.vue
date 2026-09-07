<script setup lang="ts">
import HRMStatCard from '../common/HRMStatCard.vue';
import type { AttendanceStats } from '../../types/attendances';

withDefaults(
    defineProps<{
        stats?: AttendanceStats;
    }>(),
    {
        stats: () => ({
            present: 0,
            absent: 0,
            late: 0,
            half_day: 0,
            on_leave: 0,
            holiday: 0,
            week_off: 0,
            total_hours: 0,
        }),
    },
);
</script>

<template>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7 mb-6">
        <HRMStatCard
            title="Present"
            :value="stats.present"
            color="emerald"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </template>
        </HRMStatCard>

        <HRMStatCard
            title="Absent"
            :value="stats.absent"
            color="rose"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </template>
        </HRMStatCard>

        <HRMStatCard
            title="Late"
            :value="stats.late"
            color="amber"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </template>
        </HRMStatCard>

        <HRMStatCard
            title="Half Day"
            :value="stats.half_day"
            color="indigo"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </template>
        </HRMStatCard>

        <HRMStatCard
            title="On Leave"
            :value="stats.on_leave"
            color="purple"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </template>
        </HRMStatCard>

        <HRMStatCard
            title="Holiday"
            :value="stats.holiday"
            color="sky"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
            </template>
        </HRMStatCard>

        <HRMStatCard
            title="Week Off"
            :value="stats.week_off"
            color="indigo"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </template>
        </HRMStatCard>
    </div>
</template>
