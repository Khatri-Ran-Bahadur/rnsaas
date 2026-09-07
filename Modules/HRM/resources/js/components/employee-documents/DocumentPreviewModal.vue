<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';
import { formatFileSize } from '@/utils/fileCompressor';
import type { EmployeeDocumentItem } from '../../types/employee-documents';

defineProps<{
    show: boolean;
    doc: EmployeeDocumentItem | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const formatDate = (dateStr?: string | null) => {
    if (!dateStr) return '—';
    try {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }).format(new Date(dateStr));
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Modal
        :show="show"
        :title="doc?.title || 'Document Preview'"
        :description="`Uploaded for ${doc?.staff?.name} (${doc?.staff?.employee_code})`"
        max-width="2xl"
        @close="emit('close')"
    >
        <div v-if="doc" class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/50">
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-zinc-400">Document Type:</span>
                        <span class="ml-1.5 font-medium text-zinc-800 dark:text-zinc-200">{{ doc.type?.label || doc.document_type }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-400">Document Number:</span>
                        <span class="ml-1.5 font-mono text-zinc-800 dark:text-zinc-200">{{ doc.document_number || '—' }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-400">Issue Date:</span>
                        <span class="ml-1.5 text-zinc-800 dark:text-zinc-200">{{ formatDate(doc.issue_date) }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-400">Expiry Date:</span>
                        <span class="ml-1.5 text-zinc-800 dark:text-zinc-200">{{ formatDate(doc.expiry_date) }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-400">File Name:</span>
                        <span class="ml-1.5 truncate text-zinc-800 dark:text-zinc-200 font-mono">{{ doc.file?.name || doc.file_name }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-400">File Size:</span>
                        <span class="ml-1.5 text-zinc-800 dark:text-zinc-200">{{ formatFileSize(doc.file?.size) }}</span>
                    </div>
                </div>
            </div>

            <div v-if="doc.notes || doc.description" class="rounded-lg bg-zinc-100 p-3 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                <span class="font-semibold block mb-1">Notes:</span>
                {{ doc.notes || doc.description }}
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <Button variant="secondary" @click="emit('close')">
                    Close
                </Button>
                <a
                    v-if="doc.file?.download_url || doc.file_url"
                    :href="doc.file?.download_url || doc.file_url"
                    target="_blank"
                >
                    <Button variant="primary">
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Document
                    </Button>
                </a>
            </div>
        </div>
    </Modal>
</template>
