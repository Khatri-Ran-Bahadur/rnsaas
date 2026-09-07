<script setup lang="ts">
import { computed } from 'vue';
import type { FileOrganizationConfig, FileNamingStrategy, DirectoryStructure } from '@/types/superadmin/file-system';

const props = defineProps<{
    modelValue: FileOrganizationConfig;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', val: FileOrganizationConfig): void;
}>();

const updateField = <K extends keyof FileOrganizationConfig>(key: K, value: FileOrganizationConfig[K]) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value,
    });
};

const namingOptions: Array<{ id: FileNamingStrategy; label: string; desc: string }> = [
    { id: 'uuid', label: 'UUID Only', desc: 'f47ac10b-58cc-4372-a567-0e02b2c3d479.pdf' },
    { id: 'original', label: 'Original Name', desc: 'employee_contract_john_doe.pdf' },
    { id: 'uuid_original', label: 'UUID + Original Name', desc: 'f47ac10b_employee_contract.pdf' },
];

const directoryOptions: Array<{ id: DirectoryStructure; label: string; desc: string; recommended?: boolean }> = [
    {
        id: 'tenant_module_date',
        label: 'Tenant / Module / Year / Month',
        desc: 'Strict multi-tenant security partition with temporal indexing.',
        recommended: true,
    },
    {
        id: 'tenant',
        label: 'By Tenant Only',
        desc: 'tenants/{tenant_id}/{filename}',
    },
    {
        id: 'module',
        label: 'By Module Only',
        desc: 'modules/{module_name}/{filename}',
    },
    {
        id: 'year_month',
        label: 'By Year / Month Only',
        desc: '{year}/{month}/{filename}',
    },
];

const sampleFilename = computed(() => {
    switch (props.modelValue.namingStrategy) {
        case 'original':
            return 'employment_agreement.pdf';
        case 'uuid_original':
            return '9b1deb4d_employment_agreement.pdf';
        case 'uuid':
        default:
            return '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d.pdf';
    }
});

const previewTreeLines = computed(() => {
    const file = sampleFilename.value;
    switch (props.modelValue.directoryStructure) {
        case 'tenant':
            return [
                'storage-root/',
                '  └── tenants/',
                '        └── 25/',
                `              └── ${file}`,
            ];
        case 'module':
            return [
                'storage-root/',
                '  └── hrm/',
                '        └── employee-documents/',
                `              └── ${file}`,
            ];
        case 'year_month':
            return [
                'storage-root/',
                '  └── 2026/',
                '        └── 09/',
                `              └── ${file}`,
            ];
        case 'tenant_module_date':
        default:
            return [
                'storage-root/',
                '  └── tenants/',
                '        └── 25/',
                '              └── hrm/',
                '                    └── employee-documents/',
                '                          └── 2026/',
                '                                └── 09/',
                `                                      └── ${file}`,
            ];
    }
});
</script>

<template>
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 mb-8">
        <div class="flex items-center gap-3 pb-4 border-b border-zinc-100 dark:border-zinc-800 mb-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                    File Naming & Organization
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Control how files are partitioned across storage disks and structured within directories.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Side: Selection Controls -->
            <div class="lg:col-span-7 space-y-6">
                <!-- 1. File Naming Strategy -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                        File Naming Strategy
                    </label>
                    <div class="space-y-2">
                        <label
                            v-for="opt in namingOptions"
                            :key="opt.id"
                            :class="[
                                'flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all',
                                modelValue.namingStrategy === opt.id
                                    ? 'border-primary-500 bg-primary-50/40 text-primary-950 dark:bg-primary-950/30 dark:border-primary-800 dark:text-white ring-1 ring-primary-500/30'
                                    : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-800/60 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300',
                            ]"
                        >
                            <input
                                type="radio"
                                name="namingStrategy"
                                :value="opt.id"
                                :checked="modelValue.namingStrategy === opt.id"
                                class="mt-0.5 text-primary-600 focus:ring-primary-500"
                                @change="updateField('namingStrategy', opt.id)"
                            />
                            <div>
                                <span class="text-xs font-bold block">{{ opt.label }}</span>
                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-mono">{{ opt.desc }}</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 2. Directory Structure -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2">
                        Directory Structure Partitioning
                    </label>
                    <div class="space-y-2">
                        <label
                            v-for="opt in directoryOptions"
                            :key="opt.id"
                            :class="[
                                'flex items-start justify-between p-3 rounded-xl border cursor-pointer transition-all',
                                modelValue.directoryStructure === opt.id
                                    ? 'border-primary-500 bg-primary-50/40 text-primary-950 dark:bg-primary-950/30 dark:border-primary-800 dark:text-white ring-1 ring-primary-500/30'
                                    : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-800/60 text-zinc-700 dark:text-zinc-300 hover:border-zinc-300',
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    type="radio"
                                    name="directoryStructure"
                                    :value="opt.id"
                                    :checked="modelValue.directoryStructure === opt.id"
                                    class="mt-0.5 text-primary-600 focus:ring-primary-500"
                                    @change="updateField('directoryStructure', opt.id)"
                                />
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold">{{ opt.label }}</span>
                                        <span
                                            v-if="opt.recommended"
                                            class="rounded-full bg-emerald-50 px-2 py-0.2 text-[10px] font-bold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800"
                                        >
                                            Recommended
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ opt.desc }}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Side: Live Dynamic Tree Preview -->
            <div class="lg:col-span-5 flex flex-col">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Live Storage Path Preview
                    </span>
                    <span class="text-[10px] uppercase font-bold text-zinc-400">Dynamic</span>
                </div>

                <div class="flex-1 rounded-xl bg-zinc-950 p-4 border border-zinc-800 text-zinc-200 font-mono text-xs overflow-x-auto shadow-inner flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 pb-3 mb-3 border-b border-zinc-800/80 text-[11px] text-zinc-500">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-500/80"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500/80"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500/80"></span>
                            <span class="ml-2">storage-partition-explorer</span>
                        </div>

                        <div class="space-y-1 text-emerald-400/90 leading-relaxed select-all">
                            <div v-for="(line, idx) in previewTreeLines" :key="idx">
                                {{ line }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-zinc-800/80 text-[11px] text-zinc-400">
                        <span class="text-zinc-500">Target path:</span>
                        <p class="text-zinc-300 truncate mt-0.5">
                            tenants/25/hrm/employee-documents/2026/09/{{ sampleFilename }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
