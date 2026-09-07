<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show: boolean;
        title: string;
        subtitle?: string;
        width?: string;
    }>(),
    {
        subtitle: '',
        width: 'max-w-md',
    },
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        emit('close');
    }
};

watch(
    () => props.show,
    (val) => {
        if (val) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },
);

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                @click="emit('close')"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="show"
                :class="[
                    'fixed inset-y-0 right-0 z-50 flex w-full flex-col border-l border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900',
                    width,
                ]"
            >
                <!-- Drawer Header -->
                <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                            {{ title }}
                        </h2>
                        <p v-if="subtitle" class="mt-0.5 text-xs text-slate-500 dark:text-zinc-400">
                            {{ subtitle }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-zinc-100 hover:text-slate-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                        @click="emit('close')"
                    >
                        <span class="sr-only">Close drawer</span>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Drawer Content -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    <slot />
                </div>

                <!-- Drawer Footer Slot -->
                <div
                    v-if="$slots.footer"
                    class="border-t border-zinc-200 bg-zinc-50/70 px-6 py-3.5 dark:border-zinc-800 dark:bg-zinc-950/40"
                >
                    <slot name="footer" />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
