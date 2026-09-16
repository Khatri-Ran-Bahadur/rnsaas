<?php

namespace Modules\POS\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'public_id',
    'tenant_id',
    'order_id',
    'ticket_number',
    'order_ref',
    'order_type',
    'destination',
    'station',
    'priority',
    'status',
    'elapsed_seconds',
    'server_name',
    'notes',
    'prepared_at',
    'served_at',
])]
class PosKitchenTicket extends Model
{
    use HasFactory;

    protected $table = 'pos_kitchen_tickets';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'elapsed_seconds' => 'integer',
            'prepared_at' => 'datetime',
            'served_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PosOrder::class, 'order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosKitchenTicketItem::class, 'ticket_id');
    }
}
