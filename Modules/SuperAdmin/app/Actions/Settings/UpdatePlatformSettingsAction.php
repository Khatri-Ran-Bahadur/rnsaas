<?php

namespace Modules\SuperAdmin\Actions\Settings;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\SuperAdmin\Services\PlatformSettings;

class UpdatePlatformSettingsAction
{
    public function __construct(
        private readonly PlatformSettings $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(
        array $data,
        ?object $actor = null,
    ): void {
        DB::transaction(function () use ($data, $actor): void {
            $this->updateGroup(
                group: 'general',
                settings: $data['general'] ?? [],
                actor: $actor,
            );

            $this->updateGroup(
                group: 'branding',
                settings: $data['branding'] ?? [],
                actor: $actor,
            );

            $this->updateGroup(
                group: 'system',
                settings: $data['system'] ?? [],
                actor: $actor,
            );

            $this->updateMailSettings(
                $data['mail'] ?? [],
                $actor,
            );
        });

        $this->settings->clearCache();
    }

    private function updateGroup(
        string $group,
        array $settings,
        ?object $actor,
    ): void {
        foreach ($settings as $key => $value) {
            if ((str_ends_with($key, '_media_id') || str_ends_with($key, '_id')) && blank($value)) {
                $value = null;
            }

            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                str_ends_with($key, '_media_id') || str_ends_with($key, '_id') => 'integer',
                is_float($value) => 'float',
                is_array($value) => 'json',
                default => 'string',
            };

            $this->settings->set(
                group: $group,
                key: $key,
                value: $value,
                type: $type,
            );
        }

        $this->auditLogger->record(
            event: 'platform.settings.updated',
            actor: $actor,
            metadata: [
                'group' => $group,
                'keys' => array_keys($settings),
            ],
        );
    }

    private function updateMailSettings(
        array $settings,
        ?object $actor,
    ): void {
        foreach ($settings as $key => $value) {
            /*
             * Never overwrite an existing SMTP password with
             * an empty value coming from the UI.
             */
            if ($key === 'password' && blank($value)) {
                continue;
            }

            $this->settings->set(
                group: 'mail',
                key: $key,
                value: $value,
                type: 'string',
                isSecret: $key === 'password',
            );
        }

        $this->auditLogger->record(
            event: 'platform.mail.settings.updated',
            actor: $actor,
            metadata: [
                'keys' => array_keys($settings),
            ],
        );
    }
}
