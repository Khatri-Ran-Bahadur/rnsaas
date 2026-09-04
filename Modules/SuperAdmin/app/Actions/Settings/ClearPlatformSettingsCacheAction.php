<?php

namespace Modules\SuperAdmin\Actions\Settings;

use Modules\Audit\Domain\Contracts\AuditLogger;
use Modules\SuperAdmin\Services\PlatformSettings;

class ClearPlatformSettingsCacheAction
{
    public function __construct(
        private readonly PlatformSettings $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(?object $actor = null): void
    {
        $this->settings->clearCache();

        $this->auditLogger->record(
            event: 'platform.settings.cache_cleared',
            actor: $actor,
        );
    }
}
