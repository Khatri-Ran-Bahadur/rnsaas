<?php

namespace Modules\Subscription\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Expired = 'expired';

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isTrialing(): bool
    {
        return $this === self::Trialing;
    }

    public function isActiveOrTrialing(): bool
    {
        return in_array($this, [
            self::Active,
            self::Trialing,
        ], true);
    }

    public function isPastDue(): bool
    {
        return $this === self::PastDue;
    }

    public function isCanceled(): bool
    {
        return $this === self::Canceled;
    }

    public function isExpired(): bool
    {
        return $this === self::Expired;
    }
}
