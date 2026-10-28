<?php

namespace App\Models;

use App\Enums\ProjectMemberRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTemplateRole extends Model
{
    use HasFactory;

    protected $table = 'project_template_roles';

    protected $fillable = [
        'project_template_id',
        'project_role',
        'cooperation_title',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'project_role' => ProjectMemberRole::class,
        ];
    }

    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
