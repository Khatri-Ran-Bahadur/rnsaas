<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('shifts.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'in:10,15,25,50',
            ],
        ];
    }

    public function search(): ?string
    {
        $search = $this->input('search');

        return filled($search)
            ? trim($search)
            : null;
    }

    public function isActive(): ?bool
    {
        if (! $this->has('is_active')) {
            return null;
        }

        return $this->boolean('is_active');
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 15);
    }
}
