<?php

namespace Modules\Accounting\Http\Resources\Statements;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Application\DTOs\Statements\FinancialStatementData;

/** @mixin FinancialStatementData */
final class FinancialStatementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'statement' => $this->statement->value,

            'period' => [
                'from' => $this->fromDate->toDateString(),
                'to' => $this->toDate->toDateString(),
            ],

            'sections' => $this->sections,

            'total' => $this->total,
        ];
    }
}
