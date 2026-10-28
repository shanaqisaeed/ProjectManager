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
use App\Models\ProjectApprovalStage;
use App\Models\ProjectMember;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Models\TaskSupervisor;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Database\Seeders\ProjectPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProjectPermissionSeeder::class);

    $this->rootOrg = OrganizationNode::create([
        'name' => 'شرکت اصلی',
        'type' => OrganizationNodeType::ORGANIZATION->value,
    ]);

    $this->superAdmin = User::create([
        'username' => 'superadmin',
        'first_name' => 'مدیر',
        'last_name' => 'ارشد',
        'status' => UserStatus::ACTIVE->value,
        'password_hash' => bcrypt('password'),
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->superAdmin->id,
        'role' => UserRole::SUPER_ADMIN->value,
        'status' => RoleAssignmentStatus::ACTIVE->value,
        'is_primary' => true,
    ]);

    $this->projectManager = User::create([
        'username' => 'projmanager',
        'first_name' => 'علی',
        'last_name' => 'مدیری',
        'status' => UserStatus::ACTIVE->value,
        'password_hash' => bcrypt('password'),
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->projectManager->id,
        'role' => UserRole::UNIT_MANAGER->value,
        'organization_node_id' => $this->rootOrg->id,
        'status' => RoleAssignmentStatus::ACTIVE->value,
        'is_primary' => true,
    ]);

    $this->developer = User::create([
        'username' => 'developer',
        'first_name' => 'سارا',
        'last_name' => 'توسعه‌دهنده',
        'status' => UserStatus::ACTIVE->value,
        'password_hash' => bcrypt('password'),
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->developer->id,
        'role' => UserRole::MEMBER->value,
        'organization_node_id' => $this->rootOrg->id,
        'status' => RoleAssignmentStatus::ACTIVE->value,
        'is_primary' => true,
    ]);

    $this->supervisor = User::create([
        'username' => 'supervisor',
        'first_name' => 'رضا',
        'last_name' => 'ناظر',
        'status' => UserStatus::ACTIVE->value,
        'password_hash' => bcrypt('password'),
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->supervisor->id,
        'role' => UserRole::MEMBER->value,
        'organization_node_id' => $this->rootOrg->id,
        'status' => RoleAssignmentStatus::ACTIVE->value,
        'is_primary' => true,
    ]);
});

