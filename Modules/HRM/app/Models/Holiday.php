<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\HRM\database\factories\HolidayFactory;
use Modules\HRM\Domain\Enums\HolidayType;
use Modules\Tenancy\Models\Tenant;

/** @use HasFactory<HolidayFactory> */
#[Fillable([
    'public_id',
    'tenant_id',
    'name',
    'start_date',
    'end_date',
    'type',
    'description',
    'is_recurring',
    'is_active',
])]
class Holiday extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'type' => HolidayType::class,
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Holiday $holiday): void {
            $holiday->public_id ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function setStartDateAttribute(mixed $value): void
    {
        $this->attributes['start_date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : (is_string($value) ? substr($value, 0, 10) : $value);
    }

    public function setEndDateAttribute(mixed $value): void
    {
        $this->attributes['end_date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : (is_string($value) ? substr($value, 0, 10) : $value);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    #[Scope]
    protected function forTenant(
        Builder $query,
        int $tenantId
    ): void {
        $query->where('tenant_id', $tenantId);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function coveringDate(
        Builder $query,
        Carbon|string $date
    ): void {
        $date = $date instanceof Carbon
            ? $date->toDateString()
            : $date;

        $query->where('start_date', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }

    public function isMultiDay(): bool
    {
        return $this->end_date !== null
            && ! $this->start_date->isSameDay($this->end_date);
    }

    public function coversDate(Carbon|string $date): bool
    {
        $date = $date instanceof Carbon
            ? $date
            : Carbon::parse($date);

        return $date->betweenIncluded(
            $this->start_date,
            $this->end_date ?? $this->start_date
        );
    }
}
