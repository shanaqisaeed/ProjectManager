<?php

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\ProjectApprovalStage;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskSupervisor;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Database\Seeders\ProjectPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProjectPermissionSeeder::class);

    $this->orgNode = OrganizationNode::create([
        'name' => 'دفتر فنی و مهندسی',
        'code' => 'ENG-01',
        'type' => 'DEPARTMENT',
    ]);

    $this->user = User::factory()->create();
    UserRoleAssignment::create([
        'user_id' => $this->user->id,
        'role' => UserRole::SUPER_ADMIN->value,
        'is_primary' => true,
        'status' => 'ACTIVE',
    ]);

    $this->project = Project::create([
        'organization_node_id' => $this->orgNode->id,
        'title' => 'پروژه احداث مجتمع اداری',
        'project_key' => 'ADM-100',
        'created_by' => $this->user->id,
    ]);

    $this->executor = User::factory()->create(['first_name' => 'علی', 'last_name' => 'رضایی']);
    $this->supervisor1 = User::factory()->create(['first_name' => 'رضا', 'last_name' => 'احمدی']);
    $this->supervisor2 = User::factory()->create(['first_name' => 'محمد', 'last_name' => 'حسینی']);

    ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->executor->id,
        'project_role' => ProjectMemberRole::MEMBER->value,
        'status' => ProjectMemberStatus::ACCEPTED->value,
    ]);

    ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->supervisor1->id,
        'project_role' => ProjectMemberRole::PROJECT_SUPERVISOR->value,
        'status' => ProjectMemberStatus::ACCEPTED->value,
    ]);

    ProjectMember::create([
        'project_id' => $this->project->id,
        'user_id' => $this->supervisor2->id,
        'project_role' => ProjectMemberRole::PROJECT_SUPERVISOR->value,
        'status' => ProjectMemberStatus::ACCEPTED->value,
    ]);
});

test('authenticated manager can create a task with executor and multiple supervisors', function () {
    $response = $this->actingAs($this->user)->post(route('projects.tasks.store', $this->project), [
        'title' => 'طراحی نقشه سازه',
        'description' => 'آماده‌سازی فایل‌های محاسباتی',
        'executor_user_id' => $this->executor->id,
        'supervisor_ids' => [$this->supervisor1->id, $this->supervisor2->id],
        'priority' => 'HIGH',
        'status' => 'TODO',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('tasks', [
        'project_id' => $this->project->id,
        'title' => 'طراحی نقشه سازه',
        'executor_user_id' => $this->executor->id,
        'priority' => 'HIGH',
        'status' => 'TODO',
    ]);

    $task = Task::where('title', 'طراحی نقشه سازه')->first();
    expect($task->taskSupervisors)->toHaveCount(2);
});

test('task requires all assigned supervisors approval to become completed', function () {
    $task = Task::create([
        'project_id' => $this->project->id,
        'title' => 'اجرای بتن‌ریزی فونداسیون',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::HIGH->value,
    ]);

    TaskSupervisor::create(['task_id' => $task->id, 'user_id' => $this->supervisor1->id, 'status' => 'PENDING']);
    TaskSupervisor::create(['task_id' => $task->id, 'user_id' => $this->supervisor2->id, 'status' => 'PENDING']);

    // Executor submits task for review
    $this->actingAs($this->executor)->post(route('projects.tasks.update-status', [$this->project, $task]), [
        'status' => 'PENDING_REVIEW',
    ]);

    expect($task->fresh()->status)->toBe(TaskStatus::PENDING_REVIEW);

    // Supervisor 1 approves
    $this->actingAs($this->supervisor1)->post(route('projects.tasks.review', [$this->project, $task]), [
        'action' => 'APPROVE',
        'comments' => 'تایید اولیه کیفیت بتن',
    ]);

    // Should stay PENDING_REVIEW because supervisor2 hasn't approved yet
    expect($task->fresh()->status)->toBe(TaskStatus::PENDING_REVIEW);

    // Supervisor 2 approves
    $this->actingAs($this->supervisor2)->post(route('projects.tasks.review', [$this->project, $task]), [
        'action' => 'APPROVE',
        'comments' => 'تایید نهایی آزمایشگاه',
    ]);

    // Now task becomes COMPLETED
    expect($task->fresh()->status)->toBe(TaskStatus::COMPLETED);
    expect($task->fresh()->completed_at)->not()->toBeNull();
});

test('rejection by any supervisor sets task to NEEDS_REVISION', function () {
    $task = Task::create([
        'project_id' => $this->project->id,
        'title' => 'نصب اسکلت فلزی',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::PENDING_REVIEW->value,
    ]);

    TaskSupervisor::create(['task_id' => $task->id, 'user_id' => $this->supervisor1->id, 'status' => 'PENDING']);

    $this->actingAs($this->supervisor1)->post(route('projects.tasks.review', [$this->project, $task]), [
        'action' => 'REJECT',
        'comments' => 'عدم رعایت جوشکاری استاندار',
    ]);

    expect($task->fresh()->status)->toBe(TaskStatus::NEEDS_REVISION);
});

test('task with zero supervisors completes directly when executor finishes', function () {
    $task = Task::create([
        'project_id' => $this->project->id,
        'title' => 'خرید ملزومات اداری',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
    ]);

    $this->actingAs($this->executor)->post(route('projects.tasks.update-status', [$this->project, $task]), [
        'status' => 'COMPLETED',
    ]);

    expect($task->fresh()->status)->toBe(TaskStatus::COMPLETED);
});

test('project progress percentage is calculated accurately based on tasks', function () {
    expect($this->project->tasks_progress)->toBe(0);

    Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک ۱',
        'created_by' => $this->user->id,
        'status' => TaskStatus::COMPLETED->value,
    ]);

    Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک ۲',
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
    ]);

    // 1 completed out of 2 = 50%
    expect($this->project->fresh()->tasks_progress)->toBe(50);
});