test('project manager can save existing project with subprojects and tasks as template', function () {
    // 1. Create a parent project
    $parentProject = Project::create([
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه پرتال سازمانی',
        'project_key' => 'PRJ-PARENT-01',
        'description' => 'توسعه پرتال جامع سازمانی',
        'priority' => ProjectPriority::HIGH->value,
        'status' => ProjectStatus::ACTIVE->value,
        'start_date' => now()->format('Y-m-d'),
        'deadline' => now()->addDays(60)->format('Y-m-d'),
        'created_by' => $this->projectManager->id,
    ]);

    ProjectMember::create([
        'project_id' => $parentProject->id,
        'user_id' => $this->projectManager->id,
        'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
        'status' => ProjectMemberStatus::ACCEPTED->value,
        'joined_at' => now(),
    ]);

    ProjectMember::create([
        'project_id' => $parentProject->id,
        'user_id' => $this->developer->id,
        'project_role' => ProjectMemberRole::MEMBER->value,
        'cooperation_title' => 'توسعه‌دهنده بک‌اند',
        'status' => ProjectMemberStatus::ACCEPTED->value,
        'joined_at' => now(),
    ]);

    ProjectApprovalStage::create([
        'project_id' => $parentProject->id,
        'stage_number' => 1,
        'stage_name' => 'تایید معماری فنی',
        'supervisor_user_id' => $this->supervisor->id,
        'status' => 'PENDING',
    ]);

    // Parent Task
    $parentTask = Task::create([
        'project_id' => $parentProject->id,
        'title' => 'طراحی پایگاه داده',
        'description' => 'طراحی مدل‌ها و جداول',
        'priority' => TaskPriority::HIGH->value,
        'status' => TaskStatus::IN_PROGRESS->value,
        'start_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(10)->format('Y-m-d'),
        'executor_user_id' => $this->developer->id,
        'created_by' => $this->projectManager->id,
    ]);

    // Subtask
    $subTask = Task::create([
        'project_id' => $parentProject->id,
        'parent_id' => $parentTask->id,
        'title' => 'مایگریشن‌های دیتابیس',
        'description' => 'نوشتن اسکریپت‌های مایگریشن',
        'priority' => TaskPriority::MEDIUM->value,
        'status' => TaskStatus::TODO->value,
        'start_date' => now()->addDays(2)->format('Y-m-d'),
        'due_date' => now()->addDays(8)->format('Y-m-d'),
        'executor_user_id' => $this->developer->id,
        'created_by' => $this->projectManager->id,
    ]);

    TaskSupervisor::create([
        'task_id' => $parentTask->id,
        'stage_number' => 1,
        'stage_name' => 'بررسی لاجیک دیتا',
        'user_id' => $this->supervisor->id,
        'status' => 'PENDING',
    ]);

    // 2. Create a Subproject
    $subProject = Project::create([
        'parent_project_id' => $parentProject->id,
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'زیرپروژه فرانت‌اند پرتال',
        'project_key' => 'PRJ-CHILD-01',
        'description' => 'طراحی و پیاده‌سازی رابط کاربری',
        'priority' => ProjectPriority::MEDIUM->value,
        'status' => ProjectStatus::ACTIVE->value,
        'start_date' => now()->addDays(5)->format('Y-m-d'),
        'deadline' => now()->addDays(50)->format('Y-m-d'),
        'created_by' => $this->projectManager->id,
    ]);

    Task::create([
        'project_id' => $subProject->id,
        'title' => 'طراحی کامپوننت‌های داشبورد',
        'description' => 'پیاده‌سازی با Tailwind و Blade',
        'priority' => TaskPriority::HIGH->value,
        'status' => TaskStatus::TODO->value,
        'start_date' => now()->addDays(6)->format('Y-m-d'),
        'due_date' => now()->addDays(20)->format('Y-m-d'),
        'executor_user_id' => $this->developer->id,
        'created_by' => $this->projectManager->id,
    ]);

    // Save as Template via POST request
    $response = $this->actingAs($this->projectManager)->post(route('projects.save-as-template', $parentProject), [
        'title' => 'الگوی استاندارد پرتال جامع',
        'description' => 'تمپلیت پرتال شامل فرانت‌اند و بک‌اند',
    ]);

    $response->assertRedirect(route('projects.show', [$parentProject, 'tab' => 'template']));
    $response->assertSessionHas('success');

    // Verify Template in DB
    $rootTemplate = ProjectTemplate::whereNull('parent_template_id')->first();
    expect($rootTemplate)->not->toBeNull();
    expect($rootTemplate->title)->toBe('الگوی استاندارد پرتال جامع');
    expect($rootTemplate->duration_days)->toBe(60);
    expect($rootTemplate->templateRoles)->toHaveCount(2);
    expect($rootTemplate->approvalStages)->toHaveCount(1);
    expect($rootTemplate->tasks)->toHaveCount(2);

    $rootTemplateTasks = $rootTemplate->rootTasks;
    expect($rootTemplateTasks)->toHaveCount(1);
    expect($rootTemplateTasks->first()->title)->toBe('طراحی پایگاه داده');
    expect($rootTemplateTasks->first()->subtasks)->toHaveCount(1);
    expect($rootTemplateTasks->first()->subtasks->first()->title)->toBe('مایگریشن‌های دیتابیس');

    // Verify Child Template
    expect($rootTemplate->children)->toHaveCount(1);
    $childTemplate = $rootTemplate->children->first();
    expect($childTemplate->title)->toBe('زیرپروژه فرانت‌اند پرتال');
    expect($childTemplate->tasks)->toHaveCount(1);
    expect($childTemplate->tasks->first()->title)->toBe('طراحی کامپوننت‌های داشبورد');
});

