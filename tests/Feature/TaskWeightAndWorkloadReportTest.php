<?php

use App\Enums\OrganizationNodeType;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\RoleAssignmentStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use App\Models\Task;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\OrganizationScopeService;
use App\Services\ProjectTemplateService;
use App\Services\TaskService;
use Database\Seeders\ProjectPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProjectPermissionSeeder::class);

    $this->node = OrganizationNode::create([
        'name' => 'واحد فنی و مهندسی',
        'type' => OrganizationNodeType::DEPARTMENT->value,
    ]);

    $this->user = User::create([
        'username' => 'testuser',
        'first_name' => 'علی',
        'last_name' => 'محمدی',
        'status' => UserStatus::ACTIVE->value,
        'password_hash' => bcrypt('password'),
    ]);

    $this->assignment = UserRoleAssignment::create([
        'user_id' => $this->user->id,
        'organization_node_id' => $this->node->id,
        'role' => UserRole::SUPER_ADMIN->value,
        'status' => RoleAssignmentStatus::ACTIVE->value,
        'is_primary' => true,
    ]);
});

test('task can be created and updated with custom weight', function () {
    $project = Project::create([
        'title' => 'پروژه تست وزن',
        'project_key' => 'PRJ-WGT',
        'organization_node_id' => $this->node->id,
        'status' => ProjectStatus::ACTIVE->value,
        'priority' => ProjectPriority::HIGH->value,
        'created_by' => $this->user->id,
    ]);

    ProjectMember::create([
        'project_id' => $project->id,
        'user_id' => $this->user->id,
        'project_role' => ProjectMemberRole::MEMBER->value,
        'status' => ProjectMemberStatus::ACCEPTED->value,
    ]);

    $taskService = app(TaskService::class);

    $task = $taskService->createTask($this->user, $project, [
        'title' => 'وظیفه مهم وزنی',
        'priority' => TaskPriority::HIGH->value,
        'weight' => 5,
        'status' => TaskStatus::TODO->value,
        'executor_user_id' => $this->user->id,
    ]);

    expect($task->weight)->toBe(5);

    $updated = $taskService->updateTask($this->user, $task, [
        'weight' => 8,
    ]);

    expect($updated->fresh()->weight)->toBe(8);
});

test('saving project to template calculates task validity duration from creation to deadline and preserves weight', function () {
    $project = Project::create([
        'title' => 'پروژه منبع تمپلیت',
        'project_key' => 'SRC-TMP',
        'organization_node_id' => $this->node->id,
        'status' => ProjectStatus::ACTIVE->value,
        'priority' => ProjectPriority::MEDIUM->value,
        'start_date' => now()->startOfDay(),
        'deadline' => now()->addDays(30)->startOfDay(),
        'created_by' => $this->user->id,
    ]);

    // Task created with created_at = now() - 2 days, due_date = now() + 8 days
    $task = Task::create([
        'project_id' => $project->id,
        'title' => 'تسک مدت دار',
        'priority' => TaskPriority::HIGH->value,
        'status' => TaskStatus::TODO->value,
        'weight' => 4,
        'start_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(8)->format('Y-m-d'),
        'created_by' => $this->user->id,
    ]);
    $task->timestamps = false;
    $task->created_at = now()->subDays(2);
    $task->save();

    $templateService = app(ProjectTemplateService::class);
    $template = $templateService->saveProjectAsTemplate($this->user, $project, 'تمپلیت تست وزن و تاریخ');

    $templateTask = ProjectTemplateTask::where('project_template_id', $template->id)->first();

    expect($templateTask)->not->toBeNull();
    expect($templateTask->weight)->toBe(4);
    // Task created 2 days ago and deadline in 8 days => 10 days total validity duration
    expect($templateTask->duration_days)->toBe(10);
});

test('instantiating template to project retains task weight and calculates deadlines from instantiation date', function () {
    $template = ProjectTemplate::create([
        'title' => 'تمپلیت آماده',
        'duration_days' => 14,
        'priority' => ProjectPriority::MEDIUM->value,
        'organization_node_id' => $this->node->id,
        'created_by' => $this->user->id,
    ]);

    $tTask = ProjectTemplateTask::create([
        'project_template_id' => $template->id,
        'title' => 'تسک تمپلیت وزن‌دار',
        'priority' => TaskPriority::MEDIUM->value,
        'weight' => 6,
        'offset_days' => 2,
        'duration_days' => 9,
    ]);

    $templateService = app(ProjectTemplateService::class);
    $newProject = $templateService->createProjectFromTemplate($this->user, $template, $this->node->id);

    $newTask = Task::where('project_id', $newProject->id)->first();

    expect($newTask)->not->toBeNull();
    expect($newTask->weight)->toBe(6);
    expect($newTask->start_date->format('Y-m-d'))->toBe(now()->addDays(2)->format('Y-m-d'));
    expect($newTask->due_date->format('Y-m-d'))->toBe(now()->addDays(11)->format('Y-m-d'));
});

test('member workload report calculates total weight, active weight, and load status', function () {
    $project = Project::create([
        'title' => 'پروژه محاسبه بار',
        'project_key' => 'WLD-PRJ',
        'organization_node_id' => $this->node->id,
        'status' => ProjectStatus::ACTIVE->value,
        'priority' => ProjectPriority::MEDIUM->value,
        'created_by' => $this->user->id,
    ]);

    // Create 3 active tasks with total weight = 13 (overloaded > 10)
    Task::create([
        'project_id' => $project->id,
        'title' => 'تسک ۱',
        'priority' => TaskPriority::MEDIUM->value,
        'status' => TaskStatus::IN_PROGRESS->value,
        'executor_user_id' => $this->user->id,
        'weight' => 5,
        'created_by' => $this->user->id,
    ]);
    Task::create([
        'project_id' => $project->id,
        'title' => 'تسک ۲',
        'priority' => TaskPriority::MEDIUM->value,
        'status' => TaskStatus::TODO->value,
        'executor_user_id' => $this->user->id,
        'weight' => 4,
        'created_by' => $this->user->id,
    ]);
    Task::create([
        'project_id' => $project->id,
        'title' => 'تسک ۳',
        'priority' => TaskPriority::MEDIUM->value,
        'status' => TaskStatus::TODO->value,
        'executor_user_id' => $this->user->id,
        'weight' => 4,
        'created_by' => $this->user->id,
    ]);
    // Completed task should not count towards active weight
    Task::create([
        'project_id' => $project->id,
        'title' => 'تسک تکمیل شده',
        'priority' => TaskPriority::LOW->value,
        'status' => TaskStatus::COMPLETED->value,
        'executor_user_id' => $this->user->id,
        'weight' => 3,
        'created_by' => $this->user->id,
    ]);

    $scopeService = app(OrganizationScopeService::class);
    $report = $scopeService->getMemberWorkloadReport($this->user);

    $userStats = $report->firstWhere('user.id', $this->user->id);

    expect($userStats)->not->toBeNull();
    expect($userStats['active_tasks'])->toBe(3);
    expect($userStats['active_weight'])->toBe(13);
    expect($userStats['completed_weight'])->toBe(3);
    expect($userStats['total_weight'])->toBe(16);
    expect($userStats['load_status'])->toBe('overloaded');
});

test('project create view does not render template fast loader box', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('projects.create'));

    $response->assertOk();
    $response->assertDontSee('template_loader_select');
    $response->assertDontSee('ایجاد سریع پروژه بر اساس تمپلیت آماده');
});
