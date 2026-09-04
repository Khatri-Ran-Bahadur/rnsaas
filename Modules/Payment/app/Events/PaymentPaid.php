<?php

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Models\PaymentTransaction;

class PaymentPaid
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PaymentTransaction $payment,
    ) {}
}
