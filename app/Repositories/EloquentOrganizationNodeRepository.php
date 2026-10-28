<?php

namespace App\Repositories;

use App\Contracts\OrganizationNodeRepositoryInterface;
use App\Models\OrganizationNode;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrganizationNodeRepository implements OrganizationNodeRepositoryInterface
{
    public function findById(int $id): ?OrganizationNode
    {
        return OrganizationNode::with(['parent', 'children.children'])->find($id);
    }

    public function getTree(): Collection
    {
        return OrganizationNode::whereNull('parent_id')
            ->with([
                'children' => function ($q) {
                    $q->withCount(['children', 'projects'])
                        ->with([
                            'children' => function ($q2) {
                                $q2->withCount(['children', 'projects'])
                                    ->with([
                                        'children' => function ($q3) {
                                            $q3->withCount(['children', 'projects'])
                                                ->with([
                                                    'children' => function ($q4) {
                                                        $q4->withCount(['children', 'projects']);
                                                    },
                                                ]);
                                        },
                                    ]);
                            },
                        ]);
                },
            ])
            ->withCount(['children', 'projects'])
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function getAllFlat(): Collection
    {
        return OrganizationNode::with(['parent.parent.parent'])
            ->withCount(['children', 'projects'])
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function create(array $data): OrganizationNode
    {
        return OrganizationNode::create($data);
    }

    public function update(OrganizationNode $node, array $data): OrganizationNode
    {
        $node->update($data);

        return $node->fresh();
    }

    public function delete(OrganizationNode $node): bool
    {
        return $node->delete();
    }
}
