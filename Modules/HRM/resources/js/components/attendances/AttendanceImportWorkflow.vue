<script setup lang="ts">
import { ref } from 'vue';
import HRMImportStepper from '../common/HRMImportStepper.vue';
import HRMImportDropzone from '../common/HRMImportDropzone.vue';
import Select from '@/components/Select.vue';
import type { ImportPreviewRow, ImportValidationSummary, ImportResultSummary } from '../../types/attendances';

const currentStep = ref(1);
const selectedFile = ref<File | null>(null);

const steps = [
    { step: 1, title: 'Upload' },
    { step: 2, title: 'Mapping' },
    { step: 3, title: 'Preview' },
    { step: 4, title: 'Validation' },
    { step: 5, title: 'Import' },
    { step: 6, title: 'Result' },
];

// Column mapping state
const mappings = ref([
    { excel: 'Employee Code', system: 'tenant_staff_code', required: true },
    { excel: 'Attendance Date', system: 'attendance_date', required: true },
    { excel: 'Punch In', system: 'check_in', required: true },
    { excel: 'Punch Out', system: 'check_out', required: true },
    { excel: 'Attendance Status', system: 'status', required: false },
    { excel: 'Punch Source', system: 'source', required: false },
]);

const systemFieldOptions = [
    { value: 'tenant_staff_code', label: 'Employee Code (Required)' },
    { value: 'attendance_date', label: 'Attendance Date (Required)' },
    { value: 'check_in', label: 'Check In Time (Required)' },
    { value: 'check_out', label: 'Check Out Time (Required)' },
    { value: 'status', label: 'Attendance Status' },
    { value: 'source', label: 'Attendance Source' },
    { value: 'notes', label: 'Notes / Remarks' },
    { value: 'ignore', label: '— Ignore Column —' },
];

// Sample preview rows
const previewRows = ref<ImportPreviewRow[]>([
    { rowNumber: 1, employee_code: 'EMP-001', employee_name: 'Aarav Sharma', attendance_date: '2026-09-07', check_in: '09:02', check_out: '17:05', status: 'present', source: 'biometric' },
    { rowNumber: 2, employee_code: 'EMP-002', employee_name: 'Pooja Thapa', attendance_date: '2026-09-07', check_in: '09:35', check_out: '17:30', status: 'late', source: 'biometric', warning: 'Late arrival detected' },
    { rowNumber: 3, employee_code: 'EMP-999', employee_name: 'Unknown', attendance_date: '2026-09-07', check_in: '09:00', check_out: '17:00', status: 'present', source: 'biometric', error: 'Employee code EMP-999 not found' },
    { rowNumber: 4, employee_code: 'EMP-004', employee_name: 'Bikash Rana', attendance_date: '2026-09-07', check_in: '08:58', check_out: '17:01', status: 'present', source: 'biometric' },
]);

const validationSummary = ref<ImportValidationSummary>({
    totalRows: 120,
    validRows: 108,
    warningRows: 8,
    errorRows: 4,
    duplicateRows: 2,
    existingRecords: 14,
    newRecords: 94,
});

const isImporting = ref(false);
const importResult = ref<ImportResultSummary>({
    imported: 94,
    updated: 14,
    skipped: 8,
    failed: 4,
});

