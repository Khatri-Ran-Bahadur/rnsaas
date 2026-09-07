<script setup lang="ts">
const props = defineProps<{
    currentStep: number;
    steps: Array<{
        step: number;
        title: string;
        description?: string;
    }>;
}>();

const emit = defineEmits<{
    (e: 'select-step', step: number): void;
}>();
</script>

<template>
    <div class="border-b border-zinc-200 bg-white px-4 py-3 dark:border-zinc-800 dark:bg-zinc-900 rounded-xl mb-6 shadow-xs">
        <nav aria-label="Progress">
            <ol class="flex items-center justify-between gap-2 overflow-x-auto">
                <li
                    v-for="s in steps"
                    :key="s.step"
                    class="flex items-center gap-3 shrink-0"
                >
                    <div class="flex items-center gap-2">
                        <!-- Step Circle -->
                        <span
                            :class="[
                                'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors',
                                currentStep > s.step
                                    ? 'bg-emerald-600 text-white'
                                    : currentStep === s.step
                                      ? 'bg-indigo-600 text-white shadow-xs'
                                      : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                            ]"
                        >
                            <svg
                                v-if="currentStep > s.step"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>{{ s.step }}</span>
                        </span>

                        <!-- Step Text -->
                        <div class="text-left">
                            <span
                                :class="[
                                    'block text-xs font-medium',
                                    currentStep === s.step
                                        ? 'text-indigo-600 dark:text-indigo-400 font-semibold'
                                        : currentStep > s.step
                                          ? 'text-slate-900 dark:text-zinc-200'
                                          : 'text-slate-500 dark:text-zinc-500',
                                ]"
                            >
                                {{ s.title }}
                            </span>
                        </div>
                    </div>

                    <!-- Chevron divider -->
                    <svg
                        v-if="s.step < steps.length"
                        class="h-4 w-4 text-slate-300 dark:text-zinc-700 shrink-0 ml-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </li>
            </ol>
        </nav>
    </div>
</template>
