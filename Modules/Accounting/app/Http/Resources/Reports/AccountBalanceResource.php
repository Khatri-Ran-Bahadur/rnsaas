<?php

namespace Modules\Accounting\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Application\DTOs\Reports\AccountBalanceData;

/** @mixin AccountBalanceData */
final class AccountBalanceResource extends JsonResource
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

            'debit' => $this->debit,
            'credit' => $this->credit,
            'balance' => $this->balance,
        ];
    }
}
