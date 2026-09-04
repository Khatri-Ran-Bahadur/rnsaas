<?php

namespace Modules\SuperAdmin\Services;

use Illuminate\Support\Facades\Cache;
use Modules\SuperAdmin\Models\PlatformSetting;

class PlatformSettings
{
    private const CACHE_KEY = 'sathisaas.platform.settings';

    public function get(
        string $group,
        string $key,
        mixed $default = null,
    ): mixed {
        $settings = $this->all();

        return $settings[$group][$key] ?? $default;
    }

    public function set(
        string $group,
        string $key,
        mixed $value,
        string $type = 'string',
        bool $isSecret = false,
    ): PlatformSetting {
        $setting = PlatformSetting::query()->firstOrNew([
            'group' => $group,
            'key' => $key,
        ]);

        $setting->type = $type;
        $setting->is_secret = $isSecret;
        $setting->setResolvedValue($value);
        $setting->save();

        $this->clearCache();

        return $setting->refresh();
    }

    public function all(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addHour(),
            function (): array {
                $settings = [];

                PlatformSetting::query()
                    ->orderBy('group')
                    ->orderBy('key')
                    ->get()
                    ->each(function (PlatformSetting $setting) use (&$settings): void {
                        $settings[$setting->group][$setting->key] =
                            $setting->getResolvedValue();
                    });

                return $settings;
            },
        );
    }

    public function group(string $group): array
    {
        return $this->all()[$group] ?? [];
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