test('can send chat messages and upload task attachments', function () {
    Storage::fake('public');

    $task = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک تست چت و پیوست',
        'created_by' => $this->user->id,
    ]);

    // Chat
    $this->actingAs($this->executor)->post(route('projects.tasks.chats.store', [$this->project, $task]), [
        'message' => 'سلام، نمونه کار آماده گردید.',
    ]);

    expect($task->fresh()->chats)->toHaveCount(1);
    expect($task->fresh()->chats->first()->message)->toBe('سلام، نمونه کار آماده گردید.');

    // Attachment
    $file = UploadedFile::fake()->create('report.pdf', 100);

    $this->actingAs($this->executor)->post(route('projects.tasks.attachments.store', [$this->project, $task]), [
        'file' => $file,
    ]);

    expect($task->fresh()->attachments)->toHaveCount(1);
    expect($task->fresh()->attachments->first()->original_name)->toBe('report.pdf');
});

test('project show page with tasks tab renders successfully', function () {
    $response = $this->actingAs($this->user)->get(route('projects.show', ['project' => $this->project, 'tab' => 'tasks']));

    $response->assertOk();
    $response->assertSee('فعالیت‌های مرتبط با');
});

test('authenticated manager can create a task mapped to project approval stages with Shamsi due date', function () {
    $stage1 = ProjectApprovalStage::create([
        'project_id' => $this->project->id,
        'stage_number' => 1,
        'stage_name' => 'تایید سرپرست',
        'supervisor_user_id' => $this->supervisor1->id,
    ]);

    $stage2 = ProjectApprovalStage::create([
        'project_id' => $this->project->id,
        'stage_number' => 2,
        'stage_name' => 'تایید واحد فروش',
        'supervisor_user_id' => $this->supervisor2->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('projects.tasks.store', $this->project), [
        'title' => 'اجرای لوله‌کشی و زیرساخت تاسیسات',
        'executor_user_id' => $this->executor->id,
        'due_date' => '۱۴۰۵/۰۷/۰۱',
        'priority' => 'HIGH',
        'status' => 'TODO',
        'stage_supervisors' => [
            $stage1->id => [
                'user_id' => $this->supervisor1->id,
                'stage_number' => 1,
                'stage_name' => 'تایید سرپرست',
            ],
            $stage2->id => [
                'user_id' => $this->supervisor2->id,
                'stage_number' => 2,
                'stage_name' => 'تایید واحد فروش',
            ],
        ],
    ]);

    $response->assertRedirect();

    $task = Task::where('title', 'اجرای لوله‌کشی و زیرساخت تاسیسات')->first();
    expect($task)->not()->toBeNull();
    expect($task->due_date->format('Y-m-d'))->toBe('2026-09-23');
    expect($task->taskSupervisors)->toHaveCount(2);

    $sup1 = $task->taskSupervisors->where('stage_number', 1)->first();
    expect($sup1->stage_name)->toBe('تایید سرپرست');
    expect($sup1->user_id)->toBe($this->supervisor1->id);
});

test('parent project lists both main project tasks and sub-project tasks with group badges', function () {
    $subProject = Project::create([
        'parent_project_id' => $this->project->id,
        'organization_node_id' => $this->orgNode->id,
        'title' => 'زیرپروژه فاز اول ساخت',
        'project_key' => 'SUB-101',
        'created_by' => $this->user->id,
    ]);

    $mainTask = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک مربوط به پروژه مادر',
        'created_by' => $this->user->id,
        'priority' => 'HIGH',
        'status' => 'TODO',
    ]);

    $subTask = Task::create([
        'project_id' => $subProject->id,
        'title' => 'تسک مربوط به زیرپروژه فاز یک',
        'created_by' => $this->user->id,
        'priority' => 'MEDIUM',
        'status' => 'IN_PROGRESS',
    ]);

    $response = $this->actingAs($this->user)->get(route('projects.show', ['project' => $this->project, 'tab' => 'tasks']));

    $response->assertOk();
    $response->assertSee('تسک مربوط به پروژه مادر');
    $response->assertSee('تسک مربوط به زیرپروژه فاز یک');
    $response->assertSee('زیرپروژه فاز اول ساخت');
});

