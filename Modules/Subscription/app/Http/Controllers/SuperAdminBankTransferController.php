<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\SubscriptionBankTransfer;
use Modules\Subscription\Models\TenantSubscription;
use Modules\Tenancy\Domain\Enums\TenantStatus;
use Modules\Tenancy\Models\Tenant;

class SuperAdminBankTransferController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search', '');

        $query = SubscriptionBankTransfer::query()
            ->with(['tenant', 'user', 'plan', 'approver'])
            ->when($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'], true), function (Builder $q) use ($status) {
                $q->where('status', $status);
            })
            ->when(! empty($search), function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('transaction_reference', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn (Builder $t) => $t->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('plan', fn (Builder $p) => $p->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id');

        $transfers = $query->paginate(15)->withQueryString();

        $counts = [
            'all' => SubscriptionBankTransfer::query()->count(),
            'pending' => SubscriptionBankTransfer::query()->where('status', 'pending')->count(),
            'approved' => SubscriptionBankTransfer::query()->where('status', 'approved')->count(),
            'rejected' => SubscriptionBankTransfer::query()->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Subscription/SuperAdmin/BankTransfers', [
            'transfers' => $transfers,
            'counts' => $counts,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function approve(Request $request, SubscriptionBankTransfer $transfer): RedirectResponse
    {
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'This bank transfer request is not pending.');
        }

        DB::transaction(function () use ($request, $transfer) {
            $transfer->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
            ]);

            $metadata = $transfer->metadata ?? [];
            $tenant = Tenant::query()->find($transfer->tenant_id);

            if (! $tenant) {
                return;
            }

            if (($metadata['type'] ?? '') === 'branch_addon') {
                $activeSub = $tenant->subscriptions()
                    ->whereIn('status', ['active', 'trialing'])
                    ->latest('id')
                    ->first();

                if ($activeSub) {
                    $targetBranches = (int) ($metadata['target_total_branches'] ?? ($activeSub->allowed_branches + (int) ($metadata['additional_branches'] ?? 1)));
                    $activeSub->update([
                        'allowed_branches' => $targetBranches,
                    ]);
                }
            } else {
                // Activate plan subscription
                $periodEnds = match ($transfer->billing_cycle) {
                    'yearly' => now()->addYear(),
                    'lifetime' => null,
                    default => now()->addMonth(),
                };

                // Check for existing pending subscription created by submitBankTransfer
                $existingSub = TenantSubscription::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('status', SubscriptionStatus::Pending)
                    ->latest('id')
                    ->first();

                if ($existingSub) {
                    $existingSub->update([
                        'plan_id' => $transfer->plan_id,
                        'status' => SubscriptionStatus::Active,
                        'allowed_branches' => $transfer->branches_count,
                        'starts_at' => now(),
                        'current_period_starts_at' => now(),
                        'current_period_ends_at' => $periodEnds,
                    ]);
                } else {
                    TenantSubscription::create([
                        'tenant_id' => $tenant->id,
                        'plan_id' => $transfer->plan_id,
                        'status' => SubscriptionStatus::Active,
                        'allowed_branches' => $transfer->branches_count,
                        'starts_at' => now(),
                        'current_period_starts_at' => now(),
                        'current_period_ends_at' => $periodEnds,
                    ]);
                }

                // Ensure tenant status is active
                $tenant->update(['status' => TenantStatus::Active]);
            }
        });

        return back()->with('success', "Bank transfer #{$transfer->transaction_reference} has been approved and organization subscription is activated!");
    }

    public function reject(Request $request, SubscriptionBankTransfer $transfer): RedirectResponse
    {
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'This bank transfer request is not pending.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $transfer, $validated) {
            $transfer->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
            ]);

            // Cancel any pending tenant subscription linked to this
            TenantSubscription::query()
                ->where('tenant_id', $transfer->tenant_id)
                ->where('status', SubscriptionStatus::Pending)
                ->update(['status' => SubscriptionStatus::Canceled]);
        });

        return back()->with('success', "Bank transfer #{$transfer->transaction_reference} has been rejected.");
    }
}
