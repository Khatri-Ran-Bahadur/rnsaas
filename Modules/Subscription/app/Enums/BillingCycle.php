<?php

namespace Modules\Subscription\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
    case Lifetime = 'lifetime';

    public function isRecurring(): bool
    {
        return $this !== self::Lifetime;
    }
}