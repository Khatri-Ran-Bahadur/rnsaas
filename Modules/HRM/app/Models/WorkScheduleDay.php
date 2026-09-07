<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HRM\Domain\Enums\DayOfWeek;

#[Fillable([
    'work_schedule_id',
    'day_of_week',
    'is_working_day',
    'start_time',
    'end_time',
    'break_minutes',
])]
class WorkScheduleDay extends Model
{
    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'is_working_day' => 'boolean',
            'break_minutes' => 'integer',
        ];
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    #[Scope]
    protected function workingDays(Builder $query): void
    {
        $query->where('is_working_day', true);
    }
}
