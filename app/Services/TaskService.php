<?php

namespace App\Services;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectPermission;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectApprovalStage;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskChat;
use App\Models\TaskSupervisor;
use App\Models\User;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(
        private readonly ProjectPermissionResolver $permissionResolver,
        private readonly FileOptimizationService $fileOptimizationService,
        private readonly AuthorizationService $authorizationService
    ) {}

    public function createTask(User $user, Project $project, array $data): Task
    {
        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::CREATE_TASK)) {
            abort(403, 'شما دسترسی لازم جهت ایجاد تسک در این پروژه را ندارید.');
        }

        // Validate parent task (subtask) if provided
        $parentId = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($parentId) {
            $parentTask = Task::find($parentId);
            if (! $parentTask) {
                throw ValidationException::withMessages(['parent_id' => 'تسک والد انتخاب شده یافت نشد.']);
            }

            $allowedProjectIds = array_unique(array_merge([$project->id], $project->getAllDescendantIds(), $project->getAncestorIds()));
            if (! in_array($parentTask->project_id, $allowedProjectIds)) {
                throw ValidationException::withMessages(['parent_id' => 'وظیفه والد انتخابی باید متعلق به همین پروژه یا زیرپروژه‌های مرتبط با آن باشد.']);
            }
        }

        if (! empty($data['executor_user_id'])) {
            $isMember = $project->members()
                ->where('user_id', $data['executor_user_id'])
                ->whereIn('status', [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value])
                ->exists();

            if (! $isMember && $project->organization_node_id) {
                $targetExecutor = User::find($data['executor_user_id']);
                if ($targetExecutor && $this->authorizationService->canAccessNode($targetExecutor, $project->organization_node_id)) {
                    ProjectMember::firstOrCreate([
                        'project_id' => $project->id,
                        'user_id' => $targetExecutor->id,
                    ], [
                        'project_role' => ProjectMemberRole::MEMBER->value,
                        'status' => ProjectMemberStatus::ACCEPTED->value,
                        'joined_at' => now(),
                    ]);
                    $isMember = true;
                }
            }

            if (! $isMember && ! $user->isSuperAdmin() && ! $user->isExecutiveOrAdmin() && ! $user->isProjectOrUnitManager()) {
                throw ValidationException::withMessages(['executor_user_id' => 'کاربر انتخاب شده به عنوان مجری، باید دارای عضویت در این پروژه یا واحد سازمانی مربوطه باشد.']);
            }
        }

        $startDate = $this->convertShamsiToGregorian($data['start_date'] ?? null);
        $dueDate = $this->convertShamsiToGregorian($data['due_date'] ?? null);

        // Date bounds validation
        if ($startDate && $dueDate && strtotime($dueDate) < strtotime($startDate)) {
            throw ValidationException::withMessages(['due_date' => 'تاریخ پایان (ددلاین) تسک نمی‌تواند قبل از تاریخ شروع آن باشد.']);
        }

        if ($dueDate && $project->deadline && strtotime($dueDate) > strtotime($project->deadline->format('Y-m-d'))) {
            throw ValidationException::withMessages(['due_date' => 'ددلاین تسک نمی‌تواند خارج از مهلت ددلاین پروژه ('.$project->deadline->format('Y-m-d').') باشد.']);
        }

        $initialStatus = ! empty($data['status']) ? TaskStatus::from($data['status']) : TaskStatus::TODO;

        $task = Task::create([
            'project_id' => $project->id,
            'parent_id' => $parentId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'executor_user_id' => $data['executor_user_id'] ?? null,
            'priority' => $data['priority'] ?? TaskPriority::MEDIUM->value,
            'weight' => ! empty($data['weight']) ? (int) $data['weight'] : 1,
            'status' => $initialStatus->value,
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'version' => ! empty($data['version']) ? trim($data['version']) : null,
            'created_by' => $user->id,
            'completed_at' => $initialStatus === TaskStatus::COMPLETED ? now() : null,
        ]);

        // Stage-based supervisors assignment from project approval stages
        if (! empty($data['stage_supervisors']) && is_array($data['stage_supervisors'])) {
            foreach ($data['stage_supervisors'] as $stageId => $stageData) {
                if (empty($stageData['user_id'])) {
                    continue;
                }

                $projectStage = ProjectApprovalStage::find($stageId);

                TaskSupervisor::create([
                    'task_id' => $task->id,
                    'project_approval_stage_id' => $projectStage?->id,
                    'stage_number' => $stageData['stage_number'] ?? $projectStage?->stage_number ?? 1,
                    'stage_name' => $stageData['stage_name'] ?? $projectStage?->stage_name ?? 'مرحله تایید',
                    'user_id' => $stageData['user_id'],
                    'status' => 'PENDING',
                ]);
            }
        } elseif (! empty($data['supervisor_ids']) && is_array($data['supervisor_ids'])) {
            // Fallback for simple supervisor selections
            foreach ($data['supervisor_ids'] as $idx => $supervisorId) {
                TaskSupervisor::create([
                    'task_id' => $task->id,
                    'stage_number' => $idx + 1,
                    'stage_name' => 'مرحله تایید '.($idx + 1),
                    'user_id' => $supervisorId,
                    'status' => 'PENDING',
                ]);
            }
        }

        return $task->load(['executor', 'supervisors', 'taskSupervisors.user', 'creator', 'parent']);
    }

    public function updateTask(User $user, Task $task, array $data): Task
    {
        $project = $task->project;

        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::UPDATE_TASK)) {
            abort(403, 'شما دسترسی لازم برای ویرایش این تسک را ندارید.');
        }

        // Circular subtask & project hierarchy check
        $parentId = isset($data['parent_id']) && ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($parentId && $parentId !== $task->parent_id) {
            $parentTask = Task::find($parentId);
            if (! $parentTask) {
                throw ValidationException::withMessages(['parent_id' => 'تسک والد انتخاب شده یافت نشد.']);
            }

            if ($parentId === $task->id || $task->isDescendantOf($parentId)) {
                throw ValidationException::withMessages(['parent_id' => 'امکان انتخاب این تسک به عنوان تسک والد وجود ندارد؛ زیرا باعث ایجاد رابطه دوری (Circular Reference) می‌شود.']);
            }

            $allowedProjectIds = array_unique(array_merge([$project->id], $project->getAllDescendantIds(), $project->getAncestorIds()));
            if (! in_array($parentTask->project_id, $allowedProjectIds)) {
                throw ValidationException::withMessages(['parent_id' => 'وظیفه والد انتخابی باید متعلق به همین پروژه یا زیرپروژه‌های مرتبط با آن باشد.']);
            }
        }

        if (! empty($data['executor_user_id'])) {
            $isMember = $project->members()
                ->where('user_id', $data['executor_user_id'])
                ->whereIn('status', [ProjectMemberStatus::ACCEPTED->value, ProjectMemberStatus::INVITED->value])
                ->exists();

            if (! $isMember && $project->organization_node_id) {
                $targetExecutor = User::find($data['executor_user_id']);
                if ($targetExecutor && $this->authorizationService->canAccessNode($targetExecutor, $project->organization_node_id)) {
                    ProjectMember::firstOrCreate([
                        'project_id' => $project->id,
                        'user_id' => $targetExecutor->id,
                    ], [
                        'project_role' => ProjectMemberRole::MEMBER->value,
                        'status' => ProjectMemberStatus::ACCEPTED->value,
                        'joined_at' => now(),
                    ]);
                    $isMember = true;
                }
            }

            if (! $isMember && ! $user->isSuperAdmin() && ! $user->isExecutiveOrAdmin() && ! $user->isProjectOrUnitManager()) {
                throw ValidationException::withMessages(['executor_user_id' => 'کاربر انتخاب شده به عنوان مجری، باید دارای عضویت در این پروژه یا واحد سازمانی مربوطه باشد.']);
            }
        }

        $startDate = array_key_exists('start_date', $data) ? $this->convertShamsiToGregorian($data['start_date']) : $task->start_date?->format('Y-m-d');
        $dueDate = array_key_exists('due_date', $data) ? $this->convertShamsiToGregorian($data['due_date']) : $task->due_date?->format('Y-m-d');

        if ($startDate && $dueDate && strtotime($dueDate) < strtotime($startDate)) {
            throw ValidationException::withMessages(['due_date' => 'تاریخ پایان (ددلاین) تسک نمی‌تواند قبل از تاریخ شروع آن باشد.']);
        }

        if ($dueDate && $project->deadline && strtotime($dueDate) > strtotime($project->deadline->format('Y-m-d'))) {
            throw ValidationException::withMessages(['due_date' => 'ددلاین تسک نمی‌تواند خارج از مهلت ددلاین پروژه ('.$project->deadline->format('Y-m-d').') باشد.']);
        }

        $updatePayload = [
            'parent_id' => $parentId,
            'title' => $data['title'] ?? $task->title,
            'description' => array_key_exists('description', $data) ? $data['description'] : $task->description,
            'executor_user_id' => array_key_exists('executor_user_id', $data) ? $data['executor_user_id'] : $task->executor_user_id,
            'priority' => $data['priority'] ?? $task->priority->value,
            'weight' => array_key_exists('weight', $data) ? (int) $data['weight'] : ($task->weight ?? 1),
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'version' => array_key_exists('version', $data) ? (! empty($data['version']) ? trim($data['version']) : null) : $task->version,
        ];

        // Handle status update if provided
        if (! empty($data['status']) && $data['status'] !== $task->status->value) {
            $newStatus = TaskStatus::from($data['status']);
            if ($newStatus === TaskStatus::COMPLETED && $task->hasUnresolvedSubtasks()) {
                throw ValidationException::withMessages([
                    'status' => 'امکان تغییر وضعیت به «تکمیل شده» وجود ندارد؛ زیرا این وظیفه دارای زیروظایف تعیین‌تکلیف‌نشده است. تمامی زیروظایف باید ابتدا تکمیل، لغو یا حذف گردند.',
                ]);
            }

            $updatePayload['status'] = $newStatus->value;
            $updatePayload['completed_at'] = $newStatus === TaskStatus::COMPLETED ? now() : null;
        }

        $task->update($updatePayload);

        // Handle supervisor updates (more, fewer, or zero supervisors)
        if (isset($data['stage_supervisors']) && is_array($data['stage_supervisors'])) {
            TaskSupervisor::where('task_id', $task->id)->delete();

            foreach ($data['stage_supervisors'] as $stageId => $stageData) {
                if (empty($stageData['user_id'])) {
                    continue;
                }

                $projectStage = ProjectApprovalStage::find($stageId);

                TaskSupervisor::create([
                    'task_id' => $task->id,
                    'project_approval_stage_id' => $projectStage?->id,
                    'stage_number' => $stageData['stage_number'] ?? $projectStage?->stage_number ?? 1,
                    'stage_name' => $stageData['stage_name'] ?? $projectStage?->stage_name ?? 'مرحله تایید',
                    'user_id' => $stageData['user_id'],
                    'status' => 'PENDING',
                ]);
            }
        } elseif (isset($data['supervisor_ids']) && is_array($data['supervisor_ids'])) {
            TaskSupervisor::where('task_id', $task->id)->delete();

            foreach ($data['supervisor_ids'] as $idx => $supervisorId) {
                if (empty($supervisorId)) {
                    continue;
                }

                TaskSupervisor::create([
                    'task_id' => $task->id,
                    'stage_number' => $idx + 1,
                    'stage_name' => 'مرحله تایید '.($idx + 1),
                    'user_id' => $supervisorId,
                    'status' => 'PENDING',
                ]);
            }
        } elseif (array_key_exists('stage_supervisors', $data) || array_key_exists('supervisor_ids', $data)) {
            // Explicit empty supervisors passed -> sets supervisor count to 0
            TaskSupervisor::where('task_id', $task->id)->delete();
        }

        return $task->fresh(['executor', 'supervisors', 'taskSupervisors.user', 'creator']);
    }

    public function updateStatus(User $user, Task $task, string $newStatus, ?string $comments = null): Task
    {
        $project = $task->project;
        $isExecutor = $task->executor_user_id === $user->id;
        $isManager = $this->permissionResolver->hasPermission($user, $project, ProjectPermission::UPDATE_TASK);

        if (! $isExecutor && ! $isManager) {
            abort(403, 'شما مجاز به تغییر وضعیت این تسک نیستید.');
        }

        $taskStatus = TaskStatus::from($newStatus);
        $hasSupervisors = $task->taskSupervisors()->count() > 0;

        if ($taskStatus === TaskStatus::PENDING_REVIEW || $taskStatus === TaskStatus::COMPLETED) {
            if ($hasSupervisors && $taskStatus === TaskStatus::PENDING_REVIEW) {
                $task->update([
                    'status' => TaskStatus::PENDING_REVIEW->value,
                    'completed_at' => null,
                ]);

                TaskSupervisor::where('task_id', $task->id)->update([
                    'status' => 'PENDING',
                    'comments' => null,
                    'action_at' => null,
                ]);
            } else {
                // Task is transitioning to COMPLETED (either explicitly or because it has no supervisors)
                if ($task->hasUnresolvedSubtasks()) {
                    throw ValidationException::withMessages([
                        'status' => 'امکان نهایی‌سازی و تکمیل این وظیفه وجود ندارد؛ زیرا دارای زیروظایف تعیین‌تکلیف‌نشده (در انتظار انجام، در حال انجام یا نیازمند اصلاح) است. تمامی زیروظایف باید ابتدا تکمیل، لغو یا حذف گردند.',
                    ]);
                }

                $task->update([
                    'status' => TaskStatus::COMPLETED->value,
                    'completed_at' => now(),
                ]);
            }
        } else {
            $task->update([
                'status' => $taskStatus->value,
                'completed_at' => null,
            ]);
        }

        if ($comments) {
            $this->addChat($user, $task, "تغییر وضعیت به '{$taskStatus->label()}': {$comments}");
        }

        return $task->fresh(['supervisors', 'taskSupervisors.user', 'executor']);
    }

    public function reviewTask(User $user, Task $task, string $action, ?string $comments = null): Task
    {
        $project = $task->project;

        $taskSupervisor = TaskSupervisor::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->orderBy('stage_number', 'asc')
            ->first();

        $isProjectManager = $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_APPROVAL_STAGES)
            || $this->permissionResolver->hasPermission($user, $project, ProjectPermission::UPDATE_TASK);
        $isAuthorized = $taskSupervisor || $user->isSuperAdmin() || $isProjectManager;

        if (! $isAuthorized) {
            abort(403, 'شما مجاز به تایید/رد این تسک نیستید.');
        }

        if (! $taskSupervisor) {
            $taskSupervisor = TaskSupervisor::where('task_id', $task->id)
                ->where('status', 'PENDING')
                ->orderBy('stage_number', 'asc')
                ->first();
        }

        if ($action === 'APPROVE') {
            if ($taskSupervisor) {
                $taskSupervisor->update([
                    'status' => 'APPROVED',
                    'comments' => $comments,
                    'action_at' => now(),
                ]);
            }

            if ($task->fresh()->allSupervisorsApproved()) {
                if ($task->hasUnresolvedSubtasks()) {
                    throw ValidationException::withMessages([
                        'review' => 'امکان تایید نهایی و تکمیل این وظیفه وجود ندارد؛ زیرا هنوز دارای زیروظایف تعیین‌تکلیف‌نشده است. تمامی زیروظایف باید ابتدا تکمیل، لغو یا حذف گردند.',
                    ]);
                }

                $task->update([
                    'status' => TaskStatus::COMPLETED->value,
                    'completed_at' => now(),
                ]);
                $this->addChat($user, $task, '✅ تسک توسط تمامی ناظران مراحل تایید پروژه تایید نهایی و تکمیل گردید.');
            } else {
                $stageInfo = $taskSupervisor ? "برای مرحله '{$taskSupervisor->stage_name}' " : '';
                $onBehalf = ($taskSupervisor && $taskSupervisor->user_id !== $user->id && $taskSupervisor->user)
                    ? " (به نمایندگی از ناظر: {$taskSupervisor->user->full_name})"
                    : '';
                $this->addChat($user, $task, "👍 تسک {$stageInfo}توسط ({$user->full_name}){$onBehalf} تایید شد و منتظر تایید سایر ناظران است.");
            }
        } elseif ($action === 'REJECT') {
            if ($taskSupervisor) {
                $taskSupervisor->update([
                    'status' => 'REJECTED',
                    'comments' => $comments,
                    'action_at' => now(),
                ]);
            }

            $task->update([
                'status' => TaskStatus::NEEDS_REVISION->value,
                'completed_at' => null,
            ]);

            $rejectionNote = $comments ? " علت: {$comments}" : '';
            $stageInfo = $taskSupervisor ? "مرحله '{$taskSupervisor->stage_name}' " : '';
            $onBehalf = ($taskSupervisor && $taskSupervisor->user_id !== $user->id && $taskSupervisor->user)
                ? " (به نمایندگی از ناظر: {$taskSupervisor->user->full_name})"
                : '';
            $this->addChat($user, $task, "🚨 تسک در {$stageInfo}توسط ({$user->full_name}){$onBehalf} رد شد و به وضعیت نیازمند اصلاح بازگشت.{$rejectionNote}");
        }

        return $task->fresh(['supervisors', 'taskSupervisors.user', 'executor']);
    }

    public function addChat(User $user, Task $task, string $message): TaskChat
    {
        $project = $task->project;

        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::VIEW_PROJECT)) {
            abort(403, 'شما دسترسی لازم جهت گفتگو در این تسک را ندارید.');
        }

        return TaskChat::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'message' => $message,
        ]);
    }

    public function addAttachment(User $user, Task $task, UploadedFile $file): TaskAttachment
    {
        $project = $task->project;

        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::VIEW_PROJECT)) {
            abort(403, 'شما دسترسی لازم جهت پیوست فایل در این تسک را ندارید.');
        }

        $path = $this->fileOptimizationService->storeOptimizedFile($file, 'task-attachments');

        return TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);
    }

    public function deleteAttachment(User $user, TaskAttachment $attachment): void
    {
        $task = $attachment->task;
        $project = $task->project;

        if ($attachment->user_id !== $user->id && ! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::UPDATE_TASK)) {
            abort(403, 'شما مجاز به حذف این پیوست نیستید.');
        }

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();
    }

    public function deleteTask(User $user, Task $task): void
    {
        $project = $task->project;

        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::DELETE_TASK)) {
            abort(403, 'شما دسترسی لازم برای حذف این تسک را ندارید.');
        }

        foreach ($task->subtasks as $subtask) {
            $this->deleteTask($user, $subtask);
        }

        $task->delete();
    }

    /**
     * Convert Shamsi/Jalali Date String to Gregorian YYYY-MM-DD.
     */
    public function convertShamsiToGregorian(?string $dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $clean = str_replace($persianDigits, $englishDigits, $dateStr);
        $clean = str_replace($arabicDigits, $englishDigits, $clean);
        $clean = trim($clean);

        try {
            if (preg_match('/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/', $clean)) {
                $parts = preg_split('/[\/-]/', $clean);
                $year = (int) $parts[0];
                $month = (int) $parts[1];
                $day = (int) $parts[2];

                if ($year >= 1300 && $year <= 1500) {
                    $formattedDate = sprintf('%04d/%02d/%02d', $year, $month, $day);
                    $v = Verta::parseFormat('Y/m/d', $formattedDate);

                    return $v->datetime()->format('Y-m-d');
                }

                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