test('super admin can approve a pending stage on behalf of another supervisor', function () {
    $task = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک نیازمند تایید ناظر غیرهم‌نام',
        'created_by' => $this->user->id,
        'executor_user_id' => $this->executor->id,
        'priority' => 'HIGH',
        'status' => 'PENDING_REVIEW',
    ]);

    $taskSup = TaskSupervisor::create([
        'task_id' => $task->id,
        'stage_number' => 1,
        'stage_name' => 'تایید سرپرست واحد',
        'user_id' => $this->supervisor1->id,
        'status' => 'PENDING',
    ]);

    // $this->user is super admin (different from $this->supervisor1)
    $response = $this->actingAs($this->user)->post(route('projects.tasks.review', [$this->project, $task]), [
        'action' => 'APPROVE',
        'comments' => 'تایید شد توسط مدیر ارشد',
    ]);

    $response->assertRedirect();
    expect($taskSup->fresh()->status)->toBe('APPROVED');
    expect($task->fresh()->status->value)->toBe('COMPLETED');
});

test('creating task defaults version to null and edit page displays shamsi dates', function () {
    $response = $this->actingAs($this->user)->post(route('projects.tasks.store', $this->project), [
        'title' => 'تسک تست بدون ورژن',
        'priority' => 'MEDIUM',
        'status' => 'TODO',
        'start_date' => '1405/06/01',
        'due_date' => '1405/06/15',
    ]);

    $response->assertRedirect();
    $task = Task::where('title', 'تسک تست بدون ورژن')->first();
    expect($task)->not->toBeNull();
    expect($task->version)->toBeNull();

    $editResponse = $this->actingAs($this->user)->get(route('projects.tasks.edit', [$this->project, $task]));
    $editResponse->assertOk();
    $editResponse->assertSee('1405/06/01');
    $editResponse->assertSee('1405/06/15');

    $showResponse = $this->actingAs($this->user)->get(route('projects.tasks.show', [$this->project, $task]));
    $showResponse->assertOk();
    $showResponse->assertSee(route('projects.tasks.edit', [$this->project, $task]));
});

