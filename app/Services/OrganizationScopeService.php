<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\OrganizationNode;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class OrganizationScopeService
{
    /**
     * Cache key for overall organization tree.
     */
    public const CACHE_KEY_ORG_TREE = 'org_chart_tree_v1';

    /**
     * Get cached full organization node tree structure.
     */
    public function getCachedOrganizationTree(): Collection
    {
        return Cache::remember(self::CACHE_KEY_ORG_TREE, 86400, function () {
            return OrganizationNode::whereNull('parent_id')
                ->with(['children.children.children', 'children.children'])
                ->orderBy('sort_order', 'asc')
                ->get();
        });
    }

    /**
     * Clear organization tree cache.
     */
    public function clearOrganizationTreeCache(): void
    {
        Cache::forget(self::CACHE_KEY_ORG_TREE);
    }

    /**
     * Check if user has global organization administration authority.
     */
    public function isGlobalAdminOrManager(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->activeRoleAssignments->contains(function ($assignment) {
            return $assignment->role === UserRole::ORGANIZATION_MANAGER && is_null($assignment->organization_node_id);
        });
    }

    /**
     * Get array of node IDs authorized for a specific user.
     */
    public function getAuthorizedNodeIdsForUser(User $user): array
    {
        $cacheKey = "user_auth_nodes_{$user->id}";

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            if ($this->isGlobalAdminOrManager($user)) {
                return OrganizationNode::pluck('id')->toArray();
            }

            $authorizedNodeIds = [];
            foreach ($user->activeRoleAssignments as $assignment) {
                if ($assignment->organization_node_id) {
                    $authorizedNodeIds[] = $assignment->organization_node_id;
                    if ($assignment->organizationNode) {
                        $authorizedNodeIds = array_merge(
                            $authorizedNodeIds,
                            $assignment->organizationNode->getAllDescendantIds()
                        );
                    }
                }
            }

            return array_values(array_unique($authorizedNodeIds));
        });
    }

    /**
     * Clear user authorized node scope cache.
     */
    public function clearUserScopeCache(int $userId): void
    {
        Cache::forget("user_auth_nodes_{$userId}");
    }

    /**
     * Get workload analysis per member in authorized org scope.
     */
    public function getMemberWorkloadReport(User $currentUser): Collection
    {
        $authorizedNodeIds = $this->getAuthorizedNodeIdsForUser($currentUser);

        $query = User::where('status', 'ACTIVE')
            ->with(['activeRoleAssignments.organizationNode', 'primaryRoleAssignment.organizationNode']);

        if (! $currentUser->isSuperAdmin()) {
            if (empty($authorizedNodeIds)) {
                return collect();
            }

            $query->whereHas('activeRoleAssignments', function ($q) use ($authorizedNodeIds) {
                $q->whereIn('organization_node_id', $authorizedNodeIds);
            });
        }

        $members = $query->orderBy('first_name')->get();

        return $members->map(function (User $member) {
            $tasks = Task::where('executor_user_id', $member->id)->get();

            $totalTasks = $tasks->count();
            $completedTasks = $tasks->where('status', TaskStatus::COMPLETED)->count();
            $inProgressTasks = $tasks->where('status', TaskStatus::IN_PROGRESS)->count();
            $pendingTasks = $tasks->whereIn('status', [TaskStatus::TODO, TaskStatus::NEEDS_REVISION])->count();
            $overdueTasks = $tasks->filter(function ($t) {
                return $t->status !== TaskStatus::COMPLETED && $t->due_date && $t->due_date->isPast();
            })->count();

            $totalWeight = (int) $tasks->sum(fn ($t) => $t->weight ?? 1);
            $completedWeight = (int) $tasks->where('status', TaskStatus::COMPLETED)->sum(fn ($t) => $t->weight ?? 1);
            $inProgressWeight = (int) $tasks->where('status', TaskStatus::IN_PROGRESS)->sum(fn ($t) => $t->weight ?? 1);
            $pendingWeight = (int) $tasks->whereIn('status', [TaskStatus::TODO, TaskStatus::NEEDS_REVISION])->sum(fn ($t) => $t->weight ?? 1);
            $activeWeight = $inProgressWeight + $pendingWeight;
            $overdueWeight = (int) $tasks->filter(function ($t) {
                return $t->status !== TaskStatus::COMPLETED && $t->due_date && $t->due_date->isPast();
            })->sum(fn ($t) => $t->weight ?? 1);

            // Calculate workload load factor based on active task weights
            $activeCount = $inProgressTasks + $pendingTasks;
            $loadStatus = 'balanced';
            if ($activeWeight > 10) {
                $loadStatus = 'overloaded';
            } elseif ($activeWeight <= 3) {
                $loadStatus = 'underutilized';
            }

            return [
                'user' => $member,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'in_progress_tasks' => $inProgressTasks,
                'pending_tasks' => $pendingTasks,
                'overdue_tasks' => $overdueTasks,
                'active_tasks' => $activeCount,
                'total_weight' => $totalWeight,
                'completed_weight' => $completedWeight,
                'in_progress_weight' => $inProgressWeight,
                'pending_weight' => $pendingWeight,
                'overdue_weight' => $overdueWeight,
                'active_weight' => $activeWeight,
                'load_status' => $loadStatus,
            ];
        });
    }
}