const nextStep = () => {
    if (currentStep.value === 1 && !selectedFile.value) {
        alert('Please choose an attendance file to upload.');
        return;
    }
    if (currentStep.value < 6) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const executeImport = () => {
    isImporting.value = true;
    setTimeout(() => {
        isImporting.value = false;
        currentStep.value = 6;
    }, 1200);
};

const downloadTemplate = () => {
    alert('Downloading attendance import template (.xlsx)...');
};

const resetWorkflow = () => {
    currentStep.value = 1;
    selectedFile.value = null;
};
</script>

<template>
    <div class="space-y-6">
        <!-- 6-Step Stepper -->
        <HRMImportStepper :current-step="currentStep" :steps="steps" />

        <!-- STEP 1: UPLOAD -->
        <div v-if="currentStep === 1" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                Step 1: Upload Attendance Excel File
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                Upload your attendance spreadsheet exported from biometric punch clocks, card readers, or mobile tracking systems.
            </p>

            <div class="mt-6">
                <HRMImportDropzone
                    v-model="selectedFile"
                    @download-template="downloadTemplate"
                />
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    :disabled="!selectedFile"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    @click="nextStep"
                >
                    Continue to Mapping
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- STEP 2: COLUMN MAPPING -->
        <div v-else-if="currentStep === 2" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                Step 2: Match Excel Columns to System Fields
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                Ensure each column from your uploaded file maps correctly to an attendance property.
            </p>

            <div class="mt-5 divide-y divide-zinc-200/80 dark:divide-zinc-800">
                <div
                    v-for="(mapItem, idx) in mappings"
                    :key="idx"
                    class="grid grid-cols-1 gap-4 py-3 sm:grid-cols-2 sm:items-center"
                >
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-zinc-100 text-xs font-bold text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ idx + 1 }}
                        </span>
                        <div>
                            <p class="text-xs font-semibold text-slate-900 dark:text-white">
                                {{ mapItem.excel }}
                            </p>
                            <span v-if="mapItem.required" class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium">
                                Required Field
                            </span>
                        </div>
                    </div>

                    <div>
                        <Select
                            v-model="mapItem.system"
                            :options="systemFieldOptions"
                            placeholder="Select target field"
                        />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-between border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <button
                    type="button"
                    class="rounded-lg border border-zinc-300 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    @click="prevStep"
                >
                    Back
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500"
                    @click="nextStep"
                >
                    Preview Data
                </button>
            </div>
        </div>

        <!-- STEP 3: PREVIEW -->
        <div v-else-if="currentStep === 3" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                Step 3: Preview Rows
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                Review the first parsed rows. Any column warnings or formatting discrepancies are shown inline.
            </p>

            <div class="mt-4 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-slate-600 font-semibold dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-300">
                        <tr>
                            <th class="py-2.5 px-3">Row</th>
                            <th class="py-2.5 px-3">Emp Code</th>
                            <th class="py-2.5 px-3">Employee</th>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Check In</th>
                            <th class="py-2.5 px-3">Check Out</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Validation Alert</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        <tr v-for="r in previewRows" :key="r.rowNumber" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="py-2.5 px-3 font-mono text-slate-500">{{ r.rowNumber }}</td>
                            <td class="py-2.5 px-3 font-medium text-slate-900 dark:text-white">{{ r.employee_code }}</td>
                            <td class="py-2.5 px-3 text-slate-700 dark:text-zinc-300">{{ r.employee_name }}</td>
                            <td class="py-2.5 px-3">{{ r.attendance_date }}</td>
                            <td class="py-2.5 px-3 font-mono">{{ r.check_in }}</td>
                            <td class="py-2.5 px-3 font-mono">{{ r.check_out }}</td>
                            <td class="py-2.5 px-3 capitalize">{{ r.status }}</td>
                            <td class="py-2.5 px-3">
                                <span v-if="r.error" class="rounded bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                    ✕ {{ r.error }}
                                </span>
                                <span v-else-if="r.warning" class="rounded bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                                    ⚠ {{ r.warning }}
                                </span>
                                <span v-else class="text-emerald-600 dark:text-emerald-400 font-semibold text-[10px]">
                                    ✓ Valid
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-between border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <button
                    type="button"
                    class="rounded-lg border border-zinc-300 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    @click="prevStep"
                >
                    Back
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500"
                    @click="nextStep"
                >
                    Validate File
                </button>
            </div>
        </div>

        <!-- STEP 4: VALIDATION -->
        <div v-else-if="currentStep === 4" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                Step 4: Validation Summary
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                System checks for duplicate records, active tenant staff matching, and time sequence validity.
            </p>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-3.5 dark:border-emerald-900/60 dark:bg-emerald-950/30">
                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Valid Rows</span>
                    <p class="mt-1 text-xl font-bold text-emerald-800 dark:text-emerald-200">
                        {{ validationSummary.validRows }}
                    </p>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-3.5 dark:border-amber-900/60 dark:bg-amber-950/30">
                    <span class="text-xs font-medium text-amber-700 dark:text-amber-300">Warnings</span>
                    <p class="mt-1 text-xl font-bold text-amber-800 dark:text-amber-200">
                        {{ validationSummary.warningRows }}
                    </p>
                </div>

                <div class="rounded-lg border border-rose-200 bg-rose-50/50 p-3.5 dark:border-rose-900/60 dark:bg-rose-950/30">
                    <span class="text-xs font-medium text-rose-700 dark:text-rose-300">Errors (Will skip)</span>
                    <p class="mt-1 text-xl font-bold text-rose-800 dark:text-rose-200">
                        {{ validationSummary.errorRows }}
                    </p>
                </div>

                <div class="rounded-lg border border-indigo-200 bg-indigo-50/50 p-3.5 dark:border-indigo-900/60 dark:bg-indigo-950/30">
                    <span class="text-xs font-medium text-indigo-700 dark:text-indigo-300">Total Scanned</span>
                    <p class="mt-1 text-xl font-bold text-indigo-800 dark:text-indigo-200">
                        {{ validationSummary.totalRows }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-between border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <button
                    type="button"
                    class="rounded-lg border border-zinc-300 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    @click="prevStep"
                >
                    Back
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500"
                    @click="nextStep"
                >
                    Proceed to Import
                </button>
            </div>
        </div>

        <!-- STEP 5: IMPORT CONFIRMATION -->
        <div v-else-if="currentStep === 5" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                Step 5: Import Confirmation
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                You are about to insert attendance records for active tenant staff.
            </p>

            <div class="mt-5 rounded-xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40 space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600 dark:text-zinc-400">New Attendance Records:</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ validationSummary.newRecords }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600 dark:text-zinc-400">Existing Records (Will update):</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ validationSummary.existingRecords }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600 dark:text-zinc-400">Invalid / Unmatched Records (Skipped):</span>
                    <span class="font-bold text-rose-600">{{ validationSummary.errorRows }}</span>
                </div>
            </div>

            <div class="mt-6 flex justify-between border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <button
                    type="button"
                    :disabled="isImporting"
                    class="rounded-lg border border-zinc-300 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    @click="prevStep"
                >
                    Back
                </button>
                <button
                    type="button"
                    :disabled="isImporting"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition-colors"
                    @click="executeImport"
                >
                    <svg
                        v-if="isImporting"
                        class="h-4 w-4 animate-spin text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    {{ isImporting ? 'Importing Records...' : 'Start Import' }}
                </button>
            </div>
        </div>

        <!-- STEP 6: RESULT -->
        <div v-else class="rounded-xl border border-zinc-200 bg-white p-8 text-center shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h3 class="mt-4 text-lg font-bold text-slate-900 dark:text-white">
                Attendance Import Completed
            </h3>
            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">
                Records have been inserted and linked to your tenant staff profiles.
            </p>

            <div class="mx-auto mt-6 grid max-w-lg grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Imported</span>
                    <p class="mt-1 text-base font-bold text-emerald-600">{{ importResult.imported }}</p>
                </div>
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Updated</span>
                    <p class="mt-1 text-base font-bold text-indigo-600">{{ importResult.updated }}</p>
                </div>
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Skipped</span>
                    <p class="mt-1 text-base font-bold text-amber-600">{{ importResult.skipped }}</p>
                </div>
                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800/50">
                    <span class="text-xs text-slate-500 dark:text-zinc-400">Failed</span>
                    <p class="mt-1 text-base font-bold text-rose-600">{{ importResult.failed }}</p>
                </div>
            </div>

            <div class="mt-8 flex justify-center gap-3">
                <button
                    type="button"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500"
                    @click="resetWorkflow"
                >
                    Import Another File
                </button>
            </div>
        </div>
    </div>
</template>
