<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { useLocale, type LocaleOption } from '@/composables/useLocale';

const { locale, supportedLocales, currentLocaleObj, switchLocale } = useLocale();

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);

const toggleDropdown = async () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        await nextTick();
        searchInputRef.value?.focus();
    }
};

const filteredLocales = computed<LocaleOption[]>(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) {
        return supportedLocales.value;
    }
    return supportedLocales.value.filter(
        (l) => l.name.toLowerCase().includes(q) || l.code.toLowerCase().includes(q)
    );
});

const handleSelect = (code: string) => {
    isOpen.value = false;
    searchQuery.value = '';
    if (code !== locale.value) {
        switchLocale(code);
    }
};

const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isOpen.value = false;
        searchQuery.value = '';
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block text-left rtl:text-right">
        <button
            type="button"
            class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 cursor-pointer shadow-xs"
            :title="`Current language: ${currentLocaleObj.name}`"
            @click="toggleDropdown"
        >
            <span class="text-sm leading-none">{{ currentLocaleObj.flag }}</span>
            <span class="uppercase font-semibold text-[11px] tracking-wide">{{ currentLocaleObj.code }}</span>
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

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 rtl:right-auto rtl:left-0 mt-1.5 w-60 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 z-50 overflow-hidden"
            >
                <div class="px-2 py-1 flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-1.5 mb-1.5">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                        Select Language
                    </span>
                    <span class="text-[10px] text-zinc-400 dark:text-zinc-500 font-mono">
                        {{ supportedLocales.length }} available
                    </span>
                </div>

                <!-- Search Input -->
                <div class="px-1 mb-1.5">
                    <div class="relative">
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search language..."
                            class="w-full rounded-md border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs text-zinc-800 placeholder-zinc-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden dark:border-zinc-700 dark:bg-zinc-800/80 dark:text-zinc-200 dark:placeholder-zinc-500"
                            @keydown.esc="isOpen = false"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 text-xs"
                            @click="searchQuery = ''"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Locales List -->
                <div class="max-h-60 overflow-y-auto space-y-0.5 pr-0.5 scrollbar-thin">
                    <button
                        v-for="l in filteredLocales"
                        :key="l.code"
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition cursor-pointer text-left"
                        :class="[
                            locale === l.code
                                ? 'bg-indigo-50 text-indigo-700 font-semibold dark:bg-indigo-950/40 dark:text-indigo-300'
                                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-200',
                        ]"
                        @click="handleSelect(l.code)"
                    >
                        <div class="flex items-center gap-2 truncate">
                            <span class="text-sm leading-none shrink-0">{{ l.flag }}</span>
                            <span class="truncate">{{ l.name }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 ml-2">
                            <span class="text-[10px] uppercase font-mono text-zinc-400 dark:text-zinc-500">{{ l.code }}</span>
                            <span v-if="locale === l.code" class="h-1.5 w-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400" />
                        </div>
                    </button>

                    <div v-if="filteredLocales.length === 0" class="px-3 py-4 text-center text-xs text-zinc-400 dark:text-zinc-500">
                        No languages found matching "{{ searchQuery }}"
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
