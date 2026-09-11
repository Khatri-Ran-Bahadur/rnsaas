<?php

namespace Modules\Accounting\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Accounting\Application\Actions\Invoices\CreateSalesInvoiceAction;
use Modules\Accounting\Application\Actions\Invoices\IssueSalesInvoiceAction;
use Modules\Accounting\Application\Actions\Invoices\PostSalesInvoiceAction;
use Modules\Accounting\Application\Actions\Invoices\UpdateSalesInvoiceAction;
use Modules\Accounting\Application\Actions\Invoices\VoidSalesInvoiceAction;
use Modules\Accounting\Application\DTOs\Invoices\CreateSalesInvoiceData;
use Modules\Accounting\Application\DTOs\Invoices\SalesInvoiceLineData;
use Modules\Accounting\Http\Requests\Invoices\StoreSalesInvoiceRequest;
use Modules\Accounting\Http\Resources\Invoices\SalesInvoiceResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\Customer;
use Modules\Accounting\Models\SalesInvoice;

class SalesInvoiceController extends Controller
{
    public function index(
        Request $request,
        CurrentTenant $currentTenant,
    ): Response|JsonResponse {
        $query = SalesInvoice::query()
            ->where('tenant_id', $currentTenant->id())
            ->with('customer')
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->string('search')->toString();

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('invoice_number', 'like', "%{$search}%")
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
            ->latest('invoice_date');

        if ($request->wantsJson()) {
            return response()->json(
                SalesInvoiceResource::collection($query->paginate(20)->withQueryString())
            );
        }

        $invoices = $query->paginate(15)->withQueryString();

        $stats = [
            'total_invoices' => SalesInvoice::where('tenant_id', $currentTenant->id())->count(),
            'draft_count' => SalesInvoice::where('tenant_id', $currentTenant->id())->where('status', 'draft')->count(),
            'issued_count' => SalesInvoice::where('tenant_id', $currentTenant->id())->whereIn('status', ['issued', 'posted'])->count(),
            'total_amount' => (string) SalesInvoice::where('tenant_id', $currentTenant->id())->sum('grand_total'),
        ];

        return Inertia::render('Accounting/Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
                'customer_id' => $request->query('customer_id', ''),
            ],
        ]);
    }

    public function create(CurrentTenant $currentTenant): Response
    {
        $customers = Customer::where('tenant_id', $currentTenant->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'public_id', 'customer_code', 'name', 'payment_terms_days']);

        $revenueAccounts = Account::where('tenant_id', $currentTenant->id())
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $nextNumber = 'INV-'.date('Ymd').'-'.str_pad((string) (SalesInvoice::where('tenant_id', $currentTenant->id())->count() + 1), 4, '0', STR_PAD_LEFT);

        return Inertia::render('Accounting/Invoices/Create', [
            'customers' => $customers,
            'accounts' => $revenueAccounts,
            'suggestedInvoiceNumber' => $nextNumber,
        ]);
    }

    public function store(
        StoreSalesInvoiceRequest $request,
        CreateSalesInvoiceAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|JsonResponse {
        $lines = collect($request->input('lines', []))
            ->values()
            ->map(
                fn (array $line, int $index) => new SalesInvoiceLineData(
                    lineNumber: $index + 1,
                    description: $line['description'],
                    quantity: $line['quantity'],
                    unitPrice: $line['unit_price'],
                    discountAmount: $line['discount_amount'] ?? '0',
                    taxRate: $line['tax_rate'] ?? '0',
                    taxAmount: $line['tax_amount'] ?? '0',
                    subtotal: $line['subtotal'],
                    total: $line['total'],
                    revenueAccountId: (int) $line['revenue_account_id'],
                )
            )
            ->all();

        $invoice = $action->execute(
            data: new CreateSalesInvoiceData(
                customerId: $request->integer('customer_id'),
                invoiceNumber: $request->string('invoice_number')->toString(),
                invoiceDate: $request->date('invoice_date'),
                dueDate: $request->date('due_date'),
                currency: $request->string('currency', 'MYR')->toString(),
                reference: $request->input('reference'),
                notes: $request->input('notes'),
                lines: $lines,
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return (new SalesInvoiceResource($invoice))
                ->response()
                ->setStatusCode(201);
        }

        return redirect()
            ->route('admin.accounting.invoices.show', $invoice)
            ->with('success', 'Sales invoice created successfully.');
    }

    public function show(
        SalesInvoice $invoice,
        CurrentTenant $currentTenant,
        Request $request,
    ): SalesInvoiceResource|Response {
        abort_unless(
            $invoice->tenant_id === $currentTenant->id(),
            404
        );

        $invoice->load([
            'customer',
            'lines.revenueAccount',
            'attachments',
            'journalEntry',
            'allocations.customerPayment',
        ]);

        if ($request->wantsJson()) {
            return new SalesInvoiceResource($invoice);
        }

        return Inertia::render('Accounting/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(
        SalesInvoice $invoice,
        CurrentTenant $currentTenant,
    ): Response {
        abort_unless(
            $invoice->tenant_id === $currentTenant->id(),
            404
        );

        abort_if(
            $invoice->status !== 'draft',
            422,
            'Only draft invoices can be edited.'
        );

        $invoice->load([
            'customer',
            'lines',
        ]);

        $customers = Customer::where('tenant_id', $currentTenant->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'public_id', 'customer_code', 'name']);

        $revenueAccounts = Account::where('tenant_id', $currentTenant->id())
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Accounting/Invoices/Edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'accounts' => $revenueAccounts,
        ]);
    }

    public function update(
        StoreSalesInvoiceRequest $request,
        SalesInvoice $invoice,
        UpdateSalesInvoiceAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|JsonResponse {
        abort_unless(
            $invoice->tenant_id === $currentTenant->id(),
            404
        );

        abort_if(
            $invoice->status !== 'draft',
            422,
            'Only draft invoices can be updated.'
        );

        $lines = collect($request->input('lines', []))
            ->values()
            ->map(
                fn (array $line, int $index) => new SalesInvoiceLineData(
                    lineNumber: $index + 1,
                    description: $line['description'],
                    quantity: $line['quantity'],
                    unitPrice: $line['unit_price'],
                    discountAmount: $line['discount_amount'] ?? '0',
                    taxRate: $line['tax_rate'] ?? '0',
                    taxAmount: $line['tax_amount'] ?? '0',
                    subtotal: $line['subtotal'],
                    total: $line['total'],
                    revenueAccountId: (int) $line['revenue_account_id'],
                )
            )
            ->all();

        $updatedInvoice = $action->execute(
            invoice: $invoice,
            data: new CreateSalesInvoiceData(
                customerId: $request->integer('customer_id'),
                invoiceNumber: $request->string('invoice_number')->toString(),
                invoiceDate: $request->date('invoice_date'),
                dueDate: $request->date('due_date'),
                currency: $request->string('currency', 'MYR')->toString(),
                reference: $request->input('reference'),
                notes: $request->input('notes'),
                lines: $lines,
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return (new SalesInvoiceResource($updatedInvoice))
                ->response()
                ->setStatusCode(200);
        }

        return redirect()
            ->route('admin.accounting.invoices.show', $updatedInvoice)
            ->with('success', 'Sales invoice updated successfully.');
    }

    public function issue(
        SalesInvoice $invoice,
        IssueSalesInvoiceAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|JsonResponse {
        $action->execute(
            invoice: $invoice,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Invoice issued successfully.']);
        }

        return back()->with('success', 'Invoice issued successfully.');
    }

    public function post(
        SalesInvoice $invoice,
        PostSalesInvoiceAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|JsonResponse {
        $action->execute(
            invoice: $invoice,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Invoice posted successfully.']);
        }

        return back()->with('success', 'Invoice posted successfully.');
    }

    public function void(
        SalesInvoice $invoice,
        VoidSalesInvoiceAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|JsonResponse {
        $action->execute(
            invoice: $invoice,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Invoice voided successfully.']);
        }

        return back()->with('success', 'Invoice voided successfully.');
    }
}
