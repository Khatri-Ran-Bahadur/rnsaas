<?php

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Subscription\Models\Coupon;

class SubscriptionCouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'public_id' => (string) Str::uuid(),
                'code' => 'WELCOME20',
                'name' => 'New Customer 20% Discount',
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'max_uses' => 500,
                'used_count' => 12,
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ],
            [
                'public_id' => (string) Str::uuid(),
                'code' => 'LAUNCH50',
                'name' => 'Platform Launch 50% Off',
                'discount_type' => 'percentage',
                'discount_value' => 50.00,
                'max_uses' => 100,
                'used_count' => 45,
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'public_id' => (string) Str::uuid(),
                'code' => 'SAVE30',
                'name' => 'Special Business Discount',
                'discount_type' => 'fixed',
                'discount_value' => 30.00,
                'max_uses' => 200,
                'used_count' => 5,
                'expires_at' => now()->addYear(),
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}
