<?php

namespace App\Models;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectRolePermission extends Model
{
    use HasFactory;

    protected $table = 'project_role_permissions';

    protected $fillable = [
        'project_role',
        'permission',
    ];

    protected function casts(): array
    {
        return [
            'project_role' => ProjectMemberRole::class,
            'permission' => ProjectPermission::class,
        ];
    }
}
