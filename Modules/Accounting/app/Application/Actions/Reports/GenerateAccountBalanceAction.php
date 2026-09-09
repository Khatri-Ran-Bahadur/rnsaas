<?php

namespace Modules\Accounting\Application\Actions\Reports;

use Modules\Accounting\Application\DTOs\Reports\AccountBalanceData;
use Modules\Accounting\Application\Services\Reports\AccountingReportQuery;
use Modules\Accounting\Models\Account;
use RuntimeException;

final class GenerateAccountBalanceAction
{
    public function __construct(
        private readonly AccountingReportQuery $query,
    ) {}

    public function execute(
        int $tenantId,
        int $accountId,
    ): AccountBalanceData {
        $account = Account::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($accountId)
            ->first();

        if ($account === null) {
            throw new RuntimeException('Account not found.');
        }

        $row = $this->query
            ->accountTotals(tenantId: $tenantId)
            ->where('accounting_accounts.id', $accountId)
            ->first();

        $debit = (string) ($row->total_debit ?? '0');
        $credit = (string) ($row->total_credit ?? '0');

        $normalBalance = $this->resolveNormalBalance($account);

        $balance = $normalBalance === 'debit'
            ? bcsub($debit, $credit, 6)
            : bcsub($credit, $debit, 6);

        return new AccountBalanceData(
            accountId: $account->id,
            accountCode: $account->code,
            accountName: $account->name,
            normalBalance: $normalBalance,
            debit: $debit,
            credit: $credit,
            balance: $balance,
        );
    }

    private function resolveNormalBalance(Account $account): string
    {
        if (
            isset($account->normal_balance)
            && $account->normal_balance !== null
        ) {
            return $account->normal_balance->value
                ?? $account->normal_balance;
        }

        $accountType = $account->accountType;

        if ($accountType === null) {
            throw new RuntimeException(
                'Account type is missing.'
            );
        }

        return $accountType->normal_balance->value
            ?? $accountType->normal_balance;
    }
}
