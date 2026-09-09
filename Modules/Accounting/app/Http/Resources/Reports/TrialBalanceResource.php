<?php

namespace Modules\Accounting\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Application\DTOs\Reports\TrialBalanceData;

/** @mixin TrialBalanceData */
final class TrialBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'period' => [
                'from' => $this->fromDate->toDateString(),
                'to' => $this->toDate->toDateString(),
            ],

            'accounts' => $this->accounts,

            'totals' => [
                'debit' => $this->totalDebit,
                'credit' => $this->totalCredit,
            ],

            'is_balanced' => bccomp(
                $this->totalDebit,
                $this->totalCredit,
                6
            ) === 0,
        ];
    }
}
