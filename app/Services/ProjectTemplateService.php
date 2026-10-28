<?php

namespace App\Services;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectPermission;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\ProjectApprovalStage;
use App\Models\ProjectMember;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateApprovalStage;
use App\Models\ProjectTemplateRole;
use App\Models\ProjectTemplateTask;
use App\Models\ProjectTemplateTaskSupervisor;
use App\Models\Task;
use App\Models\TaskSupervisor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectTemplateService
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly ProjectPermissionResolver $permissionResolver,
        private readonly ProjectKeyGeneratorService $keyGenerator
    ) {}

    /**
     * Checks if a user has permission to convert/save a project as a template.
     */
    public function canUserSaveProjectAsTemplate(User $user, Project $project): bool
    {
        if ($this->authorizationService->isSuperAdmin($user)) {
            return true;
        }

        if ($this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_TEMPLATES)) {
            return true;
        }

        // Check if user is an accepted project manager on this project
        return $project->projectManagers->pluck('user_id')->contains($user->id);
    }

    /**
     * Converts/saves an existing project (and all its subprojects, tasks, roles, and approval stages) into a ProjectTemplate.
     */
    public function saveProjectAsTemplate(User $actor, Project $project, ?string $title = null, ?string $description = null): ProjectTemplate
    {
        if (! $this->canUserSaveProjectAsTemplate($actor, $project)) {
            throw ValidationException::withMessages([
                'template' => ['شما دسترسی لازم جهت تبدیل این پروژه به الگو را ندارید.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $project, $title, $description) {
            $durationDays = 30;
            if ($project->start_date && $project->deadline) {
                $durationDays = max(1, (int) $project->start_date->diffInDays($project->deadline));
            }

            $templateTitle = ! empty(trim($title ?? '')) ? trim($title) : 'الگوی: '.$project->title;
            $templateDesc = $description ?? $project->description;

            $rootTemplate = ProjectTemplate::create([
                'parent_template_id' => null,
                'organization_node_id' => $project->organization_node_id,
                'title' => $templateTitle,
                'description' => $templateDesc,
                'priority' => $project->priority ?? ProjectPriority::MEDIUM,
                'offset_days' => 0,
                'duration_days' => $durationDays,
                'created_by' => $actor->id,
            ]);

            $this->copyProjectEntitiesToTemplate($project, $rootTemplate, $project->start_date);
            $this->copySubprojectsToTemplateRecursive($project, $rootTemplate, $project->start_date, $actor);

            return $rootTemplate;
        });
    }

    /**
     * Copy roles, approval stages, and tasks of a single project into the corresponding template.
     */
    private function copyProjectEntitiesToTemplate(Project $project, ProjectTemplate $template, ?Carbon $rootStartDate): void
    {
        // 1. Copy Roles
        foreach ($project->members as $member) {
            ProjectTemplateRole::create([
                'project_template_id' => $template->id,
                'project_role' => $member->project_role?->value ?? (string) $member->project_role,
                'cooperation_title' => $member->cooperation_title,
                'user_id' => $member->user_id,
            ]);
        }

        // 2. Copy Approval Stages
        foreach ($project->approvalStages as $stage) {
            ProjectTemplateApprovalStage::create([
                'project_template_id' => $template->id,
                'stage_number' => $stage->stage_number,
                'stage_name' => $stage->stage_name,
                'supervisor_user_id' => $stage->supervisor_user_id,
                'is_required' => true,
            ]);
        }

        // 3. Copy Tasks with hierarchy
        $allTasks = Task::where('project_id', $project->id)->with('taskSupervisors')->get();
        $taskMap = []; // old_task_id => new ProjectTemplateTask

        $copyTaskRecursive = function ($task, $parentTemplateTaskId = null) use (&$copyTaskRecursive, $allTasks, &$taskMap, $template, $project, $rootStartDate) {
            $offsetDays = 0;
            if ($rootStartDate && $task->start_date) {
                $offsetDays = max(0, (int) $rootStartDate->diffInDays($task->start_date));
            } elseif ($project->start_date && $task->start_date) {
                $offsetDays = max(0, (int) $project->start_date->diffInDays($task->start_date));
            } elseif ($task->created_at && $project->created_at) {
                $offsetDays = max(0, (int) $project->created_at->startOfDay()->diffInDays($task->created_at->startOfDay()));
            }

            // Calculate duration: validity period in days from creation date (created_at) to deadline (due_date)
            $taskCreationDate = $task->created_at ? $task->created_at->copy()->startOfDay() : ($task->start_date ? $task->start_date->copy()->startOfDay() : now()->startOfDay());
            $durationDays = 7;
            if ($task->due_date) {
                $taskDueDate = $task->due_date->copy()->startOfDay();
                $durationDays = max(1, (int) $taskCreationDate->diffInDays($taskDueDate));
            } elseif ($task->start_date && $project->deadline) {
                $durationDays = max(1, (int) $taskCreationDate->diffInDays($project->deadline->copy()->startOfDay()));
            }

            $templateTask = ProjectTemplateTask::create([
                'project_template_id' => $template->id,
                'parent_task_id' => $parentTemplateTaskId,
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority?->value ?? TaskPriority::MEDIUM->value,
                'weight' => $task->weight ?? 1,
                'offset_days' => $offsetDays,
                'duration_days' => $durationDays,
                'executor_user_id' => $task->executor_user_id,
                'version' => $task->version,
            ]);

            $taskMap[$task->id] = $templateTask;

            // Copy Task Supervisors
            foreach ($task->taskSupervisors as $ts) {
                ProjectTemplateTaskSupervisor::create([
                    'project_template_task_id' => $templateTask->id,
                    'stage_number' => $ts->stage_number ?? 1,
                    'stage_name' => $ts->stage_name,
                    'user_id' => $ts->user_id,
                ]);
            }

            // Copy child tasks (subtasks)
            $subtasks = $allTasks->where('parent_id', $task->id);
            foreach ($subtasks as $subtask) {
                $copyTaskRecursive($subtask, $templateTask->id);
            }
        };

        // Process root tasks first
        $rootTasks = $allTasks->whereNull('parent_id');
        foreach ($rootTasks as $rootTask) {
            $copyTaskRecursive($rootTask, null);
        }
    }

    /**
     * Recursively copies subprojects into child ProjectTemplates.
     */
    private function copySubprojectsToTemplateRecursive(Project $parentProject, ProjectTemplate $parentTemplate, ?Carbon $rootStartDate, User $actor): void
    {
        $children = Project::where('parent_project_id', $parentProject->id)->get();

        foreach ($children as $child) {
            $childOffset = 0;
            if ($rootStartDate && $child->start_date) {
                $childOffset = max(0, (int) $rootStartDate->diffInDays($child->start_date));
            } elseif ($parentProject->start_date && $child->start_date) {
                $childOffset = max(0, (int) $parentProject->start_date->diffInDays($child->start_date));
            }

            $childDuration = 30;
            if ($child->start_date && $child->deadline) {
                $childDuration = max(1, (int) $child->start_date->diffInDays($child->deadline));
            }

            $childTemplate = ProjectTemplate::create([
                'parent_template_id' => $parentTemplate->id,
                'organization_node_id' => $child->organization_node_id,
                'title' => $child->title,
                'description' => $child->description,
                'priority' => $child->priority ?? ProjectPriority::MEDIUM,
                'offset_days' => $childOffset,
                'duration_days' => $childDuration,
                'created_by' => $actor->id,
            ]);

            $this->copyProjectEntitiesToTemplate($child, $childTemplate, $rootStartDate);
            $this->copySubprojectsToTemplateRecursive($child, $childTemplate, $rootStartDate, $actor);
        }
    }

    /**
     * Instantly creates a complete Project (and all subprojects, tasks, stages, roles) from a ProjectTemplate.
     */
    public function createProjectFromTemplate(User $actor, ProjectTemplate $template, ?int $targetOrgNodeId = null): Project
    {
        return DB::transaction(function () use ($actor, $template, $targetOrgNodeId) {
            $startDate = now()->startOfDay();
            $durationDays = max(1, (int) ($template->duration_days ?: 30));
            $deadline = (clone $startDate)->addDays($durationDays);

            $orgNodeId = $targetOrgNodeId
                ?: ($template->organization_node_id
                    ?: ($actor->primaryRoleAssignment?->organization_node_id
                        ?: OrganizationNode::first()?->id));

            $projectKey = $this->keyGenerator->generateUniqueKey();

            $rootProject = Project::create([
                'parent_project_id' => null,
                'organization_node_id' => $orgNodeId,
                'title' => $template->title,
                'project_key' => $projectKey,
                'description' => $template->description,
                'start_date' => $startDate->format('Y-m-d'),
                'deadline' => $deadline->format('Y-m-d'),
                'status' => ProjectStatus::ACTIVE->value,
                'priority' => $template->priority?->value ?? ProjectPriority::MEDIUM->value,
                'created_by' => $actor->id,
            ]);

            // Ensure actor is a Project Manager member
            ProjectMember::create([
                'project_id' => $rootProject->id,
                'user_id' => $actor->id,
                'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
                'status' => ProjectMemberStatus::ACCEPTED->value,
                'joined_at' => now(),
            ]);

            $this->instantiateTemplateEntitiesToProject($template, $rootProject, $startDate, $actor);
            $this->instantiateSubtemplatesToProjectRecursive($template, $rootProject, $startDate, $actor);

            return $rootProject;
        });
    }

    /**
     * Instantiates roles, approval stages, and tasks from a template to a project.
     */
    private function instantiateTemplateEntitiesToProject(ProjectTemplate $template, Project $project, Carbon $rootStartDate, User $actor): void
    {
        // 1. Roles
        foreach ($template->templateRoles as $tRole) {
            if ($tRole->user_id && $tRole->user_id !== $actor->id) {
                ProjectMember::firstOrCreate([
                    'project_id' => $project->id,
                    'user_id' => $tRole->user_id,
                ], [
                    'project_role' => $tRole->project_role?->value ?? (string) $tRole->project_role,
                    'cooperation_title' => $tRole->cooperation_title,
                    'status' => ProjectMemberStatus::ACCEPTED->value,
                    'joined_at' => now(),
                ]);
            }
        }

        // 2. Approval Stages
        foreach ($template->approvalStages as $tStage) {
            ProjectApprovalStage::create([
                'project_id' => $project->id,
                'stage_number' => $tStage->stage_number,
                'stage_name' => $tStage->stage_name,
                'supervisor_user_id' => $tStage->supervisor_user_id,
                'status' => 'PENDING',
            ]);
        }

        // 3. Tasks with hierarchy
        $allTemplateTasks = ProjectTemplateTask::where('project_template_id', $template->id)->with('supervisors')->get();
        $taskMap = []; // template_task_id => new Task

        $instantiateTaskRecursive = function ($tTask, $parentTaskId = null) use (&$instantiateTaskRecursive, $allTemplateTasks, &$taskMap, $project, $rootStartDate, $actor) {
            $taskStart = (clone $rootStartDate)->addDays((int) ($tTask->offset_days ?: 0));
            $taskDue = (clone $taskStart)->addDays((int) ($tTask->duration_days ?: 1));

            $newTask = Task::create([
                'project_id' => $project->id,
                'parent_id' => $parentTaskId,
                'title' => $tTask->title,
                'description' => $tTask->description,
                'priority' => $tTask->priority?->value ?? TaskPriority::MEDIUM->value,
                'weight' => $tTask->weight ?? 1,
                'status' => TaskStatus::TODO->value,
                'start_date' => $taskStart->format('Y-m-d'),
                'due_date' => $taskDue->format('Y-m-d'),
                'executor_user_id' => $tTask->executor_user_id,
                'version' => $tTask->version,
                'created_by' => $actor->id,
            ]);

            $taskMap[$tTask->id] = $newTask;

            // Supervisors
            foreach ($tTask->supervisors as $ts) {
                TaskSupervisor::create([
                    'task_id' => $newTask->id,
                    'stage_number' => $ts->stage_number ?? 1,
                    'stage_name' => $ts->stage_name,
                    'user_id' => $ts->user_id,
                    'status' => 'PENDING',
                ]);
            }

            // Child subtasks
            $subtasks = $allTemplateTasks->where('parent_task_id', $tTask->id);
            foreach ($subtasks as $subtask) {
                $instantiateTaskRecursive($subtask, $newTask->id);
            }
        };

        $rootTemplateTasks = $allTemplateTasks->whereNull('parent_task_id');
        foreach ($rootTemplateTasks as $rootTTask) {
            $instantiateTaskRecursive($rootTTask, null);
        }
    }

    /**
     * Recursively instantiates subtemplates as subprojects of the given project.
     */
    private function instantiateSubtemplatesToProjectRecursive(ProjectTemplate $parentTemplate, Project $parentProject, Carbon $rootStartDate, User $actor): void
    {
        $childrenTemplates = ProjectTemplate::where('parent_template_id', $parentTemplate->id)->get();

        foreach ($childrenTemplates as $childTmpl) {
            $subStart = (clone $rootStartDate)->addDays((int) ($childTmpl->offset_days ?: 0));
            $subDeadline = (clone $subStart)->addDays((int) ($childTmpl->duration_days ?: 30));

            // Ensure subproject deadline does not exceed parent deadline if parent has one
            if ($parentProject->deadline && $subDeadline->greaterThan($parentProject->deadline)) {
                $subDeadline = clone $parentProject->deadline;
            }

            $childProject = Project::create([
                'parent_project_id' => $parentProject->id,
                'organization_node_id' => $childTmpl->organization_node_id ?: $parentProject->organization_node_id,
                'title' => $childTmpl->title,
                'project_key' => $this->keyGenerator->generateUniqueKey(),
                'description' => $childTmpl->description,
                'start_date' => $subStart->format('Y-m-d'),
                'deadline' => $subDeadline->format('Y-m-d'),
                'status' => ProjectStatus::ACTIVE->value,
                'priority' => $childTmpl->priority?->value ?? ProjectPriority::MEDIUM->value,
                'created_by' => $actor->id,
            ]);

            // Add actor as manager
            ProjectMember::create([
                'project_id' => $childProject->id,
                'user_id' => $actor->id,
                'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
                'status' => ProjectMemberStatus::ACCEPTED->value,
                'joined_at' => now(),
            ]);

            $this->instantiateTemplateEntitiesToProject($childTmpl, $childProject, $rootStartDate, $actor);
            $this->instantiateSubtemplatesToProjectRecursive($childTmpl, $childProject, $rootStartDate, $actor);
        }
    }
}
