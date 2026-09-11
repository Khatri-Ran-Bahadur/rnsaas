<?php

namespace Modules\Accounting\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Application\Actions\Payments\AllocateCustomerPaymentAction;
use Modules\Accounting\Application\Actions\Payments\CreateCustomerPaymentAction;
use Modules\Accounting\Application\Actions\Payments\PostCustomerPaymentAction;
use Modules\Accounting\Application\DTOs\Payments\CreateCustomerPaymentData;
use Modules\Accounting\Http\Requests\Payments\StoreCustomerPaymentRequest;
use Modules\Accounting\Http\Resources\Payments\CustomerPaymentResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\CustomerPayment;
use Modules\Accounting\Models\SalesInvoice;

class CustomerPaymentController extends Controller
{
    public function index(
        Request $request,
        CurrentTenant $currentTenant,
    ): Response|JsonResponse {
        $query = CustomerPayment::query()
            ->where('tenant_id', $currentTenant->id())
            ->with(['customer', 'bankAccount'])
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->string('search')->toString();

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('payment_number', 'like', "%{$search}%")
                            ->orWhere('reference', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    });
                }
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->when(
                $request->filled('customer_id'),
                fn ($query) => $query->where('customer_id', $request->integer('customer_id'))
            )
            ->latest('payment_date');

        if ($request->wantsJson()) {
            return response()->json(
                CustomerPaymentResource::collection($query->paginate(20)->withQueryString())
            );
        }

        $payments = $query->paginate(15)->withQueryString();

        $stats = [
            'total_collected' => (string) CustomerPayment::where('tenant_id', $currentTenant->id())->where('status', 'posted')->sum('amount'),
            'posted_count' => CustomerPayment::where('tenant_id', $currentTenant->id())->where('status', 'posted')->count(),
            'draft_count' => CustomerPayment::where('tenant_id', $currentTenant->id())->where('status', 'draft')->count(),
        ];

        return Inertia::render('Accounting/Payments/Index', [
            'payments' => $payments,
            'stats' => $stats,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
                'customer_id' => $request->query('customer_id', ''),
            ],
        ]);
    }

    public function create(Request $request, CurrentTenant $currentTenant): Response
    {
        $customers = Customer::where('tenant_id', $currentTenant->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'public_id', 'customer_code', 'name']);

        $bankAccounts = Account::where('tenant_id', $currentTenant->id())
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $openInvoices = SalesInvoice::where('tenant_id', $currentTenant->id())
            ->whereIn('status', ['issued', 'posted'])
            ->with('customer:id,name')
            ->orderBy('due_date')
            ->get(['id', 'public_id', 'customer_id', 'invoice_number', 'due_date', 'grand_total']);

        $nextNumber = 'RCP-'.date('Ymd').'-'.str_pad((string) (CustomerPayment::where('tenant_id', $currentTenant->id())->count() + 1), 4, '0', STR_PAD_LEFT);

        return Inertia::render('Accounting/Payments/Create', [
            'customers' => $customers,
            'bankAccounts' => $bankAccounts,
            'openInvoices' => $openInvoices,
            'suggestedPaymentNumber' => $nextNumber,
            'selectedCustomerId' => $request->query('customer_id'),
        ]);
    }

    public function store(
        StoreCustomerPaymentRequest $request,
        CreateCustomerPaymentAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|JsonResponse {
        $payment = $action->execute(
            data: new CreateCustomerPaymentData(
                customerId: $request->integer('customer_id'),
                paymentNumber: $request->string('payment_number')->toString(),
                paymentDate: $request->date('payment_date'),
                amount: $request->input('amount'),
                currency: $request->string('currency', 'MYR')->toString(),
                bankAccountId: $request->integer('bank_account_id'),
                paymentMethod: $request->string('payment_method')->toString(),
                reference: $request->input('reference'),
                notes: $request->input('notes'),
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return (new CustomerPaymentResource($payment))
                ->response()
                ->setStatusCode(201);
        }

        return redirect()
            ->route('admin.accounting.payments.show', $payment)
            ->with('success', 'Customer payment created successfully.');
    }

    public function show(
        CustomerPayment $payment,
        CurrentTenant $currentTenant,
        Request $request,
    ): CustomerPaymentResource|Response {
        abort_unless(
            $payment->tenant_id === $currentTenant->id(),
            404
        );

        $payment->load([
            'customer',
            'bankAccount',
            'allocations.invoice',
            'attachments',
            'journalEntry',
        ]);

        if ($request->wantsJson()) {
            return new CustomerPaymentResource($payment);
        }

        $openInvoices = [];
        if ($payment->status === 'draft') {
            $openInvoices = SalesInvoice::where('tenant_id', $currentTenant->id())
                ->where('customer_id', $payment->customer_id)
                ->whereIn('status', ['issued', 'posted'])
                ->orderBy('due_date')
                ->get(['id', 'public_id', 'invoice_number', 'due_date', 'grand_total']);
        }

        return Inertia::render('Accounting/Payments/Show', [
            'payment' => $payment,
            'openInvoices' => $openInvoices,
        ]);
    }

    public function allocate(
        Request $request,
        CustomerPayment $payment,
        AllocateCustomerPaymentAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|JsonResponse {
        $allocations = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => [
                'required',
                'integer',
            ],
            'allocations.*.amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        $action->execute(
            payment: $payment,
            allocations: $allocations['allocations'],
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Payment allocated successfully.']);
        }

        return back()->with('success', 'Payment allocated successfully.');
    }

    public function post(
        CustomerPayment $payment,
        PostCustomerPaymentAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|JsonResponse {
        $action->execute(
            payment: $payment,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Customer payment posted successfully.']);
        }

        return back()->with('success', 'Customer payment posted successfully.');
    }
}
