<?php

namespace Modules\Subscription\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Subscription\Models\Plan;

class UpdatePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var Plan $plan */
        $plan = $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:150'],

            'slug' => [
                'required',
                'string',
                'max:150',
                Rule::unique('subscription_plans', 'slug')
                    ->ignore($plan->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'currency' => [
                'required',
                'string',
                'size:3',
            ],

            'billing_cycle' => [
                'required',
                'string',
                'in:monthly,quarterly,yearly,lifetime',
            ],

            'trial_days' => [
                'required',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
            ],

            'feature_ids' => [
                'nullable',
                'array',
            ],

            'feature_ids.*' => [
                'integer',
                'exists:subscription_features,id',
            ],
        ];
    }
}
