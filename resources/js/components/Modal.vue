<script setup lang="ts">
import { onMounted, onBeforeUnmount, watch, computed } from 'vue';

const props = withDefaults(
    defineProps<{
        show: boolean;
        title?: string;
        description?: string;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl';
    }>(),
    {
        show: false,
        title: undefined,
        description: undefined,
        maxWidth: 'md',
    }
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const close = () => {
    emit('close');
};

const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (typeof document !== 'undefined') {
            if (isOpen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
    }
);

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

const maxWidthClass = computed(() => {
    switch (props.maxWidth) {
        case 'sm':
            return 'sm:max-w-sm';
        case 'md':
            return 'sm:max-w-md';
        case 'lg':
            return 'sm:max-w-lg';
        case 'xl':
            return 'sm:max-w-xl';
        case '2xl':
            return 'sm:max-w-2xl';
        case '3xl':
            return 'sm:max-w-3xl';
        case '4xl':
            return 'sm:max-w-4xl';
        case '5xl':
            return 'sm:max-w-5xl';
        default:
            return 'sm:max-w-md';
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 sm:p-6"
            >
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs transition-opacity"
                    @click="close"
                />

                <!-- Dialog Card -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div
                        v-if="show"
                        :class="[
                            'relative w-full rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-2xl transition-all dark:border-zinc-800/80 dark:bg-zinc-900',
                            maxWidthClass,
                        ]"
                        @click.stop
                    >
                        <!-- Header -->
                        <div class="flex items-start justify-between pb-4 border-b border-zinc-100 dark:border-zinc-800">
                            <div>
                                <h3 v-if="title" class="text-lg font-semibold text-zinc-900 dark:text-white">
                                    {{ title }}
                                </h3>
                                <p v-if="description" class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ description }}
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300 transition-colors"
                                @click="close"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Content Body -->
                        <div class="py-4">
                            <slot />
                        </div>

                        <!-- Footer Actions -->
                        <div v-if="$slots.footer" class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
