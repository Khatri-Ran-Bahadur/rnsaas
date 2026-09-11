<?php

namespace Modules\Accounting\Http\Resources\VendorPayments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Http\Resources\Attachments\AccountingAttachmentResource;

class VendorPaymentResource extends JsonResource
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

            'vendor' => $this->whenLoaded(
                'vendor',
                fn () => [
                    'id' => $this->vendor->public_id,
                    'code' => $this->vendor->vendor_code,
                    'name' => $this->vendor->name,
                ]
            ),

            'bank_account' => $this->whenLoaded(
                'bankAccount',
                fn () => [
                    'id' => $this->bankAccount->public_id,
                    'code' => $this->bankAccount->code,
                    'name' => $this->bankAccount->name,
                ]
            ),

            'allocations' => $this->whenLoaded(
                'allocations',
                fn () => $this->allocations->map(
                    fn ($allocation) => [
                        'id' => $allocation->public_id,
                        'allocated_amount' => $allocation->allocated_amount,

                        'purchase_bill' => $allocation->whenLoaded(
                            'purchaseBill',
                            fn () => [
                                'id' => $allocation->purchaseBill->public_id,
                                'bill_number' => $allocation->purchaseBill->bill_number,
                                'grand_total' => $allocation->purchaseBill->grand_total,
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
