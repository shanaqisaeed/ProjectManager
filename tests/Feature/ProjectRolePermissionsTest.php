<?php

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPermission;
use App\Enums\RoleAssignmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::create([
        'first_name' => 'Super',
        'last_name' => 'Admin',
        'username' => 'superadmin_matrix',
        'password_hash' => bcrypt('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    UserRoleAssignment::create([
        'user_id' => $this->superAdmin->id,
        'role' => UserRole::SUPER_ADMIN->value,
        'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $this->regularUser = User::create([
        'first_name' => 'Regular',
        'last_name' => 'User',
        'username' => 'regularuser_matrix',
        'password_hash' => bcrypt('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);
});

test('super admin can view global project role permissions matrix page', function () {
    $response = $this->actingAs($this->superAdmin)->get('/project-role-permissions');

    $response->assertStatus(200);
    $response->assertSee('ماتریس دسترسی پیش‌فرض نقش‌های پروژه');
});

test('non super admin cannot view project role permissions matrix page', function () {
    $response = $this->actingAs($this->regularUser)->get('/project-role-permissions');

    $response->assertStatus(403);
});

test('super admin can update project role permissions matrix', function () {
    $matrix = [
        ProjectMemberRole::PROJECT_MANAGER->value => [
            ProjectPermission::VIEW_PROJECT->value => 1,
            ProjectPermission::UPDATE_PROJECT->value => 1,
            ProjectPermission::MANAGE_MEMBERS->value => 1,
        ],
        ProjectMemberRole::VIEWER->value => [
            ProjectPermission::VIEW_PROJECT->value => 1,
        ],
    ];

    $response = $this->actingAs($this->superAdmin)->put('/project-role-permissions', [
        'matrix' => $matrix,
    ]);

    $response->assertRedirect('/project-role-permissions');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('project_role_permissions', [
        'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
        'permission' => ProjectPermission::VIEW_PROJECT->value,
    ]);

    $this->assertDatabaseHas('project_role_permissions', [
        'project_role' => ProjectMemberRole::VIEWER->value,
        'permission' => ProjectPermission::VIEW_PROJECT->value,
    ]);

    $this->assertDatabaseMissing('project_role_permissions', [
        'project_role' => ProjectMemberRole::VIEWER->value,
        'permission' => ProjectPermission::DELETE_PROJECT->value,
    ]);
});
