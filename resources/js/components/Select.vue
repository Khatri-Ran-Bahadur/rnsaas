<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';

export interface SelectOption {
    label: string;
    value: string | number;
    sublabel?: string;
    flag?: string;
    disabled?: boolean;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string | number;
        options: (SelectOption | string)[];
        label?: string;
        placeholder?: string;
        searchPlaceholder?: string;
        error?: string;
        hint?: string;
        required?: boolean;
        disabled?: boolean;
        searchable?: boolean;
        size?: 'sm' | 'md';
    }>(),
    {
        modelValue: '',
        label: undefined,
        placeholder: 'Select an option',
        searchPlaceholder: 'Search...',
        error: undefined,
        hint: undefined,
        required: false,
        disabled: false,
        searchable: undefined,
        size: 'md',
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
    (e: 'change', value: string | number, option?: SelectOption): void;
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref<HTMLElement | null>(null);
const dropdownMenuRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);
const dropdownStyle = ref<Record<string, string>>({});
const optionsMaxHeight = ref(200);

const formattedOptions = computed<SelectOption[]>(() => {
    return props.options.map((opt) => {
        if (typeof opt === 'string') {
            return { label: opt, value: opt };
        }
        return opt;
    });
});

const isSearchable = computed(() => {
    if (props.searchable !== undefined) {
        return props.searchable;
    }
    // Auto-enable search filter if more than 6 options
    return formattedOptions.value.length > 6;
});

const filteredOptions = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return formattedOptions.value;

    return formattedOptions.value.filter((opt) => {
        const label = opt.label.toLowerCase();
        const value = String(opt.value).toLowerCase();
        const sublabel = (opt.sublabel || '').toLowerCase();
        return label.includes(q) || value.includes(q) || sublabel.includes(q);
    });
});

const selectedOption = computed(() => {
    return formattedOptions.value.find((opt) => String(opt.value) === String(props.modelValue));
});

const instanceId = Symbol();

const updatePosition = () => {
    if (!containerRef.value) return;
    const rect = containerRef.value.getBoundingClientRect();
    const menuWidth = Math.max(rect.width, 240);
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    // Prefer opening DOWNWARDS. Only open upwards if space below is tight (< 140px) AND space above has more room
    const isDropUp = spaceBelow < 140 && spaceAbove > 180;

    // Horizontal clamping: ensure menu doesn't go offscreen horizontally
    let left = rect.left;
    if (left + menuWidth > window.innerWidth - 16) {
        left = Math.max(16, window.innerWidth - menuWidth - 16);
    }
    if (left < 16) {
        left = 16;
    }

    if (isDropUp) {
        const availableHeight = Math.min(280, Math.max(140, spaceAbove - 16));
        optionsMaxHeight.value = isSearchable.value ? Math.max(80, availableHeight - 58) : availableHeight - 16;

        dropdownStyle.value = {
            position: 'fixed',
            bottom: `${window.innerHeight - rect.top + 6}px`,
            left: `${left}px`,
            width: `${menuWidth}px`,
            maxWidth: '420px',
            maxHeight: `${availableHeight}px`,
            zIndex: '99999',
            display: 'flex',
            flexDirection: 'column',
        };
    } else {
        const availableHeight = Math.min(280, Math.max(140, spaceBelow - 16));
        optionsMaxHeight.value = isSearchable.value ? Math.max(80, availableHeight - 58) : availableHeight - 16;

        dropdownStyle.value = {
            position: 'fixed',
            top: `${rect.bottom + 6}px`,
            left: `${left}px`,
            width: `${menuWidth}px`,
            maxWidth: '420px',
            maxHeight: `${availableHeight}px`,
            zIndex: '99999',
            display: 'flex',
            flexDirection: 'column',
        };
    }
};

const toggleDropdown = () => {
    if (props.disabled) return;
    const nextState = !isOpen.value;
    if (nextState) {
        window.dispatchEvent(new CustomEvent('close-comboboxes', { detail: { id: instanceId } }));
        updatePosition();
    }
    isOpen.value = nextState;
    if (isOpen.value) {
        searchQuery.value = '';
        nextTick(() => {
            updatePosition();
            if (isSearchable.value) {
                searchInputRef.value?.focus();
            }
        });
    }
};

const selectOption = (opt: SelectOption) => {
    if (opt.disabled) return;
    emit('update:modelValue', opt.value);
    emit('change', opt.value, opt);
    isOpen.value = false;
    searchQuery.value = '';
};

const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as Node;
    if (containerRef.value && containerRef.value.contains(target)) {
        return;
    }
    if (dropdownMenuRef.value && dropdownMenuRef.value.contains(target)) {
        return;
    }
    isOpen.value = false;
    searchQuery.value = '';
};

const handleScrollOrResize = (e: Event) => {
    if (!isOpen.value) return;
    if (dropdownMenuRef.value && dropdownMenuRef.value.contains(e.target as Node)) {
        return;
    }
    updatePosition();
};

