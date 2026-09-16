<script setup lang="ts">
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import type { ChartData, ChartOptions } from 'chart.js';
import { ensureChartRegistered, getChartTheme } from './register';

ensureChartRegistered();

interface Props {
    data: ChartData<'line'>;
    options?: ChartOptions<'line'>;
    height?: number;
    currencyPrefix?: string;
}

const props = withDefaults(defineProps<Props>(), {
    height: 280,
    currencyPrefix: '',
});

const defaultOptions = computed<ChartOptions<'line'>>(() => {
    const theme = getChartTheme();

    return {
        responsive: true,
        maintainAspectRatio: false,
        elements: {
            line: {
                tension: 0.35,
            },
            point: {
                radius: 3,
                hoverRadius: 6,
            },
        },
        plugins: {
            legend: {
                display: (props.data.datasets?.length || 0) > 1,
                position: 'top',
                labels: {
                    color: theme.textColor,
                    font: { size: 11, weight: 500 },
                    boxWidth: 12,
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
                        const label = context.dataset.label || '';
                        const formatted = props.currencyPrefix
                            ? `${props.currencyPrefix} ${val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                            : val.toLocaleString();
                        return label ? `${label}: ${formatted}` : formatted;
                    },
                },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: {
                    color: theme.textColor,
                    font: { size: 11 },
                },
            },
            y: {
                grid: {
                    color: theme.gridColor,
                },
                ticks: {
                    color: theme.textColor,
                    font: { size: 11 },
                    callback: (val) => {
                        const num = Number(val);
                        if (props.currencyPrefix) {
                            if (num >= 1000000) return `${props.currencyPrefix}${(num / 1000000).toFixed(1)}M`;
                            if (num >= 1000) return `${props.currencyPrefix}${(num / 1000).toFixed(0)}k`;
                            return `${props.currencyPrefix}${num}`;
                        }
                        if (num >= 1000) return `${(num / 1000).toFixed(0)}k`;
                        return num;
                    },
                },
            },
        },
        ...props.options,
    };
});
</script>

<template>
    <div class="w-full relative" :style="{ height: `${height}px` }">
        <Line :data="data" :options="defaultOptions" />
    </div>
</template>
