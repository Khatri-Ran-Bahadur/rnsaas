<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Plan;
use Modules\Subscription\Models\SubscriptionBankTransfer;
use Modules\SuperAdmin\Services\PlatformSettings;
use Modules\Tenancy\Models\Branch;

class TenantSubscriptionPortalController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly PlatformSettings $platformSettings,
    ) {}

    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->get();

        $subscription = $tenant->subscriptions()
            ->with(['plan.features'])
            ->latest('id')
            ->first();

        $pendingTransfer = SubscriptionBankTransfer::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->with(['plan', 'user'])
            ->latest('id')
            ->first();

        $allowedBranches = $subscription?->allowed_branches ?? 1;
        $usedBranches = Branch::query()->where('tenant_id', $tenant->id)->count();

        $bankSettings = $this->platformSettings->group('bank_transfer');

        return Inertia::render('Subscription/Portal/Index', [
            'subscription' => $subscription,
            'pendingTransfer' => $pendingTransfer,
            'branchStats' => [
                'allowed' => $allowedBranches,
                'used' => $usedBranches,
                'available' => max(0, $allowedBranches - $usedBranches),
            ],
            'bankSettings' => [
                'enabled' => (bool) ($bankSettings['enabled'] ?? true),
                'instructions' => $bankSettings['instructions'] ?? "Bank: Chase Bank N.A.\nAccount Name: SathiSaaS Technologies Inc.\nAccount No: 1234567890\nSWIFT: CHASUS33XXX",
            ],
        ]);
    }

    public function plans(Request $request): Response
    {
        $tenant = $this->currentTenant->get();

        $plans = Plan::query()
            ->active()
            ->with('features')
            ->orderBy('sort_order')
            ->get();

        $currentSubscription = $tenant->subscriptions()
            ->with('plan')
            ->latest('id')
            ->first();

        $pendingTransfer = SubscriptionBankTransfer::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->first();

        $bankSettings = $this->platformSettings->group('bank_transfer');

        return Inertia::render('Subscription/Portal/Plans', [
            'plans' => $plans,
            'currentSubscription' => $currentSubscription,
            'pendingTransfer' => $pendingTransfer,
            'bankSettings' => [
                'enabled' => (bool) ($bankSettings['enabled'] ?? true),
                'instructions' => $bankSettings['instructions'] ?? "Bank: Chase Bank N.A.\nAccount Name: SathiSaaS Technologies Inc.\nAccount No: 1234567890\nSWIFT: CHASUS33XXX",
            ],
            'selectedPlanSlug' => $request->query('plan'),
        ]);
    }

    public function submitBankTransfer(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->get();

        $validated = $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'branches_count' => ['required', 'integer', 'min:1', 'max:100'],
            'transaction_reference' => ['required', 'string', 'max:100'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);

        $branchesCount = (int) $validated['branches_count'];
        $baseAmount = (float) $plan->price;
        $includedBranches = max(1, (int) ($plan->included_branches ?? 1));
        $extraBranches = max(0, $branchesCount - $includedBranches);
        $extraBranchPrice = (float) ($plan->extra_branch_price ?? 15.00);
        $extraBranchesAmount = $extraBranches * $extraBranchPrice;
        $totalAmount = $baseAmount + $extraBranchesAmount;

        $receiptPath = $request->file('receipt')->store('subscription_receipts', 'public');

        $transfer = SubscriptionBankTransfer::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'plan_id' => $plan->id,
            'branches_count' => $branchesCount,
            'base_amount' => $baseAmount,
            'extra_branches_amount' => $extraBranchesAmount,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
            'currency' => $plan->currency ?? 'USD',
            'billing_cycle' => $plan->billing_cycle?->value ?? 'monthly',
            'transaction_reference' => $validated['transaction_reference'],
            'receipt_path' => $receiptPath,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'metadata' => [
                'plan_name' => $plan->name,
                'included_branches' => $includedBranches,
                'extra_branches' => $extraBranches,
                'extra_branch_price' => $extraBranchPrice,
            ],
        ]);

        // Create or update tenant subscription in pending status
        $now = now();
        $cycle = $plan->billing_cycle instanceof \BackedEnum ? $plan->billing_cycle->value : (string) $plan->billing_cycle;
        $periodEnds = match ($cycle) {
            'yearly' => $now->copy()->addYear(),
            'lifetime' => null,
            default => $now->copy()->addMonth(),
        };

        $existingPendingSub = $tenant->subscriptions()
            ->where('status', SubscriptionStatus::Pending)
            ->latest('id')
            ->first();

        if ($existingPendingSub) {
            $existingPendingSub->update([
                'plan_id' => $plan->id,
                'allowed_branches' => $branchesCount,
                'starts_at' => $now,
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $periodEnds,
                'metadata' => [
                    'bank_transfer_id' => $transfer->id,
                    'reference' => $transfer->transaction_reference,
                ],
            ]);
        } else {
            $tenant->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Pending,
                'allowed_branches' => $branchesCount,
                'starts_at' => $now,
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $periodEnds,
                'metadata' => [
                    'bank_transfer_id' => $transfer->id,
                    'reference' => $transfer->transaction_reference,
                ],
            ]);
        }

        return redirect()->route('admin.subscription.index')
            ->with('success', 'Bank transfer receipt submitted successfully! Your subscription will be activated upon SuperAdmin verification.');
    }

    public function addBranches(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->get();

        $activeSubscription = $tenant->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->with('plan')
            ->latest('id')
            ->first();

        if (! $activeSubscription || ! $activeSubscription->plan) {
            return back()->withErrors(['error' => 'You do not have an active subscription to add branches to.']);
        }

        $validated = $request->validate([
            'additional_branches' => ['required', 'integer', 'min:1', 'max:50'],
            'transaction_reference' => ['required', 'string', 'max:100'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $plan = $activeSubscription->plan;
        $additionalBranches = (int) $validated['additional_branches'];
        $unitPrice = (float) ($plan->extra_branch_price ?? 15.00);
        $totalAmount = $additionalBranches * $unitPrice;
        $newTotalBranches = ($activeSubscription->allowed_branches ?? 1) + $additionalBranches;

        $receiptPath = $request->file('receipt')->store('subscription_receipts', 'public');

        SubscriptionBankTransfer::create([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'plan_id' => $plan->id,
            'branches_count' => $newTotalBranches,
            'base_amount' => 0,
            'extra_branches_amount' => $totalAmount,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
            'currency' => $plan->currency ?? 'USD',
            'billing_cycle' => $plan->billing_cycle?->value ?? 'monthly',
            'transaction_reference' => $validated['transaction_reference'],
            'receipt_path' => $receiptPath,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'metadata' => [
                'type' => 'branch_addon',
                'additional_branches' => $additionalBranches,
                'target_total_branches' => $newTotalBranches,
                'subscription_id' => $activeSubscription->id,
            ],
        ]);

        return redirect()->route('admin.subscription.index')
            ->with('success', "Request to add {$additionalBranches} branch(es) submitted successfully! Once approved, your allowed branches will increase to {$newTotalBranches}.");
    }

    public function orders(Request $request): Response
    {
        $tenant = $this->currentTenant->get();

        $transfers = SubscriptionBankTransfer::query()
            ->where('tenant_id', $tenant->id)
            ->with(['plan', 'user'])
            ->latest('id')
            ->paginate(15);

        return Inertia::render('Subscription/Portal/Orders', [
            'transfers' => $transfers,
        ]);
    }
}
