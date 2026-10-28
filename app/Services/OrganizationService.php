<?php

namespace App\Services;

use App\Contracts\OrganizationNodeRepositoryInterface;
use App\Models\OrganizationNode;
use Illuminate\Database\Eloquent\Collection;

class OrganizationService
{
    public function __construct(
        private readonly OrganizationNodeRepositoryInterface $organizationRepository
    ) {}

    public function getTree(): Collection
    {
        return $this->organizationRepository->getTree();
    }

    public function getAllFlat(): Collection
    {
        return $this->organizationRepository->getAllFlat();
    }

    public function createNode(array $data): OrganizationNode
    {
        return $this->organizationRepository->create($data);
    }

    public function updateNode(OrganizationNode $node, array $data): OrganizationNode
    {
        return $this->organizationRepository->update($node, $data);
    }

    public function deleteNode(OrganizationNode $node): bool
    {
        return $this->organizationRepository->delete($node);
    }
}
