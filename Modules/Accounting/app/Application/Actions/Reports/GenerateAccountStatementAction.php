<?php

namespace Modules\Accounting\Application\Actions\Reports;

use Carbon\CarbonImmutable;
use Modules\Accounting\Application\DTOs\Reports\AccountStatementData;

final class GenerateAccountStatementAction
{
    public function __construct(
        private readonly GenerateGeneralLedgerAction $generalLedger,
    ) {}

    public function execute(
        int $tenantId,
        int $accountId,
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate,
    ): AccountStatementData {
        $ledger = $this->generalLedger->execute(
            tenantId: $tenantId,
            accountId: $accountId,
            fromDate: $fromDate,
            toDate: $toDate,
        );

        return new AccountStatementData(
            accountId: $ledger->accountId,
            accountCode: $ledger->accountCode,
            accountName: $ledger->accountName,
            normalBalance: $ledger->normalBalance,
            fromDate: $ledger->fromDate,
            toDate: $ledger->toDate,
            openingBalance: $ledger->openingBalance,
            totalDebit: $ledger->totalDebit,
            totalCredit: $ledger->totalCredit,
            closingBalance: $ledger->closingBalance,
            lines: $ledger->lines,
        );
    }
}
