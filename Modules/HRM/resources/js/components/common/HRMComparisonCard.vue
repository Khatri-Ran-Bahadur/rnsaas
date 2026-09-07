<script setup lang="ts">
interface RecordSide {
    checkIn: string | null;
    checkOut: string | null;
    workedHours: string | number;
}

const props = defineProps<{
    current: RecordSide;
    requested: RecordSide;
}>();

const isCheckInChanged = () => props.current.checkIn !== props.requested.checkIn;
const isCheckOutChanged = () => props.current.checkOut !== props.requested.checkOut;
const isWorkedChanged = () => props.current.workedHours !== props.requested.workedHours;
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <!-- Current Record Card -->
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-800 dark:bg-zinc-900/50">
            <div class="flex items-center gap-2 mb-3">
                <span class="h-2 w-2 rounded-full bg-slate-400" />
                <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                    Current Record
                </h4>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between py-1 border-b border-zinc-200/60 dark:border-zinc-800/60">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Check In</span>
                    <span class="font-medium text-slate-900 dark:text-white">
                        {{ current.checkIn || '—' }}
                    </span>
                </div>

                <div class="flex items-center justify-between py-1 border-b border-zinc-200/60 dark:border-zinc-800/60">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Check Out</span>
                    <span class="font-medium text-slate-900 dark:text-white">
                        {{ current.checkOut || '—' }}
                    </span>
                </div>

                <div class="flex items-center justify-between py-1">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Worked Hours</span>
                    <span class="font-medium text-slate-900 dark:text-white">
                        {{ current.workedHours }}h
                    </span>
                </div>
            </div>
        </div>

        <!-- Requested Change Card -->
        <div class="rounded-xl border border-indigo-200 bg-indigo-50/30 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/20">
            <div class="flex items-center gap-2 mb-3">
                <span class="h-2 w-2 rounded-full bg-indigo-500" />
                <h4 class="text-xs font-semibold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">
                    Requested Change
                </h4>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between py-1 border-b border-indigo-100/80 dark:border-indigo-900/40">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Check In</span>
                    <div class="flex items-center gap-1.5 font-medium">
                        <span
                            :class="[
                                isCheckInChanged()
                                    ? 'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-200 px-1.5 py-0.5 rounded'
                                    : 'text-slate-900 dark:text-white',
                            ]"
                        >
                            {{ requested.checkIn || '—' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between py-1 border-b border-indigo-100/80 dark:border-indigo-900/40">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Check Out</span>
                    <div class="flex items-center gap-1.5 font-medium">
                        <span
                            :class="[
                                isCheckOutChanged()
                                    ? 'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-200 px-1.5 py-0.5 rounded'
                                    : 'text-slate-900 dark:text-white',
                            ]"
                        >
                            {{ requested.checkOut || '—' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between py-1">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Worked Hours</span>
                    <span
                        :class="[
                            'font-medium',
                            isWorkedChanged()
                                ? 'bg-indigo-100 text-indigo-900 dark:bg-indigo-900/50 dark:text-indigo-200 px-1.5 py-0.5 rounded'
                                : 'text-slate-900 dark:text-white',
                        ]"
                    >
                        {{ requested.workedHours }}h
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
