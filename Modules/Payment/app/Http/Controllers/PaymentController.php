<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payment\Models\PaymentTransaction;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = PaymentTransaction::query()
            ->with([
                'tenant:id,name',
                'subscription:id,public_id,plan_id,status',
                'subscription.plan:id,name',
            ])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'status',
                    $request->string('status')->value(),
                ),
            )
            ->when(
                $request->filled('provider'),
                fn ($query) => $query->where(
                    'provider',
                    $request->string('provider')->value(),
                ),
            )
            ->when(
                $request->filled('search'),
                function ($query) use ($request): void {
                    $search = $request->string('search')->value();

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('public_id', 'like', "%{$search}%")
                            ->orWhere(
                                'provider_transaction_id',
                                'like',
                                "%{$search}%",
                            )
                            ->orWhereHas(
                                'tenant',
                                fn ($tenantQuery) => $tenantQuery->where(
                                    'name',
                                    'like',
                                    "%{$search}%",
                                ),
                            );
                    });
                },
            )
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Payment/Payments/Index', [
            'payments' => $payments,
            'filters' => [
                'search' => $request->string('search')->value(),
                'status' => $request->string('status')->value(),
                'provider' => $request->string('provider')->value(),
            ],
        ]);
    }
}