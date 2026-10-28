<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskChat;
use App\Models\TaskSupervisor;
use App\Models\User;
use App\Services\OrganizationScopeService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly OrganizationScopeService $orgScopeService
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $user->load(['activeRoleAssignments.organizationNode', 'primaryRoleAssignment.organizationNode']);

        // System-level counts
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'ACTIVE')->count();
        $totalNodes = OrganizationNode::count();

        // 1. Scoped projects for the current user
        $scopedProjectsQuery = $this->projectService->scopeVisibleProjects(Project::query(), $user);
        $accessibleProjectIds = (clone $scopedProjectsQuery)->pluck('id')->toArray();

        // Project metrics
        $totalProjects = (clone $scopedProjectsQuery)->count();
        $activeProjects = (clone $scopedProjectsQuery)->where('status', ProjectStatus::ACTIVE->value)->count();
        $completedProjects = (clone $scopedProjectsQuery)->where('status', ProjectStatus::COMPLETED->value)->count();
        $onHoldProjects = (clone $scopedProjectsQuery)->where('status', ProjectStatus::ON_HOLD->value)->count();
        $cancelledProjects = (clone $scopedProjectsQuery)->where('status', ProjectStatus::CANCELLED->value)->count();
        $archivedProjects = (clone $scopedProjectsQuery)->where('status', ProjectStatus::ARCHIVED->value)->count();

        $overdueProjects = (clone $scopedProjectsQuery)
            ->whereNotIn('status', [ProjectStatus::COMPLETED->value, ProjectStatus::ARCHIVED->value])
            ->whereNotNull('deadline')
            ->where('deadline', '<', now()->startOfDay())
            ->count();

        // 2. Fetch main/top-level projects ONLY (NO SUBPROJECTS on the dashboard)
        $mainProjects = (clone $scopedProjectsQuery)
            ->whereNull('parent_project_id')
            ->where('status', '!=', ProjectStatus::ARCHIVED->value)
            ->with(['organizationNode', 'projectManagers.user'])
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => function ($q) {
                    $q->where('status', TaskStatus::COMPLETED->value);
                },
                'tasks as in_progress_tasks_count' => function ($q) {
                    $q->where('status', TaskStatus::IN_PROGRESS->value);
                },
            ])
            ->orderByRaw("CASE WHEN status = 'ACTIVE' THEN 1 WHEN status = 'ON_HOLD' THEN 2 ELSE 3 END")
            ->orderBy('deadline', 'asc')
            ->take(6)
            ->get();

        // 3. Tasks Metrics across accessible scope
        $tasksBaseQuery = Task::whereIn('project_id', $accessibleProjectIds);
        $totalTasks = (clone $tasksBaseQuery)->count();

        $taskStatusCounts = [
            'TODO' => (clone $tasksBaseQuery)->where('status', TaskStatus::TODO->value)->count(),
            'IN_PROGRESS' => (clone $tasksBaseQuery)->where('status', TaskStatus::IN_PROGRESS->value)->count(),
            'PENDING_REVIEW' => (clone $tasksBaseQuery)->where('status', TaskStatus::PENDING_REVIEW->value)->count(),
            'NEEDS_REVISION' => (clone $tasksBaseQuery)->where('status', TaskStatus::NEEDS_REVISION->value)->count(),
            'COMPLETED' => (clone $tasksBaseQuery)->where('status', TaskStatus::COMPLETED->value)->count(),
            'CANCELLED' => (clone $tasksBaseQuery)->where('status', TaskStatus::CANCELLED->value)->count(),
        ];

        $taskPriorityCounts = [
            'URGENT' => (clone $tasksBaseQuery)->where('priority', TaskPriority::URGENT->value)->count(),
            'HIGH' => (clone $tasksBaseQuery)->where('priority', TaskPriority::HIGH->value)->count(),
            'MEDIUM' => (clone $tasksBaseQuery)->where('priority', TaskPriority::MEDIUM->value)->count(),
            'LOW' => (clone $tasksBaseQuery)->where('priority', TaskPriority::LOW->value)->count(),
        ];

        $activeTasksCount = $taskStatusCounts['TODO'] + $taskStatusCounts['IN_PROGRESS'] + $taskStatusCounts['PENDING_REVIEW'] + $taskStatusCounts['NEEDS_REVISION'];
        $overdueTasksCount = (clone $tasksBaseQuery)
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->count();

        $overallTaskProgress = $totalTasks > 0 ? round(($taskStatusCounts['COMPLETED'] / $totalTasks) * 100, 1) : 0;
        $overallProjectProgress = $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100, 1) : 0;

        // 4. Personal Workspace (میز کار من)
        // A. Tasks assigned to current user
        $myTasksQuery = Task::where('executor_user_id', $user->id)
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->with(['project.organizationNode', 'creator', 'taskSupervisors']);

        $myTasks = (clone $myTasksQuery)
            ->orderByRaw('CASE WHEN due_date < ? THEN 0 ELSE 1 END, due_date ASC', [now()->startOfDay()])
            ->take(8)
            ->get();

        $myActiveTasksCount = (clone $myTasksQuery)->count();
        $myOverdueTasksCount = (clone $myTasksQuery)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->count();
        $myCompletedTasksCount = Task::where('executor_user_id', $user->id)
            ->where('status', TaskStatus::COMPLETED->value)
            ->count();

        // B. Pending Supervisor Reviews for current user (کارتابل تایید ناظر)
        $myPendingApprovals = TaskSupervisor::where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->whereHas('task', function ($q) use ($accessibleProjectIds) {
                $q->whereIn('project_id', $accessibleProjectIds)
                    ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value]);
            })
            ->with(['task.project.organizationNode', 'task.executor', 'approvalStage'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        $myPendingApprovalsCount = TaskSupervisor::where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->whereHas('task', function ($q) use ($accessibleProjectIds) {
                $q->whereIn('project_id', $accessibleProjectIds)
                    ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value]);
            })
            ->count();

        // C. Tasks created by current user
        $myCreatedTasks = Task::where('created_by', $user->id)
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->with(['project', 'executor'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // 5. Critical Deadlines & Alarms (< 48 hours or overdue)
        $imminentTasks = (clone $tasksBaseQuery)
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(2)->endOfDay())
            ->with(['project', 'executor'])
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        // 6. Recent Live Activity Feed
        $recentChats = TaskChat::whereHas('task', function ($q) use ($accessibleProjectIds) {
            $q->whereIn('project_id', $accessibleProjectIds);
        })
            ->with(['user', 'task.project'])
            ->latest()
            ->take(5)
            ->get();

        $recentAttachments = TaskAttachment::whereHas('task', function ($q) use ($accessibleProjectIds) {
            $q->whereIn('project_id', $accessibleProjectIds);
        })
            ->with(['user', 'task.project'])
            ->latest()
            ->take(4)
            ->get();

        $recentCompletedTasks = Task::whereIn('project_id', $accessibleProjectIds)
            ->where('status', TaskStatus::COMPLETED->value)
            ->whereNotNull('completed_at')
            ->with(['executor', 'project'])
            ->latest('completed_at')
            ->take(4)
            ->get();

        return view('dashboard.index', compact(
            'user',
            'totalUsers',
            'activeUsers',
            'totalNodes',
            'totalProjects',
            'activeProjects',
            'completedProjects',
            'onHoldProjects',
            'cancelledProjects',
            'archivedProjects',
            'overdueProjects',
            'mainProjects',
            'totalTasks',
            'activeTasksCount',
            'overdueTasksCount',
            'taskStatusCounts',
            'taskPriorityCounts',
            'overallTaskProgress',
            'overallProjectProgress',
            'myTasks',
            'myActiveTasksCount',
            'myOverdueTasksCount',
            'myCompletedTasksCount',
            'myPendingApprovals',
            'myPendingApprovalsCount',
            'myCreatedTasks',
            'imminentTasks',
            'recentChats',
            'recentAttachments',
            'recentCompletedTasks'
        ));
    }
}
