<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    variances: {
        material_price_variance: string;
        material_usage_variance: string;
        labor_rate_variance: string;
        labor_efficiency_variance: string;
        machine_cost_variance: string;
        overhead_variance: string;
        scrap_cost_variance: string;
    };
}>();

const isUnfavorable = (val: string) => {
    return val.startsWith('+') && parseFloat(val) > 2.0;
};
const isFavorable = (val: string) => {
    return val.startsWith('-') || (val.startsWith('+') && parseFloat(val) <= 0);
};
</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h4 class="font-bold text-sm text-slate-900 dark:text-slate-100">7-Point Standard Cost Variance Matrix</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">Actual vs Standard manufacturing cost variance breakdown</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                Period: Current Month
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block truncate">Material Price</span>
                <span class="text-base font-bold mt-1 inline-block" :class="isUnfavorable(variances.material_price_variance) ? 'text-red-500' : 'text-emerald-500'">
                    {{ variances.material_price_variance }}
                </span>
            </div>

            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block truncate">Material Usage</span>
                <span class="text-base font-bold mt-1 inline-block" :class="isUnfavorable(variances.material_usage_variance) ? 'text-red-500' : 'text-emerald-500'">
                    {{ variances.material_usage_variance }}
                </span>
            </div>

            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block truncate">Labor Rate</span>
                <span class="text-base font-bold mt-1 inline-block text-slate-700 dark:text-slate-300">
                    {{ variances.labor_rate_variance }}
                </span>
            </div>

            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block truncate">Labor Efficiency</span>
                <span class="text-base font-bold mt-1 inline-block text-emerald-500">
                    {{ variances.labor_efficiency_variance }}
                </span>
            </div>

            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block truncate">Machine Cost</span>
                <span class="text-base font-bold mt-1 inline-block text-slate-700 dark:text-slate-300">
                    {{ variances.machine_cost_variance }}
                </span>
            </div>

            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block truncate">Overhead</span>
                <span class="text-base font-bold mt-1 inline-block text-slate-700 dark:text-slate-300">
                    {{ variances.overhead_variance }}
                </span>
            </div>

            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 text-center">
                <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400 block truncate">Scrap / Defect</span>
                <span class="text-base font-bold mt-1 inline-block" :class="isUnfavorable(variances.scrap_cost_variance) ? 'text-red-500' : 'text-emerald-500'">
                    {{ variances.scrap_cost_variance }}
                </span>
            </div>
        </div>
    </div>
</template>
