<?php

namespace Modules\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Events\PaymentPaid;
use Modules\Payment\Exceptions\PaymentCannotBeMarkedAsPaidException;
use Modules\Payment\Exceptions\PaymentConcurrencyException;
use Modules\Payment\Models\PaymentTransaction;

class MarkPaymentAsPaidAction
{
    public function handle(
        PaymentTransaction $payment,
    ): PaymentTransaction {
        return DB::transaction(function () use ($payment): PaymentTransaction {
            $payment->refresh();

            if ($payment->status->isPaid()) {
                throw new PaymentCannotBeMarkedAsPaidException;
            }

            if (! $payment->status->isPending()) {
                throw new PaymentCannotBeMarkedAsPaidException;
            }

            $updated = PaymentTransaction::query()
                ->whereKey($payment->getKey())
                ->where('status', PaymentStatus::Pending->value)
                ->update([
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new PaymentConcurrencyException;
            }

            $payment->refresh();

            PaymentPaid::dispatch($payment);

            return $payment;
        });
    }
}
