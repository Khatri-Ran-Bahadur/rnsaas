<?php

namespace Modules\Admin\Http\Requests;

use App\Support\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Models\Designation;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $designation = $this->route('designation');
        if ($designation instanceof Designation && $designation->tenant_id !== app(CurrentTenant::class)->id()) {
            abort(404);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'code' => strtoupper(trim((string) $this->code)),
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenant::class)->id();
        $designation = $this->route('designation');
        $designationId = $designation instanceof Designation ? $designation->id : $designation;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('designations', 'code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($designationId),
            ],
        ];
    }
}
