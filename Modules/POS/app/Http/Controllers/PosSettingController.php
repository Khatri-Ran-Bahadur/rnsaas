<?php

namespace Modules\POS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosSettingController extends Controller
{
    public function receiptTemplates(Request $request): Response
    {
        return Inertia::render('POS/Settings/ReceiptTemplates', [
            'templates' => [
                [
                    'id' => 1,
                    'name' => 'Standard Retail 80mm',
                    'paper_size' => '80mm',
                    'style' => 'modern',
                    'locale' => 'en',
                    'direction' => 'ltr',
                    'is_default' => true,
                    'header_title' => 'TAX INVOICE / RECEIPT',
                    'show_logo' => true,
                    'logo_url' => null,
                    'show_tax_number' => true,
                    'show_branch' => true,
                    'show_cashier' => true,
                    'show_customer' => true,
                    'show_table' => true,
                    'show_sku' => true,
                    'show_modifiers' => true,
                    'show_discounts' => true,
                    'show_tax_summary' => true,
                    'show_barcode' => true,
                    'show_qr_code' => true,
                    'qr_type' => 'einvoice',
                    'footer_notes' => 'Thank you for shopping with SathiSaaS! Goods sold are returnable within 7 days with valid receipt.',
                ],
                [
                    'id' => 2,
                    'name' => 'Restaurant & Cafe 80mm (Dual EN/AR)',
                    'paper_size' => '80mm',
                    'style' => 'restaurant',
                    'locale' => 'ar',
                    'direction' => 'rtl',
                    'is_default' => false,
                    'header_title' => 'فاتورة ضريبية / TAX INVOICE',
                    'show_logo' => true,
                    'logo_url' => null,
                    'show_tax_number' => true,
                    'show_branch' => true,
                    'show_cashier' => true,
                    'show_customer' => true,
                    'show_table' => true,
                    'show_sku' => false,
                    'show_modifiers' => true,
                    'show_discounts' => true,
                    'show_tax_summary' => true,
                    'show_barcode' => true,
                    'show_qr_code' => true,
                    'qr_type' => 'zatca',
                    'footer_notes' => 'شكراً لزيارتكم! نسعد بخدمتكم دائماً. Thank you for dining with us!',
                ],
                [
                    'id' => 3,
                    'name' => 'Mobile Mini 58mm',
                    'paper_size' => '58mm',
                    'style' => 'compact',
                    'locale' => 'en',
                    'direction' => 'ltr',
                    'is_default' => false,
                    'header_title' => 'SALES RECEIPT',
                    'show_logo' => false,
                    'logo_url' => null,
                    'show_tax_number' => true,
                    'show_branch' => false,
                    'show_cashier' => true,
                    'show_customer' => true,
                    'show_table' => false,
                    'show_sku' => false,
                    'show_modifiers' => true,
                    'show_discounts' => true,
                    'show_tax_summary' => false,
                    'show_barcode' => true,
                    'show_qr_code' => false,
                    'qr_type' => 'none',
                    'footer_notes' => 'Have a wonderful day!',
                ],
                [
                    'id' => 4,
                    'name' => 'Corporate A4 / A5 Tax Invoice',
                    'paper_size' => 'a4',
                    'style' => 'tax_invoice',
                    'locale' => 'en',
                    'direction' => 'ltr',
                    'is_default' => false,
                    'header_title' => 'OFFICIAL TAX INVOICE',
                    'show_logo' => true,
                    'logo_url' => null,
                    'show_tax_number' => true,
                    'show_branch' => true,
                    'show_cashier' => true,
                    'show_customer' => true,
                    'show_table' => false,
                    'show_sku' => true,
                    'show_modifiers' => true,
                    'show_discounts' => true,
                    'show_tax_summary' => true,
                    'show_barcode' => true,
                    'show_qr_code' => true,
                    'qr_type' => 'einvoice',
                    'footer_notes' => 'Payment terms: Due on receipt. For electronic bank transfers, please quote invoice number in payment description.',
                ],
            ],
            'printers' => [
                [
                    'id' => 1,
                    'name' => 'Cashier Thermal Printer (Epson TM-T88VI)',
                    'type' => 'network',
                    'ip_address' => '192.168.1.201',
                    'paper_size' => '80mm',
                    'auto_cut' => true,
                    'open_drawer' => true,
                    'copies' => 1,
                    'status' => 'connected',
                ],
                [
                    'id' => 2,
                    'name' => 'Kitchen Hot Line Ticket Printer (Bixolon SRP-350)',
                    'type' => 'network',
                    'ip_address' => '192.168.1.202',
                    'paper_size' => '80mm',
                    'auto_cut' => true,
                    'open_drawer' => false,
                    'copies' => 1,
                    'status' => 'connected',
                ],
                [
                    'id' => 3,
                    'name' => 'Bluetooth Handheld Mini (Sunmi V2)',
                    'type' => 'bluetooth',
                    'ip_address' => 'BT:4A:2C:99:12',
                    'paper_size' => '58mm',
                    'auto_cut' => false,
                    'open_drawer' => false,
                    'copies' => 1,
                    'status' => 'idle',
                ],
            ],
            'company' => [
                'name' => 'SathiSaaS Global Enterprise Sdn Bhd',
                'reg_no' => '202401029384 (1548291-K)',
                'tax_id' => 'W10-1808-32000123',
                'address' => 'Level 18, Pavilion Tower, 75 Jalan Raja Chulan, 50200 Kuala Lumpur',
                'phone' => '+60 3-2148 8888',
                'email' => 'billing@sathisaas.com',
                'website' => 'www.sathisaas.com',
            ],
        ]);
    }

