<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Select } from '@/components';

interface TenantSummary {
    id: number;
    name: string;
    slug: string;
}

interface EmailSettings {
    enable_custom_smtp: boolean;
    mail_driver: string;
    mail_host: string;
    mail_port: string;
    mail_username: string;
    mail_password?: string;
    mail_encryption: 'tls' | 'ssl' | 'none';
    mail_from_address: string;
    mail_from_name: string;
    has_mail_password?: boolean;
}

interface EmailNotifications {
    new_user: boolean;
    customer_invoice_send: boolean;
    payment_reminder: boolean;
    invoice_payment_create: boolean;
    proposal_status_updated: boolean;
    new_helpdesk_ticket: boolean;
    new_helpdesk_ticket_reply: boolean;
    purchase_send: boolean;
    purchase_payment_create: boolean;
}

interface BankTransfer {
    enable_bank_transfer: boolean;
    bank_details: string;
}

interface AiSettings {
    is_enabled: boolean;
    ai_provider: 'gemini' | 'openai' | 'groq';
    ai_model: string;
    ai_api_key?: string;
    has_ai_api_key?: boolean;
    custom_system_prompt?: string;
}

const props = defineProps<{
    tenant: TenantSummary;
    email_settings: EmailSettings;
    email_notifications: EmailNotifications;
    bank_transfer: BankTransfer;
    ai_settings?: AiSettings;
    is_ai_module_enabled?: boolean;
}>();

const activeTab = ref<'notifications' | 'smtp' | 'bank' | 'ai'>('notifications');
const showPassword = ref(false);
const showAiKey = ref(false);
const testEmailAddress = ref('');
const isTestingEmail = ref(false);
const testMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

const isTestingAi = ref(false);
const aiTestMessage = ref<{ type: 'success' | 'error'; text: string } | null>(null);

const defaultAiSettings: AiSettings = {
    is_enabled: true,
    ai_provider: 'gemini',
    ai_model: 'gemini-3.6-flash',
    ai_api_key: '',
    custom_system_prompt: '',
    has_ai_api_key: false,
};

const form = useForm({
    email_settings: { ...props.email_settings, mail_password: '' },
    email_notifications: { ...props.email_notifications },
    bank_transfer: { ...props.bank_transfer },
    ai_settings: { ...defaultAiSettings, ...(props.ai_settings || {}), ai_api_key: '' },
});

const submitForm = () => {
    form.put('/admin/company-settings', {
        preserveScroll: true,
    });
};

const sendTestAi = async () => {
    isTestingAi.value = true;
    aiTestMessage.value = null;

    try {
        const response = await fetch('/admin/company-settings/ai/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                ai_provider: form.ai_settings.ai_provider,
                ai_model: form.ai_settings.ai_model,
                ai_api_key: form.ai_settings.ai_api_key || null,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            aiTestMessage.value = { type: 'success', text: data.message };
        } else {
            aiTestMessage.value = { type: 'error', text: data.message || 'AI Connection failed.' };
        }
    } catch (e: any) {
        aiTestMessage.value = { type: 'error', text: e.message || 'Network error while testing AI connection.' };
    } finally {
        isTestingAi.value = false;
    }
};

const sendTest = async () => {
    if (!testEmailAddress.value) {
        testMessage.value = { type: 'error', text: 'Please enter a recipient email address.' };
        return;
    }

    isTestingEmail.value = true;
    testMessage.value = null;

    try {
        const response = await fetch('/admin/company-settings/email/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ test_email: testEmailAddress.value }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            testMessage.value = { type: 'success', text: data.message };
        } else {
            testMessage.value = { type: 'error', text: data.message || 'Failed to send test email.' };
        }
    } catch (e: any) {
        testMessage.value = { type: 'error', text: e.message || 'Network error while testing email.' };
    } finally {
        isTestingEmail.value = false;
    }
};

