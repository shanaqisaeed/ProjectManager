<?php

namespace App\Contracts;

use App\Models\OrganizationNode;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationNodeRepositoryInterface
{
    public function findById(int $id): ?OrganizationNode;

    public function getTree(): Collection;

    public function getAllFlat(): Collection;

    public function create(array $data): OrganizationNode;

    public function update(OrganizationNode $node, array $data): OrganizationNode;

    public function delete(OrganizationNode $node): bool;
}