test('parent task cannot be completed directly when it has unresolved subtasks', function () {
    $parentTask = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک والد احداث فونداسیون',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::HIGH->value,
    ]);

    $subTask1 = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $parentTask->id,
        'title' => 'زیرتسک خاک‌برداری',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    expect($parentTask->hasUnresolvedSubtasks())->toBeTrue();
    expect($parentTask->getUnresolvedSubtasks())->toHaveCount(1);

    // Attempting to complete the parent task directly should fail and redirect back with error
    $response = $this->actingAs($this->executor)->post(route('projects.tasks.update-status', [$this->project, $parentTask]), [
        'status' => 'COMPLETED',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $response->assertSessionHasErrors(['status']);
    expect($parentTask->fresh()->status)->toBe(TaskStatus::IN_PROGRESS);

    // Also testing JSON / AJAX request returns 422 JSON
    $ajaxResponse = $this->actingAs($this->executor)->postJson(route('projects.tasks.update-status', [$this->project, $parentTask]), [
        'status' => 'COMPLETED',
    ]);
    $ajaxResponse->assertStatus(422);
    $ajaxResponse->assertJsonValidationErrors(['status']);
});

test('parent task can be completed when all subtasks are either completed, cancelled, or deleted', function () {
    $parentTask = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک والد اجرای تاسیسات',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::HIGH->value,
    ]);

    $subTask1 = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $parentTask->id,
        'title' => 'زیرتسک لوله‌کشی',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::COMPLETED->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    $subTask2 = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $parentTask->id,
        'title' => 'زیرتسک سیم‌کشی برق اضطراری (لغو شده)',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::CANCELLED->value,
        'priority' => TaskPriority::LOW->value,
    ]);

    $subTask3 = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $parentTask->id,
        'title' => 'زیرتسک تست فشار (حذف شده)',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);
    $subTask3->delete(); // Soft deleted

    expect($parentTask->hasUnresolvedSubtasks())->toBeFalse();
    expect($parentTask->getUnresolvedSubtasks())->toHaveCount(0);

    // Now parent task can be completed
    $response = $this->actingAs($this->executor)->post(route('projects.tasks.update-status', [$this->project, $parentTask]), [
        'status' => 'COMPLETED',
    ]);

    $response->assertRedirect();
    expect($parentTask->fresh()->status)->toBe(TaskStatus::COMPLETED);
    expect($parentTask->fresh()->completed_at)->not->toBeNull();
});

test('supervisors cannot finalize parent task via review when subtasks are unresolved', function () {
    $parentTask = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک والد نظارتی',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::PENDING_REVIEW->value,
        'priority' => TaskPriority::HIGH->value,
    ]);

    TaskSupervisor::create([
        'task_id' => $parentTask->id,
        'user_id' => $this->supervisor1->id,
        'status' => 'PENDING',
        'stage_number' => 1,
        'stage_name' => 'مرحله نظارت',
    ]);

    $subTask = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $parentTask->id,
        'title' => 'زیرتسک ناتمام',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    // Supervisor attempts to approve parent task
    $response = $this->actingAs($this->supervisor1)->post(route('projects.tasks.review', [$this->project, $parentTask]), [
        'action' => 'APPROVE',
        'comments' => 'تایید مشروط',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $response->assertSessionHasErrors(['review']);
    expect($parentTask->fresh()->status)->toBe(TaskStatus::PENDING_REVIEW);
});

