<?php

namespace Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Modules\Tenancy\Models\Tenant;

#[Fillable([
    'tenant_id',
    'attachable_type',
    'attachable_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'uploaded_by',
])]
#[Hidden([
    'id',
])]
class AccountingAttachment extends Model
{
    protected $table = 'accounting_attachments';

    protected static function booted(): void
    {
        static::creating(function (self $attachment): void {
            $attachment->public_id ??= (string) Str::uuid();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
