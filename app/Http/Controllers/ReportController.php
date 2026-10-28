<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Services\OrganizationScopeService;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
        private readonly OrganizationScopeService $orgScopeService
    ) {}

    /**
     * Comprehensive Project Report & Analytics Page.
     */
    public function projects(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status');
        $priority = $request->query('priority');
        $organizationNodeId = $request->query('organization_node_id');
        $search = $request->query('search');
        $health = $request->query('health');

        $baseQuery = $this->projectService->scopeVisibleProjects(Project::query(), $user);

        // Aggregate counts without fetching full collections
        $totalProjects = (clone $baseQuery)->count();
        $activeProjects = (clone $baseQuery)->where('status', ProjectStatus::ACTIVE->value)->count();
        $completedProjects = (clone $baseQuery)->where('status', ProjectStatus::COMPLETED->value)->count();
        $onHoldProjects = (clone $baseQuery)->where('status', ProjectStatus::ON_HOLD->value)->count();
        $cancelledProjects = (clone $baseQuery)->where('status', ProjectStatus::CANCELLED->value)->count();

        // Priority breakdowns
        $forceMajeureProjects = (clone $baseQuery)->where('priority', ProjectPriority::FORCE_MAJEURE->value)->count();
        $highPriorityProjects = (clone $baseQuery)->where('priority', ProjectPriority::HIGH->value)->count();
        $mediumPriorityProjects = (clone $baseQuery)->where('priority', ProjectPriority::MEDIUM->value)->count();
        $lowPriorityProjects = (clone $baseQuery)->where('priority', ProjectPriority::LOW->value)->count();

        // Health & Overdue Projects
        $now = now();
        $overdueProjects = (clone $baseQuery)
            ->whereNotIn('status', [ProjectStatus::COMPLETED->value, ProjectStatus::ARCHIVED->value])
            ->whereNotNull('deadline')
            ->where('deadline', '<', $now)
            ->count();

        // Root vs Subprojects
        $rootProjectsCount = (clone $baseQuery)->whereNull('parent_project_id')->count();
        $subProjectsCount = $totalProjects - $rootProjectsCount;

        // Query with eager loads
        $query = Project::with([
            'organizationNode',
            'creator',
            'parent',
            'projectManagers.user',
            'members.user',
            'children',
        ])
            ->withCount(['tasks', 'children', 'members']);

        $scopedQuery = $this->projectService->scopeVisibleProjects($query, $user);

        if (! empty($search)) {
            $scopedQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('project_key', 'like', "%{$search}%");
            });
        }

        if (! empty($status)) {
            $scopedQuery->where('status', $status);
        }

        if (! empty($priority)) {
            $scopedQuery->where('priority', $priority);
        }

        if (! empty($organizationNodeId)) {
            $scopedQuery->where('organization_node_id', $organizationNodeId);
        }

        if ($health === 'overdue') {
            $scopedQuery->whereNotIn('status', [ProjectStatus::COMPLETED->value, ProjectStatus::ARCHIVED->value])
                ->whereNotNull('deadline')
                ->where('deadline', '<', $now);
        } elseif ($health === 'completed') {
            $scopedQuery->where('status', ProjectStatus::COMPLETED->value);
        } elseif ($health === 'active') {
            $scopedQuery->where('status', ProjectStatus::ACTIVE->value);
        }

        $projects = $scopedQuery->orderBy('deadline', 'asc')->paginate(20)->withQueryString();
        $organizationNodes = OrganizationNode::orderBy('name')->get();

        // Org Unit Distribution Stats for charts
        $orgDistribution = [];
        foreach ($organizationNodes as $node) {
            $count = (clone $baseQuery)->where('organization_node_id', $node->id)->count();
            if ($count > 0) {
                $orgDistribution[] = [
                    'id' => $node->id,
                    'name' => $node->name,
                    'count' => $count,
                    'percentage' => $totalProjects > 0 ? (int) round(($count / $totalProjects) * 100) : 0,
                ];
            }
        }

        return view('reports.projects', compact(
            'projects',
            'organizationNodes',
            'status',
            'priority',
            'organizationNodeId',
            'search',
            'health',
            'totalProjects',
            'activeProjects',
            'completedProjects',
            'onHoldProjects',
            'cancelledProjects',
            'forceMajeureProjects',
            'highPriorityProjects',
            'mediumPriorityProjects',
            'lowPriorityProjects',
            'overdueProjects',
            'rootProjectsCount',
            'subProjectsCount',
            'orgDistribution'
        ));
    }

    /**
     * Member Workload & Capacity Report Page.
     */
    public function workload(Request $request): View
    {
        $user = $request->user();
        $workloadData = $this->orgScopeService->getMemberWorkloadReport($user);
        $organizationNodes = OrganizationNode::orderBy('name')->get();

        // Aggregate capacity analytics
        $totalMembers = $workloadData->count();
        $overloadedCount = $workloadData->where('load_status', 'overloaded')->count();
        $balancedCount = $workloadData->where('load_status', 'balanced')->count();
        $underutilizedCount = $workloadData->where('load_status', 'underutilized')->count();

        $totalActiveTasks = $workloadData->sum('active_tasks');
        $totalCompletedTasks = $workloadData->sum('completed_tasks');
        $totalOverdueTasks = $workloadData->sum('overdue_tasks');
        $totalInProgressTasks = $workloadData->sum('in_progress_tasks');

        $totalActiveWeight = (int) $workloadData->sum('active_weight');
        $totalCompletedWeight = (int) $workloadData->sum('completed_weight');
        $totalOverdueWeight = (int) $workloadData->sum('overdue_weight');
        $totalWeight = (int) $workloadData->sum('total_weight');

        return view('reports.workload', compact(
            'workloadData',
            'user',
            'organizationNodes',
            'totalMembers',
            'overloadedCount',
            'balancedCount',
            'underutilizedCount',
            'totalActiveTasks',
            'totalCompletedTasks',
            'totalOverdueTasks',
            'totalInProgressTasks',
            'totalActiveWeight',
            'totalCompletedWeight',
            'totalOverdueWeight',
            'totalWeight'
        ));
    }
}
