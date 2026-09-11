<?php

namespace Modules\Accounting\Http\Controllers\VendorPayments;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Accounting\Application\Actions\VendorPayments\AllocateVendorPaymentAction;
use Modules\Accounting\Application\Actions\VendorPayments\CreateVendorPaymentAction;
use Modules\Accounting\Application\Actions\VendorPayments\PostVendorPaymentAction;
use Modules\Accounting\Application\DTOs\VendorPayments\CreateVendorPaymentData;
use Modules\Accounting\Http\Requests\VendorPayments\StoreVendorPaymentRequest;
use Modules\Accounting\Http\Resources\VendorPayments\VendorPaymentResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\Vendor;
use Modules\Accounting\Models\VendorPayment;

class VendorPaymentController extends Controller
{
    public function index(
        Request $request,
        CurrentTenant $currentTenant,
    ): InertiaResponse|JsonResponse {
        $tenantId = $currentTenant->id();

        $payments = VendorPayment::query()
            ->where('tenant_id', $tenantId)
            ->with(['vendor', 'bankAccount'])
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->string('search')->toString();

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('payment_number', 'like', "%{$search}%")
                            ->orWhere('reference', 'like', "%{$search}%")
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
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        if ($request->wantsJson()) {
            return VendorPaymentResource::collection($payments)->response();
        }

        $allPayments = VendorPayment::query()->where('tenant_id', $tenantId)->get();
        $stats = [
            'total_paid' => (string) $allPayments->sum('amount'),
            'posted_count' => $allPayments->where('status.value', 'posted')->count(),
            'draft_count' => $allPayments->where('status.value', 'draft')->count(),
        ];

        return Inertia::render('Accounting/VendorPayments/Index', [
            'payments' => $payments,
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
            ->get(['id', 'public_id', 'vendor_code', 'name']);

        $bankAccounts = Account::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Accounting/VendorPayments/Create', [
            'vendors' => $vendors,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function store(
        StoreVendorPaymentRequest $request,
        CreateVendorPaymentAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|JsonResponse {
        $payment = $action->execute(
            data: new CreateVendorPaymentData(
                vendorId: $request->integer('vendor_id'),
                paymentNumber: $request->string('payment_number')->toString(),
                paymentDate: $request->date('payment_date'),
                amount: (string) $request->input('amount'),
                currency: $request->string('currency')->toString(),
                bankAccountId: $request->integer('bank_account_id'),
                paymentMethod: $request->string('payment_method')->toString(),
                reference: $request->input('reference'),
                notes: $request->input('notes'),
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return (new VendorPaymentResource($payment))->response()->setStatusCode(201);
        }

        return redirect()
            ->route('admin.accounting.vendor-payments.show', $payment)
            ->with('success', 'Vendor payment created successfully.');
    }

    public function show(
        Request $request,
        VendorPayment $payment,
        CurrentTenant $currentTenant,
    ): VendorPaymentResource|InertiaResponse {
        abort_unless(
            $payment->tenant_id === $currentTenant->id(),
            404
        );

        $payment->load([
            'vendor',
            'bankAccount',
            'allocations.purchaseBill',
            'attachments',
            'journalEntry.lines.account',
        ]);

        if ($request->wantsJson()) {
            return new VendorPaymentResource($payment);
        }

        $openBills = PurchaseBill::query()
            ->where('tenant_id', $currentTenant->id())
            ->where('vendor_id', $payment->vendor_id)
            ->whereIn('status', ['issued', 'posted'])
            ->with('allocations')
            ->get();

        return Inertia::render('Accounting/VendorPayments/Show', [
            'payment' => (new VendorPaymentResource($payment))->resolve(),
            'openBills' => $openBills,
        ]);
    }

    public function allocate(
        Request $request,
        VendorPayment $payment,
        AllocateVendorPaymentAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|VendorPaymentResource {
        $data = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.purchase_bill_id' => [
                'required',
                'integer',
            ],
            'allocations.*.amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $updatedPayment = $action->execute(
            payment: $payment,
            allocations: $data['allocations'],
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new VendorPaymentResource($updatedPayment);
        }

        return back()
            ->with('success', 'Payment allocated successfully.');
    }

    public function post(
        VendorPayment $payment,
        PostVendorPaymentAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|VendorPaymentResource {
        $updatedPayment = $action->execute(
            payment: $payment,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new VendorPaymentResource($updatedPayment);
        }

        return back()
            ->with('success', 'Vendor payment posted successfully.');
    }
}
