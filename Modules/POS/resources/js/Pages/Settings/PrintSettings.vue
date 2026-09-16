<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import OrganizationLayout from '@/layouts/OrganizationLayout.vue';
import { Card, Badge, Button, Modal } from '@/components';

export interface PrinterDevice {
    id: number;
    name: string;
    role: 'receipt' | 'kitchen_hot' | 'kitchen_bar' | 'label' | 'report';
    connection_type: 'network_tcp' | 'usb' | 'bluetooth' | 'serial_com' | 'browser_direct';
    target: string;
    paper_width: '80mm' | '58mm' | 'a4' | 'label_50x30';
    font_family: string;
    font_scale: string;
    auto_cut: boolean;
    cash_drawer_pulse: boolean;
    print_copies: number;
    status: 'online' | 'offline' | 'warning';
}

export interface HardwareSettings {
    direct_browser_print: boolean;
    silent_background_print: boolean;
    drawer_pulse_signal: string;
    auto_print_on_payment_complete: boolean;
    auto_print_kitchen_tickets: boolean;
    customer_display_enabled: boolean;
    customer_display_port: string;
}

const props = defineProps<{
    printers: PrinterDevice[];
    hardwareSettings: HardwareSettings;
}>();

const printerList = ref<PrinterDevice[]>([...props.printers]);
const hardwareConfig = ref<HardwareSettings>({ ...props.hardwareSettings });
const showAddPrinterModal = ref(false);

const newPrinter = ref<Partial<PrinterDevice>>({
    name: '',
    role: 'receipt',
    connection_type: 'network_tcp',
    target: '192.168.1.200:9100',
    paper_width: '80mm',
    font_family: 'monospace',
    font_scale: '100%',
    auto_cut: true,
    cash_drawer_pulse: true,
    print_copies: 1,
    status: 'online',
});

const handleSaveConfig = () => {
    alert('Hardware and printer routing settings saved successfully!');
};

const handleTestPulse = (printer: PrinterDevice) => {
    alert(`Sending test print and cash drawer pulse command to ${printer.name} at ${printer.target}...`);
};

const handleAddPrinter = () => {
    if (!newPrinter.value.name) {
        alert('Please enter a printer name');
        return;
    }
    printerList.value.push({
        id: Date.now(),
        name: newPrinter.value.name,
        role: newPrinter.value.role as any,
        connection_type: newPrinter.value.connection_type as any,
        target: newPrinter.value.target || '192.168.1.200:9100',
        paper_width: newPrinter.value.paper_width as any,
        font_family: 'monospace',
        font_scale: '100%',
        auto_cut: !!newPrinter.value.auto_cut,
        cash_drawer_pulse: !!newPrinter.value.cash_drawer_pulse,
        print_copies: Number(newPrinter.value.print_copies) || 1,
        status: 'online',
    });
    showAddPrinterModal.value = false;
    newPrinter.value = {
        name: '',
        role: 'receipt',
        connection_type: 'network_tcp',
        target: '192.168.1.200:9100',
        paper_width: '80mm',
        auto_cut: true,
        cash_drawer_pulse: true,
        print_copies: 1,
        status: 'online',
    };
};
</script>

