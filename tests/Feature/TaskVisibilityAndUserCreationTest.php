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
use App\Models\Task;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Database\Seeders\ProjectPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProjectPermissionSeeder::class);

    $this->rootOrg = OrganizationNode::create([
        'name' => 'ستاد مرکزی',
        'type' => OrganizationNodeType::ORGANIZATION->value,
    ]);

    $this->ictDept = OrganizationNode::create([
        'parent_id' => $this->rootOrg->id,
        'name' => 'معاونت فناوری اطلاعات',
        'type' => OrganizationNodeType::DEPARTMENT->value,
    ]);

    $this->softwareUnit = OrganizationNode::create([
        'parent_id' => $this->ictDept->id,
        'name' => 'واحد توسعه نرم‌افزار',
        'type' => OrganizationNodeType::UNIT->value,
    ]);

    // Super Admin
    $this->superAdmin = User::create([
        'first_name' => 'مدیر', 'last_name' => 'ارشد', 'username' => 'admin_'.uniqid(),
        'password_hash' => Hash::make('password123'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->superAdmin->id, 'role' => UserRole::SUPER_ADMIN->value,
        'is_primary' => true, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    // Org Manager
    $this->orgManager = User::create([
        'first_name' => 'معاونت', 'last_name' => 'فاوا', 'username' => 'ict_manager_'.uniqid(),
        'password_hash' => Hash::make('password123'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->orgManager->id, 'role' => UserRole::ORGANIZATION_MANAGER->value,
        'organization_node_id' => $this->ictDept->id, 'is_primary' => true, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    // Experts (Members)
    $this->expert1 = User::create([
        'first_name' => 'جواد', 'last_name' => 'سلیمانی', 'username' => 'soleimani_'.uniqid(),
        'password_hash' => Hash::make('password123'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->expert1->id, 'role' => UserRole::EXPERT->value,
        'organization_node_id' => $this->softwareUnit->id, 'is_primary' => true, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $this->expert2 = User::create([
        'first_name' => 'علی', 'last_name' => 'احمدی', 'username' => 'ahmadi_'.uniqid(),
        'password_hash' => Hash::make('password123'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->expert2->id, 'role' => UserRole::EXPERT->value,
        'organization_node_id' => $this->softwareUnit->id, 'is_primary' => true, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    // Project
    $this->project = Project::create([
        'organization_node_id' => $this->softwareUnit->id,
        'title' => 'پروژه اتوماسیون',
        'project_key' => 'AUTO-'.rand(100, 999),
        'status' => ProjectStatus::ACTIVE->value,
        'priority' => ProjectPriority::HIGH->value,
        'created_by' => $this->superAdmin->id,
    ]);

    ProjectMember::create([
        'project_id' => $this->project->id, 'user_id' => $this->expert1->id,
        'project_role' => ProjectMemberRole::MEMBER->value, 'status' => ProjectMemberStatus::ACCEPTED->value,
    ]);

    ProjectMember::create([
        'project_id' => $this->project->id, 'user_id' => $this->expert2->id,
        'project_role' => ProjectMemberRole::MEMBER->value, 'status' => ProjectMemberStatus::ACCEPTED->value,
    ]);
});

test('1. ORGANIZATION_MANAGER can access user management and create user', function () {
    $response = $this->actingAs($this->orgManager)->get('/users');
    $response->assertStatus(200);

    $createResponse = $this->actingAs($this->orgManager)->post('/users', [
        'first_name' => 'کارشناس',
        'last_name' => 'جدید',
        'username' => 'new_expert_'.uniqid(),
        'password' => 'password123',
        'status' => UserStatus::ACTIVE->value,
    ]);

    $createResponse->assertRedirect('/users');
});

test('2. Regular expert only sees tasks assigned to them', function () {
    $taskForExpert1 = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک جواد سلیمانی',
        'executor_user_id' => $this->expert1->id,
        'created_by' => $this->superAdmin->id,
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    $taskForExpert2 = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک علی احمدی',
        'executor_user_id' => $this->expert2->id,
        'created_by' => $this->superAdmin->id,
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    // expert1 views tasks tab of project
    $response = $this->actingAs($this->expert1)->get("/projects/{$this->project->id}?tab=tasks");
    $response->assertStatus(200);
    $response->assertSee('تسک جواد سلیمانی');
    $response->assertDontSee('تسک علی احمدی');

    // expert1 tries to access taskForExpert2 detail view directly -> 403
    $detailResponse = $this->actingAs($this->expert1)->get("/projects/{$this->project->id}/tasks/{$taskForExpert2->id}");
    $detailResponse->assertStatus(403);
});

test('3. Regular expert cannot edit task or access edit endpoint', function () {
    $task = Task::create([
        'project_id' => $this->project->id,
        'title' => 'تسک تست',
        'executor_user_id' => $this->expert1->id,
        'created_by' => $this->superAdmin->id,
        'status' => TaskStatus::TODO->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    // Access edit page -> 403
    $editResponse = $this->actingAs($this->expert1)->get("/projects/{$this->project->id}/tasks/{$task->id}/edit");
    $editResponse->assertStatus(403);

    // Submit update request -> 403
    $updateResponse = $this->actingAs($this->expert1)->put("/projects/{$this->project->id}/tasks/{$task->id}", [
        'title' => 'تسک دستکاری شده',
        'priority' => TaskPriority::HIGH->value,
    ]);
    $updateResponse->assertStatus(403);
});
