<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectApprovalStage extends Model
{
    use HasFactory;

    protected $table = 'project_approval_stages';

    protected $fillable = [
        'project_id',
        'stage_number',
        'stage_name',
        'supervisor_user_id',
        'status',
        'comments',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'stage_number' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }
}