const notificationItems = [
    {
        key: 'new_user' as keyof EmailNotifications,
        label: 'New User',
        description: 'Send welcoming credentials and login details when a new team member is added or invited.',
    },
    {
        key: 'customer_invoice_send' as keyof EmailNotifications,
        label: 'Customer Invoice Send',
        description: 'Automatically dispatch PDF invoice and payment link when sales invoice is finalized.',
    },
    {
        key: 'payment_reminder' as keyof EmailNotifications,
        label: 'Payment Reminder',
        description: 'Notify customers with gentle automated payment reminder prior to or upon due date.',
    },
    {
        key: 'invoice_payment_create' as keyof EmailNotifications,
        label: 'Invoice Payment Create',
        description: 'Send instant receipt confirmation when customer payment is logged or received.',
    },
    {
        key: 'proposal_status_updated' as keyof EmailNotifications,
        label: 'Proposal Status Updated',
        description: 'Notify sales team and customer whenever quotation or proposal status changes.',
    },
    {
        key: 'new_helpdesk_ticket' as keyof EmailNotifications,
        label: 'New Helpdesk Ticket',
        description: 'Alert support coordinators and assignees when a new customer support ticket is opened.',
    },
    {
        key: 'new_helpdesk_ticket_reply' as keyof EmailNotifications,
        label: 'New Helpdesk Ticket Reply',
        description: 'Send real-time email notification when staff or customer posts a ticket response.',
    },
    {
        key: 'purchase_send' as keyof EmailNotifications,
        label: 'Purchase Send',
        description: 'Transmit purchase orders with specifications directly to suppliers and vendors.',
    },
    {
        key: 'purchase_payment_create' as keyof EmailNotifications,
        label: 'Purchase Payment Create',
        description: 'Notify suppliers and finance department when a purchase payout is registered.',
    },
];
</script>

