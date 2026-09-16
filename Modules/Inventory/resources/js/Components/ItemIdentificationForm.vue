<script setup lang="ts">
import { ref } from 'vue';
import { Button, Badge, Select } from '@/components';

interface SecondaryBarcode {
    type: string;
    code: string;
    description: string;
}

const props = defineProps<{
    form: {
        barcode: string;
        barcode_type: string;
        manufacturer_code: string;
        supplier_item_code: string;
        secondary_barcodes: SecondaryBarcode[];
    };
}>();

const barcodeTypes = [
    { label: 'EAN-13 (Standard Retail Barcode)', value: 'EAN13' },
    { label: 'UPC-A (Universal Product Code)', value: 'UPCA' },
    { label: 'Code 128 (Alphanumeric Logistics)', value: 'CODE128' },
    { label: 'QR Code (Quick Response Matrix)', value: 'QR' },
    { label: 'Internal Code (Custom)', value: 'INTERNAL' },
];

const addSecondaryBarcode = () => {
    props.form.secondary_barcodes.push({
        type: 'EAN13',
        code: '',
        description: '',
    });
};

const removeSecondaryBarcode = (index: number) => {
    props.form.secondary_barcodes.splice(index, 1);
};

const generateEan13 = () => {
    // Generate 12 digits + calculate Luhn check digit for valid EAN-13
    let code = '955' + Math.floor(100000000 + Math.random() * 900000000).toString();
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        sum += parseInt(code[i]) * (i % 2 === 0 ? 1 : 3);
    }
    const checkDigit = (10 - (sum % 10)) % 10;
    props.form.barcode = code + checkDigit;
    props.form.barcode_type = 'EAN13';
};
</script>

<template>
    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200/80 dark:border-zinc-800 p-6 shadow-xs space-y-6">
        <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                Item Identification & Barcode Scanning
            </h3>
            <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                Configure primary and secondary packaging barcodes for high-speed POS scanning and warehouse receiving.
            </p>
        </div>

        <!-- Primary Barcode Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-5 bg-slate-50/70 dark:bg-zinc-800/40 rounded-xl border border-slate-200 dark:border-zinc-800">
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider">
                        Primary Barcode
                    </label>
                    <button
                        type="button"
                        class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 cursor-pointer flex items-center gap-1"
                        @click="generateEan13"
                    >
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        Generate EAN-13
                    </button>
                </div>
                <div class="relative">
                    <input
                        v-model="form.barcode"
                        type="text"
                        placeholder="Scan or type barcode (e.g. 9551234567890)..."
                        class="w-full font-mono rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:focus:border-indigo-400"
                    />
                </div>
            </div>

            <div>
                <Select
                    v-model="form.barcode_type"
                    label="Barcode Symbology"
                    :options="barcodeTypes"
                    size="sm"
                />
            </div>

            <!-- Manufacturer Code -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                    Manufacturer Part # (MPN)
                </label>
                <input
                    v-model="form.manufacturer_code"
                    type="text"
                    placeholder="e.g. MPN-8849-A"
                    class="w-full font-mono rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                />
            </div>

            <!-- Supplier SKU -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">
                    Supplier Catalog Item Code
                </label>
                <input
                    v-model="form.supplier_item_code"
                    type="text"
                    placeholder="e.g. SUP-SKU-9901"
                    class="w-full font-mono rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                />
            </div>
        </div>

        <!-- Secondary Packaging Barcodes -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-zinc-300">
                        Multiple Barcodes / Packaging Multipliers
                    </h4>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        Add secondary barcodes (e.g. carton barcode, pack of 6 barcode, alternative supplier code).
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 cursor-pointer"
                    @click="addSecondaryBarcode"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Barcode
                </button>
            </div>

            <div v-if="form.secondary_barcodes.length > 0" class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-zinc-800/80 text-slate-700 dark:text-zinc-300 font-bold border-b border-slate-200 dark:border-zinc-800">
                        <tr>
                            <th class="py-2.5 px-3 w-[25%]">Symbology</th>
                            <th class="py-2.5 px-3 w-[40%]">Barcode String</th>
                            <th class="py-2.5 px-3 w-[30%]">Description / Note</th>
                            <th class="py-2.5 px-3 w-[5%] text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                        <tr v-for="(item, idx) in form.secondary_barcodes" :key="idx" class="hover:bg-slate-50/50 dark:hover:bg-zinc-800/40">
                            <td class="p-2">
                                <Select
                                    v-model="item.type"
                                    :options="barcodeTypes"
                                    size="sm"
                                />
                            </td>
                            <td class="p-2">
                                <input
                                    v-model="item.code"
                                    type="text"
                                    placeholder="Enter secondary barcode..."
                                    class="w-full font-mono rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                />
                            </td>
                            <td class="p-2">
                                <input
                                    v-model="item.description"
                                    type="text"
                                    placeholder="e.g. Outer Carton of 24"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                                />
                            </td>
                            <td class="p-2 text-center">
                                <button
                                    type="button"
                                    class="text-slate-400 hover:text-red-600 transition-colors cursor-pointer p-1"
                                    title="Remove"
                                    @click="removeSecondaryBarcode(idx)"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-4 px-3 bg-slate-50/50 dark:bg-zinc-800/30 rounded-xl border border-dashed border-slate-200 dark:border-zinc-800 text-xs text-slate-400">
                No secondary barcodes added yet. Click "+ Add Barcode" if this product is scanned using multiple packaging codes.
            </div>
        </div>
    </div>
</template>
