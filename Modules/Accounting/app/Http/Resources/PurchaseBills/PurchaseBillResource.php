<?php

namespace Modules\Accounting\Http\Resources\PurchaseBills;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Http\Resources\Attachments\AccountingAttachmentResource;

class PurchaseBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,

            'bill_number' => $this->bill_number,

            'bill_date' => $this->bill_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),

            'currency' => $this->currency,

            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,

            'status' => $this->status,

            'reference' => $this->reference,
            'notes' => $this->notes,

            'vendor' => $this->whenLoaded(
                'vendor',
                fn () => [
                    'id' => $this->vendor->public_id,
                    'code' => $this->vendor->vendor_code,
                    'name' => $this->vendor->name,
                    'email' => $this->vendor->email,
                ]
            ),

            'lines' => $this->whenLoaded(
                'lines',
                fn () => $this->lines->map(
                    fn ($line) => [
                        'id' => $line->public_id,
                        'line_number' => $line->line_number,
                        'description' => $line->description,
                        'quantity' => $line->quantity,
                        'unit_price' => $line->unit_price,
                        'discount_amount' => $line->discount_amount,
                        'tax_rate' => $line->tax_rate,
                        'tax_amount' => $line->tax_amount,
                        'subtotal' => $line->subtotal,
                        'total' => $line->total,

                        'debit_account' => $line->whenLoaded(
                            'debitAccount',
                            fn () => [
                                'id' => $line->debitAccount->public_id,
                                'code' => $line->debitAccount->code,
                                'name' => $line->debitAccount->name,
                            ]
                        ),

                        'tax_account' => $line->whenLoaded(
                            'taxAccount',
                            fn () => $line->taxAccount ? [
                                'id' => $line->taxAccount->public_id,
                                'code' => $line->taxAccount->code,
                                'name' => $line->taxAccount->name,
                            ] : null
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
