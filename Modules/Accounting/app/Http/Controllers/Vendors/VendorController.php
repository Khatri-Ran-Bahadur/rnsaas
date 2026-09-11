<?php

namespace Modules\Accounting\Http\Controllers\Vendors;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Accounting\Application\Actions\Reports\GenerateVendorBalanceAction;
use Modules\Accounting\Application\Actions\Vendors\CreateVendorAction;
use Modules\Accounting\Application\Actions\Vendors\ToggleVendorStatusAction;
use Modules\Accounting\Application\Actions\Vendors\UpdateVendorAction;
use Modules\Accounting\Application\DTOs\Vendors\CreateVendorData;
use Modules\Accounting\Application\DTOs\Vendors\UpdateVendorData;
use Modules\Accounting\Http\Requests\Vendors\StoreVendorRequest;
use Modules\Accounting\Http\Requests\Vendors\UpdateVendorRequest;
use Modules\Accounting\Http\Resources\Vendors\VendorResource;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\PurchaseBill;
use Modules\Accounting\Models\Vendor;
use Modules\Accounting\Models\VendorPayment;

class VendorController extends Controller
{
    public function index(
        Request $request,
        CurrentTenant $currentTenant,
    ): InertiaResponse|JsonResponse {
        $tenantId = $currentTenant->id();

        $vendors = Vendor::query()
            ->where('tenant_id', $tenantId)
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->string('search')->toString();

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('vendor_code', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
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
            ->with('payableAccount')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        if ($request->wantsJson()) {
            return VendorResource::collection($vendors)->response();
        }

        $allVendors = Vendor::query()->where('tenant_id', $tenantId)->get();
        $stats = [
            'total' => $allVendors->count(),
            'active' => $allVendors->where('status.value', 'active')->count(),
            'inactive' => $allVendors->where('status.value', 'inactive')->count(),
        ];

        return Inertia::render('Accounting/Vendors/Index', [
            'vendors' => $vendors,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', ''),
            ],
            'stats' => $stats,
        ]);
    }

    public function create(CurrentTenant $currentTenant): InertiaResponse
    {
        $accounts = Account::query()
            ->where('tenant_id', $currentTenant->id())
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Accounting/Vendors/Create', [
            'accounts' => $accounts,
        ]);
    }

    public function store(
        StoreVendorRequest $request,
        CreateVendorAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|JsonResponse {
        $vendor = $action->execute(
            data: new CreateVendorData(
                vendorCode: $request->string('vendor_code')->toString(),
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
                payableAccountId: $request->filled('payable_account_id') ? $request->integer('payable_account_id') : null,
                creditLimit: (string) $request->input('credit_limit', '0'),
                paymentTermsDays: $request->integer('payment_terms_days', 0),
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return (new VendorResource($vendor))->response()->setStatusCode(201);
        }

        return redirect()
            ->route('admin.accounting.vendors.show', $vendor)
            ->with('success', 'Vendor created successfully.');
    }

    public function show(
        Request $request,
        Vendor $vendor,
        CurrentTenant $currentTenant,
        GenerateVendorBalanceAction $balanceAction,
    ): VendorResource|InertiaResponse {
        abort_unless(
            $vendor->tenant_id === $currentTenant->id(),
            404
        );

        $vendor->load([
            'payableAccount',
            'attachments',
        ]);

        if ($request->wantsJson()) {
            return new VendorResource($vendor);
        }

        $balance = $balanceAction->execute($vendor, $currentTenant);

        $recentBills = PurchaseBill::query()
            ->where('tenant_id', $currentTenant->id())
            ->where('vendor_id', $vendor->id)
            ->latest('bill_date')
            ->take(10)
            ->get();

        $recentPayments = VendorPayment::query()
            ->where('tenant_id', $currentTenant->id())
            ->where('vendor_id', $vendor->id)
            ->latest('payment_date')
            ->take(10)
            ->get();

        return Inertia::render('Accounting/Vendors/Show', [
            'vendor' => (new VendorResource($vendor))->resolve(),
            'balance' => $balance,
            'recentBills' => $recentBills,
            'recentPayments' => $recentPayments,
        ]);
    }

    public function edit(
        Vendor $vendor,
        CurrentTenant $currentTenant,
    ): InertiaResponse {
        abort_unless(
            $vendor->tenant_id === $currentTenant->id(),
            404
        );

        $vendor->load('payableAccount');

        $accounts = Account::query()
            ->where('tenant_id', $currentTenant->id())
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Accounting/Vendors/Edit', [
            'vendor' => (new VendorResource($vendor))->resolve(),
            'accounts' => $accounts,
        ]);
    }

    public function update(
        UpdateVendorRequest $request,
        Vendor $vendor,
        UpdateVendorAction $action,
        CurrentTenant $currentTenant,
    ): RedirectResponse|VendorResource {
        $updatedVendor = $action->execute(
            vendor: $vendor,
            data: new UpdateVendorData(
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
                payableAccountId: $request->filled('payable_account_id') ? $request->integer('payable_account_id') : null,
                creditLimit: (string) $request->input('credit_limit', '0'),
                paymentTermsDays: $request->integer('payment_terms_days', 0),
            ),
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new VendorResource($updatedVendor);
        }

        return redirect()
            ->route('admin.accounting.vendors.show', $updatedVendor)
            ->with('success', 'Vendor updated successfully.');
    }

    public function toggleStatus(
        Vendor $vendor,
        ToggleVendorStatusAction $action,
        CurrentTenant $currentTenant,
        Request $request,
    ): RedirectResponse|VendorResource {
        $updatedVendor = $action->execute(
            vendor: $vendor,
            currentTenant: $currentTenant,
        );

        if ($request->wantsJson()) {
            return new VendorResource($updatedVendor);
        }

        return back()
            ->with('success', 'Vendor status updated successfully.');
    }
}
