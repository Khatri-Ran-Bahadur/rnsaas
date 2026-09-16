<script setup lang="ts">
import Run from '../Mrp/Run.vue';

const props = defineProps<{
    shortages?: any[];
    plannedOrders?: any[];
    lastRunDate?: string;
    planSummary?: any;
    materialRequirements?: any[];
}>();
</script>

<template>
    <Run
        :shortages="props.shortages || []"
        :planned-orders="props.plannedOrders || []"
        :last-run-date="props.lastRunDate || 'Today, 11:30 AM'"
    />
</template>
