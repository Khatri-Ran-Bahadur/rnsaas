<?php

namespace Modules\Accounting\Http\Requests\Statements;

use Illuminate\Foundation\Http\FormRequest;

final class FinancialStatementDateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => [
                'required',
                'date',
            ],

            'to_date' => [
                'required',
                'date',
                'after_or_equal:from_date',
            ],
        ];
    }
}
