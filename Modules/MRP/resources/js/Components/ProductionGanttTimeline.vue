<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components';

interface TimelineEvent {
    id: number;
    title: string;
    work_center: string;
    start: string;
    end: string;
    status: string;
    priority: string;
    quantity: number;
    color?: string;
}

const props = defineProps<{
    events: TimelineEvent[];
}>();

const workCenters = computed(() => {
    return Array.from(new Set(props.events.map(e => e.work_center)));
});

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'completed': return 'success';
        case 'in progress': return 'primary';
        case 'urgent': return 'danger';
        case 'planned': return 'secondary';
        default: return 'secondary';
    }
};
</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Shop Floor Capacity & Schedule Timeline</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Live work center queue and allocation status</p>
            </div>
            <div class="flex items-center space-x-2 text-xs">
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span><span class="text-slate-600 dark:text-slate-400">In Progress</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span><span class="text-slate-600 dark:text-slate-400">Completed</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span><span class="text-slate-600 dark:text-slate-400">Urgent</span></span>
                <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span><span class="text-slate-600 dark:text-slate-400">Planned</span></span>
            </div>
        </div>

        <div class="space-y-4">
            <div
                v-for="wc in workCenters"
                :key="wc"
                class="border border-slate-100 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-800/40"
            >
                <div class="flex items-center justify-between mb-2.5">
                    <span class="font-semibold text-sm text-slate-800 dark:text-slate-200 flex items-center space-x-2">
                        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>{{ wc }}</span>
                    </span>
                    <span class="text-xs text-slate-500">
                        {{ props.events.filter(e => e.work_center === wc).length }} Active Job(s)
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div
                        v-for="item in props.events.filter(e => e.work_center === wc)"
                        :key="item.id"
                        class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm hover:border-slate-300 dark:hover:border-slate-600 transition-all"
                    >
                        <div class="flex items-start justify-between">
                            <span class="font-medium text-xs text-slate-900 dark:text-slate-100 line-clamp-1">
                                {{ item.title }}
                            </span>
                            <Badge :variant="getStatusBadgeVariant(item.status)" size="sm">
                                {{ item.status }}
                            </Badge>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Qty: <strong class="text-slate-700 dark:text-slate-200">{{ item.quantity }}</strong></span>
                            <span>{{ item.start }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
