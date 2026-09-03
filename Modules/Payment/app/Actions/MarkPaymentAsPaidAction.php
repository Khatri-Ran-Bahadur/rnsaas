<?php

namespace Modules\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Events\PaymentPaid;
use Modules\Payment\Exceptions\PaymentCannotBeMarkedAsPaidException;
use Modules\Payment\Models\PaymentTransaction;

class MarkPaymentAsPaidAction
{
    public function handle(
        PaymentTransaction $payment,
    ): PaymentTransaction {
        return DB::transaction(function () use ($payment): PaymentTransaction {
            if ($payment->status->isPaid()) {
                throw new PaymentCannotBeMarkedAsPaidException();
            }

            if (! $payment->status->isPending()) {
                throw new PaymentCannotBeMarkedAsPaidException();
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            $payment->refresh();

            PaymentPaid::dispatch($payment);

            return $payment;
        });
    }
}