test('nested descendant subtask prevents grandparent task from completion', function () {
    $grandParent = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک پدربزرگ',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::HIGH->value,
    ]);

    $parent = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $grandParent->id,
        'title' => 'تسک والد میانی',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::COMPLETED->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    $child = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $parent->id,
        'title' => 'زیرتسک نوه (ناتمام)',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::LOW->value,
    ]);

    expect($grandParent->hasUnresolvedSubtasks())->toBeTrue();

    // Trying to complete grandParent fails
    $response = $this->actingAs($this->executor)->post(route('projects.tasks.update-status', [$this->project, $grandParent]), [
        'status' => 'COMPLETED',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $response->assertSessionHasErrors(['status']);

    // Mark child as cancelled
    $child->update(['status' => TaskStatus::CANCELLED->value]);

    expect($grandParent->hasUnresolvedSubtasks())->toBeFalse();

    // Now completes successfully
    $response = $this->actingAs($this->executor)->post(route('projects.tasks.update-status', [$this->project, $grandParent]), [
        'status' => 'COMPLETED',
    ]);
    $response->assertRedirect();
    expect($grandParent->fresh()->status)->toBe(TaskStatus::COMPLETED);
});

test('task with zero supervisors cannot be completed via pending review or direct completion when subtasks are unresolved', function () {
    $parentTask = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک والد بدون ناظر',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::HIGH->value,
    ]);

    // Subtask is still in progress
    $subTask = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $parentTask->id,
        'title' => 'زیرتسک باز',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    // Attempting PENDING_REVIEW on parent task without supervisors should still be blocked because it attempts completion
    $response1 = $this->actingAs($this->executor)->post(route('projects.tasks.update-status', [$this->project, $parentTask]), [
        'status' => 'PENDING_REVIEW',
    ]);
    $response1->assertRedirect();
    $response1->assertSessionHas('error');
    $response1->assertSessionHasErrors(['status']);
    expect($parentTask->fresh()->status)->toBe(TaskStatus::IN_PROGRESS);

    // Attempting COMPLETED directly on parent task should also fail
    $response2 = $this->actingAs($this->executor)->post(route('projects.tasks.update-status', [$this->project, $parentTask]), [
        'status' => 'COMPLETED',
    ]);
    $response2->assertRedirect();
    $response2->assertSessionHas('error');
    $response2->assertSessionHasErrors(['status']);
    expect($parentTask->fresh()->status)->toBe(TaskStatus::IN_PROGRESS);

    // Once the subtask is completed
    $subTask->update(['status' => TaskStatus::COMPLETED->value]);

    // Now parent task without supervisors completes successfully
    $response3 = $this->actingAs($this->executor)->post(route('projects.tasks.update-status', [$this->project, $parentTask]), [
        'status' => 'COMPLETED',
    ]);
    $response3->assertRedirect();
    expect($parentTask->fresh()->status)->toBe(TaskStatus::COMPLETED);
    expect($parentTask->fresh()->completed_at)->not->toBeNull();
});

