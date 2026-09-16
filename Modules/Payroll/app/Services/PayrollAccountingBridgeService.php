<?php

namespace Modules\Payroll\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalLine;
use Modules\Payroll\Models\PayrollRun;
use Modules\Tenancy\Models\Tenant;

class PayrollAccountingBridgeService
{
    /**
     * Post balanced journal entry into General Ledger for a PayrollRun.
     */
    public function postPayrollJournal(PayrollRun $run): ?JournalEntry
    {
        $tenant = Tenant::find($run->tenant_id);
        if (! $tenant) {
            return null;
        }

        return $this->postPayrollJournalEntry($run, $tenant);
    }

    /**
     * Post balanced journal entry into General Ledger for an approved/paid payroll run.
     */
    public function postPayrollJournalEntry(PayrollRun $run, Tenant $tenant): ?JournalEntry
    {
        if (! class_exists(JournalEntry::class) || ! class_exists(Account::class)) {
            return null;
        }

        try {
            return DB::transaction(function () use ($run, $tenant) {
                // Prevent duplicate journal entry for same payroll run
                $existing = JournalEntry::where('tenant_id', $tenant->id)
                    ->where('reference_number', $run->run_number)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                // Resolve Accounts
                $salaryExpenseAccount = Account::where('tenant_id', $tenant->id)
                    ->where(function ($q) {
                        $q->where('name', 'like', '%Salary%')
                            ->orWhere('name', 'like', '%Payroll%')
                            ->orWhere('name', 'like', '%Wages%');
                    })
                    ->first() ?? Account::where('tenant_id', $tenant->id)->where('type', 'expense')->first();

                $bankAccount = Account::where('tenant_id', $tenant->id)
                    ->where(function ($q) {
                        $q->where('name', 'like', '%Bank%')
                            ->orWhere('name', 'like', '%Cash%');
                    })
                    ->first() ?? Account::where('tenant_id', $tenant->id)->where('type', 'asset')->first();

                $liabilityAccount = Account::where('tenant_id', $tenant->id)
                    ->where(function ($q) {
                        $q->where('name', 'like', '%Payable%')
                            ->orWhere('name', 'like', '%Liability%');
                    })
                    ->first() ?? Account::where('tenant_id', $tenant->id)->where('type', 'liability')->first();

                if (! $salaryExpenseAccount || ! $bankAccount) {
                    Log::info("Payroll Accounting Bridge: Default accounts not configured for tenant {$tenant->id}");

                    return null;
                }

                $journalEntry = JournalEntry::create([
                    'public_id' => (string) Str::uuid(),
                    'tenant_id' => $tenant->id,
                    'entry_number' => 'JE-PAY-'.$run->run_number,
                    'reference_number' => $run->run_number,
                    'entry_date' => $run->pay_date ?? now()->toDateString(),
                    'description' => "Payroll Disbursement & Tax Withholding for {$run->period_name} ({$run->total_employees} staff)",
                    'status' => 'posted',
                    'total_debit' => $run->gross_amount,
                    'total_credit' => $run->gross_amount,
                ]);

                // 1. Debit: Salary & Wages Expense (Total Gross)
                JournalLine::create([
                    'tenant_id' => $tenant->id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $salaryExpenseAccount->id,
                    'debit' => $run->gross_amount,
                    'credit' => 0,
                    'description' => "Gross Salary Expense - {$run->period_name}",
                ]);

                // 2. Credit: Tax / Statutory Deductions Payable
                if ($run->deductions_amount > 0 && $liabilityAccount) {
                    JournalLine::create([
                        'tenant_id' => $tenant->id,
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $liabilityAccount->id,
                        'debit' => 0,
                        'credit' => $run->deductions_amount,
                        'description' => "TDS Tax & Statutory Deductions Payable - {$run->period_name}",
                    ]);
                }

                // 3. Credit: Net Salary Paid / Bank Account
                JournalLine::create([
                    'tenant_id' => $tenant->id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $bankAccount->id,
                    'debit' => 0,
                    'credit' => $run->net_amount,
                    'description' => "Net Salary Disbursed to Employees - {$run->period_name}",
                ]);

                return $journalEntry;
            });
        } catch (Exception $e) {
            Log::error("Payroll Journal Entry Posting Error: {$e->getMessage()}");

            return null;
        }
    }
}
