<script setup lang="ts">
import { ref } from 'vue';
import { Modal, Button } from '@/components';

interface ExemptionForm {
    id?: number | string;
    entity_type: 'customer' | 'vendor';
    entity_name: string;
    certificate_number: string;
    exemption_reason: string;
    effective_from: string;
    effective_until: string;
    notes: string;
}

const props = defineProps<{
    show: boolean;
    editingItem?: ExemptionForm | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', form: ExemptionForm): void;
}>();

const form = ref<ExemptionForm>({
    entity_type: 'customer',
    entity_name: '',
    certificate_number: '',
    exemption_reason: '',
    effective_from: new Date().toISOString().slice(0, 10),
    effective_until: '',
    notes: '',
});

const handleSave = () => {
    emit('save', form.value);
};
</script>

<template>
    <Modal
        :show="show"
        :title="editingItem ? 'Edit Tax Exemption Certificate' : 'Register Tax Exemption Certificate'"
        @close="emit('close')"
    >
        <form @submit.prevent="handleSave" class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                        Entity Type
                    </label>
                    <select
                        v-model="form.entity_type"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                    >
                        <option value="customer">Customer (Sales Exemption)</option>
                        <option value="vendor">Vendor (Purchase Exemption)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                        Certificate Number <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.certificate_number"
                        type="text"
                        placeholder="e.g. EXP-2026-981"
                        required
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white font-mono"
                    />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                    Customer / Vendor Legal Name <span class="text-rose-500">*</span>
                </label>
                <input
                    v-model="form.entity_name"
                    type="text"
                    placeholder="e.g. Apex Global Logistics Pte Ltd"
                    required
                    class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                />
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                    Statutory Exemption Reason <span class="text-rose-500">*</span>
                </label>
                <input
                    v-model="form.exemption_reason"
                    type="text"
                    placeholder="e.g. Designated Free Commercial Zone, Export Consignment, Government Ministry"
                    required
                    class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                        Effective From <span class="text-rose-500">*</span>
                    </label>
                    <input
                        v-model="form.effective_from"
                        type="date"
                        required
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                        Effective Until / Expiry
                    </label>
                    <input
                        v-model="form.effective_until"
                        type="date"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                    />
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase mb-1">
                    Audit Verification Notes
                </label>
                <textarea
                    v-model="form.notes"
                    rows="2"
                    placeholder="Reference to gazette order, ministry approval, or customs verification details..."
                    class="w-full px-3 py-2 text-xs bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg focus:ring-1 focus:ring-zinc-900 dark:text-white"
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <Button type="button" variant="ghost" size="sm" @click="emit('close')">
                    Cancel
                </Button>
                <Button type="submit" variant="primary" size="sm">
                    Save Certificate
                </Button>
            </div>
        </form>
    </Modal>
</template>