    public function saveReceiptTemplate(Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Receipt template configuration saved successfully.');
    }

    public function printSettings(Request $request): Response
    {
        return Inertia::render('POS/Settings/PrintSettings', [
            'printers' => [
                [
                    'id' => 1,
                    'name' => 'Cashier Thermal Printer (Epson TM-T88VI)',
                    'role' => 'receipt',
                    'connection_type' => 'network_tcp',
                    'target' => '192.168.1.201:9100',
                    'paper_width' => '80mm',
                    'font_family' => 'monospace',
                    'font_scale' => '100%',
                    'auto_cut' => true,
                    'cash_drawer_pulse' => true,
                    'print_copies' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 2,
                    'name' => 'Kitchen Hot Food Station (Bixolon SRP-350)',
                    'role' => 'kitchen_hot',
                    'connection_type' => 'network_tcp',
                    'target' => '192.168.1.202:9100',
                    'paper_width' => '80mm',
                    'font_family' => 'monospace',
                    'font_scale' => '120%',
                    'auto_cut' => true,
                    'cash_drawer_pulse' => false,
                    'print_copies' => 1,
                    'status' => 'online',
                ],
                [
                    'id' => 3,
                    'name' => 'Bar & Beverage Station Printer (Star TSP100)',
                    'role' => 'kitchen_bar',
                    'connection_type' => 'usb',
                    'target' => '/dev/usb/lp0',
                    'paper_width' => '80mm',
                    'font_family' => 'monospace',
                    'font_scale' => '100%',
                    'auto_cut' => true,
                    'cash_drawer_pulse' => false,
                    'print_copies' => 1,
                    'status' => 'online',
                ],
            ],
            'hardwareSettings' => [
                'direct_browser_print' => true,
                'silent_background_print' => false,
                'drawer_pulse_signal' => 'pin2_200ms',
                'auto_print_on_payment_complete' => true,
                'auto_print_kitchen_tickets' => true,
                'customer_display_enabled' => true,
                'customer_display_port' => 'COM3 / 9600 baud',
            ],
        ]);
    }

    public function savePrintSettings(Request $request): RedirectResponse
    {
        return redirect()->back()->with('success', 'Hardware and printer settings saved.');
    }
}