test('instant project instantiation from template creates complete project tree with tasks and dates', function () {
    // 1. Create a template structure directly
    $template = ProjectTemplate::create([
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'الگوی اتوماسیون اداری',
        'description' => 'سیستم اتوماسیون و گردش مکاتبات',
        'priority' => ProjectPriority::HIGH->value,
        'duration_days' => 45,
        'created_by' => $this->superAdmin->id,
    ]);

    $template->templateRoles()->create([
        'project_role' => ProjectMemberRole::MEMBER->value,
        'cooperation_title' => 'برنامه‌نویس ارشد',
        'user_id' => $this->developer->id,
    ]);

    $template->approvalStages()->create([
        'stage_number' => 1,
        'stage_name' => 'تایید مدیر ارشد',
        'supervisor_user_id' => $this->supervisor->id,
        'is_required' => true,
    ]);

    $rootTTask = $template->tasks()->create([
        'title' => 'تحلیل نیازمندی‌ها',
        'description' => 'برگزاری جلسات شناخت',
        'priority' => TaskPriority::HIGH->value,
        'offset_days' => 0,
        'duration_days' => 5,
        'executor_user_id' => $this->developer->id,
    ]);

    $childTTask = $template->tasks()->create([
        'parent_task_id' => $rootTTask->id,
        'title' => 'تهیه مستند SRS',
        'description' => 'مستندسازی فرآیندها',
        'priority' => TaskPriority::MEDIUM->value,
        'offset_days' => 2,
        'duration_days' => 3,
        'executor_user_id' => $this->developer->id,
    ]);

    // Sub-template
    $childTemplate = ProjectTemplate::create([
        'parent_template_id' => $template->id,
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'ماژول انبارداری اتوماسیون',
        'description' => 'مدیریت موجودی کالا',
        'priority' => ProjectPriority::MEDIUM->value,
        'offset_days' => 10,
        'duration_days' => 30,
        'created_by' => $this->superAdmin->id,
    ]);

    $childTemplate->tasks()->create([
        'title' => 'پیاده‌سازی انبارداری',
        'description' => 'کدنویسی فرم‌های انبار',
        'priority' => TaskPriority::MEDIUM->value,
        'offset_days' => 0,
        'duration_days' => 15,
        'executor_user_id' => $this->developer->id,
    ]);

    // 2. Perform Instant Instantiation via POST
    $response = $this->actingAs($this->projectManager)->post(route('project-templates.instantiate', $template));

    $newProject = Project::whereNull('parent_project_id')->latest('id')->first();
    expect($newProject)->not->toBeNull();
    expect($newProject->title)->toBe('الگوی اتوماسیون اداری');
    expect($newProject->status)->toBe(ProjectStatus::ACTIVE);
    expect($newProject->start_date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
    expect($newProject->deadline->format('Y-m-d'))->toBe(now()->addDays(45)->format('Y-m-d'));

    $response->assertRedirect(route('projects.show', $newProject));
    $response->assertSessionHas('success');

    // Check Members
    expect($newProject->members)->toHaveCount(2); // Project Manager + Developer
    expect($newProject->members->where('user_id', $this->projectManager->id)->first()->project_role)->toBe(ProjectMemberRole::PROJECT_MANAGER);

    // Check Stages
    expect($newProject->approvalStages)->toHaveCount(1);
    expect($newProject->approvalStages->first()->stage_name)->toBe('تایید مدیر ارشد');

    // Check Tasks
    $tasks = $newProject->tasks;
    expect($tasks)->toHaveCount(2);
    $parentCreatedTask = $tasks->whereNull('parent_id')->first();
    expect($parentCreatedTask->title)->toBe('تحلیل نیازمندی‌ها');
    expect($parentCreatedTask->status)->toBe(TaskStatus::TODO);
    expect($parentCreatedTask->due_date->format('Y-m-d'))->toBe(now()->addDays(5)->format('Y-m-d'));

    $subCreatedTask = $tasks->where('parent_id', $parentCreatedTask->id)->first();
    expect($subCreatedTask)->not->toBeNull();
    expect($subCreatedTask->title)->toBe('تهیه مستند SRS');
    expect($subCreatedTask->due_date->format('Y-m-d'))->toBe(now()->addDays(2 + 3)->format('Y-m-d'));

    // Check Subprojects
    expect($newProject->children)->toHaveCount(1);
    $createdSubproject = $newProject->children->first();
    expect($createdSubproject->title)->toBe('ماژول انبارداری اتوماسیون');
    expect($createdSubproject->tasks)->toHaveCount(1);
    expect($createdSubproject->tasks->first()->title)->toBe('پیاده‌سازی انبارداری');
});
