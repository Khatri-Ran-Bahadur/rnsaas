<?php

namespace Modules\Accounting\Http\Resources\Vendors;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Http\Resources\Attachments\AccountingAttachmentResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'vendor_code' => $this->vendor_code,
            'name' => $this->name,

            'email' => $this->email,
            'phone' => $this->phone,
            'tax_number' => $this->tax_number,

            'billing_address' => [
                'line_1' => $this->billing_address_line_1,
                'line_2' => $this->billing_address_line_2,
                'city' => $this->billing_city,
                'state' => $this->billing_state,
                'postcode' => $this->billing_postcode,
                'country' => $this->billing_country,
            ],

            'credit_limit' => $this->credit_limit,
            'payment_terms_days' => $this->payment_terms_days,

            'status' => $this->status,

            'payable_account' => $this->whenLoaded(
                'payableAccount',
                fn () => [
                    'id' => $this->payableAccount->public_id,
                    'code' => $this->payableAccount->code,
                    'name' => $this->payableAccount->name,
                ]
            ),

            'attachments' => AccountingAttachmentResource::collection(
                $this->whenLoaded('attachments')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
