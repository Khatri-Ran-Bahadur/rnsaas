<?php

namespace Modules\SuperAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'general' => ['required', 'array'],

            'general.platform_name' => [
                'required',
                'string',
                'max:100',
            ],

            'general.support_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'general.support_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'general.timezone' => [
                'required',
                'string',
                Rule::in(timezone_identifiers_list()),
            ],

            'general.currency' => [
                'required',
                'string',
                'size:3',
            ],

            'general.date_format' => [
                'required',
                'string',
                'max:50',
            ],

            'branding' => ['nullable', 'array'],

            'branding.logo_media_id' => [
                'nullable',
                'integer',
            ],

            'branding.favicon_media_id' => [
                'nullable',
                'integer',
            ],

            'branding.login_logo_media_id' => [
                'nullable',
                'integer',
            ],

            'branding.logo_url' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'branding.favicon_url' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'branding.login_logo_url' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'system' => ['required', 'array'],

            'system.maintenance_mode' => [
                'required',
                'boolean',
            ],

            'system.maintenance_message' => [
                'nullable',
                'string',
                'max:500',
            ],

            'system.allow_registrations' => [
                'nullable',
                'boolean',
            ],

            'system.system_notice' => [
                'nullable',
                'string',
                'max:500',
            ],

            'system.default_language' => [
                'nullable',
                'string',
                'max:20',
            ],

            'system.time_format' => [
                'nullable',
                'string',
                'max:20',
            ],

            'system.calendar_start_day' => [
                'nullable',
                'string',
                'max:20',
            ],

            'mail' => ['nullable', 'array'],

            'mail.host' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mail.port' => [
                'nullable',
                'integer',
                'between:1,65535',
            ],

            'mail.username' => [
                'nullable',
                'string',
                'max:255',
            ],

            'mail.password' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'mail.encryption' => [
                'nullable',
                'string',
                Rule::in(['tls', 'ssl', 'none']),
            ],

            'mail.from_address' => [
                'nullable',
                'email',
                'max:255',
            ],

            'mail.from_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'general.platform_name' => 'platform name',
            'general.support_email' => 'support email',
            'general.support_phone' => 'support phone',
            'general.timezone' => 'timezone',
            'general.currency' => 'currency',
            'general.date_format' => 'date format',
            'system.maintenance_mode' => 'maintenance mode',
            'system.maintenance_message' => 'maintenance message',
            'mail.host' => 'mail host',
            'mail.port' => 'mail port',
            'mail.username' => 'mail username',
            'mail.password' => 'mail password',
            'mail.encryption' => 'mail encryption',
            'mail.from_address' => 'mail from address',
            'mail.from_name' => 'mail from name',
        ];
    }
}