const handleCloseOther = (e: Event) => {
    const customEvent = e as CustomEvent;
    if (customEvent.detail?.id !== instanceId) {
        isOpen.value = false;
        searchQuery.value = '';
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
    window.addEventListener('close-comboboxes', handleCloseOther as EventListener);
    window.addEventListener('scroll', handleScrollOrResize, true);
    window.addEventListener('resize', handleScrollOrResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('click', handleClickOutside);
    window.removeEventListener('close-comboboxes', handleCloseOther as EventListener);
    window.removeEventListener('scroll', handleScrollOrResize, true);
    window.removeEventListener('resize', handleScrollOrResize);
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <!-- Label -->
        <label
            v-if="label"
            class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5"
            @click="toggleDropdown"
        >
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>

        <!-- Clean Input-Style Trigger Button (Matching Shadcn & Accounting design) -->
        <button
            type="button"
            :disabled="disabled"
            :class="[
                'flex w-full items-center justify-between bg-white text-left transition-all duration-150 border border-zinc-200 dark:bg-zinc-950 dark:border-zinc-800',
                size === 'sm' ? 'h-8 px-2.5 text-xs rounded-md' : 'h-10 px-3.5 text-sm rounded-lg',
                disabled ? 'opacity-50 cursor-not-allowed bg-zinc-50 dark:bg-zinc-900' : 'cursor-pointer hover:border-zinc-300 dark:hover:border-zinc-700',
                error
                    ? 'border-rose-500 focus:border-rose-500 focus:ring-1 focus:ring-rose-500'
                    : 'focus:border-primary-500 focus:ring-1 focus:ring-primary-500/20 dark:focus:border-primary-500',
                isOpen ? 'border-primary-500 ring-1 ring-primary-500/20 dark:border-primary-500' : 'shadow-2xs',
            ]"
            @click="toggleDropdown"
        >
            <div class="flex items-center gap-2.5 overflow-hidden truncate">
                <!-- Selected Flag / Icon if any -->
                <span v-if="selectedOption?.flag" class="text-base shrink-0 leading-none">
                    {{ selectedOption.flag }}
                </span>

                <!-- Selected Label or Placeholder -->
                <span
                    v-if="selectedOption"
                    class="truncate font-normal text-zinc-900 dark:text-zinc-100"
                >
                    {{ selectedOption.label }}
                </span>
                <span v-else class="truncate text-zinc-400 dark:text-zinc-500">
                    {{ placeholder }}
                </span>
            </div>

            <!-- Sleek Subtle Chevron -->
            <div class="shrink-0 ml-2 text-zinc-400 dark:text-zinc-500">
                <svg
                    class="h-4 w-4 transition-transform duration-200"
                    :class="{ 'rotate-180': isOpen }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>

        <!-- Floating Dropdown Menu (Teleported to body for guaranteed escape of all overflow/card containers) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div
                    v-if="isOpen"
                    ref="dropdownMenuRef"
                    class="rounded-xl border border-zinc-200 bg-white p-1.5 shadow-2xl dark:border-zinc-800 dark:bg-zinc-900 flex flex-col"
                    :style="dropdownStyle"
                    @click.stop
                >
                    <!-- Compact Search Box (when searchable) -->
                    <div v-if="isSearchable" class="p-1 pb-1.5 border-b border-zinc-100 dark:border-zinc-800 mb-1 shrink-0">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                ref="searchInputRef"
                                v-model="searchQuery"
                                type="text"
                                :placeholder="searchPlaceholder"
                                class="w-full rounded-md border border-zinc-200 bg-zinc-50/70 py-1.5 pl-8 pr-7 text-xs text-zinc-900 placeholder-zinc-400 focus:border-zinc-400 focus:bg-white focus:outline-hidden focus:ring-1 focus:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-white dark:placeholder-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-500"
                                @click.stop
                            />
                            <button
                                v-if="searchQuery"
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                                @click.stop="searchQuery = ''"
                            >
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Options List with Checkmark and Selection -->
                    <div
                        class="overflow-y-auto space-y-0.5 overscroll-contain flex-1"
                        :style="{ maxHeight: `${optionsMaxHeight}px` }"
                    >
                        <button
                            v-for="opt in filteredOptions"
                            :key="opt.value"
                            type="button"
                            :disabled="opt.disabled"
                            :class="[
                                'flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-xs transition-colors',
                                String(modelValue) === String(opt.value)
                                    ? 'bg-primary-50 text-primary-900 font-semibold dark:bg-primary-950/60 dark:text-primary-200'
                                    : 'text-zinc-700 hover:bg-zinc-50/80 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800/60 dark:hover:text-white',
                                opt.disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
                            ]"
                            @click="selectOption(opt)"
                        >
                            <!-- Left Checkmark Indicator (Visible when selected) -->
                            <div class="w-4 shrink-0 flex items-center justify-center text-primary-600 dark:text-primary-400">
                                <svg
                                    v-if="String(modelValue) === String(opt.value)"
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>

                            <!-- Flag Icon if any -->
                            <span v-if="opt.flag" class="text-sm shrink-0 leading-none">
                                {{ opt.flag }}
                            </span>

                            <!-- Label Text -->
                            <div class="truncate">
                                <span class="font-normal">{{ opt.label }}</span>
                                <span v-if="opt.sublabel" class="ml-1.5 text-zinc-400 dark:text-zinc-500 font-mono text-[11px]">
                                    {{ opt.sublabel }}
                                </span>
                            </div>
                        </button>

                        <!-- Empty State -->
                        <div v-if="filteredOptions.length === 0" class="py-5 text-center text-xs text-zinc-400 dark:text-zinc-500">
                            No results found
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Error Message -->
        <p v-if="error" class="mt-1.5 text-xs text-rose-500 font-medium">
            {{ error }}
        </p>

        <!-- Hint Message -->
        <p v-else-if="hint" class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
            {{ hint }}
        </p>
    </div>
</template>