<template>
    <OrganizationLayout title="Email & Notification Settings">
        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2.5">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Company Email & Notifications
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Configure transactional notification triggers, custom SMTP server, and bank payment instructions for {{ tenant.name }}.
                    </p>
                </div>
                <div>
                    <button
                        @click="submitForm"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-xs transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>{{ form.processing ? 'Saving...' : 'Save Settings' }}</span>
                    </button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8">
                    <button
                        @click="activeTab = 'notifications'"
                        :class="[
                            'pb-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer',
                            activeTab === 'notifications'
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span>Email Notifications</span>
                    </button>

                    <button
                        @click="activeTab = 'smtp'"
                        :class="[
                            'pb-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer',
                            activeTab === 'smtp'
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                        </svg>
                        <span>Custom SMTP Server</span>
                    </button>

                    <button
                        @click="activeTab = 'bank'"
                        :class="[
                            'pb-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer',
                            activeTab === 'bank'
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span>Bank Transfer Details</span>
                    </button>

                    <button
                        @click="activeTab = 'ai'"
                        :class="[
                            'pb-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer',
                            activeTab === 'ai'
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span class="flex items-center gap-1.5">
                            AI Assistant
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">New</span>
                        </span>
                    </button>
                </nav>
            </div>

            <form @submit.prevent="submitForm">
                <!-- TAB 1: EMAIL NOTIFICATIONS (MATCHING SCREENSHOT 3) -->
                <div v-if="activeTab === 'notifications'" class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                Email Notification Triggers
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Control which automated transactional emails are sent to users, customers, and vendors.
                            </p>
                        </div>

                        <div class="divide-y divide-gray-100 p-2">
                            <div
                                v-for="item in notificationItems"
                                :key="item.key"
                                class="flex items-center justify-between p-4 hover:bg-gray-50/75 rounded-xl transition-colors"
                            >
                                <div class="pr-6">
                                    <p class="text-sm font-semibold text-gray-900">{{ item.label }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ item.description }}</p>
                                </div>
                                <div class="shrink-0">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            v-model="form.email_notifications[item.key]"
                                            type="checkbox"
                                            class="sr-only peer"
                                        />
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: CUSTOM SMTP -->
                <div v-if="activeTab === 'smtp'" class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-6">
                        <div class="flex items-start justify-between pb-6 border-b border-gray-100">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                    </svg>
                                    Custom SMTP Configuration
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    By default, emails are delivered using the platform's verified mail relay. Enable this option if you want to deliver emails through your own corporate SMTP server.
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                                <input
                                    v-model="form.email_settings.enable_custom_smtp"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div v-if="form.email_settings.enable_custom_smtp" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mail Driver</label>
                                    <input
                                        v-model="form.email_settings.mail_driver"
                                        type="text"
                                        class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600 font-mono"
                                        readonly
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mail Host *</label>
                                    <input
                                        v-model="form.email_settings.mail_host"
                                        type="text"
                                        placeholder="e.g. smtp.mailgun.org or smtp.gmail.com"
                                        class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mail Port *</label>
                                    <input
                                        v-model="form.email_settings.mail_port"
                                        type="text"
                                        placeholder="587"
                                        class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                                        required
                                    />
                                </div>
                                <div>
                                    <Select
                                        v-model="form.email_settings.mail_encryption"
                                        label="Encryption"
                                        :options="[
                                            { label: 'TLS (Port 587)', value: 'tls' },
                                            { label: 'SSL (Port 465)', value: 'ssl' },
                                            { label: 'None / Plain', value: 'none' },
                                        ]"
                                        size="sm"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mail Username *</label>
                                    <input
                                        v-model="form.email_settings.mail_username"
                                        type="text"
                                        placeholder="user@yourdomain.com"
                                        class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">Mail Password</label>
                                    <div class="relative">
                                        <input
                                            v-model="form.email_settings.mail_password"
                                            :type="showPassword ? 'text' : 'password'"
                                            :placeholder="email_settings.has_mail_password ? 'Leave blank to keep current password' : 'SMTP password'"
                                            class="w-full px-3.5 py-2 pr-10 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer"
                                        >
                                            <svg v-if="showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">From Email Address *</label>
                                    <input
                                        v-model="form.email_settings.mail_from_address"
                                        type="email"
                                        placeholder="billing@yourcompany.com"
                                        class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">From Name *</label>
                                    <input
                                        v-model="form.email_settings.mail_from_name"
                                        type="text"
                                        placeholder="Your Company Name"
                                        class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Test Mail Section -->
                        <div class="pt-6 border-t border-gray-100">
                            <h4 class="text-sm font-bold text-gray-900 mb-1 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                Send Test Email
                            </h4>
                            <p class="text-xs text-gray-500 mb-3">
                                Test your current mail delivery setup by sending an automated diagnostic message.
                            </p>

                            <div class="flex flex-col sm:flex-row items-center gap-3">
                                <input
                                    v-model="testEmailAddress"
                                    type="email"
                                    placeholder="your-email@domain.com"
                                    class="w-full sm:w-80 px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                />
                                <button
                                    type="button"
                                    @click="sendTest"
                                    :disabled="isTestingEmail"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    <span>{{ isTestingEmail ? 'Sending...' : 'Send Test Mail' }}</span>
                                </button>
                            </div>

                            <div v-if="testMessage" class="mt-3">
                                <div
                                    :class="[
                                        'p-3 rounded-lg text-xs flex items-center gap-2',
                                        testMessage.type === 'success'
                                            ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                                            : 'bg-red-50 text-red-800 border border-red-200'
                                    ]"
                                >
                                    <svg v-if="testMessage.type === 'success'" class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ testMessage.text }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: BANK TRANSFER -->
                <div v-if="activeTab === 'bank'" class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-6">
                        <div class="flex items-start justify-between pb-6 border-b border-gray-100">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    Bank Transfer & Wire Details
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Allow customers to pay invoices via direct bank deposit or wire transfer. Instructions will appear on PDF invoices and payment checkout pages.
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                                <input
                                    v-model="form.bank_transfer.enable_bank_transfer"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                                Bank Account Wire Instructions *
                            </label>
                            <textarea
                                v-model="form.bank_transfer.bank_details"
                                rows="6"
                                placeholder="Bank Name: Maybank / JPMorgan Chase&#10;Account Name: SathiSaaS Technologies Sdn Bhd&#10;Account Number: 5140 1200 9981&#10;Swift / BIC: MBBEMYKL&#10;Reference: Please include your Invoice Number in the transfer remarks."
                                class="w-full px-3.5 py-3 border border-gray-300 rounded-xl text-sm font-mono text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            ></textarea>
                            <p class="text-xs text-gray-400 mt-1.5">
                                Formatted as plain text. Will be rendered with preserved line breaks on invoice footers and payment portals.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: AI ASSISTANT & COPILOT -->
                <div v-if="activeTab === 'ai'" class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 space-y-6">
                        <!-- Header & Master Toggle -->
                        <div class="flex items-start justify-between pb-6 border-b border-gray-100">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    AI Agent & Copilot Integration
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Configure your company-specific AI credentials. The AI Copilot can read live Accounting, Inventory, and POS data to generate real-time reports and insights.
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                                <input
                                    v-model="form.ai_settings.is_enabled"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>

                        <!-- Provider Selection Grid -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-3">
                                Select AI Provider
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Gemini -->
                                <div
                                    @click="form.ai_settings.ai_provider = 'gemini'; form.ai_settings.ai_model = 'gemini-3.6-flash'"
                                    :class="[
                                        'p-4 rounded-xl border-2 transition-all cursor-pointer flex flex-col justify-between',
                                        form.ai_settings.ai_provider === 'gemini'
                                            ? 'border-purple-600 bg-purple-50/50 shadow-xs ring-1 ring-purple-600'
                                            : 'border-gray-200 hover:border-gray-300 bg-white'
                                    ]"
                                >
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-sm text-gray-900">Google Gemini</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Free Tier</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1.5">
                                            15 Requests/min free tier. Large context window & excellent Nepali understanding.
                                        </p>
                                    </div>
                                    <div class="mt-3 text-[11px] font-medium text-purple-700">Recommended for Startups</div>
                                </div>

                                <!-- Groq -->
                                <div
                                    @click="form.ai_settings.ai_provider = 'groq'; form.ai_settings.ai_model = 'llama-3.3-70b-versatile'"
                                    :class="[
                                        'p-4 rounded-xl border-2 transition-all cursor-pointer flex flex-col justify-between',
                                        form.ai_settings.ai_provider === 'groq'
                                            ? 'border-purple-600 bg-purple-50/50 shadow-xs ring-1 ring-purple-600'
                                            : 'border-gray-200 hover:border-gray-300 bg-white'
                                    ]"
                                >
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-sm text-gray-900">Groq Cloud (Llama 3)</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-blue-800">Ultra Fast</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1.5">
                                            Free tier available. Fastest token generation speed (500+ tok/s).
                                        </p>
                                    </div>
                                    <div class="mt-3 text-[11px] font-medium text-blue-700">High Performance</div>
                                </div>

                                <!-- OpenAI -->
                                <div
                                    @click="form.ai_settings.ai_provider = 'openai'; form.ai_settings.ai_model = 'gpt-4o-mini'"
                                    :class="[
                                        'p-4 rounded-xl border-2 transition-all cursor-pointer flex flex-col justify-between',
                                        form.ai_settings.ai_provider === 'openai'
                                            ? 'border-purple-600 bg-purple-50/50 shadow-xs ring-1 ring-purple-600'
                                            : 'border-gray-200 hover:border-gray-300 bg-white'
                                    ]"
                                >
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-sm text-gray-900">OpenAI</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-zinc-100 text-zinc-800">Standard</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1.5">
                                            Industry standard reasoning with GPT-4o-mini and GPT-4o.
                                        </p>
                                    </div>
                                    <div class="mt-3 text-[11px] font-medium text-zinc-700">Commercial Grade</div>
                                </div>
                            </div>
                        </div>

                        <!-- Model Selection -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                                Model Version
                            </label>
                            <select
                                v-model="form.ai_settings.ai_model"
                                class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            >
                                <template v-if="form.ai_settings.ai_provider === 'gemini'">
                                    <option value="gemini-3.6-flash">gemini-3.6-flash (Recommended - Latest & Fastest)</option>
                                    <option value="gemini-3.6-pro">gemini-3.6-pro (Deep Reasoning)</option>
                                    <option value="gemini-2.5-flash">gemini-2.5-flash</option>
                                    <option value="gemini-2.0-flash">gemini-2.0-flash</option>
                                    <option value="gemini-1.5-flash-latest">gemini-1.5-flash-latest</option>
                                </template>
                                <template v-else-if="form.ai_settings.ai_provider === 'groq'">
                                    <option value="llama-3.3-70b-versatile">llama-3.3-70b-versatile (Recommended)</option>
                                    <option value="llama-3.1-8b-instant">llama-3.1-8b-instant (Fastest)</option>
                                    <option value="mixtral-8x7b-32768">mixtral-8x7b-32768</option>
                                </template>
                                <template v-else>
                                    <option value="gpt-4o-mini">gpt-4o-mini (Fast & Cost-Effective)</option>
                                    <option value="gpt-4o">gpt-4o (Most Capable)</option>
                                </template>
                            </select>
                        </div>

                        <!-- API Key Input -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    {{ form.ai_settings.ai_provider.toUpperCase() }} API Key
                                </label>
                                <span v-if="form.ai_settings.has_ai_api_key" class="inline-flex items-center gap-1.5 text-xs text-emerald-600 font-medium">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Key Saved & Encrypted (AES-256)
                                </span>
                            </div>
                            <div class="relative">
                                <input
                                    v-model="form.ai_settings.ai_api_key"
                                    :type="showAiKey ? 'text' : 'password'"
                                    :placeholder="form.ai_settings.has_ai_api_key ? '•••••••••••••••••••••••••••••••• (Leave blank to keep existing key)' : 'Enter your API key here...'"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm font-mono text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 pr-10"
                                />
                                <button
                                    type="button"
                                    @click="showAiKey = !showAiKey"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
                                >
                                    <svg v-if="!showAiKey" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1.5 flex items-center justify-between">
                                <span>Keys are encrypted with AES-256 before saving to the database.</span>
                                <a
                                    v-if="form.ai_settings.ai_provider === 'gemini'"
                                    href="https://aistudio.google.com/app/apikey"
                                    target="_blank"
                                    class="text-purple-600 hover:underline inline-flex items-center gap-1 font-medium"
                                >
                                    Get Free Gemini API Key &rarr;
                                </a>
                                <a
                                    v-else-if="form.ai_settings.ai_provider === 'groq'"
                                    href="https://console.groq.com/keys"
                                    target="_blank"
                                    class="text-purple-600 hover:underline inline-flex items-center gap-1 font-medium"
                                >
                                    Get Free Groq Key &rarr;
                                </a>
                            </p>
                        </div>

                        <!-- Custom Instructions Prompt -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                                Custom AI Personality & System Instructions (Optional)
                            </label>
                            <textarea
                                v-model="form.ai_settings.custom_system_prompt"
                                rows="3"
                                placeholder="E.g. You are an expert chartered accountant in Nepal. Prioritize Nepali VAT tax compliance when discussing sales and purchase bills."
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                            ></textarea>
                        </div>

                        <!-- Live Test Connection Box -->
                        <div class="pt-4 border-t border-gray-100">
                            <div class="bg-purple-50/60 rounded-xl p-4 border border-purple-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">Verify AI Connection</h4>
                                    <p class="text-xs text-gray-600 mt-0.5">
                                        Sends a lightweight ping test to verify that the selected provider and API key are active.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="sendTestAi"
                                    :disabled="isTestingAi"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors disabled:opacity-50 cursor-pointer shrink-0"
                                >
                                    <svg v-if="isTestingAi" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ isTestingAi ? 'Testing...' : 'Test AI Connection' }}</span>
                                </button>
                            </div>

                            <!-- Test Message Feedback -->
                            <div v-if="aiTestMessage" class="mt-3">
                                <div
                                    :class="[
                                        'p-3 rounded-xl text-xs flex items-center gap-2',
                                        aiTestMessage.type === 'success'
                                            ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                                            : 'bg-rose-50 text-rose-800 border border-rose-200'
                                    ]"
                                >
                                    <svg v-if="aiTestMessage.type === 'success'" class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ aiTestMessage.text }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Save Bar -->
                <div class="pt-6 flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-xs transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <span>{{ form.processing ? 'Saving...' : 'Save All Settings' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </OrganizationLayout>
</template>
