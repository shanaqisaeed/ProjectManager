<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPermission;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Services\ProjectPermissionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GanttController extends Controller
{
    public function __construct(
        private readonly ProjectPermissionResolver $permissionResolver
    ) {}

    public function show(Request $request, Project $project): View
    {
        if (! $this->permissionResolver->hasPermission($request->user(), $project, ProjectPermission::VIEW_PROJECT)) {
            abort(403);
        }

        return view('projects.gantt', compact('project'));
    }

    public function data(Request $request, Project $project): JsonResponse
    {
        if (! $this->permissionResolver->hasPermission($request->user(), $project, ProjectPermission::VIEW_PROJECT)) {
            abort(403);
        }

        $descendantIds = $project->getAllDescendantIds();
        $allProjectIds = array_merge([$project->id], $descendantIds);

        $projects = Project::whereIn('id', $allProjectIds)
            ->with(['tasks.executor'])
            ->get();

        $ganttItems = [];

        foreach ($projects as $p) {
            $isRoot = $p->id === $project->id;
            $startDate = $p->start_date ? verta($p->start_date)->format('Y/m/d') : ($p->created_at ? verta($p->created_at)->format('Y/m/d') : verta()->format('Y/m/d'));
            $dueDate = $p->deadline ? verta($p->deadline)->format('Y/m/d') : null;

            $ganttItems[] = [
                'id' => "proj-{$p->id}",
                'text' => $p->title.($isRoot ? ' (پروژه اصلی)' : ''),
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'progress' => $p->tasks_progress,
                'type' => $isRoot ? 'project' : 'subproject',
                'parent_id' => $p->parent_project_id ? "proj-{$p->parent_project_id}" : null,
                'status' => $p->status?->label() ?? 'فعال',
            ];

            foreach ($p->tasks as $task) {
                $taskStartDate = $task->start_date ? verta($task->start_date)->format('Y/m/d') : ($task->created_at ? verta($task->created_at)->format('Y/m/d') : verta()->format('Y/m/d'));
                $taskDueDate = $task->due_date ? verta($task->due_date)->format('Y/m/d') : null;

                $ganttItems[] = [
                    'id' => "task-{$task->id}",
                    'text' => $task->title,
                    'start_date' => $taskStartDate,
                    'due_date' => $taskDueDate,
                    'progress' => $task->status === TaskStatus::COMPLETED ? 100 : ($task->status === TaskStatus::IN_PROGRESS ? 50 : 0),
                    'type' => 'task',
                    'parent_id' => $task->parent_id ? "task-{$task->parent_id}" : "proj-{$p->id}",
                    'executor' => $task->executor?->full_name ?? 'تعیین‌نشده',
                    'status' => $task->status?->label() ?? 'انجام‌نشده',
                ];
            }
        }

        return response()->json([
            'project_id' => $project->id,
            'title' => $project->title,
            'items' => $ganttItems,
        ]);
    }
}
