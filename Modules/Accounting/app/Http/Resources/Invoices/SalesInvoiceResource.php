<?php

namespace Modules\Accounting\Http\Resources\Invoices;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Http\Resources\Attachments\AccountingAttachmentResource;

class SalesInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,

            'invoice_number' => $this->invoice_number,

            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),

            'currency' => $this->currency,

            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,

            'status' => $this->status,

            'reference' => $this->reference,
            'notes' => $this->notes,

            'customer' => $this->whenLoaded(
                'customer',
                fn () => [
                    'id' => $this->customer->public_id,
                    'code' => $this->customer->customer_code,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
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

                        'revenue_account' => $line->whenLoaded(
                            'revenueAccount',
                            fn () => [
                                'id' => $line->revenueAccount->public_id,
                                'code' => $line->revenueAccount->code,
                                'name' => $line->revenueAccount->name,
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
