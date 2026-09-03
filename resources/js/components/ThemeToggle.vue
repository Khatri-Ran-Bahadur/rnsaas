<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useTheme, AVAILABLE_COLOR_THEMES, type ThemeMode, type ColorTheme } from '@/composables/useTheme';

const { theme, colorTheme, isDark, setTheme, setColorTheme } = useTheme();
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const activeThemeMeta = computed(() => {
    return (
        AVAILABLE_COLOR_THEMES.find((t) => t.id === colorTheme.value) ??
        AVAILABLE_COLOR_THEMES[0]
    );
});

const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isOpen.value = false;
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
    <div ref="dropdownRef" class="relative">
        <!-- Modern Theme Trigger Button -->
        <button
            type="button"
            class="flex h-9 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2.5 text-zinc-600 transition-all hover:bg-zinc-50 hover:text-zinc-900 hover:border-zinc-300 focus:outline-hidden focus:ring-2 focus:ring-primary-500/30 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 shadow-2xs"
            :title="`Theme: ${activeThemeMeta.label} (${theme}). Click to customize.`"
            @click.stop="isOpen = !isOpen"
        >
            <!-- Current Accent Color Swatch Pill -->
            <span
                class="flex h-3 w-3 rounded-full ring-2 ring-white dark:ring-zinc-900 shadow-xs"
                :style="{ backgroundColor: activeThemeMeta.colorHex }"
            />

            <!-- Sun / Moon Icon -->
            <svg
                v-if="!isDark"
                class="h-4 w-4 text-amber-500"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                />
            </svg>
            <svg
                v-else
                class="h-4 w-4 text-indigo-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                />
            </svg>

            <svg
                class="h-3 w-3 text-zinc-400 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Popover Menu -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
            enter-to-class="transform scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform scale-100 opacity-100 translate-y-0"
            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-72 rounded-2xl border border-zinc-200 bg-white p-3 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 z-50 divide-y divide-zinc-100 dark:divide-zinc-800"
            >
                <!-- Section 1: Color Themes (3 modern presets) -->
                <div class="pb-3">
                    <div class="flex items-center justify-between mb-2 px-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                            Primary Theme
                        </span>
                        <span class="text-[11px] font-semibold text-primary-600 dark:text-primary-400">
                            {{ activeThemeMeta.name }}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="opt in AVAILABLE_COLOR_THEMES"
                            :key="opt.id"
                            type="button"
                            :class="[
                                'group flex flex-col items-center gap-1.5 p-2.5 rounded-xl border text-xs font-medium transition-all text-center relative',
                                colorTheme === opt.id
                                    ? 'border-primary-500 bg-primary-50/80 text-primary-900 dark:bg-primary-950/60 dark:text-primary-200 dark:border-primary-500 shadow-xs ring-2 ring-primary-500/20'
                                    : 'border-zinc-200/80 bg-white hover:bg-zinc-50 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800/80'
                            ]"
                            @click="setColorTheme(opt.id)"
                        >
                            <!-- Color Swatch Circle -->
                            <span
                                class="flex h-5 w-5 rounded-full shadow-xs items-center justify-center transition-transform group-hover:scale-110"
                                :style="{ backgroundColor: opt.colorHex }"
                            >
                                <svg
                                    v-if="colorTheme === opt.id"
                                    class="h-3 w-3 text-white stroke-3"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>

                            <span class="text-[11px] font-semibold tracking-tight leading-tight">
                                {{ opt.name }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Section 2: Appearance Mode -->
                <div class="pt-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 block mb-2 px-1">
                        Appearance Mode
                    </span>

                    <div class="grid grid-cols-3 gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800/80">
                        <button
                            type="button"
                            :class="[
                                'flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-lg text-xs font-medium transition-all',
                                theme === 'light'
                                    ? 'bg-white text-zinc-900 font-semibold shadow-xs dark:bg-zinc-900 dark:text-white'
                                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'
                            ]"
                            @click="setTheme('light')"
                        >
                            <svg class="h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>Light</span>
                        </button>

                        <button
                            type="button"
                            :class="[
                                'flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-lg text-xs font-medium transition-all',
                                theme === 'dark'
                                    ? 'bg-white text-zinc-900 font-semibold shadow-xs dark:bg-zinc-900 dark:text-white'
                                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'
                            ]"
                            @click="setTheme('dark')"
                        >
                            <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <span>Dark</span>
                        </button>

                        <button
                            type="button"
                            :class="[
                                'flex items-center justify-center gap-1.5 py-1.5 px-2 rounded-lg text-xs font-medium transition-all',
                                theme === 'system'
                                    ? 'bg-white text-zinc-900 font-semibold shadow-xs dark:bg-zinc-900 dark:text-white'
                                    : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white'
                            ]"
                            @click="setTheme('system')"
                        >
                            <svg class="h-3.5 w-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>System</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
