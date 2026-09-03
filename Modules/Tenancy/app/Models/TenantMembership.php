<?php

namespace Modules\Tenancy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Domain\Enums\TenantMembershipStatus;

class TenantMembership extends Model
{
    use HasFactory;

    protected $table = 'tenant_user';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'joined_at',
        'invited_at',
        'suspended_at',
        'revoked_at',
        'invited_by',
        'suspended_by',
        'revoked_by',
        'settings',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantMembershipStatus::class,
            'joined_at' => 'datetime',
            'invited_at' => 'datetime',
            'suspended_at' => 'datetime',
            'revoked_at' => 'datetime',
            'settings' => 'array',
            'version' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isActive(): bool
    {
        return $this->status === TenantMembershipStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantMembershipStatus::Suspended;
    }

    public function isRevoked(): bool
    {
        return $this->status === TenantMembershipStatus::Revoked;
    }
}
