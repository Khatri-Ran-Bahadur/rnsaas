<?php

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'public_id',
    'ticket_id',
    'name',
    'quantity',
    'modifiers',
    'notes',
    'is_done',
])]
class PosKitchenTicketItem extends Model
{
    use HasFactory;

    protected $table = 'pos_kitchen_ticket_items';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'modifiers' => 'array',
            'is_done' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PosKitchenTicket::class, 'ticket_id');
    }
}
