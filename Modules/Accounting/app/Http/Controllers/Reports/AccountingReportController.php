<?php

namespace Modules\Accounting\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Modules\Accounting\Application\Actions\Reports\GenerateAccountBalanceAction;
use Modules\Accounting\Application\Actions\Reports\GenerateGeneralLedgerAction;
use Modules\Accounting\Application\Actions\Reports\GenerateTrialBalanceAction;
use Modules\Accounting\Http\Requests\Reports\GeneralLedgerRequest;
use Modules\Accounting\Http\Requests\Reports\ReportDateRangeRequest;
use Modules\Accounting\Http\Resources\Reports\AccountBalanceResource;
use Modules\Accounting\Http\Resources\Reports\GeneralLedgerResource;
use Modules\Accounting\Http\Resources\Reports\TrialBalanceResource;

final class AccountingReportController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly GenerateGeneralLedgerAction $generalLedger,
        private readonly GenerateTrialBalanceAction $trialBalance,
        private readonly GenerateAccountBalanceAction $accountBalance,
    ) {}

    public function generalLedger(
        GeneralLedgerRequest $request,
    ): GeneralLedgerResource {
        $data = $this->generalLedger->execute(
            tenantId: $this->currentTenant->id(),
            accountId: (int) $request->integer('account_id'),
            fromDate: CarbonImmutable::parse(
                $request->string('from_date')->toString()
            ),
            toDate: CarbonImmutable::parse(
                $request->string('to_date')->toString()
            ),
        );

        return new GeneralLedgerResource($data);
    }

    public function trialBalance(
        ReportDateRangeRequest $request,
    ): TrialBalanceResource {
        $data = $this->trialBalance->execute(
            tenantId: $this->currentTenant->id(),
            fromDate: CarbonImmutable::parse(
                $request->string('from_date')->toString()
            ),
            toDate: CarbonImmutable::parse(
                $request->string('to_date')->toString()
            ),
        );

        return new TrialBalanceResource($data);
    }

    public function accountBalance(
        int $accountId,
    ): AccountBalanceResource {
        $data = $this->accountBalance->execute(
            tenantId: $this->currentTenant->id(),
            accountId: $accountId,
        );

        return new AccountBalanceResource($data);
    }
}
