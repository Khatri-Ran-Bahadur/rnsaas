<?php

namespace Modules\Accounting\Http\Resources\Payments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Http\Resources\Attachments\AccountingAttachmentResource;

class CustomerPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,

            'payment_number' => $this->payment_number,

            'payment_date' => $this->payment_date?->toDateString(),

            'amount' => $this->amount,

            'currency' => $this->currency,

            'payment_method' => $this->payment_method,

            'reference' => $this->reference,
            'notes' => $this->notes,

            'status' => $this->status,

            'customer' => $this->whenLoaded(
                'customer',
                fn () => [
                    'id' => $this->customer->public_id,
                    'code' => $this->customer->customer_code,
                    'name' => $this->customer->name,
                ]
            ),

            'allocations' => $this->whenLoaded(
                'allocations',
                fn () => $this->allocations->map(
                    fn ($allocation) => [
                        'id' => $allocation->public_id,
                        'allocated_amount' => $allocation->allocated_amount,

                        'invoice' => $allocation->whenLoaded(
                            'invoice',
                            fn () => [
                                'id' => $allocation->invoice->public_id,
                                'invoice_number' => $allocation->invoice->invoice_number,
                                'grand_total' => $allocation->invoice->grand_total,
                            ]
                        ),
                    ]
                )
            ),

            'attachments' => AccountingAttachmentResource::collection(
                $this->whenLoaded('attachments')
            ),

            'journal_entry' => $this->whenLoaded(
                'journalEntry',
                fn () => [
                    'id' => $this->journalEntry->public_id,
                    'entry_number' => $this->journalEntry->entry_number,
                    'status' => $this->journalEntry->status,
                ]
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
