<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = withDefaults(
    defineProps<{
        align?: 'left' | 'right';
        width?: string;
    }>(),
    {
        align: 'right',
        width: 'w-48',
    }
);

const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const close = () => {
    isOpen.value = false;
};

const toggle = () => {
    isOpen.value = !isOpen.value;
};

const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        close();
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    window.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block text-left">
        <!-- Trigger Slot -->
        <div @click.stop="toggle">
            <slot name="trigger" :is-open="isOpen" :toggle="toggle" />
        </div>

        <!-- Dropdown Menu Content -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                :class="[
                    'absolute z-50 mt-2 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-lg focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-900',
                    width,
                    align === 'right' ? 'right-0' : 'left-0',
                ]"
                @click="close"
            >
                <slot :close="close" />
            </div>
        </Transition>
    </div>
</template>
