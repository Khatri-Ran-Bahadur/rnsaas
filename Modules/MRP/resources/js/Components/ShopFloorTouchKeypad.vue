<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
    modelValue: string | number;
    title?: string;
    unit?: string;
    maxDigits?: number;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'submit', value: string): void;
    (e: 'cancel'): void;
}>();

const localVal = ref(String(props.modelValue || ''));

const appendDigit = (digit: string) => {
    if (props.maxDigits && localVal.value.length >= props.maxDigits) return;
    if (digit === '.' && localVal.value.includes('.')) return;
    localVal.value += digit;
    emit('update:modelValue', localVal.value);
};

const backspace = () => {
    localVal.value = localVal.value.slice(0, -1);
    emit('update:modelValue', localVal.value);
};

const clear = () => {
    localVal.value = '';
    emit('update:modelValue', '');
};

const submit = () => {
    emit('submit', localVal.value);
};
</script>

<template>
    <div class="bg-slate-900 text-white rounded-3xl p-6 shadow-2xl border border-slate-800 max-w-sm w-full mx-auto select-none">
        <div class="mb-4 text-center">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ title || 'Enter Quantity' }}</span>
            <div class="mt-2 flex items-baseline justify-center space-x-2 bg-slate-800/80 rounded-2xl py-3 px-4 border border-slate-700">
                <span class="text-3xl font-black font-mono tracking-tight text-emerald-400">
                    {{ localVal || '0' }}
                </span>
                <span v-if="unit" class="text-sm font-semibold text-slate-400">{{ unit }}</span>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2.5">
            <button
                v-for="n in ['1','2','3','4','5','6','7','8','9']"
                :key="n"
                type="button"
                @click="appendDigit(n)"
                class="h-14 rounded-2xl bg-slate-800 hover:bg-slate-700 active:scale-95 text-2xl font-bold flex items-center justify-center transition-all border border-slate-700/60 shadow-sm"
            >
                {{ n }}
            </button>

            <button
                type="button"
                @click="appendDigit('.')"
                class="h-14 rounded-2xl bg-slate-800 hover:bg-slate-700 active:scale-95 text-2xl font-bold flex items-center justify-center transition-all border border-slate-700/60"
            >
                .
            </button>
            <button
                type="button"
                @click="appendDigit('0')"
                class="h-14 rounded-2xl bg-slate-800 hover:bg-slate-700 active:scale-95 text-2xl font-bold flex items-center justify-center transition-all border border-slate-700/60"
            >
                0
            </button>
            <button
                type="button"
                @click="backspace"
                class="h-14 rounded-2xl bg-red-950/40 hover:bg-red-900/50 text-red-400 active:scale-95 text-lg font-bold flex items-center justify-center transition-all border border-red-800/50"
            >
                ⌫
            </button>
        </div>

        <div class="grid grid-cols-2 gap-2.5 mt-3">
            <button
                type="button"
                @click="clear"
                class="py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-sm font-bold text-slate-300 transition-all border border-slate-700"
            >
                Clear
            </button>
            <button
                type="button"
                @click="submit"
                class="py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white text-base font-bold shadow-lg shadow-emerald-900/40 active:scale-95 transition-all"
            >
                Confirm
            </button>
        </div>
    </div>
</template>
