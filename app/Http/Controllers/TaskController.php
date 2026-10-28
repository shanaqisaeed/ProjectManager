<?php

namespace App\Http\Controllers;

use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectPermission;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\ProjectPermissionResolver;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly ProjectPermissionResolver $permissionResolver
    ) {}

    public function searchParents(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();
        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::VIEW_PROJECT)) {
            abort(403);
        }

        $query = trim((string) $request->query('q', ''));
        $excludeTaskId = $request->query('exclude_task_id') ? (int) $request->query('exclude_task_id') : null;

        $descendantIds = $project->getAllDescendantIds();
        $ancestorIds = $project->getAncestorIds();
        $allProjectIds = array_unique(array_merge([$project->id], $descendantIds, $ancestorIds));

        $tasksQuery = Task::whereIn('project_id', $allProjectIds)
            ->with(['project:id,title,project_key']);

        if ($excludeTaskId) {
            $tasksQuery->where('id', '!=', $excludeTaskId);
        }

        if ($query !== '') {
            $tasksQuery->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('version', 'like', "%{$query}%")
                    ->orWhere('id', $query);
            });
        }

        $tasks = $tasksQuery->orderBy('title', 'asc')
            ->limit(30)
            ->get();

        if ($excludeTaskId) {
            $tasks = $tasks->reject(fn ($t) => $t->isDescendantOf($excludeTaskId))->values();
        }

        $formatted = $tasks->map(function (Task $t) {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'version' => $t->version,
                'project_title' => $t->project?->title ?? '',
                'project_key' => $t->project?->project_key ?? '',
                'status_label' => $t->status->label(),
                'badge_class' => $t->status->badgeClass(),
            ];
        });

        return response()->json([
            'success' => true,
            'tasks' => $formatted,
        ]);
    }

    public function create(Request $request, Project $project): View
    {
        $user = $request->user();
        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::CREATE_TASK)) {
            abort(403, 'شما دسترسی لازم برای ایجاد کار در این پروژه را ندارید.');
        }

        $project->load(['approvalStages.supervisor', 'members.user', 'organizationNode']);

        $selectedParentId = $request->query('parent_id') ? (int) $request->query('parent_id') : (old('parent_id') ? (int) old('parent_id') : null);
        $initialParentTask = $selectedParentId ? Task::with('project')->find($selectedParentId) : null;

        $projectMemberUsers = $project->members
            ->filter(fn ($m) => in_array($m->status?->value ?? $m->status, [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value]))
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        $projectExecutorUsers = $projectMemberUsers;
        $projectSupervisorUsers = $projectMemberUsers;

        // If user is manager/superior or super admin, also include available users in project's organizational node
        if ($user->isSuperAdmin() || $user->isExecutiveOrAdmin() || $user->isProjectOrUnitManager()) {
            $nodeDescendantIds = $project->organizationNode ? $project->organizationNode->getAllDescendantIds() : [];
            $allNodeIds = array_unique(array_merge([$project->organization_node_id], $nodeDescendantIds));

            $scopeUserIds = UserRoleAssignment::where('status', 'ACTIVE')
                ->whereIn('organization_node_id', $allNodeIds)
                ->pluck('user_id')
                ->unique()
                ->toArray();

            if (! empty($scopeUserIds)) {
                $scopeUsers = User::whereIn('id', $scopeUserIds)->where('status', 'ACTIVE')->get();
                $projectExecutorUsers = $projectExecutorUsers->concat($scopeUsers)->unique('id')->values();
                $projectSupervisorUsers = $projectSupervisorUsers->concat($scopeUsers)->unique('id')->values();
            }
        }

        // Attach active workload intelligence to candidate executors for supervisor guidance
        $executorIds = $projectExecutorUsers->pluck('id')->toArray();
        $userWorkloads = Task::whereIn('executor_user_id', $executorIds)
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->selectRaw('executor_user_id, count(*) as active_count, sum(coalesce(weight, 1)) as active_weight')
            ->groupBy('executor_user_id')
            ->get()
            ->keyBy('executor_user_id');

        $projectExecutorUsers = $projectExecutorUsers->map(function ($u) use ($userWorkloads) {
            $wl = $userWorkloads->get($u->id);
            $u->active_weight = $wl ? (int) $wl->active_weight : 0;
            $u->active_tasks_count = $wl ? (int) $wl->active_count : 0;

            return $u;
        });

        return view('projects.tasks.create', [
            'project' => $project,
            'initialParentTask' => $initialParentTask,
            'projectExecutorUsers' => $projectExecutorUsers,
            'projectSupervisorUsers' => $projectSupervisorUsers,
            'taskStatuses' => TaskStatus::cases(),
            'taskPriorities' => TaskPriority::cases(),
            'selectedParentId' => $selectedParentId,
        ]);
    }

    public function edit(Request $request, Project $project, Task $task): View
    {
        $user = $request->user();
        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::UPDATE_TASK)) {
            abort(403, 'شما دسترسی لازم برای ویرایش این کار را ندارید.');
        }

        $taskProject = $task->project ?? $project;
        $task->load(['taskSupervisors.user', 'executor', 'parent.project']);
        $taskProject->load(['approvalStages.supervisor', 'members.user', 'organizationNode']);

        $initialParentTask = $task->parent ? $task->parent : (old('parent_id') ? Task::with('project')->find((int) old('parent_id')) : null);

        $projectMemberUsers = $taskProject->members
            ->filter(fn ($m) => in_array($m->status?->value ?? $m->status, [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value]))
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        $projectExecutorUsers = $projectMemberUsers;
        $projectSupervisorUsers = $projectMemberUsers;

        if ($user->isSuperAdmin() || $user->isExecutiveOrAdmin() || $user->isProjectOrUnitManager()) {
            $nodeDescendantIds = $taskProject->organizationNode ? $taskProject->organizationNode->getAllDescendantIds() : [];
            $allNodeIds = array_unique(array_merge([$taskProject->organization_node_id], $nodeDescendantIds));

            $scopeUserIds = UserRoleAssignment::where('status', 'ACTIVE')
                ->whereIn('organization_node_id', $allNodeIds)
                ->pluck('user_id')
                ->unique()
                ->toArray();

            if (! empty($scopeUserIds)) {
                $scopeUsers = User::whereIn('id', $scopeUserIds)->where('status', 'ACTIVE')->get();
                $projectExecutorUsers = $projectExecutorUsers->concat($scopeUsers)->unique('id')->values();
                $projectSupervisorUsers = $projectSupervisorUsers->concat($scopeUsers)->unique('id')->values();
            }
        }

        // Attach active workload intelligence to candidate executors for supervisor guidance
        $executorIds = $projectExecutorUsers->pluck('id')->toArray();
        $userWorkloads = Task::whereIn('executor_user_id', $executorIds)
            ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
            ->selectRaw('executor_user_id, count(*) as active_count, sum(coalesce(weight, 1)) as active_weight')
            ->groupBy('executor_user_id')
            ->get()
            ->keyBy('executor_user_id');

        $projectExecutorUsers = $projectExecutorUsers->map(function ($u) use ($userWorkloads) {
            $wl = $userWorkloads->get($u->id);
            $u->active_weight = $wl ? (int) $wl->active_weight : 0;
            $u->active_tasks_count = $wl ? (int) $wl->active_count : 0;

            return $u;
        });

        return view('projects.tasks.edit', [
            'project' => $project,
            'task' => $task,
            'taskProject' => $taskProject,
            'initialParentTask' => $initialParentTask,
            'projectExecutorUsers' => $projectExecutorUsers,
            'projectSupervisorUsers' => $projectSupervisorUsers,
            'taskStatuses' => TaskStatus::cases(),
            'taskPriorities' => TaskPriority::cases(),
        ]);
    }

    public function show(Request $request, Project $project, Task $task): View
    {
        $user = $request->user();
        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::VIEW_PROJECT)) {
            abort(403);
        }

        $task->load([
            'project.approvalStages.supervisor',
            'executor',
            'creator',
            'supervisors',
            'taskSupervisors.user',
            'attachments.uploader',
            'chats.user',
            'subtasks.executor',
            'subtasks.taskSupervisors',
            'parent',
        ]);

        // Enforce explicit task visibility scope for non-manager members
        $canUpdateTask = $this->permissionResolver->hasPermission($user, $project, ProjectPermission::UPDATE_TASK);
        $isManager = $user->isSuperAdmin()
            || $user->isExecutiveOrAdmin()
            || $user->isProjectOrUnitManager()
            || $canUpdateTask
            || $project->projectManagers->pluck('user_id')->contains($user->id);

        if (! $isManager) {
            $isExecutor = $task->executor_user_id === $user->id;
            $isCreator = $task->created_by === $user->id;
            $isSupervisor = $task->taskSupervisors->pluck('user_id')->contains($user->id);

            if (! $isExecutor && ! $isCreator && ! $isSupervisor) {
                abort(403, 'شما دسترسی لازم برای مشاهده این کار را ندارید.');
            }
        }

        $taskProject = $task->project;

        $projectMemberUsers = $taskProject->members
            ->filter(fn ($m) => in_array($m->status?->value ?? $m->status, [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value]))
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        $projectExecutorUsers = $projectMemberUsers;
        $projectSupervisorUsers = $projectMemberUsers;

        $canEditTask = $this->permissionResolver->hasPermission($request->user(), $project, ProjectPermission::UPDATE_TASK);

        return view('projects.tasks.show', [
            'project' => $project,
            'task' => $task,
            'taskProject' => $taskProject,
            'projectExecutorUsers' => $projectExecutorUsers,
            'projectSupervisorUsers' => $projectSupervisorUsers,
            'taskStatuses' => TaskStatus::cases(),
            'taskPriorities' => TaskPriority::cases(),
            'canEditTask' => $canEditTask,
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'executor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'stage_supervisors' => ['nullable', 'array'],
            'supervisor_ids' => ['nullable', 'array'],
            'priority' => ['required', 'string', 'in:LOW,MEDIUM,HIGH,URGENT'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'status' => ['required', 'string', 'in:TODO,IN_PROGRESS,PENDING_REVIEW,NEEDS_REVISION,COMPLETED,CANCELLED'],
            'start_date' => ['nullable', 'string'],
            'due_date' => ['nullable', 'string'],
            'version' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $createdTask = $this->taskService->createTask($request->user(), $project, $validated);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'task' => $createdTask,
                ], 201);
            }

            return redirect()->route('projects.show', ['project' => $project, 'tab' => 'tasks'])
                ->with('success', 'کار جدید با موفقیت ثبت شد.');
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first() ?: 'خطا در ثبت اطلاعات کار.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMsg,
                    'message' => $errorMsg,
                    'errors' => $e->validator->errors()->toArray(),
                ], 422);
            }

            return redirect()->back()->with('error', $errorMsg)->withErrors($e->validator)->withInput();
        }
    }

    public function update(Request $request, Project $project, Task $task): JsonResponse|RedirectResponse
    {
        if (! $this->permissionResolver->hasPermission($request->user(), $project, ProjectPermission::UPDATE_TASK)) {
            abort(403, 'شما دسترسی لازم برای ویرایش این کار را ندارید.');
        }

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'executor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'stage_supervisors' => ['nullable', 'array'],
            'supervisor_ids' => ['nullable', 'array'],
            'priority' => ['required', 'string', 'in:LOW,MEDIUM,HIGH,URGENT'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'status' => ['nullable', 'string', 'in:TODO,IN_PROGRESS,PENDING_REVIEW,NEEDS_REVISION,COMPLETED,CANCELLED'],
            'start_date' => ['nullable', 'string'],
            'due_date' => ['nullable', 'string'],
            'version' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $updatedTask = $this->taskService->updateTask($request->user(), $task, $validated);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'task' => $updatedTask,
                ]);
            }

            return redirect()->route('projects.show', ['project' => $project, 'tab' => 'tasks'])
                ->with('success', 'تغییرات کار با موفقیت ذخیره شد.');
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first() ?: 'خطا در ویرایش کار.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMsg,
                    'message' => $errorMsg,
                    'errors' => $e->validator->errors()->toArray(),
                ], 422);
            }

            return redirect()->back()->with('error', $errorMsg)->withErrors($e->validator)->withInput();
        }
    }

    public function destroy(Request $request, Project $project, Task $task): RedirectResponse
    {
        $this->taskService->deleteTask($request->user(), $task);

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'tasks'])
            ->with('success', 'کار با موفقیت حذف شد.');
    }

    public function updateStatus(Request $request, Project $project, Task $task): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:TODO,IN_PROGRESS,PENDING_REVIEW,NEEDS_REVISION,COMPLETED,CANCELLED'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $updatedTask = $this->taskService->updateStatus($request->user(), $task, $validated['status'], $validated['comments'] ?? null);

            if ($request->wantsJson() || $request->ajax()) {
                return $this->showJson($request, $project, $updatedTask);
            }

            return redirect()->back()->with('success', 'وضعیت کار بروزرسانی شد.');
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first() ?: 'خطا در تغییر وضعیت کار.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMsg,
                    'message' => $errorMsg,
                    'errors' => $e->validator->errors()->toArray(),
                ], 422);
            }

            return redirect()->back()->with('error', $errorMsg)->withErrors($e->validator)->withInput();
        }
    }

    public function review(Request $request, Project $project, Task $task): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:APPROVE,REJECT'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $updatedTask = $this->taskService->reviewTask($request->user(), $task, $validated['action'], $validated['comments'] ?? null);

            if ($request->wantsJson() || $request->ajax()) {
                return $this->showJson($request, $project, $updatedTask);
            }

            $msg = $validated['action'] === 'APPROVE' ? 'تایید سرپرست با موفقیت ثبت شد.' : 'کار برای بررسی بیشتر به مسئول بازگردانده شد.';

            return redirect()->back()->with('success', $msg);
        } catch (ValidationException $e) {
            $errorMsg = $e->validator->errors()->first() ?: 'خطا در ثبت تایید سرپرست.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $errorMsg,
                    'message' => $errorMsg,
                    'errors' => $e->validator->errors()->toArray(),
                ], 422);
            }

            return redirect()->back()->with('error', $errorMsg)->withErrors($e->validator)->withInput();
        }
    }

    public function storeChat(Request $request, Project $project, Task $task): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $chat = $this->taskService->addChat($request->user(), $task, $validated['message']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'chat' => [
                    'id' => $chat->id,
                    'user_name' => $chat->user ? $chat->user->full_name : 'سیستم',
                    'is_current_user' => true,
                    'message' => $chat->message,
                    'created_at' => verta($chat->created_at)->format('Y/m/d H:i'),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'پیام شما ارسال شد.');
    }

    public function storeAttachment(Request $request, Project $project, Task $task): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $attachment = $this->taskService->addAttachment($request->user(), $task, $request->file('file'));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'attachment' => [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'file_size' => number_format($attachment->file_size / 1024, 1).' KB',
                    'file_url' => asset('storage/'.$attachment->file_path),
                    'uploader_name' => $attachment->uploader ? $attachment->uploader->full_name : 'ناشناس',
                    'created_at' => verta($attachment->created_at)->format('Y/m/d H:i'),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'فایل با موفقیت پیوست شد.');
    }

    public function destroyAttachment(Request $request, Project $project, Task $task, TaskAttachment $attachment): JsonResponse|RedirectResponse
    {
        $this->taskService->deleteAttachment($request->user(), $attachment);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'attachment_id' => $attachment->id,
            ]);
        }

        return redirect()->back()->with('success', 'فایل پیوست حذف شد.');
    }

    public function showJson(Request $request, Project $project, Task $task): JsonResponse
    {
        if (! $this->permissionResolver->hasPermission($request->user(), $project, ProjectPermission::VIEW_PROJECT)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->load([
            'project',
            'executor',
            'creator',
            'supervisors',
            'taskSupervisors.user',
            'attachments.uploader',
            'chats.user',
            'subtasks.executor',
            'parent',
        ]);

        $pendingSupervisor = $task->taskSupervisors->where('status', 'PENDING')->sortBy('stage_number')->first();
        $isDirectSupervisor = $pendingSupervisor && $pendingSupervisor->user_id === $request->user()->id;
        $isSuperAdminOrManager = $request->user()->isSuperAdmin() || $this->permissionResolver->hasPermission($request->user(), $task->project, ProjectPermission::MANAGE_APPROVAL_STAGES);
        $canReview = $isDirectSupervisor || $isSuperAdminOrManager;

        return response()->json([
            'task' => [
                'id' => $task->id,
                'project_id' => $task->project_id,
                'project_title' => $task->project ? $task->project->title : '',
                'is_sub_project' => $task->project_id !== $project->id,
                'can_review' => $canReview,
                'is_direct_supervisor' => $isDirectSupervisor,
                'is_super_admin_or_manager' => $isSuperAdminOrManager,
                'pending_supervisor_name' => $pendingSupervisor?->user ? $pendingSupervisor->user->full_name : 'تعیین‌نشده',
                'pending_stage_name' => $pendingSupervisor?->stage_name ?? 'مرحله تایید',
                'pending_stage_number' => $pendingSupervisor?->stage_number ?? 1,
                'has_unresolved_subtasks' => $task->hasUnresolvedSubtasks(),
                'unresolved_subtasks_count' => $task->getUnresolvedSubtasks()->count(),
                'title' => $task->title,
                'description' => $task->description,
                'priority' => $task->priority->value,
                'priority_label' => $task->priority->label(),
                'priority_badge' => $task->priority->badgeClass(),
                'status' => $task->status->value,
                'status_label' => $task->status->label(),
                'status_badge' => $task->status->badgeClass(),
                'start_date' => $task->start_date ? $task->start_date->format('Y-m-d') : null,
                'start_date_fa' => $task->start_date ? verta($task->start_date)->format('Y/m/d') : null,
                'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                'due_date_fa' => $task->due_date ? verta($task->due_date)->format('Y/m/d') : null,
                'executor_user_id' => $task->executor_user_id,
                'executor_name' => $task->executor ? $task->executor->full_name : 'تعیین‌نشده',
                'creator_name' => $task->creator ? $task->creator->full_name : 'ناشناس',
                'completed_at' => $task->completed_at ? verta($task->completed_at)->format('Y/m/d H:i') : null,
                'parent' => $task->parent ? [
                    'id' => $task->parent->id,
                    'title' => $task->parent->title,
                    'version' => $task->parent->version,
                    'status' => $task->parent->status->value,
                    'status_label' => $task->parent->status->label(),
                    'status_badge' => $task->parent->status->badgeClass(),
                ] : null,
                'subtasks' => $task->subtasks->map(fn ($st) => [
                    'id' => $st->id,
                    'title' => $st->title,
                    'status' => $st->status->value,
                    'status_label' => $st->status->label(),
                    'status_badge' => $st->status->badgeClass(),
                    'priority' => $st->priority->value,
                    'priority_label' => $st->priority->label(),
                    'priority_badge' => $st->priority->badgeClass(),
                    'executor_name' => $st->executor ? $st->executor->full_name : 'تعیین‌نشده',
                    'due_date_fa' => $st->due_date ? verta($st->due_date)->format('Y/m/d') : null,
                ]),
                'supervisors' => $task->taskSupervisors->map(fn ($ts) => [
                    'user_id' => $ts->user_id,
                    'name' => $ts->user ? $ts->user->full_name : 'تعیین‌نشده',
                    'stage_number' => $ts->stage_number ?? 1,
                    'stage_name' => $ts->stage_name ?? 'مرحله تایید',
                    'project_approval_stage_id' => $ts->project_approval_stage_id,
                    'status' => $ts->status,
                    'comments' => $ts->comments,
                    'action_at' => $ts->action_at ? verta($ts->action_at)->format('Y/m/d H:i') : null,
                ]),
                'attachments' => $task->attachments->map(fn ($att) => [
                    'id' => $att->id,
                    'original_name' => $att->original_name,
                    'file_size' => number_format($att->file_size / 1024, 1).' KB',
                    'file_url' => asset('storage/'.$att->file_path),
                    'uploader_name' => $att->uploader ? $att->uploader->full_name : 'ناشناس',
                    'created_at' => verta($att->created_at)->format('Y/m/d H:i'),
                ]),
                'chats' => $task->chats->map(fn ($chat) => [
                    'id' => $chat->id,
                    'user_name' => $chat->user ? $chat->user->full_name : 'سیستم',
                    'is_current_user' => $chat->user_id === $request->user()->id,
                    'message' => $chat->message,
                    'created_at' => verta($chat->created_at)->format('Y/m/d H:i'),
                ]),
            ],
        ]);
    }
}
