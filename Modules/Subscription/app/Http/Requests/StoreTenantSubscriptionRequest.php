<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [
                'required',
                'integer',
                'exists:tenants,id',
            ],

            'plan_id' => [
                'required',
                'integer',
                'exists:subscription_plans,id',
            ],

            'starts_at' => [
                'nullable',
                'date',
            ],
        ];
    }
}