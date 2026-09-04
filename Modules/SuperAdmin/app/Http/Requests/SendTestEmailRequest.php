<?php

namespace Modules\SuperAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendTestEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'host' => [
                'nullable',
                'string',
                'max:255',
            ],
            'port' => [
                'nullable',
                'integer',
                'between:1,65535',
            ],
            'username' => [
                'nullable',
                'string',
                'max:255',
            ],
            'password' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'encryption' => [
                'nullable',
                'string',
                Rule::in(['tls', 'ssl', 'none']),
            ],
            'from_address' => [
                'nullable',
                'email',
                'max:255',
            ],
            'from_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'recipient email address',
            'host' => 'SMTP host',
            'port' => 'SMTP port',
            'username' => 'SMTP username',
            'password' => 'SMTP password',
            'encryption' => 'SMTP encryption',
            'from_address' => 'sender email address',
            'from_name' => 'sender name',
        ];
    }
}