test('task status can be updated via task edit form and respects unresolved subtasks', function () {
    $parentTask = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک جهت تست ویرایش وضعیت',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::HIGH->value,
    ]);

    $subTask = Task::create([
        'project_id' => $this->project->id,
        'parent_id' => $parentTask->id,
        'title' => 'زیرتسک باز',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    // 1. Update status to IN_PROGRESS via edit form (should succeed)
    $response1 = $this->actingAs($this->user)->put(route('projects.tasks.update', [$this->project, $parentTask]), [
        'title' => 'تسک ویرایش شده',
        'priority' => 'HIGH',
        'status' => 'IN_PROGRESS',
    ]);
    $response1->assertRedirect();
    expect($parentTask->fresh()->status)->toBe(TaskStatus::IN_PROGRESS);

    // 2. Attempt update status to COMPLETED while subtask is open (should be blocked)
    $response2 = $this->actingAs($this->user)->put(route('projects.tasks.update', [$this->project, $parentTask]), [
        'title' => 'تسک ویرایش شده',
        'priority' => 'HIGH',
        'status' => 'COMPLETED',
    ]);
    $response2->assertRedirect();
    $response2->assertSessionHasErrors(['status']);
    expect($parentTask->fresh()->status)->toBe(TaskStatus::IN_PROGRESS);

    // 3. Mark subtask as completed and update status to COMPLETED (should succeed)
    $subTask->update(['status' => TaskStatus::COMPLETED->value]);

    $response3 = $this->actingAs($this->user)->put(route('projects.tasks.update', [$this->project, $parentTask]), [
        'title' => 'تسک ویرایش شده و تکمیل شده',
        'priority' => 'HIGH',
        'status' => 'COMPLETED',
    ]);
    $response3->assertRedirect();
    expect($parentTask->fresh()->status)->toBe(TaskStatus::COMPLETED);
    expect($parentTask->fresh()->completed_at)->not->toBeNull();
});

