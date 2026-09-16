<script setup lang="ts">
import Create from './Create.vue';

const props = defineProps<{
    id?: number | string;
}>();
</script>

<template>
    <Create :id="props.id" :is_editing="true" />
</template>
