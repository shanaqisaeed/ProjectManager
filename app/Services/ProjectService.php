<?php

namespace App\Services;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectPermission;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\ProjectApprovalStage;
use App\Models\ProjectMember;
use App\Models\ProjectMemberPermission;
use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly ProjectPermissionResolver $permissionResolver,
        private readonly ProjectKeyGeneratorService $keyGenerator
    ) {}

    /**
     * Creation authorization check.
     */
    public function canUserCreateProjectInNode(User $user, int $organizationNodeId): bool
    {
        // 1. Must have allowed system role
        $allowedSystemRoles = [
            UserRole::SUPER_ADMIN,
            UserRole::ORGANIZATION_MANAGER,
            UserRole::UNIT_MANAGER,
            UserRole::TEAM_LEAD,
        ];

        if (! $user->hasAnyRole($allowedSystemRoles)) {
            return false;
        }

        // 2. Must have scope authority over the specified organization node
        return $this->authorizationService->canAccessNode($user, $organizationNodeId);
    }

    /**
     * Creates a new Project or SubProject.
     */
    public function createProject(User $creator, array $data): Project
    {
        $orgNodeId = (int) $data['organization_node_id'];

        if (! $this->canUserCreateProjectInNode($creator, $orgNodeId)) {
            throw ValidationException::withMessages([
                'organization_node_id' => ['شما مجاز به ایجاد پروژه در این واحد سازمانی نیستید.'],
            ]);
        }

        // Validate parent_project_id if provided
        if (! empty($data['parent_project_id'])) {
            $parent = Project::find($data['parent_project_id']);
            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_project_id' => ['پروژه مادر انتخاب شده معتبر نیست.'],
                ]);
            }

            if (! $this->permissionResolver->hasPermission($creator, $parent, ProjectPermission::CREATE_SUBPROJECT)) {
                throw ValidationException::withMessages([
                    'parent_project_id' => ['شما دسترسی ایجاد زیرپروژه برای این پروژه مادر را ندارید.'],
                ]);
            }

            if (! empty($data['deadline']) && $parent->deadline) {
                if (strtotime($data['deadline']) > strtotime($parent->deadline->format('Y-m-d'))) {
                    throw ValidationException::withMessages([
                        'deadline' => ['تاریخ پایان (ددلاین) زیرپروژه نمی‌تواند بعد از مهلت ددلاین پروژه مادر باشد.'],
                    ]);
                }
            }
        }

        // Validate dates
        if (! empty($data['start_date']) && ! empty($data['deadline'])) {
            if (strtotime($data['deadline']) < strtotime($data['start_date'])) {
                throw ValidationException::withMessages([
                    'deadline' => ['تاریخ پایان پروژه نمی‌تواند قبل از تاریخ شروع باشد.'],
                ]);
            }
        }

        // Generate project key if empty or null
        $projectKey = ! empty($data['project_key']) ? trim($data['project_key']) : $this->keyGenerator->generateUniqueKey();

        if (Project::withTrashed()->where('project_key', $projectKey)->exists()) {
            throw ValidationException::withMessages([
                'project_key' => ['کلید پروژه وارد شده قبلاً ثبت شده و تکراری است.'],
            ]);
        }

        // If template_id provided, calculate missing dates from template duration_days
        if (! empty($data['template_id'])) {
            $template = ProjectTemplate::find($data['template_id']);
            if ($template && ! empty($template->duration_days)) {
                if (empty($data['start_date'])) {
                    $data['start_date'] = now()->startOfDay()->format('Y-m-d');
                }
                if (empty($data['deadline']) && ! empty($data['start_date'])) {
                    $data['deadline'] = Carbon::parse($data['start_date'])->addDays((int) $template->duration_days)->format('Y-m-d');
                }
            }
        }

        return DB::transaction(function () use ($creator, $data, $projectKey, $orgNodeId) {
            $project = Project::create([
                'parent_project_id' => $data['parent_project_id'] ?? null,
                'organization_node_id' => $orgNodeId,
                'title' => $data['title'],
                'project_key' => $projectKey,
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'status' => $data['status'] ?? ProjectStatus::ACTIVE->value,
                'priority' => $data['priority'] ?? ProjectPriority::MEDIUM->value,
                'created_by' => $creator->id,
            ]);

            ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => $creator->id,
                'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
                'status' => ProjectMemberStatus::ACCEPTED->value,
                'joined_at' => now(),
            ]);

            // 1. Custom initial approval stages passed in $data['approval_stages']
            $createdStagesFromForm = false;
            if (! empty($data['approval_stages']) && is_array($data['approval_stages'])) {
                $stageNum = 1;
                foreach ($data['approval_stages'] as $stageData) {
                    if (! empty($stageData['stage_name'])) {
                        ProjectApprovalStage::create([
                            'project_id' => $project->id,
                            'stage_number' => $stageNum++,
                            'stage_name' => $stageData['stage_name'],
                            'supervisor_user_id' => ! empty($stageData['supervisor_user_id']) ? $stageData['supervisor_user_id'] : null,
                            'status' => 'PENDING',
                        ]);
                        $createdStagesFromForm = true;
                    }
                }
            }

            // 2. If template_id provided, instantiate approval stages (if not passed from form) & predefined template roles
            if (! empty($data['template_id'])) {
                $template = ProjectTemplate::with(['approvalStages', 'templateRoles'])->find($data['template_id']);
                if ($template) {
                    if (! $createdStagesFromForm) {
                        foreach ($template->approvalStages as $tStage) {
                            ProjectApprovalStage::create([
                                'project_id' => $project->id,
                                'stage_number' => $tStage->stage_number,
                                'stage_name' => $tStage->stage_name,
                                'supervisor_user_id' => $tStage->supervisor_user_id,
                                'status' => 'PENDING',
                            ]);
                        }
                    }

                    foreach ($template->templateRoles as $tRole) {
                        if ($tRole->user_id && $tRole->user_id !== $creator->id) {
                            ProjectMember::firstOrCreate([
                                'project_id' => $project->id,
                                'user_id' => $tRole->user_id,
                            ], [
                                'project_role' => $tRole->project_role->value,
                                'cooperation_title' => $tRole->cooperation_title,
                                'status' => ProjectMemberStatus::ACCEPTED->value,
                                'joined_at' => now(),
                            ]);
                        }
                    }
                }
            }

            return $project;
        });
    }

    /**
     * Updates an existing project.
     */
    public function updateProject(User $actor, Project $project, array $data): Project
    {
        if (! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::UPDATE_PROJECT)) {
            throw ValidationException::withMessages([
                'project' => ['شما دسترسی کافی برای ویرایش این پروژه را ندارید.'],
            ]);
        }

        if ($project->status === ProjectStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'project' => ['پروژه‌های تکمیل‌شده قفل گردیده‌اند و امکان ویرایش اطلاعات آن‌ها وجود ندارد.'],
            ]);
        }

        if (! empty($data['parent_project_id'])) {
            $parent = Project::find($data['parent_project_id']);
            if ((int) $data['parent_project_id'] === $project->id) {
                throw ValidationException::withMessages([
                    'parent_project_id' => ['یک پروژه نمی‌تواند مادر خودش باشد.'],
                ]);
            }

            $ancestorIds = $project->getAncestorIds();
            if (in_array((int) $data['parent_project_id'], $ancestorIds) || in_array((int) $data['parent_project_id'], $project->getAllDescendantIds())) {
                throw ValidationException::withMessages([
                    'parent_project_id' => ['امکان ایجاد رابطه دورانی در درخت پروژه‌ها وجود ندارد.'],
                ]);
            }

            if ($parent && ! empty($data['deadline']) && $parent->deadline) {
                if (strtotime($data['deadline']) > strtotime($parent->deadline->format('Y-m-d'))) {
                    throw ValidationException::withMessages([
                        'deadline' => ['تاریخ پایان (ددلاین) زیرپروژه نمی‌تواند بعد از مهلت ددلاین پروژه مادر باشد.'],
                    ]);
                }
            }
        } elseif ($project->parent && ! empty($data['deadline']) && $project->parent->deadline) {
            if (strtotime($data['deadline']) > strtotime($project->parent->deadline->format('Y-m-d'))) {
                throw ValidationException::withMessages([
                    'deadline' => ['تاریخ پایان (ددلاین) زیرپروژه نمی‌تواند بعد از مهلت ددلاین پروژه مادر باشد.'],
                ]);
            }
        }

        if (! empty($data['project_key']) && $data['project_key'] !== $project->project_key) {
            if (Project::withTrashed()->where('project_key', $data['project_key'])->where('id', '!=', $project->id)->exists()) {
                throw ValidationException::withMessages([
                    'project_key' => ['کلید پروژه وارد شده تکراری است.'],
                ]);
            }
        }

        if (! empty($data['start_date']) && ! empty($data['deadline'])) {
            if (strtotime($data['deadline']) < strtotime($data['start_date'])) {
                throw ValidationException::withMessages([
                    'deadline' => ['تاریخ پایان پروژه نمی‌تواند قبل از تاریخ شروع باشد.'],
                ]);
            }
        }

        $project->update($data);

        return $project;
    }

    /**
     * Adds or invites a member to a project.
     * Implements Workflow Scenario A (within scope -> ACCEPTED) and Scenario B (cross-scope -> INVITED).
     */
    public function addOrInviteMember(User $actor, Project $project, int $targetUserId, ProjectMemberRole $role, ?string $cooperationTitle = null): ProjectMember
    {
        if (! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::INVITE_MEMBERS) &&
            ! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::MANAGE_MEMBERS)) {
            throw ValidationException::withMessages([
                'member' => ['شما دسترسی لازم برای افزودن عضو به این پروژه را ندارید.'],
            ]);
        }

        $existing = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $targetUserId)
            ->whereIn('status', [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'user_id' => ['این کاربر در حال حاضر عضو یا دارای دعوت‌نامه فعال در این پروژه می‌باشد.'],
            ]);
        }

        $targetUser = User::findOrFail($targetUserId);

        // Determine if target user is in actor's scope
        $isWithinScope = false;
        if ($this->authorizationService->isSuperAdmin($actor)) {
            $isWithinScope = true;
        } else {
            // Check actor's scope over target user's active role assignment nodes
            foreach ($targetUser->activeRoleAssignments as $assignment) {
                if ($assignment->organization_node_id && $this->authorizationService->canAccessNode($actor, $assignment->organization_node_id)) {
                    $isWithinScope = true;
                    break;
                }
            }
        }

        $status = $isWithinScope ? ProjectMemberStatus::ACCEPTED : ProjectMemberStatus::INVITED;

        return ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $targetUserId,
            'project_role' => $role->value,
            'cooperation_title' => $cooperationTitle,
            'status' => $status->value,
            'invited_by' => $actor->id,
            'approved_by' => $isWithinScope ? $actor->id : null,
            'joined_at' => $isWithinScope ? now() : null,
        ]);
    }

    /**
     * Approves a cross-scope invitation.
     */
    public function approveInvitation(User $approver, ProjectMember $member): ProjectMember
    {
        $targetUser = $member->user;
        $canApprove = false;

        if ($this->authorizationService->isSuperAdmin($approver)) {
            $canApprove = true;
        } else {
            foreach ($targetUser->activeRoleAssignments as $assignment) {
                if ($assignment->organization_node_id && $this->authorizationService->canAccessNode($approver, $assignment->organization_node_id)) {
                    $canApprove = true;
                    break;
                }
            }

            if (! $canApprove && $this->authorizationService->canAccessNode($approver, $member->project->organization_node_id)) {
                $canApprove = true;
            }
        }

        if (! $canApprove) {
            throw ValidationException::withMessages([
                'invitation' => ['شما مجاز به تایید این دعوت‌نامه فراسازمانی نیستید.'],
            ]);
        }

        $member->update([
            'status' => ProjectMemberStatus::ACCEPTED->value,
            'approved_by' => $approver->id,
            'joined_at' => now(),
        ]);

        return $member;
    }

    /**
     * Rejects an invitation.
     */
    public function rejectInvitation(User $actor, ProjectMember $member): ProjectMember
    {
        $member->update([
            'status' => ProjectMemberStatus::REJECTED->value,
        ]);

        return $member;
    }

    /**
     * Removes a member from a project.
     */
    public function removeMember(User $actor, Project $project, ProjectMember $member): bool
    {
        if (! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::REMOVE_MEMBERS) &&
            ! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::MANAGE_MEMBERS)) {
            throw ValidationException::withMessages([
                'member' => ['شما مجاز به حذف عضو از این پروژه نیستید.'],
            ]);
        }

        return $member->update([
            'status' => ProjectMemberStatus::REMOVED->value,
        ]);
    }

    private function isUserProjectMemberOrAncestorMember(Project $project, int $targetUserId): bool
    {
        $isMember = ProjectMember::where('project_id', $project->id)
            ->where('user_id', $targetUserId)
            ->whereIn('status', [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value])
            ->exists();

        if (! $isMember && $project->parent_project_id) {
            $ancestorIds = $project->getAncestorIds();
            if (! empty($ancestorIds)) {
                $isMember = ProjectMember::whereIn('project_id', $ancestorIds)
                    ->where('user_id', $targetUserId)
                    ->whereIn('status', [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value])
                    ->exists();
            }
        }

        return $isMember;
    }

    /**
     * Sets direct permission override for a user on a project.
     */
    public function setDirectPermissionOverride(User $actor, Project $project, int $targetUserId, ProjectPermission $permission, string $accessType): ProjectMemberPermission
    {
        if (! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::MANAGE_MEMBERS)) {
            throw ValidationException::withMessages([
                'permission' => ['شما مجاز به مدیریت مجوزهای اعضا در این پروژه نیستید.'],
            ]);
        }

        if (! $this->isUserProjectMemberOrAncestorMember($project, $targetUserId)) {
            throw ValidationException::withMessages([
                'user_id' => ['تنها امکان تنظیم دسترسی مستقیم برای کاربرانی که عضو یا دعوت‌شده به پروژه هستند وجود دارد.'],
            ]);
        }

        return ProjectMemberPermission::updateOrCreate(
            [
                'project_id' => $project->id,
                'user_id' => $targetUserId,
                'permission' => $permission->value,
            ],
            [
                'access_type' => $accessType,
            ]
        );
    }

    /**
     * Batch sets direct permission overrides for a target member user.
     */
    public function batchSetPermissionOverrides(User $actor, Project $project, int $targetUserId, array $permissionMap): void
    {
        if (! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::MANAGE_MEMBERS)) {
            throw ValidationException::withMessages([
                'permission' => ['شما مجاز به مدیریت مجوزهای اعضا در این پروژه نیستید.'],
            ]);
        }

        if (! $this->isUserProjectMemberOrAncestorMember($project, $targetUserId)) {
            throw ValidationException::withMessages([
                'user_id' => ['تنها امکان تنظیم دسترسی مستقیم برای کاربرانی که عضو یا دعوت‌شده به پروژه هستند وجود دارد.'],
            ]);
        }

        DB::transaction(function () use ($project, $targetUserId, $permissionMap) {
            foreach ($permissionMap as $permissionKey => $accessType) {
                $permissionEnum = ProjectPermission::tryFrom($permissionKey);
                if (! $permissionEnum) {
                    continue;
                }

                if (in_array($accessType, ['ALLOW', 'DENY'])) {
                    ProjectMemberPermission::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'user_id' => $targetUserId,
                            'permission' => $permissionEnum->value,
                        ],
                        [
                            'access_type' => $accessType,
                        ]
                    );
                } elseif ($accessType === 'DEFAULT' || empty($accessType)) {
                    ProjectMemberPermission::where('project_id', $project->id)
                        ->where('user_id', $targetUserId)
                        ->where('permission', $permissionEnum->value)
                        ->delete();
                }
            }
        });
    }

    /**
     * Archives a project.
     */
    public function archiveProject(User $actor, Project $project): Project
    {
        if (! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::ARCHIVE_PROJECT)) {
            throw ValidationException::withMessages([
                'project' => ['شما مجاز به آرشیو کردن این پروژه نیستید.'],
            ]);
        }

        if ($project->status === ProjectStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'project' => ['پروژه‌های تکمیل‌شده قفل گردیده‌اند و امکان تغییر وضعیت به آرشیو وجود ندارد.'],
            ]);
        }

        $project->update(['status' => ProjectStatus::ARCHIVED->value]);

        return $project;
    }

    /**
     * Restores/Unarchives an archived project back to active status.
     */
    public function unarchiveProject(User $actor, Project $project): Project
    {
        if (! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::ARCHIVE_PROJECT)) {
            throw ValidationException::withMessages([
                'project' => ['شما مجاز به خروج این پروژه از آرشیو نیستید.'],
            ]);
        }

        if ($project->status !== ProjectStatus::ARCHIVED) {
            throw ValidationException::withMessages([
                'project' => ['پروژه انتخاب‌شده در وضعیت آرشیو قرار ندارد.'],
            ]);
        }

        $project->update(['status' => ProjectStatus::ACTIVE->value]);

        return $project;
    }

    /**
     * Removes a direct permission override record for a user.
     */
    public function removeDirectPermissionOverride(User $actor, Project $project, ProjectMemberPermission $permissionOverride): void
    {
        if (! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::MANAGE_MEMBERS)) {
            throw ValidationException::withMessages([
                'permission' => ['شما مجاز به مدیریت مجوزهای اعضا در این پروژه نیستید.'],
            ]);
        }

        $permissionOverride->delete();
    }

    /**
     * Soft-deletes a project, enforcing the rule that projects must be archived first,
     * and parent projects with active subprojects cannot be deleted directly.
     */
    public function deleteProject(User $actor, Project $project): bool
    {
        if (! $this->permissionResolver->hasPermission($actor, $project, ProjectPermission::DELETE_PROJECT)) {
            throw ValidationException::withMessages([
                'project' => ['شما مجاز به حذف این پروژه نیستید.'],
            ]);
        }

        if ($project->status === ProjectStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'project' => ['پروژه‌های تکمیل‌شده قفل گردیده‌اند و امکان حذف آن‌ها وجود ندارد.'],
            ]);
        }

        // Rule: Project/Subproject must first be ARCHIVED before deletion is permitted
        if ($project->status !== ProjectStatus::ARCHIVED) {
            throw ValidationException::withMessages([
                'project' => ['پروژه یا زیرپروژه ابتدا باید به وضعیت آرشیو تغییر یابد و سپس حذف گردد.'],
            ]);
        }

        $activeChildrenCount = $project->children()
            ->where('status', '!=', ProjectStatus::ARCHIVED->value)
            ->count();

        if ($activeChildrenCount > 0) {
            throw ValidationException::withMessages([
                'project' => ['این پروژه دارای زیرپروژه‌های فعال است. ابتدا باید کلیه زیرپروژه‌ها به وضعیت آرشیو تغییر یابند.'],
            ]);
        }

        return $project->delete();
    }

    /**
     * Scopes query to only projects authorized for the user to view.
     */
    public function scopeVisibleProjects(Builder $query, User $user): Builder
    {
        if ($this->authorizationService->isSuperAdmin($user) || $this->authorizationService->isGlobalAdminOrManager($user)) {
            return $query;
        }

        // Collect all authorized organization node IDs for user
        $authorizedNodeIds = [];
        foreach ($user->activeRoleAssignments as $assignment) {
            if ($assignment->organization_node_id) {
                $authorizedNodeIds[] = $assignment->organization_node_id;
                if ($assignment->organizationNode) {
                    $authorizedNodeIds = array_merge($authorizedNodeIds, $assignment->organizationNode->getAllDescendantIds());
                }
            }
        }
        $authorizedNodeIds = array_unique($authorizedNodeIds);

        return $query->where(function (Builder $q) use ($user, $authorizedNodeIds) {
            // 1. Direct active or invited project member
            $q->whereHas('members', function (Builder $mq) use ($user) {
                $mq->where('user_id', $user->id)
                    ->whereIn('status', [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value]);
            });

            // 2. Creator
            $q->orWhere('created_by', $user->id);

            // 3. User with organizational scope authority
            if (! empty($authorizedNodeIds)) {
                $q->orWhereIn('organization_node_id', $authorizedNodeIds);
            }
        });
    }

    /**
     * Formats a collection of projects into a hierarchical tree sequence.
     */
    public function formatProjectsAsTree(Collection $projects): Collection
    {
        $grouped = $projects->groupBy(fn ($p) => $p->parent_project_id ?? 'root');
        $tree = collect();
        $visited = [];

        $flatten = function ($parentId, $depth = 0) use (&$flatten, $grouped, &$tree, &$visited) {
            $items = $grouped->get($parentId, collect());
            foreach ($items as $item) {
                if (isset($visited[$item->id])) {
                    continue;
                }
                $visited[$item->id] = true;
                $item->tree_depth = $depth;
                $tree->push($item);
                $flatten($item->id, $depth + 1);
            }
        };

        $allIds = $projects->pluck('id')->all();
        $rootProjects = $projects->filter(function ($p) use ($allIds) {
            return is_null($p->parent_project_id) || ! in_array($p->parent_project_id, $allIds);
        });

        foreach ($rootProjects as $root) {
            if (! isset($visited[$root->id])) {
                $visited[$root->id] = true;
                $root->tree_depth = 0;
                $tree->push($root);
                $flatten($root->id, 1);
            }
        }

        foreach ($projects as $p) {
            if (! isset($visited[$p->id])) {
                $p->tree_depth = 0;
                $tree->push($p);
            }
        }

        return $tree;
    }
}
