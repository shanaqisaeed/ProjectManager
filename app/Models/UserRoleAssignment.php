<?php

namespace App\Models;

use App\Enums\RoleAssignmentStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRoleAssignment extends Model
{
    use HasFactory;

    protected $table = 'user_role_assignments';

    protected $fillable = [
        'user_id',
        'role',
        'organization_node_id',
        'is_primary',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'status' => RoleAssignmentStatus::class,
            'is_primary' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organizationNode(): BelongsTo
    {
        return $this->belongsTo(OrganizationNode::class, 'organization_node_id');
    }
}
