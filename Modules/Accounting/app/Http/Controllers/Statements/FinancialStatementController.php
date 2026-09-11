<?php

namespace Modules\Accounting\Http\Controllers\Statements;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Application\Actions\Statements\GenerateBalanceSheetAction;
use Modules\Accounting\Application\Actions\Statements\GenerateProfitAndLossAction;
use Modules\Accounting\Http\Resources\Statements\FinancialStatementResource;

final class FinancialStatementController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly GenerateProfitAndLossAction $profitAndLoss,
        private readonly GenerateBalanceSheetAction $balanceSheet,
    ) {}

    public function profitAndLoss(
        Request $request,
    ): FinancialStatementResource|Response {
        $fromDate = $request->string('from_date', now()->startOfYear()->toDateString())->toString();
        $toDate = $request->string('to_date', now()->toDateString())->toString();

        if ($request->wantsJson()) {
            $request->validate([
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            ]);

            $data = $this->profitAndLoss->execute(
                tenantId: $this->currentTenant->id(),
                fromDate: CarbonImmutable::parse($fromDate),
                toDate: CarbonImmutable::parse($toDate),
            );

            return new FinancialStatementResource($data);
        }

        $data = $this->profitAndLoss->execute(
            tenantId: $this->currentTenant->id(),
            fromDate: CarbonImmutable::parse($fromDate),
            toDate: CarbonImmutable::parse($toDate),
        );

        return Inertia::render('Accounting/Statements/ProfitAndLoss', [
            'statement' => $data,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function balanceSheet(
        Request $request,
    ): FinancialStatementResource|Response {
        $fromDate = $request->string('from_date', now()->startOfYear()->toDateString())->toString();
        $toDate = $request->string('to_date', now()->toDateString())->toString();

        if ($request->wantsJson()) {
            $request->validate([
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            ]);

            $data = $this->balanceSheet->execute(
                tenantId: $this->currentTenant->id(),
                fromDate: CarbonImmutable::parse($fromDate),
                toDate: CarbonImmutable::parse($toDate),
            );

            return new FinancialStatementResource($data);
        }

        $data = $this->balanceSheet->execute(
            tenantId: $this->currentTenant->id(),
            fromDate: CarbonImmutable::parse($fromDate),
            toDate: CarbonImmutable::parse($toDate),
        );

        return Inertia::render('Accounting/Statements/BalanceSheet', [
            'statement' => $data,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function profitAndLossPrint(Request $request): Response
    {
        $fromDate = $request->string('from_date', now()->startOfYear()->toDateString())->toString();
        $toDate = $request->string('to_date', now()->toDateString())->toString();

        $data = $this->profitAndLoss->execute(
            tenantId: $this->currentTenant->id(),
            fromDate: CarbonImmutable::parse($fromDate),
            toDate: CarbonImmutable::parse($toDate),
        );

        return Inertia::render('Accounting/Statements/ProfitAndLossPrint', [
            'statement' => $data,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function balanceSheetPrint(Request $request): Response
    {
        $fromDate = $request->string('from_date', now()->startOfYear()->toDateString())->toString();
        $toDate = $request->string('to_date', now()->toDateString())->toString();

        $data = $this->balanceSheet->execute(
            tenantId: $this->currentTenant->id(),
            fromDate: CarbonImmutable::parse($fromDate),
            toDate: CarbonImmutable::parse($toDate),
        );

        return Inertia::render('Accounting/Statements/BalanceSheetPrint', [
            'statement' => $data,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }
}
