<?php

namespace Modules\Accounting\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Application\Actions\Reports\GenerateAccountBalanceAction;
use Modules\Accounting\Application\Actions\Reports\GenerateCustomerBalanceAction;
use Modules\Accounting\Application\Actions\Reports\GenerateCustomerStatementAction;
use Modules\Accounting\Application\Actions\Reports\GenerateGeneralLedgerAction;
use Modules\Accounting\Application\Actions\Reports\GeneratePayableAgingAction;
use Modules\Accounting\Application\Actions\Reports\GenerateReceivableAgingAction;
use Modules\Accounting\Application\Actions\Reports\GenerateTrialBalanceAction;
use Modules\Accounting\Application\Actions\Reports\GenerateVendorBalanceAction;
use Modules\Accounting\Application\Actions\Reports\GenerateVendorStatementAction;
use Modules\Accounting\Http\Resources\Reports\AccountBalanceResource;
use Modules\Accounting\Http\Resources\Reports\GeneralLedgerResource;
use Modules\Accounting\Http\Resources\Reports\TrialBalanceResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\Vendor;

final class AccountingReportController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly GenerateGeneralLedgerAction $generalLedger,
        private readonly GenerateTrialBalanceAction $trialBalance,
        private readonly GenerateAccountBalanceAction $accountBalance,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Accounting/Reports/Index');
    }

    public function generalLedger(Request $request): GeneralLedgerResource|Response
    {
        $accounts = Account::where('tenant_id', $this->currentTenant->id())
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $accountId = $request->integer('account_id', $accounts->first()?->id ?? 0);
        $fromDate = $request->string('from_date', now()->startOfMonth()->toDateString())->toString();
        $toDate = $request->string('to_date', now()->toDateString())->toString();

        if ($request->wantsJson()) {
            $request->validate([
                'account_id' => ['required', 'integer', 'exists:accounting_accounts,id'],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            ]);

            $data = $this->generalLedger->execute(
                tenantId: $this->currentTenant->id(),
                accountId: $accountId,
                fromDate: CarbonImmutable::parse($fromDate),
                toDate: CarbonImmutable::parse($toDate),
            );

            return new GeneralLedgerResource($data);
        }

        $reportData = null;
        if ($accountId > 0) {
            try {
                $reportData = $this->generalLedger->execute(
                    tenantId: $this->currentTenant->id(),
                    accountId: $accountId,
                    fromDate: CarbonImmutable::parse($fromDate),
                    toDate: CarbonImmutable::parse($toDate),
                );
            } catch (\Throwable $e) {
                Log::warning('General Ledger execution failed: '.$e->getMessage());
                $reportData = null;
            }
        }

        return Inertia::render('Accounting/Reports/GeneralLedger', [
            'accounts' => $accounts,
            'selectedAccountId' => $accountId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'ledger' => $this->formatGeneralLedgerData($reportData),
        ]);
    }

    public function trialBalance(Request $request): TrialBalanceResource|Response
    {
        $fromDate = $request->string('from_date', now()->startOfYear()->toDateString())->toString();
        $toDate = $request->string('to_date', now()->toDateString())->toString();

        if ($request->wantsJson()) {
            $request->validate([
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            ]);

            $data = $this->trialBalance->execute(
                tenantId: $this->currentTenant->id(),
                fromDate: CarbonImmutable::parse($fromDate),
                toDate: CarbonImmutable::parse($toDate),
            );

            return new TrialBalanceResource($data);
        }

        $data = $this->trialBalance->execute(
            tenantId: $this->currentTenant->id(),
            fromDate: CarbonImmutable::parse($fromDate),
            toDate: CarbonImmutable::parse($toDate),
        );

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'trialBalance' => $data,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function trialBalancePrint(Request $request): Response
    {
        $fromDate = $request->string('from_date', now()->startOfYear()->toDateString())->toString();
        $toDate = $request->string('to_date', now()->toDateString())->toString();

        $data = $this->trialBalance->execute(
            tenantId: $this->currentTenant->id(),
            fromDate: CarbonImmutable::parse($fromDate),
            toDate: CarbonImmutable::parse($toDate),
        );

        return Inertia::render('Accounting/Reports/TrialBalancePrint', [
            'trialBalance' => $data,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function generalLedgerPrint(Request $request): Response
    {
        $accounts = Account::where('tenant_id', $this->currentTenant->id())
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $accountId = $request->integer('account_id', $accounts->first()?->id ?? 0);
        $fromDate = $request->string('from_date', now()->startOfMonth()->toDateString())->toString();
        $toDate = $request->string('to_date', now()->toDateString())->toString();

        $reportData = null;
        if ($accountId > 0) {
            try {
                $reportData = $this->generalLedger->execute(
                    tenantId: $this->currentTenant->id(),
                    accountId: $accountId,
                    fromDate: CarbonImmutable::parse($fromDate),
                    toDate: CarbonImmutable::parse($toDate),
                );
            } catch (\Throwable $e) {
                Log::warning('General Ledger Print execution failed: '.$e->getMessage());
                $reportData = null;
            }
        }

        return Inertia::render('Accounting/Reports/GeneralLedgerPrint', [
            'accounts' => $accounts,
            'selectedAccountId' => $accountId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'ledger' => $this->formatGeneralLedgerData($reportData),
        ]);
    }

    public function accountBalance(int $accountId): AccountBalanceResource
    {
        $data = $this->accountBalance->execute(
            tenantId: $this->currentTenant->id(),
            accountId: $accountId,
        );

        return new AccountBalanceResource($data);
    }

    public function payableAging(
        Request $request,
        GeneratePayableAgingAction $action,
    ): JsonResponse|Response {
        $asOfDate = $request->query('as_of_date');

        $rawBills = $action->execute(
            currentTenant: $this->currentTenant,
            asOfDate: $asOfDate,
        );

        if ($request->wantsJson()) {
            return response()->json($rawBills);
        }

        $summary = [
            'current' => '0.00',
            'days_1_30' => '0.00',
            'days_31_60' => '0.00',
            'days_61_90' => '0.00',
            'days_90_plus' => '0.00',
            'total' => '0.00',
        ];

        $vendorsMap = [];

        foreach ($rawBills as $item) {
            $amt = (float) $item['outstanding_amount'];
            $bucket = match ($item['bucket']) {
                'current' => 'current',
                '1_30' => 'days_1_30',
                '31_60' => 'days_31_60',
                '61_90' => 'days_61_90',
                default => 'days_90_plus',
            };

            $summary[$bucket] = bcadd($summary[$bucket], (string) $amt, 2);
            $summary['total'] = bcadd($summary['total'], (string) $amt, 2);

            $vId = $item['vendor_id'];
            if (! isset($vendorsMap[$vId])) {
                $vendorsMap[$vId] = [
                    'vendor_id' => $vId,
                    'vendor_code' => $item['vendor_code'],
                    'vendor_name' => $item['vendor_name'],
                    'current' => '0.00',
                    'days_1_30' => '0.00',
                    'days_31_60' => '0.00',
                    'days_61_90' => '0.00',
                    'days_90_plus' => '0.00',
                    'total' => '0.00',
                ];
            }

            $vendorsMap[$vId][$bucket] = bcadd($vendorsMap[$vId][$bucket], (string) $amt, 2);
            $vendorsMap[$vId]['total'] = bcadd($vendorsMap[$vId]['total'], (string) $amt, 2);
        }

        return Inertia::render('Accounting/Reports/PayableAging', [
            'report' => [
                'as_of_date' => $asOfDate ?: now()->toDateString(),
                'summary' => $summary,
                'vendors' => array_values($vendorsMap),
                'items' => $rawBills,
            ],
            'asOfDate' => $asOfDate ?: now()->toDateString(),
        ]);
    }

    public function receivableAging(
        Request $request,
        GenerateReceivableAgingAction $action,
    ): JsonResponse|Response {
        $asOfDate = $request->query('as_of_date');

        $rawInvoices = $action->execute(
            currentTenant: $this->currentTenant,
            asOfDate: $asOfDate,
        );

        if ($request->wantsJson()) {
            return response()->json($rawInvoices);
        }

        $summary = [
            'current' => '0.00',
            'days_1_30' => '0.00',
            'days_31_60' => '0.00',
            'days_61_90' => '0.00',
            'days_90_plus' => '0.00',
            'total' => '0.00',
        ];

        $customersMap = [];

        foreach ($rawInvoices as $item) {
            $amt = (float) $item['outstanding_amount'];
            $bucket = match ($item['bucket']) {
                'current' => 'current',
                '1_30' => 'days_1_30',
                '31_60' => 'days_31_60',
                '61_90' => 'days_61_90',
                default => 'days_90_plus',
            };

            $summary[$bucket] = bcadd($summary[$bucket], (string) $amt, 2);
            $summary['total'] = bcadd($summary['total'], (string) $amt, 2);

            $cId = $item['customer_id'];
            if (! isset($customersMap[$cId])) {
                $customersMap[$cId] = [
                    'customer_id' => $cId,
                    'customer_code' => $item['customer_code'],
                    'customer_name' => $item['customer_name'],
                    'current' => '0.00',
                    'days_1_30' => '0.00',
                    'days_31_60' => '0.00',
                    'days_61_90' => '0.00',
                    'days_90_plus' => '0.00',
                    'total' => '0.00',
                ];
            }

            $customersMap[$cId][$bucket] = bcadd($customersMap[$cId][$bucket], (string) $amt, 2);
            $customersMap[$cId]['total'] = bcadd($customersMap[$cId]['total'], (string) $amt, 2);
        }

        return Inertia::render('Accounting/Reports/ReceivableAging', [
            'report' => [
                'as_of_date' => $asOfDate ?: now()->toDateString(),
                'summary' => $summary,
                'customers' => array_values($customersMap),
                'items' => $rawInvoices,
            ],
            'asOfDate' => $asOfDate ?: now()->toDateString(),
        ]);
    }

    public function customerStatement(
        Request $request,
        GenerateCustomerStatementAction $action,
        ?Customer $customer = null,
    ): JsonResponse|Response {
        if (! $customer && $request->filled('customer_id')) {
            $customer = Customer::where('tenant_id', $this->currentTenant->id())
                ->where(function ($q) use ($request) {
                    $q->where('id', $request->input('customer_id'))
                        ->orWhere('public_id', $request->input('customer_id'));
                })->first();
        }

        if (! $customer) {
            $customer = Customer::where('tenant_id', $this->currentTenant->id())
                ->first();
        }

        abort_unless($customer, 404, 'No customers found.');

        $fromDate = $request->query('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $data = $action->execute(
            customer: $customer,
            fromDate: $fromDate,
            toDate: $toDate,
            currentTenant: $this->currentTenant,
        );

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Accounting/Reports/CustomerStatement', [
            'customer' => $customer,
            'statement' => $data,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function customerBalance(
        Customer $customer,
        GenerateCustomerBalanceAction $action,
    ): JsonResponse {
        $data = $action->execute(
            customer: $customer,
            currentTenant: $this->currentTenant,
        );

        return response()->json($data);
    }

    public function vendorBalance(
        Vendor $vendor,
        GenerateVendorBalanceAction $action,
    ): JsonResponse {
        $data = $action->execute(
            vendor: $vendor,
            currentTenant: $this->currentTenant,
        );

        return response()->json($data);
    }

    public function vendorStatement(
        Request $request,
        Vendor $vendor,
        GenerateVendorStatementAction $action,
    ): JsonResponse|Response {
        $fromDate = $request->query('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->query('to_date', now()->toDateString());

        $data = $action->execute(
            vendor: $vendor,
            fromDate: $fromDate,
            toDate: $toDate,
            currentTenant: $this->currentTenant,
        );

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Accounting/Reports/VendorStatement', [
            'vendor' => $vendor,
            'statement' => $data,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    private function formatGeneralLedgerData($reportData): ?array
    {
        if (! $reportData) {
            return null;
        }

        return [
            'accountId' => $reportData->accountId,
            'accountCode' => $reportData->accountCode,
            'accountName' => $reportData->accountName,
            'normalBalance' => $reportData->normalBalance,
            'fromDate' => $reportData->fromDate->toDateString(),
            'toDate' => $reportData->toDate->toDateString(),
            'openingBalance' => (string) $reportData->openingBalance,
            'totalDebit' => (string) $reportData->totalDebit,
            'totalCredit' => (string) $reportData->totalCredit,
            'closingBalance' => (string) $reportData->closingBalance,
            'entries' => array_map(function ($line) {
                return [
                    'id' => $line['journal_entry_id'] ?? 0,
                    'journal_entry_id' => $line['journal_entry_id'] ?? 0,
                    'journal_public_id' => $line['journal_public_id'] ?? '',
                    'entryNumber' => $line['entry_number'] ?? '',
                    'entry_number' => $line['entry_number'] ?? '',
                    'entryDate' => $line['entry_date'] ?? '',
                    'entry_date' => $line['entry_date'] ?? '',
                    'description' => $line['description'] ?? '',
                    'debit' => $line['debit'] ?? '0',
                    'credit' => $line['credit'] ?? '0',
                    'balance' => $line['balance'] ?? '0',
                    'referenceType' => null,
                    'referenceId' => null,
                ];
            }, $reportData->lines ?? []),
            'lines' => $reportData->lines ?? [],
        ];
    }
}