<template>
    <Head title="Hardware & Printer Setup - SathiSaaS POS" />

    <OrganizationLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-xs text-zinc-500 mb-1">
                        <Link href="/admin/pos" class="hover:text-emerald-600 transition">POS Register</Link>
                        <span>/</span>
                        <Link href="/admin/pos/receipt-templates" class="hover:text-emerald-600 transition">Receipt Setup</Link>
                        <span>/</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-medium">Hardware & Printers</span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2.5">
                        <span>Hardware, Peripherals & Thermal Printers</span>
                    </h1>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Configure ESC/POS thermal printers, network socket print servers, cash drawers, and customer-facing pole displays.
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <Button variant="primary" size="sm" @click="showAddPrinterModal = true">
                        + Add Printer Device
                    </Button>
                </div>
            </div>

            <!-- Global Hardware Options -->
            <Card class="p-5 space-y-4">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                    <span>General Print & Peripheral Automation</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                    <label class="flex items-start space-x-3 p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                        <input type="checkbox" v-model="hardwareConfig.auto_print_on_payment_complete" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500" />
                        <div>
                            <div class="font-bold text-zinc-900 dark:text-white">Auto-Print on Payment</div>
                            <div class="text-[11px] text-zinc-500">Automatically sends thermal slip upon checkout tender</div>
                        </div>
                    </label>

                    <label class="flex items-start space-x-3 p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                        <input type="checkbox" v-model="hardwareConfig.auto_print_kitchen_tickets" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500" />
                        <div>
                            <div class="font-bold text-zinc-900 dark:text-white">Auto-Route Kitchen Slips</div>
                            <div class="text-[11px] text-zinc-500">Prints order slips to Kitchen & Bar printers automatically</div>
                        </div>
                    </label>

                    <label class="flex items-start space-x-3 p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                        <input type="checkbox" v-model="hardwareConfig.direct_browser_print" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500" />
                        <div>
                            <div class="font-bold text-zinc-900 dark:text-white">Browser Print Fallback</div>
                            <div class="text-[11px] text-zinc-500">Use native browser dialog if socket driver is offline</div>
                        </div>
                    </label>

                    <label class="flex items-start space-x-3 p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                        <input type="checkbox" v-model="hardwareConfig.customer_display_enabled" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500" />
                        <div>
                            <div class="font-bold text-zinc-900 dark:text-white">Customer Secondary Screen</div>
                            <div class="text-[11px] text-zinc-500">Dual-screen / HDMI customer pole display enabled</div>
                        </div>
                    </label>
                </div>
            </Card>

            <!-- Connected Printers Table -->
            <Card class="overflow-hidden">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Configured Station Printers</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/80 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Device Name & Role</th>
                                <th class="px-4 py-3">Connection / Target</th>
                                <th class="px-4 py-3 text-center">Paper Width</th>
                                <th class="px-4 py-3 text-center">Auto-Cut / Drawer</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            <tr
                                v-for="printer in printerList"
                                :key="printer.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-800/50 transition"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ printer.name }}</div>
                                    <span class="text-[10px] uppercase font-mono text-emerald-600 font-bold bg-emerald-50 dark:bg-emerald-950/60 px-1.5 py-0.5 rounded">
                                        {{ printer.role.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300 font-mono text-xs">
                                    <div class="font-bold">{{ printer.target }}</div>
                                    <div class="text-[10px] text-zinc-400 capitalize">{{ printer.connection_type.replace('_', ' ') }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ printer.paper_width }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs">
                                    <span v-if="printer.auto_cut" class="text-emerald-600 font-medium">Cut ✓</span>
                                    <span v-if="printer.cash_drawer_pulse" class="text-blue-600 font-medium ml-1.5">• Drawer 💵</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Badge :variant="printer.status === 'online' ? 'success' : 'secondary'" class="uppercase text-[9px]">
                                        {{ printer.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <button
                                        type="button"
                                        @click="handleTestPulse(printer)"
                                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-zinc-800 hover:bg-emerald-50 dark:hover:bg-zinc-700 text-zinc-700 hover:text-emerald-700 dark:text-zinc-300 transition cursor-pointer"
                                    >
                                        Test Pulse
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <div class="flex justify-end pt-2">
                <Button variant="primary" size="sm" @click="handleSaveConfig">
                    💾 Save Hardware Configuration
                </Button>
            </div>
        </div>

        <!-- Add Printer Modal -->
        <Modal :show="showAddPrinterModal" @close="showAddPrinterModal = false">
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-800">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Add Peripheral Printer</h3>
                    <button type="button" @click="showAddPrinterModal = false" class="text-zinc-400 hover:text-zinc-600">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Printer Friendly Name</label>
                        <input
                            v-model="newPrinter.name"
                            type="text"
                            placeholder="e.g. Counter 02 Receipt Printer"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Role</label>
                            <select v-model="newPrinter.role" class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl">
                                <option value="receipt">Cashier Receipt Printer</option>
                                <option value="kitchen_hot">Hot Kitchen KOT Station</option>
                                <option value="kitchen_bar">Bar / Beverage Station</option>
                                <option value="label">Barcode Label Printer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Connection Type</label>
                            <select v-model="newPrinter.connection_type" class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl">
                                <option value="network_tcp">Network Socket (TCP/IP:9100)</option>
                                <option value="usb">Direct USB (/dev/usb/lp0)</option>
                                <option value="bluetooth">Bluetooth Handheld</option>
                                <option value="browser_direct">Browser WebUSB / Driver</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Target Address (IP / Port / Device)</label>
                        <input
                            v-model="newPrinter.target"
                            type="text"
                            placeholder="192.168.1.200:9100"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-700 rounded-xl font-mono"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                            <input type="checkbox" v-model="newPrinter.auto_cut" class="rounded text-emerald-600 focus:ring-emerald-500" />
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Auto-Cut Paper</span>
                        </label>
                        <label class="flex items-center space-x-2 p-2.5 rounded-xl bg-slate-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 cursor-pointer">
                            <input type="checkbox" v-model="newPrinter.cash_drawer_pulse" class="rounded text-emerald-600 focus:ring-emerald-500" />
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Open Cash Drawer</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-3">
                    <button type="button" @click="showAddPrinterModal = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 text-xs font-semibold">
                        Cancel
                    </button>
                    <button type="button" @click="handleAddPrinter" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow">
                        Save Printer
                    </button>
                </div>
            </div>
        </Modal>
    </OrganizationLayout>
</template>
