<?php

namespace Modules\Accounting\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Application\DTOs\Reports\GeneralLedgerData;

/** @mixin GeneralLedgerData */
final class GeneralLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'account' => [
                'id' => $this->accountId,
                'code' => $this->accountCode,
                'name' => $this->accountName,
                'normal_balance' => $this->normalBalance,
            ],

            'period' => [
                'from' => $this->fromDate->toDateString(),
                'to' => $this->toDate->toDateString(),
            ],

            'opening_balance' => $this->openingBalance,

            'totals' => [
                'debit' => $this->totalDebit,
                'credit' => $this->totalCredit,
            ],

            'closing_balance' => $this->closingBalance,

            'lines' => $this->lines,
        ];
    }
}
