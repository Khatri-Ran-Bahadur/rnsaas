<?php

namespace Modules\Accounting\Http\Controllers\Statements;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Modules\Accounting\Application\Actions\Statements\GenerateBalanceSheetAction;
use Modules\Accounting\Application\Actions\Statements\GenerateProfitAndLossAction;
use Modules\Accounting\Http\Requests\Statements\FinancialStatementDateRangeRequest;
use Modules\Accounting\Http\Resources\Statements\FinancialStatementResource;

final class FinancialStatementController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly GenerateProfitAndLossAction $profitAndLoss,
        private readonly GenerateBalanceSheetAction $balanceSheet,
    ) {}

    public function profitAndLoss(
        FinancialStatementDateRangeRequest $request,
    ): FinancialStatementResource {
        $data = $this->profitAndLoss->execute(
            tenantId: $this->currentTenant->id(),
            fromDate: CarbonImmutable::parse(
                $request->string('from_date')->toString()
            ),
            toDate: CarbonImmutable::parse(
                $request->string('to_date')->toString()
            ),
        );

        return new FinancialStatementResource($data);
    }

    public function balanceSheet(
        FinancialStatementDateRangeRequest $request,
    ): FinancialStatementResource {
        $data = $this->balanceSheet->execute(
            tenantId: $this->currentTenant->id(),
            fromDate: CarbonImmutable::parse(
                $request->string('from_date')->toString()
            ),
            toDate: CarbonImmutable::parse(
                $request->string('to_date')->toString()
            ),
        );

        return new FinancialStatementResource($data);
    }
}