test('subproject task approval stages are dynamically detected and supervisors can be updated or cleared to zero', function () {
    // Create a subproject
    $subProject = Project::create([
        'title' => 'زیرپروژه مرحله ۲',
        'project_key' => 'SUBPRJ2',
        'organization_node_id' => $this->project->organization_node_id,
        'parent_project_id' => $this->project->id,
        'status' => 'ACTIVE',
        'created_by' => $this->user->id,
    ]);

    // Create 2 approval stages on subproject
    $stage1 = ProjectApprovalStage::create([
        'project_id' => $subProject->id,
        'stage_number' => 1,
        'stage_name' => 'تایید فنی زیرپروژه',
    ]);
    $stage2 = ProjectApprovalStage::create([
        'project_id' => $subProject->id,
        'stage_number' => 2,
        'stage_name' => 'تایید مالی زیرپروژه',
    ]);

    $task = Task::create([
        'project_id' => $subProject->id,
        'title' => 'تسک در زیرپروژه با مراحل تایید',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    // 1. Assign 2 supervisors to the task
    $response1 = $this->actingAs($this->user)->put(route('projects.tasks.update', [$this->project, $task]), [
        'title' => $task->title,
        'priority' => 'MEDIUM',
        'stage_supervisors' => [
            $stage1->id => ['stage_number' => 1, 'stage_name' => 'تایید فنی زیرپروژه', 'user_id' => $this->supervisor1->id],
            $stage2->id => ['stage_number' => 2, 'stage_name' => 'تایید مالی زیرپروژه', 'user_id' => $this->supervisor2->id],
        ],
    ]);
    $response1->assertRedirect();
    expect($task->fresh()->taskSupervisors()->count())->toBe(2);

    // 2. Reduce to 1 supervisor by clearing stage 2
    $response2 = $this->actingAs($this->user)->put(route('projects.tasks.update', [$this->project, $task]), [
        'title' => $task->title,
        'priority' => 'MEDIUM',
        'stage_supervisors' => [
            $stage1->id => ['stage_number' => 1, 'stage_name' => 'تایید فنی زیرپروژه', 'user_id' => $this->supervisor1->id],
            $stage2->id => ['stage_number' => 2, 'stage_name' => 'تایید مالی زیرپروژه', 'user_id' => ''],
        ],
    ]);
    $response2->assertRedirect();
    expect($task->fresh()->taskSupervisors()->count())->toBe(1);

    // 3. Reduce to zero supervisors
    $response3 = $this->actingAs($this->user)->put(route('projects.tasks.update', [$this->project, $task]), [
        'title' => $task->title,
        'priority' => 'MEDIUM',
        'stage_supervisors' => [
            $stage1->id => ['stage_number' => 1, 'stage_name' => 'تایید فنی زیرپروژه', 'user_id' => ''],
            $stage2->id => ['stage_number' => 2, 'stage_name' => 'تایید مالی زیرپروژه', 'user_id' => ''],
        ],
    ]);
    $response3->assertRedirect();
    expect($task->fresh()->taskSupervisors()->count())->toBe(0);
});

test('ajax search parents endpoint returns scoped tasks and excludes the edited task and circular loops', function () {
    // Create subproject
    $subProject = Project::create([
        'title' => 'زیرپروژه خاک‌برداری',
        'project_key' => 'SUB1',
        'organization_node_id' => $this->project->organization_node_id,
        'parent_project_id' => $this->project->id,
        'status' => 'ACTIVE',
        'created_by' => $this->user->id,
    ]);

    // Task in parent project
    $parentTask1 = Task::create([
        'project_id' => $this->project->id,
        'title' => 'احداث ساختمان اصلی',
        'version' => 'v1.0',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::IN_PROGRESS->value,
        'priority' => TaskPriority::HIGH->value,
    ]);

    // Task in subproject
    $subTask1 = Task::create([
        'project_id' => $subProject->id,
        'title' => 'عملیات خاک‌برداری عمیق',
        'version' => 'v1.1',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    // Task in completely unrelated project
    $otherProject = Project::create([
        'title' => 'پروژه کاملاً مجزا',
        'project_key' => 'OTHER',
        'organization_node_id' => $this->project->organization_node_id,
        'status' => 'ACTIVE',
        'created_by' => $this->user->id,
    ]);
    $unrelatedTask = Task::create([
        'project_id' => $otherProject->id,
        'title' => 'تسک در پروژه دیگر',
        'executor_user_id' => $this->executor->id,
        'created_by' => $this->user->id,
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::LOW->value,
    ]);

    // Search from parent project without query
    $response = $this->actingAs($this->user)->getJson(route('projects.tasks.search-parents', $this->project));
    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);

    $taskTitles = collect($response->json('tasks'))->pluck('title')->toArray();
    expect($taskTitles)->toContain('احداث ساختمان اصلی');
    expect($taskTitles)->toContain('عملیات خاک‌برداری عمیق');
    expect($taskTitles)->not->toContain('تسک در پروژه دیگر');

    // Search with query
    $responseQuery = $this->actingAs($this->user)->getJson(route('projects.tasks.search-parents', ['project' => $this->project, 'q' => 'خاک‌برداری']));
    $responseQuery->assertOk();
    $queriedTitles = collect($responseQuery->json('tasks'))->pluck('title')->toArray();
    expect($queriedTitles)->toContain('عملیات خاک‌برداری عمیق');
    expect($queriedTitles)->not->toContain('احداث ساختمان اصلی');

    // Exclude task
    $responseExclude = $this->actingAs($this->user)->getJson(route('projects.tasks.search-parents', ['project' => $this->project, 'exclude_task_id' => $parentTask1->id]));
    $responseExclude->assertOk();
    $excludedTitles = collect($responseExclude->json('tasks'))->pluck('title')->toArray();
    expect($excludedTitles)->not->toContain('احداث ساختمان اصلی');
    expect($excludedTitles)->toContain('عملیات خاک‌برداری عمیق');
});

test('vice president or unit manager can create, edit, show, and manage tasks on a project created by a subordinate', function () {
    // 1. Setup Organizational Hierarchy: Department -> Unit -> Team
    $deptNode = OrganizationNode::create([
        'name' => 'معاونت مهندسی و برنامه‌ریزی',
        'type' => 'DEPARTMENT',
        'status' => 'ACTIVE',
    ]);

    $unitNode = OrganizationNode::create([
        'parent_id' => $deptNode->id,
        'name' => 'واحد توسعه نرم‌افزار',
        'type' => 'UNIT',
        'status' => 'ACTIVE',
    ]);

    $teamNode = OrganizationNode::create([
        'parent_id' => $unitNode->id,
        'name' => 'تیم سامانه GIS',
        'type' => 'TEAM',
        'status' => 'ACTIVE',
    ]);

    // 2. Vice President (معاونت)
    $vpUser = User::factory()->create(['first_name' => 'معاون', 'last_name' => 'مهندسی']);
    UserRoleAssignment::create([
        'user_id' => $vpUser->id,
        'role' => UserRole::ORGANIZATION_MANAGER->value,
        'organization_node_id' => $deptNode->id,
        'is_primary' => true,
        'status' => 'ACTIVE',
    ]);

    // 3. Subordinate Team Lead (سرپرست زیردست)
    $subordinateLead = User::factory()->create(['first_name' => 'سرپرست', 'last_name' => 'زیردست']);
    UserRoleAssignment::create([
        'user_id' => $subordinateLead->id,
        'role' => UserRole::TEAM_LEAD->value,
        'organization_node_id' => $teamNode->id,
        'is_primary' => true,
        'status' => 'ACTIVE',
    ]);

    // 4. Subordinate creates a project in their team node
    $project = Project::create([
        'organization_node_id' => $teamNode->id,
        'title' => 'پروژه پیاده‌سازی ماژول GIS زیردست',
        'project_key' => 'SUB-GIS-01',
        'created_by' => $subordinateLead->id,
        'status' => 'ACTIVE',
    ]);

    ProjectMember::create([
        'project_id' => $project->id,
        'user_id' => $subordinateLead->id,
        'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
        'status' => ProjectMemberStatus::ACCEPTED->value,
        'joined_at' => now(),
    ]);

    // 5. Vice President creates a task on the subordinate's project and assigns the subordinate as executor
    $createResponse = $this->actingAs($vpUser)->post(route('projects.tasks.store', $project), [
        'title' => 'تحلیل و پیاده‌سازی لایه‌های برداری',
        'description' => 'تسک تعریف‌شده توسط معاونت برای سرپرست زیردست',
        'executor_user_id' => $subordinateLead->id,
        'priority' => 'HIGH',
        'status' => 'TODO',
    ]);

    $createResponse->assertRedirect();
    $this->assertDatabaseHas('tasks', [
        'project_id' => $project->id,
        'title' => 'تحلیل و پیاده‌سازی لایه‌های برداری',
        'executor_user_id' => $subordinateLead->id,
        'priority' => 'HIGH',
    ]);

    $task = Task::where('title', 'تحلیل و پیاده‌سازی لایه‌های برداری')->first();
    expect($task)->not->toBeNull();

    // 6. Vice President views task details page
    $showResponse = $this->actingAs($vpUser)->get(route('projects.tasks.show', [$project, $task]));
    $showResponse->assertOk();
    $showResponse->assertSee('تحلیل و پیاده‌سازی لایه‌های برداری');

    // 7. Vice President edits the task
    $editResponse = $this->actingAs($vpUser)->put(route('projects.tasks.update', [$project, $task]), [
        'title' => 'تحلیل و پیاده‌سازی لایه‌های برداری - ویرایش‌شده',
        'description' => 'توضیحات تکمیلی توسط معاونت',
        'executor_user_id' => $subordinateLead->id,
        'priority' => 'URGENT',
        'status' => 'IN_PROGRESS',
    ]);

    $editResponse->assertRedirect();
    expect($task->fresh()->title)->toBe('تحلیل و پیاده‌سازی لایه‌های برداری - ویرایش‌شده');
    expect($task->fresh()->priority->value)->toBe('URGENT');
    expect($task->fresh()->status->value)->toBe('IN_PROGRESS');

    // 8. Vice President updates task status
    $statusResponse = $this->actingAs($vpUser)->post(route('projects.tasks.update-status', [$project, $task]), [
        'status' => 'COMPLETED',
        'comments' => 'تکمیل شد توسط معاونت',
    ]);

    $statusResponse->assertRedirect();
    expect($task->fresh()->status->value)->toBe('COMPLETED');
});
