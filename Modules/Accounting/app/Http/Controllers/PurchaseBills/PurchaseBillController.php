<?php

namespace Modules\Accounting\Http\Controllers\PurchaseBills;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Accounting\Application\Actions\PurchaseBills\CreatePurchaseBillAction;
use Modules\Accounting\Application\Actions\PurchaseBills\IssuePurchaseBillAction;
use Modules\Accounting\Application\Actions\PurchaseBills\PostPurchaseBillAction;
use Modules\Accounting\Application\Actions\PurchaseBills\UpdatePurchaseBillAction;
use Modules\Accounting\Application\Actions\PurchaseBills\VoidPurchaseBillAction;
use Modules\Accounting\Application\DTOs\PurchaseBills\CreatePurchaseBillData;
use Modules\Accounting\Application\DTOs\PurchaseBills\PurchaseBillLineData;
use Modules\Accounting\Http\Requests\PurchaseBills\StorePurchaseBillRequest;
use Modules\Accounting\Http\Resources\PurchaseBills\PurchaseBillResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\Vendor;
use Modules\Accounting\Models\VendorPaymentAllocation;

class PurchaseBillController extends Controller
{
    public function index(
        Request $request,
        CurrentTenant $currentTenant,
    ): InertiaResponse|JsonResponse {
        $tenantId = $currentTenant->id();

        $bills = PurchaseBill::query()
            ->where('tenant_id', $tenantId)
            ->with('vendor')
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->string('search')->toString();

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('bill_number', 'like', "%{$search}%")
                            ->orWhereHas(
                                'vendor',
                                fn ($query) => $query->where('name', 'like', "%{$search}%")
                            );
                    });
                }
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'status',
                    $request->string('status')->toString()
                )
            )
            ->latest('bill_date')
            ->paginate(15)
            ->withQueryString();

        if ($request->wantsJson()) {
            return PurchaseBillResource::collection($bills)->response();
        }

        $allBills = PurchaseBill::query()->where('tenant_id', $tenantId)->get();
        $stats = [
            'total_amount' => (string) $allBills->sum('total_amount'),
            'draft_count' => $allBills->where('status.value', 'draft')->count(),
            'issued_count' => $allBills->where('status.value', 'issued')->count(),
            'posted_count' => $allBills->where('status.value', 'posted')->count(),
        ];

        return Inertia::render('Accounting/PurchaseBills/Index', [
            'bills' => $bills,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
            ],
            'stats' => $stats,
        ]);
    }

    public function create(CurrentTenant $currentTenant): InertiaResponse
    {
        $tenantId = $currentTenant->id();

        $vendors = Vendor::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'public_id', 'vendor_code', 'name', 'payment_terms_days', 'payable_account_id']);

        $accounts = Account::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Accounting/PurchaseBills/Create', [
            'vendors' => $vendors,
            'accounts' => $accounts,
        ]);
    }

    public function store(
        StorePurchaseBillRequest $request,
        CreatePurchaseBillAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|JsonResponse {
        $lines = collect($request->input('lines', []))
            ->values()
            ->map(
                fn (array $line, int $index) => new PurchaseBillLineData(
                    lineNumber: (int) ($line['line_number'] ?? $index + 1),
                    description: $line['description'],
                    quantity: (string) $line['quantity'],
                    unitPrice: (string) $line['unit_price'],
                    discountAmount: (string) ($line['discount_amount'] ?? '0'),
                    taxRate: (string) ($line['tax_rate'] ?? '0'),
                    taxAmount: (string) ($line['tax_amount'] ?? '0'),
                    subtotal: (string) ($line['subtotal'] ?? '0'),
                    total: (string) ($line['total'] ?? '0'),
                    debitAccountId: (int) $line['debit_account_id'],
                    taxAccountId: ! empty($line['tax_account_id']) ? (int) $line['tax_account_id'] : null,
                )
            )
            ->all();

        $bill = $action->execute(
            data: new CreatePurchaseBillData(
                vendorId: $request->integer('vendor_id'),
                billNumber: $request->string('bill_number')->toString(),
                billDate: $request->date('bill_date'),
                dueDate: $request->date('due_date'),
                currency: $request->string('currency')->toString(),
                reference: (string) $request->input('reference', ''),
                notes: $request->input('notes'),
                lines: $lines,
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return (new PurchaseBillResource($bill))->response()->setStatusCode(201);
        }

        return redirect()
            ->route('admin.accounting.purchase-bills.show', $bill)
            ->with('success', 'Purchase bill created successfully.');
    }

    public function show(
        Request $request,
        PurchaseBill $bill,
        CurrentTenant $currentTenant,
    ): PurchaseBillResource|InertiaResponse {
        abort_unless(
            $bill->tenant_id === $currentTenant->id(),
            404
        );

        $bill->load([
            'vendor',
            'lines.debitAccount',
            'lines.taxAccount',
            'attachments',
            'journalEntry.lines.account',
        ]);

        if ($request->wantsJson()) {
            return new PurchaseBillResource($bill);
        }

        $allocations = VendorPaymentAllocation::query()
            ->where('purchase_bill_id', $bill->id)
            ->with('payment')
            ->get();

        return Inertia::render('Accounting/PurchaseBills/Show', [
            'bill' => (new PurchaseBillResource($bill))->resolve(),
            'allocations' => $allocations,
        ]);
    }

    public function edit(
        PurchaseBill $bill,
        CurrentTenant $currentTenant,
    ): InertiaResponse {
        abort_unless(
            $bill->tenant_id === $currentTenant->id(),
            404
        );

        abort_unless(
            $bill->status->value === 'draft',
            422,
            'Only draft purchase bills can be edited.'
        );

        $bill->load([
            'vendor',
            'lines',
        ]);

        $vendors = Vendor::query()
            ->where('tenant_id', $currentTenant->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'public_id', 'vendor_code', 'name', 'payment_terms_days', 'payable_account_id']);

        $accounts = Account::query()
            ->where('tenant_id', $currentTenant->id())
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Accounting/PurchaseBills/Edit', [
            'bill' => (new PurchaseBillResource($bill))->resolve(),
            'vendors' => $vendors,
            'accounts' => $accounts,
        ]);
    }

    public function update(
        StorePurchaseBillRequest $request,
        PurchaseBill $bill,
        UpdatePurchaseBillAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|PurchaseBillResource {
        abort_unless(
            $bill->tenant_id === $currentTenant->id(),
            404
        );

        $lines = collect($request->input('lines', []))
            ->values()
            ->map(
                fn (array $line, int $index) => new PurchaseBillLineData(
                    lineNumber: (int) ($line['line_number'] ?? $index + 1),
                    description: $line['description'],
                    quantity: (string) $line['quantity'],
                    unitPrice: (string) $line['unit_price'],
                    discountAmount: (string) ($line['discount_amount'] ?? '0'),
                    taxRate: (string) ($line['tax_rate'] ?? '0'),
                    taxAmount: (string) ($line['tax_amount'] ?? '0'),
                    subtotal: (string) ($line['subtotal'] ?? '0'),
                    total: (string) ($line['total'] ?? '0'),
                    debitAccountId: (int) $line['debit_account_id'],
                    taxAccountId: ! empty($line['tax_account_id']) ? (int) $line['tax_account_id'] : null,
                )
            )
            ->all();

        $updatedBill = $action->execute(
            bill: $bill,
            data: new CreatePurchaseBillData(
                vendorId: $request->integer('vendor_id'),
                billNumber: $request->string('bill_number')->toString(),
                billDate: $request->date('bill_date'),
                dueDate: $request->date('due_date'),
                currency: $request->string('currency')->toString(),
                reference: (string) $request->input('reference', ''),
                notes: $request->input('notes'),
                lines: $lines,
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new PurchaseBillResource($updatedBill);
        }

        return redirect()
            ->route('admin.accounting.purchase-bills.show', $updatedBill)
            ->with('success', 'Purchase bill updated successfully.');
    }

    public function issue(
        PurchaseBill $bill,
        IssuePurchaseBillAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|PurchaseBillResource {
        $updatedBill = $action->execute(
            bill: $bill,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new PurchaseBillResource($updatedBill);
        }

        return back()
            ->with('success', 'Purchase bill issued successfully.');
    }

    public function post(
        PurchaseBill $bill,
        PostPurchaseBillAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|PurchaseBillResource {
        $updatedBill = $action->execute(
            bill: $bill,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new PurchaseBillResource($updatedBill);
        }

        return back()
            ->with('success', 'Purchase bill posted successfully.');
    }

    public function void(
        PurchaseBill $bill,
        VoidPurchaseBillAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|PurchaseBillResource {
        $updatedBill = $action->execute(
            bill: $bill,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new PurchaseBillResource($updatedBill);
        }

        return back()
            ->with('success', 'Purchase bill voided successfully.');
    }
}
