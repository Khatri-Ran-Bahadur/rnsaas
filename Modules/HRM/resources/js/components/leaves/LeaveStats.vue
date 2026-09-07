<script setup lang="ts">
import HRMStatCard from '../common/HRMStatCard.vue';
import type { LeaveStats } from '../../types/leaves';

withDefaults(
    defineProps<{
        stats?: LeaveStats;
    }>(),
    {
        stats: () => ({
            pending: 0,
            approved: 0,
            rejected: 0,
            total_days: 0,
        }),
    },
);
</script>

<template>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-6">
        <HRMStatCard
            title="Pending Requests"
            :value="stats.pending"
            color="amber"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </template>
        </HRMStatCard>

        <HRMStatCard
            title="Approved"
            :value="stats.approved"
            color="emerald"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </template>
        </HRMStatCard>

        <HRMStatCard
            title="Rejected"
            :value="stats.rejected"
            color="rose"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </template>
        </HRMStatCard>

        <HRMStatCard
            title="Total Days Taken"
            :value="stats.total_days"
            color="indigo"
        >
            <template #icon>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </template>
        </HRMStatCard>
    </div>
</template>
