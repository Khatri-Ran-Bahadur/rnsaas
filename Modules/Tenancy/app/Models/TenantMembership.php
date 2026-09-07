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
        'role_id',
        'status',
        'invitation_token',
        'joined_at',
        'invited_at',
        'expires_at',
        'suspended_at',
        'revoked_at',
        'invited_by',
        'suspended_by',
        'revoked_by',
        'settings',
        'version',
    ];

    protected $hidden = [
        'invitation_token',
    ];

    protected $appends = [
        'name',
        'email',
        'is_staff',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantMembershipStatus::class,
            'joined_at' => 'datetime',
            'invited_at' => 'datetime',
            'expires_at' => 'datetime',
            'suspended_at' => 'datetime',
            'revoked_at' => 'datetime',
            'settings' => 'array',
            'version' => 'integer',
        ];
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getIsStaffAttribute(): bool
    {
        if ($this->relationLoaded('staff')) {
            return $this->staff !== null;
        }

        return $this->staff()->exists();
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

    public function role(): BelongsTo
    {
        return $this->belongsTo(TenantRole::class, 'role_id');
    }

    public function staff()
    {
        $relation = $this->hasOne(TenantStaff::class, 'user_id', 'user_id');

        if (! empty($this->tenant_id)) {
            $relation->where('tenant_staff.tenant_id', $this->tenant_id);
        }

        return $relation;
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

    public function isInvited(): bool
    {
        return $this->status === TenantMembershipStatus::Invited;
    }

    public function isInvitationExpired(): bool
    {
        if (! $this->isInvited()) {
            return false;
        }

        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return $this->role?->hasPermission($permission) ?? false;
    }
}
