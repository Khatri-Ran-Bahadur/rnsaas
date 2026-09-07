<script setup lang="ts">
import Modal from '@/components/Modal.vue';
import Button from '@/components/Button.vue';
import type { EmployeeDocumentItem } from '../../types/employee-documents';

defineProps<{
    show: boolean;
    doc: EmployeeDocumentItem | null;
    isDeleting: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'confirm'): void;
}>();
</script>

<template>
    <Modal
        :show="show"
        title="Delete Employee Document"
        description="Are you sure you want to permanently delete this document? This action cannot be undone."
        max-width="md"
        @close="emit('close')"
    >
        <div class="space-y-4">
            <div v-if="doc" class="rounded-lg bg-rose-50 p-3 text-xs text-rose-800 dark:bg-rose-950/50 dark:text-rose-200">
                Deleting <strong>{{ doc.title }}</strong> for {{ doc.staff?.name }}.
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <Button variant="secondary" :disabled="isDeleting" @click="emit('close')">
                    Cancel
                </Button>
                <Button variant="danger" :disabled="isDeleting" @click="emit('confirm')">
                    {{ isDeleting ? 'Deleting...' : 'Delete Permanently' }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
