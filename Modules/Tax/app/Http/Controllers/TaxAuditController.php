<?php

namespace Modules\Tax\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxAuditController extends Controller
{
    public function index(Request $request): Response
    {
        $auditLogs = [
            [
                'id' => 1,
                'event_type' => 'rate_scheduled',
                'target_entity' => 'Tax Rate: Standard Rate (10%)',
                'description' => 'Scheduled future standard sales tax rate change from 8.00% to 10.00% with effective start date 2027-01-01.',
                'user_name' => 'Ran Khatri',
                'ip_address' => '192.168.1.45',
                'old_values' => ['effective_until' => 'Indefinite'],
                'new_values' => ['effective_from' => '2027-01-01', 'rate' => '10.00%'],
                'created_at' => '2026-09-10 14:22:10',
            ],
            [
                'id' => 2,
                'event_type' => 'exemption_approved',
                'target_entity' => 'Tax Exemption: EXP-2026-981 (Apex Global Logistics)',
                'description' => 'Approved and activated customer tax exemption certificate for cross-border freight forwarding.',
                'user_name' => 'Compliance Officer',
                'ip_address' => '192.168.1.12',
                'old_values' => ['status' => 'pending_review'],
                'new_values' => ['status' => 'active', 'verified_at' => '2026-09-08 11:00:00'],
                'created_at' => '2026-09-08 11:00:00',
            ],
            [
                'id' => 3,
                'event_type' => 'rule_created',
                'target_entity' => 'Tax Rule: Foreign Vendor Services Withholding Deduction',
                'description' => 'Configured automatic 10% withholding tax deduction on cross-border technical services.',
                'user_name' => 'Ran Khatri',
                'ip_address' => '192.168.1.45',
                'old_values' => null,
                'new_values' => ['priority' => 30, 'applied_tax_rate' => 'WHT-TECH-10'],
                'created_at' => '2026-09-01 09:30:15',
            ],
            [
                'id' => 4,
                'event_type' => 'settings_updated',
                'target_entity' => 'Company Tax Profile',
                'description' => 'Updated tax registration number and enabled e-Invoice statutory compliance mode.',
                'user_name' => 'Ran Khatri',
                'ip_address' => '192.168.1.45',
                'old_values' => ['enable_einvoice_compliance' => false],
                'new_values' => ['enable_einvoice_compliance' => true],
                'created_at' => '2026-08-15 16:45:00',
            ],
        ];

        return Inertia::render('Tax/Audit/Index', [
            'auditLogs' => $auditLogs,
            'filters' => $request->only(['search', 'event_type', 'from_date', 'to_date']),
        ]);
    }
}
