<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { ProductItem } from './PosProductGrid.vue';

const props = defineProps<{
    show: boolean;
    product: ProductItem | null;
    currencySymbol: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm', payload: {
        product: ProductItem;
        selectedVariant: any | null;
        selectedModifiers: Array<{ id: string; name: string; price: number }>;
        quantity: number;
        notes: string;
        calculatedPrice: number;
    }): void;
}>();

const selectedVariant = ref<any | null>(null);
const selectedModifiers = ref<Record<string, Array<{ id: string; name: string; price: number }>>>({});
const quantity = ref(1);
const notes = ref('');

watch(() => props.show, (isOpen) => {
    if (isOpen && props.product) {
        quantity.value = 1;
        notes.value = '';
        selectedModifiers.value = {};

        if (props.product.has_variants && props.product.variants && props.product.variants.length > 0) {
            selectedVariant.value = props.product.variants[0];
        } else {
            selectedVariant.value = null;
        }

        if (props.product.modifier_groups) {
            props.product.modifier_groups.forEach((group: any) => {
                if (group.required && group.options.length > 0) {
                    selectedModifiers.value[group.id] = [group.options[0]];
                } else {
                    selectedModifiers.value[group.id] = [];
                }
            });
        }
    }
});

const isModifierOptionSelected = (groupId: string, optionId: string) => {
    return !!selectedModifiers.value[groupId]?.some((o) => o.id === optionId);
};

const toggleModifierOption = (group: any, option: any) => {
    if (!selectedModifiers.value[group.id]) {
        selectedModifiers.value[group.id] = [];
    }

    if (group.max_selection === 1) {
        selectedModifiers.value[group.id] = [option];
    } else {
        const existingIdx = selectedModifiers.value[group.id].findIndex((o) => o.id === option.id);
        if (existingIdx >= 0) {
            if (!group.required || selectedModifiers.value[group.id].length > 1) {
                selectedModifiers.value[group.id].splice(existingIdx, 1);
            }
        } else {
            if (!group.max_selection || selectedModifiers.value[group.id].length < group.max_selection) {
                selectedModifiers.value[group.id].push(option);
            }
        }
    }
};

const flatModifiersList = computed(() => {
    const list: Array<{ id: string; name: string; price: number }> = [];
    Object.values(selectedModifiers.value).forEach((groupOptions) => {
        list.push(...groupOptions);
    });
    return list;
});

const baseUnitPrice = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.price;
    }
    return props.product?.price ?? 0;
});

const calculatedItemUnitPrice = computed(() => {
    const modifiersCost = flatModifiersList.value.reduce((sum, m) => sum + m.price, 0);
    return baseUnitPrice.value + modifiersCost;
});

const totalItemCost = computed(() => {
    return calculatedItemUnitPrice.value * quantity.value;
});

const isValid = computed(() => {
    if (!props.product) return false;
    if (props.product.has_variants && !selectedVariant.value) return false;

    if (props.product.modifier_groups) {
        for (const group of props.product.modifier_groups) {
            if (group.required && (!selectedModifiers.value[group.id] || selectedModifiers.value[group.id].length === 0)) {
                return false;
            }
        }
    }
    return true;
});

const handleConfirm = () => {
    if (!isValid.value || !props.product) return;
    emit('confirm', {
        product: props.product,
        selectedVariant: selectedVariant.value,
        selectedModifiers: flatModifiersList.value,
        quantity: quantity.value,
        notes: notes.value.trim(),
        calculatedPrice: baseUnitPrice.value,
    });
};
</script>

