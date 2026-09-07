<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';
import Switch from '@/components/Switch.vue';
import FileUploader from '@/components/FileUploader.vue';
import type {
    EmployeeDocumentItem,
    DocumentStaffOption,
    DocumentTypeOption,
} from '../../types/employee-documents';

const props = defineProps<{
    document: { data: EmployeeDocumentItem };
    staff_members: Array<{ id: number; public_id: string; name: string; employee_code: string }>;
    document_types: DocumentTypeOption[];
}>();

const doc = props.document.data;

// Find staff ID from public_id or matching staff in staff_members
const currentStaffId = props.staff_members.find(
    (s) => s.public_id === doc.staff?.public_id || s.employee_code === doc.staff?.employee_code
)?.id ?? '';

const staffOptions = computed(() => [
    ...props.staff_members.map((s) => ({
        label: `${s.name} (${s.employee_code})`,
        value: s.id,
    })),
]);

const form = useForm({
    tenant_staff_id: currentStaffId as number | '',
    type: doc.type.value as EmployeeDocumentTypeValue,
    title: doc.title || '',
    document_number: doc.document_number || '',
    issue_date: doc.issue_date || '',
    expiry_date: doc.expiry_date || '',
    file: null as File | null,
    notes: doc.notes || '',
    is_active: doc.is_active,
});

const submit = () => {
    // In Laravel, file uploads via PUT require POST with _method spoofing
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(`/admin/hrm/employee-documents/${doc.public_id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Edit Employee Document"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Employee Documents', href: '/admin/hrm/employee-documents' },
            { label: doc.title, href: `/admin/hrm/employee-documents/${doc.public_id}` },
            { label: 'Edit' },
        ]"
    >
        <Head :title="`Edit ${doc.title} - HRM`" />

        <div class="px-4 py-6 sm:px-8 max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Edit Document Metadata
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Update document validity dates, categorization, or replace the attached file.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="`/admin/hrm/employee-documents/${doc.public_id}`">
                        <Button variant="secondary" size="sm">
                            View Details
                        </Button>
                    </Link>
                    <Link href="/admin/hrm/employee-documents">
                        <Button variant="secondary" size="sm">
                            Back to List
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Form Card -->
            <form class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-6" @submit.prevent="submit">
                <!-- Section 1: Staff & Type -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <!-- Staff Member -->
                    <div>
                        <Select
                            v-model="form.tenant_staff_id"
                            label="Staff Member"
                            :options="staffOptions"
                            placeholder="Select an employee..."
                            :error="form.errors.tenant_staff_id"
                            required
                        />
                    </div>

                    <!-- Document Type -->
                    <div>
                        <Select
                            v-model="form.type"
                            label="Document Type"
                            :options="document_types"
                            placeholder="Select document category..."
                            :error="form.errors.type"
                            required
                        />
                    </div>
                </div>

                <!-- Section 2: Title & Document Number -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <TextInput
                            v-model="form.title"
                            label="Document Title"
                            placeholder="e.g. 2026 Employment Agreement"
                            :error="form.errors.title"
                            required
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model="form.document_number"
                            label="Document Number / ID (Optional)"
                            placeholder="e.g. PASSPORT-88219"
                            :error="form.errors.document_number"
                        />
                    </div>
                </div>

                <!-- Section 3: Issue & Expiry Dates -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <DatePicker
                            v-model="form.issue_date"
                            label="Issue Date"
                            placeholder="Select issue date..."
                            :error="form.errors.issue_date"
                        />
                    </div>

                    <div>
                        <DatePicker
                            v-model="form.expiry_date"
                            label="Expiry Date"
                            placeholder="Select expiry date..."
                            :error="form.errors.expiry_date"
                        />
                    </div>
                </div>

                <!-- Section 4: File Replacement with Client Compression -->
                <div class="border-t border-zinc-100 pt-5 dark:border-zinc-800">
                    <FileUploader
                        v-model="form.file"
                        label="Replace Document File (Optional)"
                        hint="Leave empty to keep the currently attached file."
                        :current-file-name="doc.file.name"
                        :current-file-size="doc.file.size"
                        :current-file-url="doc.file.download_url"
                        :max-size-mb="10"
                        :allowed-extensions="['pdf', 'jpg', 'jpeg', 'png', 'webp']"
                        :enable-compression="true"
                        :error="form.errors.file"
                    />
                </div>

                <!-- Section 5: Active Status & Notes -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50/50 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/50">
                        <div>
                            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Document Active Status</p>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Controls whether this document is recognized as valid and active.</p>
                        </div>
                        <Switch v-model="form.is_active" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Internal Notes (Optional)
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            placeholder="Add any specific context, verification notes, or remarks..."
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3.5 py-2.5 text-xs text-zinc-800 focus:border-primary-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                            :class="{ 'border-rose-400 ring-1 ring-rose-400/20': form.errors.notes }"
                        ></textarea>
                        <p v-if="form.errors.notes" class="mt-1 text-xs text-rose-500">
                            {{ form.errors.notes }}
                        </p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <Link :href="`/admin/hrm/employee-documents/${doc.public_id}`">
                        <Button variant="secondary" type="button" :disabled="form.processing">
                            Cancel
                        </Button>
                    </Link>
                    <Button variant="primary" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving Changes...' : 'Update Document' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
