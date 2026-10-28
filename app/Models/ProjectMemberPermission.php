<?php

namespace App\Models;

use App\Enums\PermissionAccessType;
use App\Enums\ProjectPermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMemberPermission extends Model
{
    use HasFactory;

    protected $table = 'project_member_permissions';

    protected $fillable = [
        'project_id',
        'user_id',
        'permission',
        'access_type',
    ];

    protected function casts(): array
    {
        return [
            'permission' => ProjectPermission::class,
            'access_type' => PermissionAccessType::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
