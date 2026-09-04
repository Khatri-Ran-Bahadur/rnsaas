<?php

namespace Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_secret',
    ];

    protected $casts = [
        'is_secret' => 'boolean',
    ];

    public function getResolvedValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        $value = $this->is_secret
            ? Crypt::decryptString($this->value)
            : $this->value;

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            default => $value,
        };
    }

    public function setResolvedValue(mixed $value): void
    {
        if ($value === null) {
            $this->value = null;

            return;
        }

        $storedValue = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) $value,
            'float' => (string) $value,
            'json' => json_encode(
                $value,
                JSON_THROW_ON_ERROR,
            ),
            default => (string) $value,
        };

        $this->value = $this->is_secret
            ? Crypt::encryptString($storedValue)
            : $storedValue;
    }

    public function getSettingKey(): string
    {
        return "{$this->group}.{$this->key}";
    }
}
