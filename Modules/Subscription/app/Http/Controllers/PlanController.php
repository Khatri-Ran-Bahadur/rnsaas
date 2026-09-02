<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Subscription\Http\Requests\StorePlanRequest;
use Modules\Subscription\Http\Requests\UpdatePlanRequest;
use Modules\Subscription\Models\Feature;
use Modules\Subscription\Models\Plan;

class PlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index(): Response
    {
        $plans = Plan::query()
            ->with('features')
            ->withCount('features')
            ->orderBy('sort_order')
            ->get();

        $allFeatures = Feature::query()
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Subscription/Plans/Index', [
            'plans' => $plans,
            'all_features' => $allFeatures,
        ]);
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create(): Response
    {
        $features = Feature::query()
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Subscription/Plans/Create', [
            'features' => $features,
        ]);
    }

    /**
     * Store a newly created subscription plan.
     */
    public function store(StorePlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $featureIds = $validated['feature_ids'] ?? [];

        unset($validated['feature_ids']);

        $plan = Plan::create([
            'public_id' => (string) Str::ulid(),
            ...$validated,
        ]);

        $plan->features()->sync($featureIds);

        return redirect()
            ->route('admin.subscriptions.plans.index')
            ->with(
                'success',
                "Plan '{$plan->name}' was created successfully.",
            );
    }

    /**
     * Display the specified subscription plan.
     */
    public function show(Plan $plan): Response
    {
        $plan->load('features');

        $allFeatures = Feature::query()
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Subscription/Plans/Show', [
            'plan' => $plan,
            'allFeatures' => $allFeatures,
        ]);
    }

    /**
     * Show the form for editing the specified subscription plan.
     */
    public function edit(Plan $plan): Response
    {
        $plan->load('features');

        $features = Feature::query()
            ->where('is_active', true)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Subscription/Plans/Edit', [
            'plan' => $plan,
            'features' => $features,
        ]);
    }

    /**
     * Update the specified subscription plan.
     */
    public function update(
        UpdatePlanRequest $request,
        Plan $plan,
    ): RedirectResponse {
        $validated = $request->validated();

        $featureIds = $validated['feature_ids'] ?? [];

        unset($validated['feature_ids']);

        $plan->update($validated);

        $plan->features()->sync($featureIds);

        return redirect()
            ->route('admin.subscriptions.plans.index')
            ->with(
                'success',
                "Plan '{$plan->name}' was updated successfully.",
            );
    }

    /**
     * Remove the specified subscription plan.
     */
    public function destroy(Plan $plan): RedirectResponse
    {
        $planName = $plan->name;

        $plan->delete();

        return redirect()
            ->route('admin.subscriptions.plans.index')
            ->with(
                'success',
                "Plan '{$planName}' was deleted successfully.",
            );
    }
}
