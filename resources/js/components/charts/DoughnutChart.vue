<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import type { ChartData, ChartOptions } from 'chart.js';
import { ensureChartRegistered, getChartTheme } from './register';

ensureChartRegistered();

interface Props {
    data: ChartData<'doughnut'>;
    options?: ChartOptions<'doughnut'>;
    height?: number;
    currencyPrefix?: string;
    centerText?: string;
    centerSubtext?: string;
}

const props = withDefaults(defineProps<Props>(), {
    height: 240,
    currencyPrefix: '',
    centerText: '',
    centerSubtext: '',
});

const defaultOptions = computed<ChartOptions<'doughnut'>>(() => {
    const theme = getChartTheme();

    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: theme.textColor,
                    font: { size: 11, weight: 500 },
                    boxWidth: 10,
                    padding: 14,
                    usePointStyle: true,
                },
            },
            tooltip: {
                backgroundColor: theme.tooltipBg,
                titleColor: theme.tooltipText,
                bodyColor: theme.tooltipText,
                borderColor: theme.tooltipBorder,
                borderWidth: 1,
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: (context) => {
                        const val = context.raw as number;
                        const label = context.label || '';
                        const formatted = props.currencyPrefix
                            ? `${props.currencyPrefix} ${val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                            : val.toLocaleString();
                        return label ? ` ${label}: ${formatted}` : ` ${formatted}`;
                    },
                },
            },
        },
        ...props.options,
    };
});
</script>

<template>
    <div class="w-full relative flex items-center justify-center" :style="{ height: `${height}px` }">
        <Doughnut :data="data" :options="defaultOptions" />

        <!-- Center Label Overlay when centerText is present -->
        <div
            v-if="centerText"
            class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center pb-6"
        >
            <span class="text-lg font-black tracking-tight text-zinc-900 dark:text-white">
                {{ centerText }}
            </span>
            <span v-if="centerSubtext" class="text-[10px] uppercase font-semibold text-zinc-400 dark:text-zinc-500">
                {{ centerSubtext }}
            </span>
        </div>
    </div>
</template>
