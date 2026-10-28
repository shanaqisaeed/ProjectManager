<?php

namespace App\Models;

use App\Enums\OrganizationNodeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationNode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'organization_nodes';

    protected $fillable = [
        'parent_id',
        'name',
        'type',
        'description',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrganizationNodeType::class,
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationNode::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrganizationNode::class, 'parent_id')->orderBy('sort_order', 'asc');
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class, 'organization_node_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'organization_node_id');
    }

    /**
     * Helper method to recursively get all descendant IDs of this node.
     */
    public function getAllDescendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }

        return $ids;
    }
}
