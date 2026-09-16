<?php

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Subscription\Models\Coupon;
use Modules\Subscription\Models\Plan;

class CouponController extends Controller
{
    /**
     * Display a listing of coupons.
     */
    public function index(): Response
    {
        $coupons = Coupon::query()
            ->latest()
            ->get()
            ->map(function (Coupon $coupon): array {
                return [
                    'id' => $coupon->id,
                    'public_id' => $coupon->public_id,
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => (float) $coupon->discount_value,
                    'max_uses' => $coupon->max_uses,
                    'used_count' => (int) $coupon->used_count,
                    'expires_at' => $coupon->expires_at?->format('Y-m-d'),
                    'is_active' => (bool) $coupon->is_active,
                    'is_valid' => $coupon->isValid(),
                    'created_at' => $coupon->created_at?->format('M d, Y'),
                ];
            });

        return Inertia::render('Subscription/Coupons/Index', [
            'coupons' => $coupons,
        ]);
    }

    /**
     * Store a newly created coupon.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:subscription_coupons,code'],
            'name' => ['required', 'string', 'max:150'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        Coupon::create($validated);

        return back()->with('success', 'Coupon created successfully.');
    }

    /**
     * Update the specified coupon.
     */
    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:subscription_coupons,code,'.$coupon->id],
            'name' => ['required', 'string', 'max:150'],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        $coupon->update($validated);

        return back()->with('success', 'Coupon updated successfully.');
    }

    /**
     * Toggle active state.
     */
    public function toggle(Coupon $coupon): RedirectResponse
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back()->with('success', 'Coupon status updated.');
    }

    /**
     * Remove the specified coupon.
     */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return back()->with('success', 'Coupon deleted successfully.');
    }

    /**
     * Validate a coupon code for checkout.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'plan_id' => ['nullable', 'integer'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid coupon code.',
            ], 422);
        }

        if (! $coupon->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'This coupon has expired or reached maximum usage limit.',
            ], 422);
        }

        $basePrice = 0.0;
        if (! empty($validated['plan_id'])) {
            $plan = Plan::find($validated['plan_id']);
            if ($plan) {
                $basePrice = (float) $plan->price;
            }
        }

        $discountAmount = $coupon->calculateDiscount($basePrice);

        return response()->json([
            'valid' => true,
            'coupon' => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float) $coupon->discount_value,
                'discount_amount' => $discountAmount,
                'final_price' => max(0, $basePrice - $discountAmount),
            ],
            'message' => 'Coupon applied successfully!',
        ]);
    }
}
