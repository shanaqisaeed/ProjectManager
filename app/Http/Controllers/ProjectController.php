<?php

namespace App\Http\Controllers;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectPermission;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\ProjectKeyGeneratorService;
use App\Services\ProjectPermissionResolver;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly AuthorizationService $authorizationService,
        private readonly ProjectPermissionResolver $permissionResolver,
        private readonly ProjectKeyGeneratorService $keyGenerator
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $search = $request->query('search');
        $status = $request->query('status');
        $priority = $request->query('priority');
        $subprojectFilter = $request->query('subprojects', 'hide'); // hide, include, only, tree
        $layoutView = $request->query('view', 'table'); // table, grid

        $allVisibleQuery = $this->projectService->scopeVisibleProjects(Project::query(), $user);
        $totalProjects = (clone $allVisibleQuery)->count();
        $activeProjects = (clone $allVisibleQuery)->where('status', ProjectStatus::ACTIVE->value)->count();
        $completedProjects = (clone $allVisibleQuery)->where('status', ProjectStatus::COMPLETED->value)->count();
        $forceMajeureProjects = (clone $allVisibleQuery)->where('priority', ProjectPriority::FORCE_MAJEURE->value)->count();

        $query = Project::with(['organizationNode', 'creator', 'parent', 'projectManagers.user', 'approvalStages.supervisor', 'members.user']);
        $scopedQuery = $this->projectService->scopeVisibleProjects($query, $user);

        if (! empty($search)) {
            $scopedQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('project_key', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $organizationNodeId = $request->query('organization_node_id');

        if (! empty($status)) {
            $scopedQuery->where('status', $status);
        }

        if (! empty($priority)) {
            $scopedQuery->where('priority', $priority);
        }

        if (! empty($organizationNodeId)) {
            $scopedQuery->where('organization_node_id', $organizationNodeId);
        }

        // Subproject visibility filtering logic
        if ($subprojectFilter === 'hide') {
            $scopedQuery->whereNull('parent_project_id');
        } elseif ($subprojectFilter === 'only') {
            $scopedQuery->whereNotNull('parent_project_id');
        }

        $sortBy = $request->query('sort', 'deadline'); // deadline, created_at

        if ($sortBy === 'deadline') {
            $scopedQuery->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END, deadline ASC, created_at DESC');
        } else {
            $scopedQuery->orderBy('created_at', 'desc');
        }

        $allProjects = $scopedQuery->get();

        if ($subprojectFilter === 'include' || $subprojectFilter === 'tree') {
            $formattedProjects = $this->projectService->formatProjectsAsTree($allProjects);
        } else {
            $formattedProjects = $allProjects;
        }

        $perPage = (int) $request->query('per_page', 50);
        if (! in_array($perPage, [10, 25, 50, 100, 200])) {
            $perPage = 50;
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $projects = new LengthAwarePaginator(
            $formattedProjects->slice(($page - 1) * $perPage, $perPage)->values(),
            $formattedProjects->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $statuses = ProjectStatus::cases();
        $priorities = ProjectPriority::cases();
        $organizationNodes = OrganizationNode::orderBy('name')->get();

        return view('projects.index', compact(
            'projects',
            'search',
            'status',
            'priority',
            'subprojectFilter',
            'layoutView',
            'sortBy',
            'perPage',
            'statuses',
            'priorities',
            'organizationNodes',
            'organizationNodeId',
            'totalProjects',
            'activeProjects',
            'completedProjects',
            'forceMajeureProjects'
        ));
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        if (! $user->canCreateProjects()) {
            abort(403, 'شما دسترسی لازم برای ایجاد پروژه را ندارید.');
        }

        // Filter selectable organization nodes to those authorized for the user
        $allNodes = OrganizationNode::orderBy('name')->get();
        $authorizedNodes = $allNodes->filter(function ($node) use ($user) {
            return $this->projectService->canUserCreateProjectInNode($user, $node->id);
        });

        $allProjects = $this->projectService->scopeVisibleProjects(Project::query(), $user)->get();
        $parentProjects = $this->projectService->formatProjectsAsTree($allProjects);
        $users = User::orderBy('first_name')->get();
        $autoProjectKey = $this->keyGenerator->generateUniqueKey();

        return view('projects.create', [
            'organizationNodes' => $authorizedNodes,
            'parentProjects' => $parentProjects,
            'users' => $users,
            'statuses' => ProjectStatus::cases(),
            'priorities' => ProjectPriority::cases(),
            'autoProjectKey' => $autoProjectKey,
            'selectedParentId' => $request->query('parent_id'),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = $this->projectService->createProject($request->user(), $request->validated());

        return redirect()->route('projects.show', $project)
            ->with('success', 'پروژه با موفقیت ساخته شد.');
    }

    public function show(Request $request, Project $project): View
    {
        $user = $request->user();

        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::VIEW_PROJECT)) {
            abort(403, 'شما دسترسی لازم برای مشاهده این پروژه را ندارید.');
        }

        // Lightweight eager loading for core project overview
        $project->load([
            'organizationNode',
            'creator',
            'parent',
            'projectManagers.user',
            'approvalStages.supervisor',
            'members.user',
            'members.inviter',
            'members.approver',
            'memberPermissions.user',
        ]);

        $activeTab = $request->query('tab', 'overview');

        $descendantIds = $project->getAllDescendantIds();
        $allProjectIds = array_merge([$project->id], $descendantIds);

        // Calculate Task Progress & Breakdown efficiently at DB level for Overview & Tab counters
        $taskStatsQuery = Task::whereIn('project_id', $allProjectIds);
        $totalTasksCount = (clone $taskStatsQuery)->count();
        $completedTasksCount = (clone $taskStatsQuery)->where('status', TaskStatus::COMPLETED->value)->count();
        $inProgressTasksCount = (clone $taskStatsQuery)->where('status', TaskStatus::IN_PROGRESS->value)->count();
        $notStartedTasksCount = (clone $taskStatsQuery)->whereIn('status', [TaskStatus::TODO->value, TaskStatus::NEEDS_REVISION->value])->count();
        $pendingReviewTasksCount = (clone $taskStatsQuery)->where('status', TaskStatus::PENDING_REVIEW->value)->count();
        $parentTasksCount = (clone $taskStatsQuery)->whereNull('parent_id')->count();
        $subtasksCount = (clone $taskStatsQuery)->whereNotNull('parent_id')->count();
        $overdueTasksCount = (clone $taskStatsQuery)
            ->where('status', '!=', TaskStatus::COMPLETED->value)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->count();

        $overallProgressPercent = $totalTasksCount > 0 ? (int) round(($completedTasksCount / $totalTasksCount) * 100) : 0;

        $taskMetrics = [
            'total' => $totalTasksCount,
            'completed' => $completedTasksCount,
            'in_progress' => $inProgressTasksCount,
            'not_started' => $notStartedTasksCount,
            'pending_review' => $pendingReviewTasksCount,
            'parent_tasks' => $parentTasksCount,
            'subtasks' => $subtasksCount,
            'overdue' => $overdueTasksCount,
            'progress_percent' => $overallProgressPercent,
        ];

        // Conditional Tab Queries: Process and load data ONLY for the requested active tab!
        $allProjectTasks = null;
        $subProjects = Project::where('parent_project_id', $project->id)->with(['organizationNode', 'creator', 'projectManagers.user'])->withCount(['tasks', 'children'])->orderBy('title')->get();
        $users = collect();

        if ($activeTab === 'tasks' || $request->has('tasks_page') || $request->has('task_search') || $request->has('task_status') || $request->has('task_priority') || $request->has('task_hierarchy') || $request->has('task_executor') || $request->has('task_due_range') || $request->has('task_supervisor') || $request->has('task_project_id') || $request->has('task_sort') || $request->has('task_view_mode')) {
            $taskStatus = $request->query('task_status');
            $taskPriority = $request->query('task_priority');
            $taskSearch = $request->query('task_search');
            $taskHierarchy = $request->query('task_hierarchy');
            $taskExecutor = $request->query('task_executor');
            $taskSupervisor = $request->query('task_supervisor');
            $taskDueRange = $request->query('task_due_range');
            $taskProjectId = $request->query('task_project_id');
            $taskSort = $request->query('task_sort', 'smart');
            $taskViewMode = $request->query('task_view_mode', 'tree');

            $targetProjectIds = ! empty($taskProjectId) ? [(int) $taskProjectId] : $allProjectIds;

            $tasksQuery = Task::whereIn('project_id', $targetProjectIds)
                ->select([
                    'id', 'project_id', 'parent_id', 'title', 'version',
                    'priority', 'weight', 'status', 'executor_user_id', 'created_by',
                    'start_date', 'due_date', 'completed_at', 'created_at', 'updated_at',
                ])
                ->with([
                    'project:id,title,project_key',
                    'executor:id,first_name,last_name,username',
                    'creator:id,first_name,last_name,username',
                    'taskSupervisors:id,task_id,stage_number,stage_name,user_id,status',
                    'taskSupervisors.user:id,first_name,last_name,username',
                    'parent:id,title,version,status,project_id',
                    'subtasks' => function ($sq) {
                        $sq->select([
                            'id', 'project_id', 'parent_id', 'title', 'version',
                            'priority', 'weight', 'status', 'executor_user_id', 'created_by',
                            'start_date', 'due_date', 'completed_at', 'created_at', 'updated_at',
                        ])->with([
                            'project:id,title,project_key',
                            'executor:id,first_name,last_name,username',
                            'taskSupervisors:id,task_id,stage_number,stage_name,user_id,status',
                            'taskSupervisors.user:id,first_name,last_name,username',
                        ])->withCount(['subtasks', 'chats', 'attachments']);
                    },
                ])
                ->withCount(['subtasks', 'chats', 'attachments']);

            // Enforce task visibility scoping for non-manager members
            $isManager = $user->isSuperAdmin()
                || $user->isExecutiveOrAdmin()
                || $user->isProjectOrUnitManager()
                || $project->projectManagers->pluck('user_id')->contains($user->id);

            if (! $isManager) {
                $tasksQuery->where(function ($q) use ($user) {
                    $q->where('executor_user_id', $user->id)
                        ->orWhere('created_by', $user->id)
                        ->orWhereHas('taskSupervisors', function ($sq) use ($user) {
                            $sq->where('user_id', $user->id);
                        });
                });
            }

            // Hierarchy filter & Tree View Mode
            if ($taskHierarchy === 'parent_only') {
                $tasksQuery->whereNull('parent_id');
            } elseif ($taskHierarchy === 'subtask_only') {
                $tasksQuery->whereNotNull('parent_id');
            } elseif ($taskHierarchy === 'has_subtasks') {
                $tasksQuery->has('subtasks');
            } elseif ($taskViewMode === 'tree') {
                // In Tree View by default, query parent/root tasks so their subtasks are eager-loaded and nested underneath
                $tasksQuery->whereNull('parent_id');
            }

            if (! empty($taskSearch)) {
                $tasksQuery->where(function ($q) use ($taskSearch, $taskViewMode) {
                    $q->where('title', 'like', "%{$taskSearch}%")
                        ->orWhere('description', 'like', "%{$taskSearch}%")
                        ->orWhere('version', 'like', "%{$taskSearch}%");
                    if ($taskViewMode === 'tree') {
                        $q->orWhereHas('subtasks', function ($sq) use ($taskSearch) {
                            $sq->where('title', 'like', "%{$taskSearch}%")
                                ->orWhere('description', 'like', "%{$taskSearch}%")
                                ->orWhere('version', 'like', "%{$taskSearch}%");
                        });
                    }
                });
            }

            if (! empty($taskStatus)) {
                $tasksQuery->where('status', $taskStatus);
            }

            if (! empty($taskPriority)) {
                $tasksQuery->where('priority', $taskPriority);
            }

            // Executor filter
            if ($taskExecutor === 'me') {
                $tasksQuery->where('executor_user_id', $user->id);
            } elseif ($taskExecutor === 'unassigned') {
                $tasksQuery->whereNull('executor_user_id');
            } elseif (is_numeric($taskExecutor) && (int) $taskExecutor > 0) {
                $tasksQuery->where('executor_user_id', (int) $taskExecutor);
            }

            // Supervisor filter
            if ($taskSupervisor === 'with_supervisors') {
                $tasksQuery->has('taskSupervisors');
            } elseif ($taskSupervisor === 'no_supervisors') {
                $tasksQuery->doesntHave('taskSupervisors');
            } elseif ($taskSupervisor === 'needs_revision') {
                $tasksQuery->where('status', TaskStatus::NEEDS_REVISION->value);
            } elseif ($taskSupervisor === 'pending_review') {
                $tasksQuery->where('status', TaskStatus::PENDING_REVIEW->value);
            }

            // Due Range filter
            if ($taskDueRange === 'overdue') {
                $tasksQuery->where('status', '!=', TaskStatus::COMPLETED->value)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now()->startOfDay());
            } elseif ($taskDueRange === 'today') {
                $tasksQuery->whereDate('due_date', now()->today());
            } elseif ($taskDueRange === 'this_week') {
                $tasksQuery->whereNotNull('due_date')
                    ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()]);
            } elseif ($taskDueRange === 'no_due_date') {
                $tasksQuery->whereNull('due_date');
            }

            // Sorting
            match ($taskSort) {
                'newest' => $tasksQuery->orderBy('created_at', 'desc'),
                'oldest' => $tasksQuery->orderBy('created_at', 'asc'),
                'due_date_asc' => $tasksQuery->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC, due_date ASC'),
                'priority_desc' => $tasksQuery->orderByRaw("
                    CASE priority 
                        WHEN 'URGENT' THEN 1 
                        WHEN 'HIGH' THEN 2 
                        WHEN 'MEDIUM' THEN 3 
                        WHEN 'LOW' THEN 4 
                        ELSE 5 
                    END ASC, due_date ASC
                "),
                'title_asc' => $tasksQuery->orderBy('title', 'asc'),
                default => (function () use ($tasksQuery) {
                    $sevenDaysSql = match (DB::connection()->getDriverName()) {
                        'sqlite' => "DATE(CURRENT_DATE, '+7 days')",
                        'pgsql' => "CURRENT_DATE + INTERVAL '7 days'",
                        default => 'DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)',
                    };
                    $tasksQuery->orderByRaw("
                        CASE 
                            WHEN status = 'COMPLETED' THEN 5
                            WHEN due_date IS NOT NULL AND due_date < CURRENT_DATE THEN 1
                            WHEN due_date IS NOT NULL AND due_date <= {$sevenDaysSql} THEN 2
                            ELSE 3 
                        END ASC,
                        CASE priority 
                            WHEN 'URGENT' THEN 1 
                            WHEN 'HIGH' THEN 2 
                            WHEN 'MEDIUM' THEN 3 
                            WHEN 'LOW' THEN 4 
                            ELSE 5 
                        END ASC,
                        CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC,
                        due_date ASC,
                        created_at DESC
                    ");
                })(),
            };

            $perPage = ($taskViewMode === 'tree' && $taskHierarchy !== 'subtask_only') ? 15 : 25;

            $allProjectTasks = $tasksQuery
                ->paginate($perPage, ['*'], 'tasks_page')
                ->appends(array_merge($request->query(), ['tab' => 'tasks']));
        }

        if ($activeTab === 'subprojects' || $request->has('subprojects_page')) {
            $subProjects = Project::where('parent_project_id', $project->id)
                ->with(['organizationNode', 'creator', 'projectManagers.user'])
                ->withCount(['tasks', 'children'])
                ->paginate(15, ['*'], 'subprojects_page')
                ->withQueryString();
        }

        if ($activeTab === 'members' || $request->has('invite_user')) {
            if ($user->isSuperAdmin()) {
                $users = User::where('status', UserStatus::ACTIVE->value)
                    ->with(['activeRoleAssignments.organizationNode', 'primaryRoleAssignment.organizationNode'])
                    ->orderBy('first_name')
                    ->get();
            } else {
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

                $users = User::where('status', UserStatus::ACTIVE->value)
                    ->where(function ($q) use ($authorizedNodeIds, $project) {
                        if (! empty($authorizedNodeIds)) {
                            $q->whereHas('activeRoleAssignments', function ($rq) use ($authorizedNodeIds) {
                                $rq->whereIn('organization_node_id', $authorizedNodeIds);
                            });
                        }
                        $q->orWhereHas('projectMemberships', function ($mq) use ($project) {
                            $mq->where('project_id', $project->id);
                        });
                    })
                    ->with(['activeRoleAssignments.organizationNode', 'primaryRoleAssignment.organizationNode'])
                    ->orderBy('first_name')
                    ->get();
            }
        }

        $allNodes = OrganizationNode::orderBy('name')->get();
        $authorizedNodes = $allNodes->filter(function ($node) use ($user) {
            return $this->projectService->canUserCreateProjectInNode($user, $node->id);
        });

        $pendingInvitations = $project->members->where('status', ProjectMemberStatus::INVITED);

        $projectMemberUsers = $project->members
            ->filter(fn ($m) => in_array($m->status?->value ?? $m->status, [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value]))
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        $projectExecutorUsers = $project->members
            ->filter(fn ($m) => in_array($m->status?->value ?? $m->status, [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value]))
            ->filter(fn ($m) => ($m->project_role?->value ?? $m->project_role) === ProjectMemberRole::MEMBER->value)
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        $projectSupervisorUsers = $project->members
            ->filter(fn ($m) => in_array($m->status?->value ?? $m->status, [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value]))
            ->filter(fn ($m) => in_array($m->project_role?->value ?? $m->project_role, [ProjectMemberRole::PROJECT_SUPERVISOR->value, ProjectMemberRole::PROJECT_MANAGER->value]))
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        if ($projectSupervisorUsers->isEmpty()) {
            $projectSupervisorUsers = $projectMemberUsers;
        }

        $canManageMembers = $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_MEMBERS) ||
            $this->permissionResolver->hasPermission($user, $project, ProjectPermission::INVITE_MEMBERS);
        $canManageApprovalStages = $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_APPROVAL_STAGES) ||
            $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_MEMBERS) ||
            $user->isExecutiveOrAdmin();
        $canManagePermissions = $user->isSuperAdmin() ||
            $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_MEMBERS);
        $canCreateTask = $this->permissionResolver->hasPermission($user, $project, ProjectPermission::CREATE_TASK);
        $canManageTemplates = $user->isSuperAdmin() ||
            $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_TEMPLATES) ||
            $project->projectManagers->pluck('user_id')->contains($user->id);

        if ($activeTab === 'permissions' && ! $canManagePermissions) {
            $activeTab = 'overview';
        }
        if ($activeTab === 'members' && ! $canManageMembers) {
            $activeTab = 'overview';
        }
        if ($activeTab === 'template' && ! $canManageTemplates) {
            $activeTab = 'overview';
        }

        return view('projects.show', [
            'project' => $project,
            'activeTab' => $activeTab,
            'descendantIds' => $descendantIds,
            'taskMetrics' => $taskMetrics,
            'users' => $users,
            'projectMemberUsers' => $projectMemberUsers,
            'projectExecutorUsers' => $projectExecutorUsers,
            'projectSupervisorUsers' => $projectSupervisorUsers,
            'allProjectTasks' => $allProjectTasks,
            'taskViewMode' => $request->query('task_view_mode', 'tree'),
            'subProjects' => $subProjects,
            'organizationNodes' => $authorizedNodes,
            'projectMemberRoles' => ProjectMemberRole::cases(),
            'projectPermissions' => ProjectPermission::cases(),
            'statuses' => ProjectStatus::cases(),
            'priorities' => ProjectPriority::cases(),
            'taskStatuses' => TaskStatus::cases(),
            'taskPriorities' => TaskPriority::cases(),
            'pendingInvitations' => $pendingInvitations,
            'canManageMembers' => $canManageMembers,
            'canManageApprovalStages' => $canManageApprovalStages,
            'canManagePermissions' => $canManagePermissions,
            'canCreateTask' => $canCreateTask,
            'canManageTemplates' => $canManageTemplates,
        ]);
    }

    public function edit(Request $request, Project $project): View|RedirectResponse
    {
        $user = $request->user();

        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::UPDATE_PROJECT)) {
            abort(403, 'شما دسترسی لازم برای ویرایش این پروژه را ندارید.');
        }

        if ($project->status === ProjectStatus::COMPLETED) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'این پروژه تکمیل شده است و امکان ویرایش آن وجود ندارد.');
        }

        $allNodes = OrganizationNode::orderBy('name')->get();
        $allProjects = $this->projectService->scopeVisibleProjects(Project::where('id', '!=', $project->id), $user)->get();
        $parentProjects = $this->projectService->formatProjectsAsTree($allProjects);

        return view('projects.edit', [
            'project' => $project,
            'organizationNodes' => $allNodes,
            'parentProjects' => $parentProjects,
            'statuses' => ProjectStatus::cases(),
            'priorities' => ProjectPriority::cases(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->projectService->updateProject($request->user(), $project, $request->validated());

        return redirect()->route('projects.show', $project)
            ->with('success', 'اطلاعات پروژه با موفقیت ذخیره شد.');
    }

    public function archive(Request $request, Project $project): RedirectResponse
    {
        $this->projectService->archiveProject($request->user(), $project);

        return redirect()->route('projects.index')
            ->with('success', 'پروژه با موفقیت آرشیو شد.');
    }

    public function unarchive(Request $request, Project $project): RedirectResponse
    {
        $this->projectService->unarchiveProject($request->user(), $project);

        return redirect()->back()
            ->with('success', 'پروژه از آرشیو خارج و دوباره فعال شد.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->projectService->deleteProject($request->user(), $project);

        return redirect()->route('projects.index')
            ->with('success', 'پروژه با موفقیت حذف شد.');
    }
}
