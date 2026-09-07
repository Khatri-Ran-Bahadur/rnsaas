<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import Button from '@/components/Button.vue';
import TextInput from '@/components/TextInput.vue';
import Select from '@/components/Select.vue';
import DatePicker from '@/components/DatePicker.vue';
import FileUploader from '@/components/FileUploader.vue';
import type { DocumentTypeOption, EmployeeDocumentTypeValue } from '../../types/employee-document';

const props = defineProps<{
    staff_members: Array<{ id: number; public_id: string; name: string; employee_code: string }>;
    document_types: DocumentTypeOption[];
}>();

const staffOptions = computed(() => [
    ...props.staff_members.map((s) => ({
        label: `${s.name} (${s.employee_code})`,
        value: s.id,
    })),
]);

const form = useForm({
    tenant_staff_id: '' as number | '',
    type: '' as EmployeeDocumentTypeValue | '',
    title: '',
    document_number: '',
    issue_date: '',
    expiry_date: '',
    file: null as File | null,
    notes: '',
});

const submit = () => {
    form.post('/admin/hrm/employee-documents', {
        preserveScroll: true,
    });
};
</script>

<template>
    <OrganizationLayout
        title="Upload Employee Document"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin' },
            { label: 'HRM', href: '/admin/staff' },
            { label: 'Employee Documents', href: '/admin/hrm/employee-documents' },
            { label: 'Upload' },
        ]"
    >
        <Head title="Upload Employee Document - HRM" />

        <div class="w-full space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Upload Employee Document
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Attach a new legal contract, national identity card, credential, or work certification.
                    </p>
                </div>

                <Link href="/admin/hrm/employee-documents">
                    <Button variant="secondary" size="sm">
                        Back to List
                    </Button>
                </Link>
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
                            placeholder="e.g. 2026 Employment Agreement, Master Degree Certificate"
                            :error="form.errors.title"
                            required
                        />
                    </div>

                    <div>
                        <TextInput
                            v-model="form.document_number"
                            label="Document Number / ID (Optional)"
                            placeholder="e.g. CIT-0982-KTM, PASSPORT-88219"
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

                <!-- Section 4: File Upload with Client Compression -->
                <div class="border-t border-zinc-100 pt-5 dark:border-zinc-800">
                    <FileUploader
                        v-model="form.file"
                        label="Attach Document File"
                        hint="Supports PDF, JPG, PNG, WEBP (Max 10MB)"
                        :max-size-mb="10"
                        :allowed-extensions="['pdf', 'jpg', 'jpeg', 'png', 'webp']"
                        :enable-compression="true"
                        :required="true"
                        :error="form.errors.file"
                    />
                </div>

                <!-- Section 5: Notes -->
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

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <Link href="/admin/hrm/employee-documents">
                        <Button variant="secondary" type="button" :disabled="form.processing">
                            Cancel
                        </Button>
                    </Link>
                    <Button variant="primary" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Uploading Document...' : 'Save & Upload Document' }}
                    </Button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
