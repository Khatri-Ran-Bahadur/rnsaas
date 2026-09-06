<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRolePermission extends Model
{
    protected $table = 'tenant_role_permissions';

    protected $fillable = [
        'tenant_role_id',
        'permission',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(TenantRole::class, 'tenant_role_id');
    }
}
