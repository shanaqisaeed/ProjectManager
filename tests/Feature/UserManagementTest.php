<?php

namespace Tests\Feature;

use App\Enums\OrganizationNodeType;
use App\Enums\RoleAssignmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OrganizationNode;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        $admin = User::create([
            'first_name' => 'مدیر',
            'last_name' => 'ارشد',
            'username' => 'admin_'.uniqid(),
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $admin->id,
            'role' => UserRole::SUPER_ADMIN->value,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $admin->unsetRelations();
        $admin->refresh();

        return $admin;
    }

    public function test_super_admin_can_view_users_list(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->get('/users');
        $response->assertStatus(200);
        $response->assertSee('مدیریت کاربران');
    }

    public function test_non_super_admin_cannot_access_users_management(): void
    {
        $regularUser = User::create([
            'first_name' => 'کاربر',
            'last_name' => 'عادی',
            'username' => 'regular',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($regularUser)->get('/users');
        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_new_user(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->post('/users', [
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'username' => 'alireza',
            'password' => 'secret123',
            'email' => 'ali@example.com',
            'status' => UserStatus::ACTIVE->value,
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'alireza',
            'email' => 'ali@example.com',
        ]);
    }

    public function test_super_admin_can_assign_role_and_organization_node_to_user(): void
    {
        $admin = $this->createSuperAdmin();

        $node = OrganizationNode::create([
            'name' => 'تیم نرم‌افزار',
            'type' => OrganizationNodeType::TEAM->value,
        ]);

        $targetUser = User::create([
            'first_name' => 'سارا',
            'last_name' => 'احمدی',
            'username' => 'sara',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($admin)->post("/users/{$targetUser->id}/assign-role", [
            'role' => UserRole::TEAM_LEAD->value,
            'organization_node_id' => $node->id,
            'is_primary' => true,
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('user_role_assignments', [
            'user_id' => $targetUser->id,
            'role' => UserRole::TEAM_LEAD->value,
            'organization_node_id' => $node->id,
            'is_primary' => true,
        ]);
    }

    public function test_super_admin_deactivating_user_status_blocks_user_access(): void
    {
        $admin = $this->createSuperAdmin();

        $targetUser = User::create([
            'first_name' => 'رضا',
            'last_name' => 'مرادی',
            'username' => 'reza',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $this->actingAs($admin)->post("/users/{$targetUser->id}/status", [
            'status' => UserStatus::INACTIVE->value,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'status' => UserStatus::INACTIVE->value,
        ]);

        $targetUser->refresh();

        $response = $this->actingAs($targetUser)->get('/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_is_deactivated_when_all_roles_removed_and_reactivated_on_role_assignment(): void
    {
        $admin = $this->createSuperAdmin();

        $user = User::create([
            'first_name' => 'مهدی',
            'last_name' => 'کاظمی',
            'username' => 'mehdi',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::INACTIVE->value,
        ]);

        $assignment = UserRoleAssignment::create([
            'user_id' => $user->id,
            'role' => UserRole::EXPERT->value,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $user->update(['status' => UserStatus::ACTIVE->value]);

        $this->actingAs($admin)->delete("/users/role-assignment/{$assignment->id}");

        $user->refresh();
        $this->assertEquals(UserStatus::INACTIVE, $user->status);

        $this->actingAs($admin)->post("/users/{$user->id}/assign-role", [
            'role' => UserRole::EXPERT->value,
        ]);

        $user->refresh();
        $this->assertEquals(UserStatus::ACTIVE, $user->status);
    }

    public function test_user_management_filters_and_per_page_pagination(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->get('/users?per_page=50&role='.UserRole::SUPER_ADMIN->value);
        $response->assertStatus(200);
        $response->assertSee('تعداد در صفحه');
        $response->assertSee('(پیش‌فرض)');
    }

    public function test_unit_manager_only_sees_users_and_nodes_in_their_authorized_scope(): void
    {
        $deputyNodeA = OrganizationNode::create([
            'name' => 'معاونت فناوری',
            'type' => OrganizationNodeType::DEPARTMENT->value,
        ]);
        $subUnitA = OrganizationNode::create([
            'parent_id' => $deputyNodeA->id,
            'name' => 'واحد برنامه‌نویسی',
            'type' => OrganizationNodeType::UNIT->value,
        ]);
        $deputyNodeB = OrganizationNode::create([
            'name' => 'معاونت مالی',
            'type' => OrganizationNodeType::DEPARTMENT->value,
        ]);

        $managerA = User::create([
            'first_name' => 'مدیر',
            'last_name' => 'فناوری',
            'username' => 'manager_tech',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);
        UserRoleAssignment::create([
            'user_id' => $managerA->id,
            'role' => UserRole::UNIT_MANAGER->value,
            'organization_node_id' => $deputyNodeA->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $userTech = User::create([
            'first_name' => 'کارشناس',
            'last_name' => 'برنامه‌نویس',
            'username' => 'tech_expert',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);
        UserRoleAssignment::create([
            'user_id' => $userTech->id,
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $subUnitA->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $userFinance = User::create([
            'first_name' => 'کارشناس',
            'last_name' => 'حسابدار',
            'username' => 'finance_expert',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);
        UserRoleAssignment::create([
            'user_id' => $userFinance->id,
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $deputyNodeB->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($managerA)->get('/users');
        $response->assertStatus(200);
        $response->assertSee('tech_expert');
        $response->assertDontSee('finance_expert');
        $response->assertSee('معاونت فناوری');
        $response->assertSee('واحد برنامه‌نویسی');
        $response->assertDontSee('معاونت مالی');
    }

    public function test_unit_manager_cannot_assign_role_in_unauthorized_node(): void
    {
        $deputyNodeA = OrganizationNode::create([
            'name' => 'معاونت اول',
            'type' => OrganizationNodeType::DEPARTMENT->value,
        ]);
        $deputyNodeB = OrganizationNode::create([
            'name' => 'معاونت دوم',
            'type' => OrganizationNodeType::DEPARTMENT->value,
        ]);

        $managerA = User::create([
            'first_name' => 'مدیر',
            'last_name' => 'اول',
            'username' => 'manager_one',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);
        UserRoleAssignment::create([
            'user_id' => $managerA->id,
            'role' => UserRole::UNIT_MANAGER->value,
            'organization_node_id' => $deputyNodeA->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $targetUser = User::create([
            'first_name' => 'عضو',
            'last_name' => 'جدید',
            'username' => 'member_new',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);
        UserRoleAssignment::create([
            'user_id' => $targetUser->id,
            'role' => UserRole::MEMBER->value,
            'organization_node_id' => $deputyNodeA->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($managerA)->post("/users/{$targetUser->id}/assign-role", [
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $deputyNodeB->id,
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('user_role_assignments', [
            'user_id' => $targetUser->id,
            'organization_node_id' => $deputyNodeB->id,
        ]);
    }

    public function test_unit_manager_can_see_unassigned_users_and_assign_role_in_their_node(): void
    {
        $deputyNodeA = OrganizationNode::create([
            'name' => 'معاونت فنی و مهندسی',
            'type' => OrganizationNodeType::DEPARTMENT->value,
        ]);
        $deputyNodeB = OrganizationNode::create([
            'name' => 'معاونت حقوقی',
            'type' => OrganizationNodeType::DEPARTMENT->value,
        ]);

        $managerA = User::create([
            'first_name' => 'مدیر',
            'last_name' => 'فنی',
            'username' => 'manager_eng',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);
        UserRoleAssignment::create([
            'user_id' => $managerA->id,
            'role' => UserRole::UNIT_MANAGER->value,
            'organization_node_id' => $deputyNodeA->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $unassignedUser = User::create([
            'first_name' => 'کاربر',
            'last_name' => 'بی‌سمت',
            'username' => 'unassigned_person',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::INACTIVE->value,
        ]);

        $userOfOtherNode = User::create([
            'first_name' => 'کارشناس',
            'last_name' => 'حقوقی',
            'username' => 'law_expert',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);
        UserRoleAssignment::create([
            'user_id' => $userOfOtherNode->id,
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $deputyNodeB->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        // Manager A should see unassigned user, but NOT userOfOtherNode
        $response = $this->actingAs($managerA)->get('/users');
        $response->assertStatus(200);
        $response->assertSee('unassigned_person');
        $response->assertDontSee('law_expert');

        // Manager A can assign unassigned user to their node
        $assignResponse = $this->actingAs($managerA)->post("/users/{$unassignedUser->id}/assign-role", [
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $deputyNodeA->id,
            'is_primary' => true,
        ]);

        $assignResponse->assertRedirect('/users');
        $assignResponse->assertSessionHas('success');

        $this->assertDatabaseHas('user_role_assignments', [
            'user_id' => $unassignedUser->id,
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $deputyNodeA->id,
        ]);

        $unassignedUser->refresh();
        $this->assertEquals(UserStatus::ACTIVE, $unassignedUser->status);
    }

    public function test_filter_users_by_without_role(): void
    {
        $admin = $this->createSuperAdmin();

        $userWithRole = User::create([
            'first_name' => 'دارای',
            'last_name' => 'سمت',
            'username' => 'with_role_user',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);
        UserRoleAssignment::create([
            'user_id' => $userWithRole->id,
            'role' => UserRole::EXPERT->value,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $userWithoutRole = User::create([
            'first_name' => 'بدون',
            'last_name' => 'سمت',
            'username' => 'no_role_user',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::INACTIVE->value,
        ]);

        $response = $this->actingAs($admin)->get('/users?role=none');
        $response->assertStatus(200);
        $response->assertSee('no_role_user');
        $response->assertDontSee('with_role_user');
    }
}
