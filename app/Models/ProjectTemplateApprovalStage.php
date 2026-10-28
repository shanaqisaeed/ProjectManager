<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTemplateApprovalStage extends Model
{
    use HasFactory;

    protected $table = 'project_template_approval_stages';

    protected $fillable = [
        'project_template_id',
        'stage_number',
        'stage_name',
        'supervisor_user_id',
        'supervisor_role',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'stage_number' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function projectTemplate(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'project_template_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }
}
