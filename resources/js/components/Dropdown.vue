<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = withDefaults(
    defineProps<{
        align?: 'left' | 'right';
        width?: string;
        teleport?: boolean;
    }>(),
    {
        align: 'right',
        width: 'w-48',
        teleport: true,
    }
);

const isOpen = ref(false);
const triggerRef = ref<HTMLElement | null>(null);
const menuRef = ref<HTMLElement | null>(null);

const menuStyle = ref<{
    top?: string;
    left?: string;
    right?: string;
    bottom?: string;
}>({});

const close = () => {
    isOpen.value = false;
};

const updatePosition = () => {
    if (!props.teleport || !triggerRef.value) return;

    const rect = triggerRef.value.getBoundingClientRect();
    const menuEl = menuRef.value;
    const menuWidth = menuEl?.offsetWidth || 192; // default fallback 48 * 4 = 192px
    const menuHeight = menuEl?.offsetHeight || 160;

    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    // Determine vertical placement: flip upwards if not enough space below
    const openUpwards = spaceBelow < menuHeight + 16 && spaceAbove > menuHeight + 16;

    let top: number;
    if (openUpwards) {
        top = Math.max(8, rect.top - menuHeight - 6);
    } else {
        top = Math.min(window.innerHeight - menuHeight - 8, rect.bottom + 6);
    }

    // Determine horizontal placement
    let left: number;
    if (props.align === 'right') {
        left = rect.right - menuWidth;
    } else {
        left = rect.left;
    }

    // Boundary constraints within viewport
    left = Math.max(8, Math.min(left, window.innerWidth - menuWidth - 8));

    menuStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
    };
};

const toggle = async () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value && props.teleport) {
        await nextTick();
        updatePosition();
        // Recalculate once menu is rendered to get exact offsetHeight
        requestAnimationFrame(() => {
            updatePosition();
        });
    }
};

const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as Node;
    if (triggerRef.value && triggerRef.value.contains(target)) {
        return;
    }
    if (menuRef.value && menuRef.value.contains(target)) {
        return;
    }
    close();
};

const handleScrollOrResize = () => {
    if (isOpen.value) {
        updatePosition();
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
    window.addEventListener('resize', handleScrollOrResize);
    window.addEventListener('scroll', handleScrollOrResize, true);
});

onBeforeUnmount(() => {
    window.removeEventListener('click', handleClickOutside);
    window.removeEventListener('resize', handleScrollOrResize);
    window.removeEventListener('scroll', handleScrollOrResize, true);
});
</script>

<template>
    <div class="relative inline-block text-left">
        <!-- Trigger Slot -->
        <div ref="triggerRef" @click.stop="toggle">
            <slot name="trigger" :is-open="isOpen" :toggle="toggle" />
        </div>

        <!-- Dropdown Menu Content (Teleported to body when teleport is true to avoid table clipping) -->
        <template v-if="teleport">
            <Teleport to="body">
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
                        ref="menuRef"
                        :style="menuStyle"
                        :class="[
                            'fixed z-[9999] rounded-xl border border-zinc-200 bg-white p-1.5 shadow-xl focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-900',
                            width,
                        ]"
                        @click="close"
                    >
                        <slot :close="close" />
                    </div>
                </Transition>
            </Teleport>
        </template>

        <!-- Inline Dropdown Menu Content (when teleport is false) -->
        <template v-else>
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
                    ref="menuRef"
                    :class="[
                        'absolute z-50 mt-2 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-xl focus:outline-hidden dark:border-zinc-800 dark:bg-zinc-900',
                        width,
                        align === 'right' ? 'right-0' : 'left-0',
                    ]"
                    @click="close"
                >
                    <slot :close="close" />
                </div>
            </Transition>
        </template>
    </div>
</template>
