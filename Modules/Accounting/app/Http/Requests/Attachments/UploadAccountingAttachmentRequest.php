<?php

namespace Modules\Accounting\Http\Requests\Attachments;

use Illuminate\Foundation\Http\FormRequest;

class UploadAccountingAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'Only JPG, JPEG, PNG, WEBP, and PDF files are allowed.',
            'file.max' => 'The attachment may not be larger than 10 MB.',
        ];
    }
}
