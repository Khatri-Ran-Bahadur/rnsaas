<?php

namespace Modules\Accounting\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Accounting\Domain\Enums\AccountClassification;
use Modules\Accounting\Domain\Enums\AccountNormalBalance;
use Modules\Accounting\Domain\Enums\FinancialStatement;
use Modules\Accounting\Domain\Enums\FinancialStatementSection;
use Modules\Accounting\Domain\Enums\PurchaseBillStatus;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AccountGroup;
use Modules\Accounting\Models\AccountType;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\PurchaseBillLine;
use Modules\Accounting\Models\SalesInvoice;
use Modules\Accounting\Models\SalesInvoiceLine;
use Modules\Accounting\Models\Vendor;
use Modules\Tenancy\Models\Tenant;

class AccountingDatabaseSeeder extends Seeder
{
    public function run(?int $targetTenantId = null): void
    {
        $tenants = $targetTenantId
            ? Tenant::where('id', $targetTenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $tenants = collect([(object) ['id' => $targetTenantId ?: 1]]);
        }

        $user = User::first() ?? User::factory()->create();

        foreach ($tenants as $tenant) {
            $tenantId = $tenant->id;

            // 1. Account Types
            $assetType = AccountType::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CURR_ASSET'],
                [
                    'name' => 'Current Assets',
                    'classification' => AccountClassification::ASSET->value,
                    'normal_balance' => AccountNormalBalance::DEBIT->value,
                    'financial_statement' => FinancialStatement::BALANCE_SHEET->value,
                    'financial_statement_section' => FinancialStatementSection::ASSETS->value,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $liabilityType = AccountType::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'CURR_LIAB'],
                [
                    'name' => 'Current Liabilities',
                    'classification' => AccountClassification::LIABILITY->value,
                    'normal_balance' => AccountNormalBalance::CREDIT->value,
                    'financial_statement' => FinancialStatement::BALANCE_SHEET->value,
                    'financial_statement_section' => FinancialStatementSection::LIABILITIES->value,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $revenueType = AccountType::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'OP_REV'],
                [
                    'name' => 'Operating Revenue',
                    'classification' => AccountClassification::REVENUE->value,
                    'normal_balance' => AccountNormalBalance::CREDIT->value,
                    'financial_statement' => FinancialStatement::PROFIT_AND_LOSS->value,
                    'financial_statement_section' => FinancialStatementSection::REVENUE->value,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $expenseType = AccountType::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'OP_EXP'],
                [
                    'name' => 'Operating Expenses',
                    'classification' => AccountClassification::EXPENSE->value,
                    'normal_balance' => AccountNormalBalance::DEBIT->value,
                    'financial_statement' => FinancialStatement::PROFIT_AND_LOSS->value,
                    'financial_statement_section' => FinancialStatementSection::OPERATING_EXPENSES->value,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            // 2. Account Groups
            $assetGroup = AccountGroup::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GRP_CA'],
                [
                    'account_type_id' => $assetType->id,
                    'name' => 'Current Assets Group',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $liabilityGroup = AccountGroup::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GRP_CL'],
                [
                    'account_type_id' => $liabilityType->id,
                    'name' => 'Current Liabilities Group',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $revenueGroup = AccountGroup::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GRP_REV'],
                [
                    'account_type_id' => $revenueType->id,
                    'name' => 'Revenue Group',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $expenseGroup = AccountGroup::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'GRP_EXP'],
                [
                    'account_type_id' => $expenseType->id,
                    'name' => 'Expense Group',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            // 3. Chart of Accounts
            $arAccount = Account::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => '1200'],
                [
                    'account_type_id' => $assetType->id,
                    'account_group_id' => $assetGroup->id,
                    'name' => 'Accounts Receivable',
                    'financial_statement_section' => FinancialStatementSection::ASSETS->value,
                    'is_postable' => true,
                    'is_control_account' => true,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $apAccount = Account::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => '2000'],
                [
                    'account_type_id' => $liabilityType->id,
                    'account_group_id' => $liabilityGroup->id,
                    'name' => 'Accounts Payable',
                    'financial_statement_section' => FinancialStatementSection::LIABILITIES->value,
                    'is_postable' => true,
                    'is_control_account' => true,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $salesAccount = Account::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => '4000'],
                [
                    'account_type_id' => $revenueType->id,
                    'account_group_id' => $revenueGroup->id,
                    'name' => 'Sales Revenue',
                    'financial_statement_section' => FinancialStatementSection::REVENUE->value,
                    'is_postable' => true,
                    'is_control_account' => false,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $cogsAccount = Account::firstOrCreate(
                ['tenant_id' => $tenantId, 'code' => '5000'],
                [
                    'account_type_id' => $expenseType->id,
                    'account_group_id' => $expenseGroup->id,
                    'name' => 'Cost of Goods Sold',
                    'financial_statement_section' => FinancialStatementSection::OPERATING_EXPENSES->value,
                    'is_postable' => true,
                    'is_control_account' => false,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            // 3. Customers
            $cust1 = Customer::firstOrCreate(
                ['tenant_id' => $tenantId, 'customer_code' => 'CUST-001'],
                [
                    'name' => 'Apex Global Technologies',
                    'email' => 'finance@apexglobal.tech',
                    'phone' => '+60 3-8888 1234',
                    'tax_number' => 'TAX-MY-99210',
                    'billing_address_line_1' => 'Tower A, Level 24, KLCC Gateway',
                    'billing_city' => 'Kuala Lumpur',
                    'billing_state' => 'Wilayah Persekutuan',
                    'billing_postcode' => '50088',
                    'billing_country' => 'MY',
                    'receivable_account_id' => $arAccount->id,
                    'credit_limit' => 50000.00,
                    'payment_terms_days' => 30,
                    'is_active' => true,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]
            );

            $cust2 = Customer::firstOrCreate(
                ['tenant_id' => $tenantId, 'customer_code' => 'CUST-002'],
                [
                    'name' => 'Summit Retail Mart Group',
                    'email' => 'accounts@summitmart.com',
                    'phone' => '+60 3-7722 5566',
                    'tax_number' => 'TAX-MY-77112',
                    'billing_address_line_1' => 'Summit Avenue Central Park',
                    'billing_city' => 'Petaling Jaya',
                    'billing_state' => 'Selangor',
                    'billing_postcode' => '47301',
                    'billing_country' => 'MY',
                    'receivable_account_id' => $arAccount->id,
                    'credit_limit' => 30000.00,
                    'payment_terms_days' => 15,
                    'is_active' => true,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]
            );

            $cust3 = Customer::firstOrCreate(
                ['tenant_id' => $tenantId, 'customer_code' => 'CUST-003'],
                [
                    'name' => 'Walk-in Retail & POS Customers',
                    'email' => 'walkin@sathisaas.com',
                    'phone' => '+60 3-0000 0000',
                    'tax_number' => null,
                    'billing_address_line_1' => 'Counter POS Front Store',
                    'billing_city' => 'Kuala Lumpur',
                    'billing_state' => 'Wilayah Persekutuan',
                    'billing_postcode' => '50000',
                    'billing_country' => 'MY',
                    'receivable_account_id' => $arAccount->id,
                    'credit_limit' => 0.00,
                    'payment_terms_days' => 0,
                    'is_active' => true,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]
            );

            // 4. Vendors
            $vend1 = Vendor::firstOrCreate(
                ['tenant_id' => $tenantId, 'vendor_code' => 'VEND-001'],
                [
                    'name' => 'Fresh Meat Supply Sdn Bhd',
                    'email' => 'sales@freshmeat.my',
                    'phone' => '+60 3-5566 9911',
                    'tax_number' => 'V-TAX-8812',
                    'billing_address_line_1' => 'Industrial Lot 14, Klang Port Road',
                    'billing_city' => 'Klang',
                    'billing_state' => 'Selangor',
                    'billing_postcode' => '41200',
                    'billing_country' => 'MY',
                    'payable_account_id' => $apAccount->id,
                    'credit_limit' => 25000.00,
                    'payment_terms_days' => 30,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]
            );

            $vend2 = Vendor::firstOrCreate(
                ['tenant_id' => $tenantId, 'vendor_code' => 'VEND-002'],
                [
                    'name' => 'Global Packaging & Boxes Co',
                    'email' => 'orders@globalpack.com',
                    'phone' => '+60 3-3344 7788',
                    'tax_number' => 'V-TAX-4455',
                    'billing_address_line_1' => 'Subang Hi-Tech Park Bay 9',
                    'billing_city' => 'Subang Jaya',
                    'billing_state' => 'Selangor',
                    'billing_postcode' => '47500',
                    'billing_country' => 'MY',
                    'payable_account_id' => $apAccount->id,
                    'credit_limit' => 15000.00,
                    'payment_terms_days' => 30,
                    'status' => 'active',
                    'created_by' => $user->id,
                ]
            );

            // 5. Sales Invoices
            $inv1 = SalesInvoice::updateOrCreate(
                ['tenant_id' => $tenantId, 'invoice_number' => 'INV-2026-0001'],
                [
                    'customer_id' => $cust1->id,
                    'invoice_date' => now()->subDays(10)->toDateString(),
                    'due_date' => now()->addDays(20)->toDateString(),
                    'currency' => 'MYR',
                    'subtotal' => 1250.00,
                    'discount_total' => 50.00,
                    'tax_total' => 96.00,
                    'grand_total' => 1296.00,
                    'status' => 'paid',
                    'reference' => 'PO-APEX-8891',
                    'notes' => 'Corporate catering delivery and setup package.',
                    'created_by' => $user->id,
                ]
            );

            SalesInvoiceLine::updateOrCreate(
                ['tenant_id' => $tenantId, 'sales_invoice_id' => $inv1->id, 'line_number' => 1],
                [
                    'revenue_account_id' => $salesAccount->id,
                    'description' => 'Gourmet Burger Buffet Catering (50 pax)',
                    'quantity' => 50,
                    'unit_price' => 25.00,
                    'discount_amount' => 50.00,
                    'tax_rate' => 8.00,
                    'tax_amount' => 96.00,
                    'subtotal' => 1200.00,
                    'total' => 1296.00,
                ]
            );

            $inv2 = SalesInvoice::updateOrCreate(
                ['tenant_id' => $tenantId, 'invoice_number' => 'INV-2026-0002'],
                [
                    'customer_id' => $cust2->id,
                    'invoice_date' => now()->subDays(3)->toDateString(),
                    'due_date' => now()->addDays(12)->toDateString(),
                    'currency' => 'MYR',
                    'subtotal' => 3200.00,
                    'discount_total' => 0.00,
                    'tax_total' => 256.00,
                    'grand_total' => 3456.00,
                    'status' => 'posted',
                    'reference' => 'SO-SUMMIT-412',
                    'notes' => 'Weekly bulk packaged food distribution shipment.',
                    'created_by' => $user->id,
                ]
            );

            SalesInvoiceLine::updateOrCreate(
                ['tenant_id' => $tenantId, 'sales_invoice_id' => $inv2->id, 'line_number' => 1],
                [
                    'revenue_account_id' => $salesAccount->id,
                    'description' => 'Frozen Artisanal Pizzas Wholesale (160 units)',
                    'quantity' => 160,
                    'unit_price' => 20.00,
                    'discount_amount' => 0.00,
                    'tax_rate' => 8.00,
                    'tax_amount' => 256.00,
                    'subtotal' => 3200.00,
                    'total' => 3456.00,
                ]
            );

            $inv3 = SalesInvoice::updateOrCreate(
                ['tenant_id' => $tenantId, 'invoice_number' => 'INV-2026-0003'],
                [
                    'customer_id' => $cust1->id,
                    'invoice_date' => now()->toDateString(),
                    'due_date' => now()->addDays(30)->toDateString(),
                    'currency' => 'MYR',
                    'subtotal' => 850.00,
                    'discount_total' => 0.00,
                    'tax_total' => 68.00,
                    'grand_total' => 918.00,
                    'status' => 'draft',
                    'reference' => 'QUO-2026-081',
                    'notes' => 'Draft invoice for upcoming product showcase luncheon.',
                    'created_by' => $user->id,
                ]
            );

            SalesInvoiceLine::updateOrCreate(
                ['tenant_id' => $tenantId, 'sales_invoice_id' => $inv3->id, 'line_number' => 1],
                [
                    'revenue_account_id' => $salesAccount->id,
                    'description' => 'Barista Coffee & Beverage Station Setup',
                    'quantity' => 1,
                    'unit_price' => 850.00,
                    'discount_amount' => 0.00,
                    'tax_rate' => 8.00,
                    'tax_amount' => 68.00,
                    'subtotal' => 850.00,
                    'total' => 918.00,
                ]
            );

            // 6. Purchase Bills
            $bill1 = PurchaseBill::updateOrCreate(
                ['tenant_id' => $tenantId, 'bill_number' => 'BILL-2026-0001'],
                [
                    'vendor_id' => $vend1->id,
                    'bill_date' => now()->subDays(7)->toDateString(),
                    'due_date' => now()->addDays(23)->toDateString(),
                    'currency' => 'MYR',
                    'subtotal' => 1850.00,
                    'discount_total' => 0.00,
                    'tax_total' => 0.00,
                    'grand_total' => 1850.00,
                    'status' => PurchaseBillStatus::POSTED->value,
                    'reference' => 'DO-FM-5512',
                    'notes' => 'Bulk raw chicken fillet and prime minced beef batch delivery.',
                    'created_by' => $user->id,
                ]
            );

            PurchaseBillLine::updateOrCreate(
                ['purchase_bill_id' => $bill1->id, 'line_number' => 1],
                [
                    'debit_account_id' => $cogsAccount->id,
                    'description' => 'Chicken Breast Fillet 100kg Lot',
                    'quantity' => 100,
                    'unit_price' => 18.50,
                    'discount_amount' => 0.00,
                    'tax_rate' => 0.00,
                    'tax_amount' => 0.00,
                    'subtotal' => 1850.00,
                    'total' => 1850.00,
                ]
            );

            $bill2 = PurchaseBill::updateOrCreate(
                ['tenant_id' => $tenantId, 'bill_number' => 'BILL-2026-0002'],
                [
                    'vendor_id' => $vend2->id,
                    'bill_date' => now()->subDays(2)->toDateString(),
                    'due_date' => now()->addDays(28)->toDateString(),
                    'currency' => 'MYR',
                    'subtotal' => 1200.00,
                    'discount_total' => 0.00,
                    'tax_total' => 96.00,
                    'grand_total' => 1296.00,
                    'status' => PurchaseBillStatus::POSTED->value,
                    'reference' => 'INV-GP-99120',
                    'notes' => 'Custom branded thermal paper rolls and burger box packaging.',
                    'created_by' => $user->id,
                ]
            );

            PurchaseBillLine::updateOrCreate(
                ['purchase_bill_id' => $bill2->id, 'line_number' => 1],
                [
                    'debit_account_id' => $cogsAccount->id,
                    'description' => 'Kraft Eco-Friendly Burger Boxes (Pack of 500)',
                    'quantity' => 4,
                    'unit_price' => 300.00,
                    'discount_amount' => 0.00,
                    'tax_rate' => 8.00,
                    'tax_amount' => 96.00,
                    'subtotal' => 1200.00,
                    'total' => 1296.00,
                ]
            );
        }
    }
}
