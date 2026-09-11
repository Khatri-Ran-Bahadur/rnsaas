<?php

namespace Modules\Accounting\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Application\Actions\Customers\CreateCustomerAction;
use Modules\Accounting\Application\Actions\Customers\ToggleCustomerStatusAction;
use Modules\Accounting\Application\Actions\Customers\UpdateCustomerAction;
use Modules\Accounting\Application\Actions\Reports\GenerateCustomerBalanceAction;
use Modules\Accounting\Application\DTOs\Customers\CreateCustomerData;
use Modules\Accounting\Application\DTOs\Customers\UpdateCustomerData;
use Modules\Accounting\Http\Requests\Customers\StoreCustomerRequest;
use Modules\Accounting\Http\Requests\Customers\UpdateCustomerRequest;
use Modules\Accounting\Http\Resources\Customers\CustomerResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\SalesInvoice;

class CustomerController extends Controller
{
    public function index(
        Request $request,
        CurrentTenant $currentTenant,
    ): Response|JsonResponse {
        $query = Customer::query()
            ->where('tenant_id', $currentTenant->id())
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where(function ($query) use ($request) {
                    $search = $request->string('search')->toString();

                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'status',
                    $request->string('status')->toString()
                )
            )
            ->with('receivableAccount')
            ->orderBy('name');

        if ($request->wantsJson()) {
            return response()->json(
                CustomerResource::collection($query->paginate(20)->withQueryString())
            );
        }

        $customers = $query->paginate(15)->withQueryString();

        $stats = [
            'total_customers' => Customer::where('tenant_id', $currentTenant->id())->count(),
            'active_customers' => Customer::where('tenant_id', $currentTenant->id())->where('status', 'active')->count(),
            'total_receivables' => (string) SalesInvoice::where('tenant_id', $currentTenant->id())
                ->whereIn('status', ['issued', 'posted'])
                ->sum('grand_total'),
            'overdue_receivables' => (string) SalesInvoice::where('tenant_id', $currentTenant->id())
                ->whereIn('status', ['issued', 'posted'])
                ->whereDate('due_date', '<', now())
                ->sum('grand_total'),
        ];

        return Inertia::render('Accounting/Customers/Index', [
            'customers' => $customers,
            'stats' => $stats,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
            ],
        ]);
    }

    public function create(CurrentTenant $currentTenant): Response
    {
        $accounts = Account::where('tenant_id', $currentTenant->id())
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Accounting/Customers/Create', [
            'accounts' => $accounts,
        ]);
    }

    public function store(
        StoreCustomerRequest $request,
        CreateCustomerAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|JsonResponse {
        $customer = $action->execute(
            data: new CreateCustomerData(
                name: $request->string('name')->toString(),
                customerCode: $request->string('customer_code')->toString(),
                email: $request->input('email'),
                phone: $request->input('phone'),
                taxNumber: $request->input('tax_number'),
                billingAddressLine1: $request->input('billing_address_line_1'),
                billingAddressLine2: $request->input('billing_address_line_2'),
                billingCity: $request->input('billing_city'),
                billingState: $request->input('billing_state'),
                billingPostcode: $request->input('billing_postcode'),
                billingCountry: $request->input('billing_country'),
                receivableAccountId: $request->integer('receivable_account_id'),
                creditLimit: $request->input('credit_limit', '0'),
                paymentTermsDays: $request->integer('payment_terms_days', 0),
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return (new CustomerResource($customer))
                ->response()
                ->setStatusCode(201);
        }

        return redirect()
            ->route('admin.accounting.customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    public function show(
        Customer $customer,
        CurrentTenant $currentTenant,
        GenerateCustomerBalanceAction $balanceAction,
        Request $request,
    ): CustomerResource|Response {
        abort_unless(
            $customer->tenant_id === $currentTenant->id(),
            404
        );

        $customer->load([
            'receivableAccount',
            'attachments',
        ]);

        if ($request->wantsJson()) {
            return new CustomerResource($customer);
        }

        $balance = $balanceAction->execute($customer, $currentTenant);

        $invoices = SalesInvoice::where('tenant_id', $currentTenant->id())
            ->where('customer_id', $customer->id)
            ->latest('invoice_date')
            ->limit(10)
            ->get();

        return Inertia::render('Accounting/Customers/Show', [
            'customer' => $customer,
            'balance' => $balance,
            'invoices' => $invoices,
        ]);
    }

    public function edit(
        Customer $customer,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            $customer->tenant_id === $currentTenant->id(),
            404
        );

        $accounts = Account::where('tenant_id', $currentTenant->id())
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Accounting/Customers/Edit', [
            'customer' => $customer,
            'accounts' => $accounts,
        ]);
    }

    public function update(
        UpdateCustomerRequest $request,
        Customer $customer,
        UpdateCustomerAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|JsonResponse {
        $customer = $action->execute(
            customer: $customer,
            data: new UpdateCustomerData(
                name: $request->string('name')->toString(),
                email: $request->input('email'),
                phone: $request->input('phone'),
                taxNumber: $request->input('tax_number'),
                billingAddressLine1: $request->input('billing_address_line_1'),
                billingAddressLine2: $request->input('billing_address_line_2'),
                billingCity: $request->input('billing_city'),
                billingState: $request->input('billing_state'),
                billingPostcode: $request->input('billing_postcode'),
                billingCountry: $request->input('billing_country'),
                receivableAccountId: $request->integer('receivable_account_id'),
                creditLimit: $request->input('credit_limit', '0'),
                paymentTermsDays: $request->integer('payment_terms_days', 0),
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new CustomerResource($customer);
        }

        return redirect()
            ->route('admin.accounting.customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function toggleStatus(
        Customer $customer,
        ToggleCustomerStatusAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|JsonResponse {
        $customer = $action->execute(
            customer: $customer,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new CustomerResource($customer);
        }

        return back()->with('success', 'Customer status updated successfully.');
    }
}
