<?php

namespace Modules\SuperAdmin\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SuperAdmin\Models\PlatformSetting;
use Modules\SuperAdmin\Services\PlatformSettings;

class PlatformSettingsSeeder extends Seeder
{
    public function run(PlatformSettings $settings): void
    {
        $defaults = [
            [
                'group' => 'general',
                'key' => 'platform_name',
                'value' => 'SathiSaaS',
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'support_email',
                'value' => null,
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'support_phone',
                'value' => null,
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'timezone',
                'value' => 'UTC',
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'currency',
                'value' => 'USD',
                'type' => 'string',
            ],
            [
                'group' => 'general',
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'type' => 'string',
            ],

            [
                'group' => 'branding',
                'key' => 'logo_media_id',
                'value' => null,
                'type' => 'integer',
            ],
            [
                'group' => 'branding',
                'key' => 'favicon_media_id',
                'value' => null,
                'type' => 'integer',
            ],
            [
                'group' => 'branding',
                'key' => 'login_logo_media_id',
                'value' => null,
                'type' => 'integer',
            ],
            [
                'group' => 'branding',
                'key' => 'logo_url',
                'value' => null,
                'type' => 'string',
            ],
            [
                'group' => 'branding',
                'key' => 'favicon_url',
                'value' => null,
                'type' => 'string',
            ],
            [
                'group' => 'branding',
                'key' => 'login_logo_url',
                'value' => null,
                'type' => 'string',
            ],

            [
                'group' => 'system',
                'key' => 'maintenance_mode',
                'value' => false,
                'type' => 'boolean',
            ],
            [
                'group' => 'system',
                'key' => 'maintenance_message',
                'value' => 'We are currently performing scheduled maintenance.',
                'type' => 'string',
            ],

            [
                'group' => 'mail',
                'key' => 'host',
                'value' => null,
                'type' => 'string',
            ],
            [
                'group' => 'mail',
                'key' => 'port',
                'value' => 587,
                'type' => 'integer',
            ],
            [
                'group' => 'mail',
                'key' => 'username',
                'value' => null,
                'type' => 'string',
            ],
            [
                'group' => 'mail',
                'key' => 'password',
                'value' => null,
                'type' => 'string',
                'is_secret' => true,
            ],
            [
                'group' => 'mail',
                'key' => 'encryption',
                'value' => 'tls',
                'type' => 'string',
            ],
            [
                'group' => 'mail',
                'key' => 'from_address',
                'value' => null,
                'type' => 'string',
            ],
            [
                'group' => 'mail',
                'key' => 'from_name',
                'value' => 'SathiSaaS',
                'type' => 'string',
            ],
        ];

        foreach ($defaults as $setting) {
            $existing = PlatformSetting::query()
                ->where('group', $setting['group'])
                ->where('key', $setting['key'])
                ->first();

            if ($existing) {
                continue;
            }

            $settings->set(
                group: $setting['group'],
                key: $setting['key'],
                value: $setting['value'],
                type: $setting['type'],
                isSecret: $setting['is_secret'] ?? false,
            );
        }
    }
}