<template>
    <div
        v-if="show && product"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/80 backdrop-blur-xs select-none"
        @keydown.esc="emit('close')"
    >
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[88vh]">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-zinc-950 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                        <span>{{ product.name }}</span>
                    </h3>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 font-mono mt-0.5">
                        Base: {{ currencySymbol }} {{ product.price.toFixed(2) }} • SKU: {{ product.sku }}
                    </div>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Content (Variants & Modifiers) -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Variants Section -->
                <div v-if="product.has_variants && product.variants && product.variants.length > 0">
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-2.5">
                        Select Variant / Size / Model
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        <button
                            v-for="v in product.variants"
                            :key="v.id"
                            type="button"
                            @click="selectedVariant = v"
                            :class="[
                                'p-3 rounded-xl border text-left transition flex flex-col justify-between cursor-pointer',
                                selectedVariant?.id === v.id
                                    ? 'bg-blue-50 dark:bg-blue-600/20 text-blue-900 dark:text-blue-300 border-blue-400 dark:border-blue-500 shadow-xs ring-1 ring-blue-400'
                                    : 'bg-slate-50 dark:bg-zinc-950 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-200'
                            ]"
                        >
                            <div class="text-xs font-bold leading-tight">{{ v.name }}</div>
                            <div class="mt-2 flex items-baseline justify-between">
                                <span class="text-xs font-mono font-bold text-zinc-900 dark:text-zinc-200">{{ currencySymbol }} {{ v.price.toFixed(2) }}</span>
                                <span class="text-[10px] font-mono text-zinc-400">{{ v.stock }} left</span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Modifier Groups Section -->
                <div v-if="product.modifier_groups && product.modifier_groups.length > 0" class="space-y-5">
                    <div
                        v-for="group in product.modifier_groups"
                        :key="group.id"
                        class="bg-slate-50/70 dark:bg-zinc-950/60 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-bold text-zinc-800 dark:text-zinc-200 flex items-center gap-2">
                                <span>{{ group.name }}</span>
                                <span
                                    v-if="group.required"
                                    class="text-[9px] uppercase px-1.5 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 font-mono font-bold"
                                >
                                    Required
                                </span>
                            </div>
                            <div class="text-[11px] text-zinc-500">
                                {{ group.max_selection === 1 ? 'Choose 1' : `Max ${group.max_selection}` }}
                            </div>
                        </div>

                        <!-- Modifier Options Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <button
                                v-for="opt in group.options"
                                :key="opt.id"
                                type="button"
                                @click="toggleModifierOption(group, opt)"
                                :class="[
                                    'p-2.5 rounded-xl border text-xs text-left flex items-center justify-between transition cursor-pointer',
                                    isModifierOptionSelected(group.id, opt.id)
                                        ? 'bg-emerald-50 dark:bg-emerald-600/20 text-emerald-800 dark:text-emerald-300 border-emerald-400 dark:border-emerald-500 shadow-xs font-semibold'
                                        : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 border-zinc-200 dark:border-zinc-800 hover:bg-slate-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-200'
                                ]"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-4 h-4 rounded-md flex items-center justify-center text-[10px] font-bold border"
                                        :class="isModifierOptionSelected(group.id, opt.id) ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-100 dark:bg-zinc-950 border-zinc-300 dark:border-zinc-700'"
                                    >
                                        <span v-if="isModifierOptionSelected(group.id, opt.id)">✓</span>
                                    </div>
                                    <span class="font-medium">{{ opt.name }}</span>
                                </div>
                                <span class="font-mono text-zinc-500">
                                    {{ opt.price > 0 ? `+${currencySymbol}${opt.price.toFixed(2)}` : 'Free' }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Special Cooking Instructions Note -->
                <div>
                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-2">
                        Kitchen / Special Cooking Note
                    </label>
                    <input
                        v-model="notes"
                        type="text"
                        placeholder="e.g. Less spicy, extra sauce on side, no onions..."
                        class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-800 rounded-xl text-xs text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <!-- Quantity Adjuster -->
                <div class="flex items-center justify-between bg-slate-50 dark:bg-zinc-950 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Quantity</span>
                    <div class="flex items-center space-x-2">
                        <button
                            type="button"
                            @click="quantity = Math.max(1, quantity - 1)"
                            class="w-8 h-8 rounded-lg bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center font-bold text-base transition cursor-pointer shadow-xs"
                        >
                            -
                        </button>
                        <span class="w-10 text-center font-mono font-bold text-zinc-800 dark:text-zinc-100 text-sm">
                            {{ quantity }}
                        </span>
                        <button
                            type="button"
                            @click="quantity = quantity + 1"
                            class="w-8 h-8 rounded-lg bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center font-bold text-base transition cursor-pointer shadow-xs"
                        >
                            +
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <div class="text-[10px] uppercase text-zinc-400 font-bold">Total Price</div>
                    <div class="text-lg font-black font-mono text-emerald-600 dark:text-emerald-400">
                        {{ currencySymbol }} {{ totalItemCost.toFixed(2) }}
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-xs font-semibold transition cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="!isValid"
                        @click="handleConfirm"
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white text-xs font-bold shadow-md shadow-emerald-900/20 transition cursor-pointer"
                    >
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
