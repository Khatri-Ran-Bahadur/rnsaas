<?php

namespace Modules\Payment\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    public function isRefunded(): bool
    {
        return $this === self::Refunded;
    }
}