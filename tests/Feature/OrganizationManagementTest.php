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

class OrganizationManagementTest extends TestCase
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

    public function test_super_admin_can_view_organization_structure(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->get('/organization');
        $response->assertStatus(200);
        $response->assertSee('مدیریت ساختار سازمانی');
    }

    public function test_non_super_admin_can_view_but_cannot_create_organization_node(): void
    {
        $regularUser = User::create([
            'first_name' => 'کاربر',
            'last_name' => 'عادی',
            'username' => 'regular',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($regularUser)->get('/organization');
        $response->assertStatus(200);

        $createResponse = $this->actingAs($regularUser)->post('/organization', [
            'name' => 'واحد غیرمجاز',
            'type' => OrganizationNodeType::UNIT->value,
        ]);
        $createResponse->assertStatus(403);
    }

    public function test_super_admin_can_create_organization_node(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->post('/organization', [
            'name' => 'معاونت جدید',
            'type' => OrganizationNodeType::DEPARTMENT->value,
        ]);

        $response->assertRedirect('/organization');
        $this->assertDatabaseHas('organization_nodes', [
            'name' => 'معاونت جدید',
            'type' => OrganizationNodeType::DEPARTMENT->value,
        ]);
    }

    public function test_super_admin_can_update_organization_node(): void
    {
        $admin = $this->createSuperAdmin();

        $node = OrganizationNode::create([
            'name' => 'واحد اولیه',
            'type' => OrganizationNodeType::UNIT->value,
        ]);

        $response = $this->actingAs($admin)->put("/organization/{$node->id}", [
            'name' => 'واحد بهینه‌سازی و هوشمندسازی',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'description' => 'توضیحات به‌روزرسانی شده',
            'sort_order' => 5,
        ]);

        $response->assertRedirect('/organization');
        $this->assertDatabaseHas('organization_nodes', [
            'id' => $node->id,
            'name' => 'واحد بهینه‌سازی و هوشمندسازی',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'description' => 'توضیحات به‌روزرسانی شده',
            'sort_order' => 5,
        ]);
    }

    public function test_super_admin_can_delete_organization_node(): void
    {
        $admin = $this->createSuperAdmin();

        $node = OrganizationNode::create([
            'name' => 'واحد آزمایشی',
            'type' => OrganizationNodeType::UNIT->value,
        ]);

        $response = $this->actingAs($admin)->delete("/organization/{$node->id}");

        $response->assertRedirect('/organization');
        $this->assertSoftDeleted('organization_nodes', [
            'id' => $node->id,
        ]);
    }

    public function test_level_4_user_can_view_full_hierarchy_up_to_level_1(): void
    {
        $rootOrg = OrganizationNode::create([
            'name' => 'شرکت توزیع نیروی برق',
            'type' => OrganizationNodeType::ORGANIZATION->value,
        ]);

        $deputy = OrganizationNode::create([
            'name' => 'معاونت بهره‌برداری و دیسپاچینگ',
            'type' => OrganizationNodeType::DEPARTMENT->value,
            'parent_id' => $rootOrg->id,
        ]);

        $unit = OrganizationNode::create([
            'name' => 'واحد دیسپاچینگ و فوریت‌ها',
            'type' => OrganizationNodeType::UNIT->value,
            'parent_id' => $deputy->id,
        ]);

        $team = OrganizationNode::create([
            'name' => 'تیم توسعه سامانه GIS و مدیریت پروژه‌ها',
            'type' => OrganizationNodeType::TEAM->value,
            'parent_id' => $unit->id,
        ]);

        $expert = User::create([
            'first_name' => 'کارشناس',
            'last_name' => 'سامانه',
            'username' => 'gis_expert',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $expert->id,
            'role' => UserRole::EXPERT->value,
            'organization_node_id' => $team->id,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($expert)->get('/organization');
        $response->assertStatus(200);

        // Assert all 4 levels are rendered in the response for full visibility
        $response->assertSee('شرکت توزیع نیروی برق');
        $response->assertSee('معاونت بهره‌برداری و دیسپاچینگ');
        $response->assertSee('واحد دیسپاچینگ و فوریت‌ها');
        $response->assertSee('تیم توسعه سامانه GIS و مدیریت پروژه‌ها');
    }
}
