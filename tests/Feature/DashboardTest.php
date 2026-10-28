<?php

namespace Tests\Feature;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
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
use App\Models\TaskSupervisor;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser(string $role = UserRole::MEMBER->value, ?int $nodeId = null): User
    {
        $user = User::create([
            'first_name' => 'کاربر',
            'last_name' => 'تست '.uniqid(),
            'username' => 'user_'.uniqid(),
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        UserRoleAssignment::create([
            'user_id' => $user->id,
            'role' => $role,
            'organization_node_id' => $nodeId,
            'is_primary' => true,
            'status' => RoleAssignmentStatus::ACTIVE->value,
        ]);

        return $user;
    }

    private function createOrgNode(string $name = 'واحد فناوری اطلاعات'): OrganizationNode
    {
        return OrganizationNode::create([
            'name' => $name,
            'code' => 'ORG_'.uniqid(),
            'type' => 'DEPARTMENT',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard_with_metrics(): void
    {
        $node = $this->createOrgNode();
        $admin = $this->createTestUser(UserRole::SUPER_ADMIN->value, $node->id);

        $project = Project::create([
            'organization_node_id' => $node->id,
            'title' => 'پروژه مادر اصلی ۱',
            'project_key' => 'PRJ-MAIN-1',
            'status' => ProjectStatus::ACTIVE->value,
            'created_by' => $admin->id,
            'start_date' => now()->subDays(5),
            'deadline' => now()->addDays(20),
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'تسک تست برای کاربر',
            'executor_user_id' => $admin->id,
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::IN_PROGRESS->value,
            'due_date' => now()->addDays(3),
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('داشبورد مدیریتی و عملیاتی');
        $response->assertSee('پروژه مادر اصلی ۱');
        $response->assertSee('تسک تست برای کاربر');
        $response->assertSee('میز کار و اقدامات فوری من');
        $response->assertSee('قیف و وضعیت تسک‌ها');
    }

    public function test_dashboard_does_not_show_subprojects_in_main_projects(): void
    {
        $node = $this->createOrgNode();
        $admin = $this->createTestUser(UserRole::SUPER_ADMIN->value, $node->id);

        // Root project
        $rootProject = Project::create([
            'organization_node_id' => $node->id,
            'title' => 'پروژه ریشه ۱۰۰',
            'project_key' => 'PRJ-ROOT-100',
            'status' => ProjectStatus::ACTIVE->value,
            'created_by' => $admin->id,
        ]);

        // Child subproject
        $subProject = Project::create([
            'organization_node_id' => $node->id,
            'parent_project_id' => $rootProject->id,
            'title' => 'زیرپروژه فرعی پنهان',
            'project_key' => 'PRJ-SUB-999',
            'status' => ProjectStatus::ACTIVE->value,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('پروژه ریشه ۱۰۰');
        // Subproject title should NOT be present in main projects section
        $response->assertDontSee('زیرپروژه فرعی پنهان');
        $response->assertDontSee('↳ زیرپروژه');
    }

    public function test_dashboard_displays_user_assigned_tasks_and_approvals(): void
    {
        $node = $this->createOrgNode();
        $user = $this->createTestUser(UserRole::PROJECT_MANAGER->value, $node->id);
        $otherUser = $this->createTestUser(UserRole::MEMBER->value, $node->id);

        $project = Project::create([
            'organization_node_id' => $node->id,
            'title' => 'پروژه مدیریت وظایف',
            'project_key' => 'PRJ-MGT-1',
            'status' => ProjectStatus::ACTIVE->value,
            'created_by' => $user->id,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
            'status' => ProjectMemberStatus::ACCEPTED->value,
        ]);

        // Task assigned to user
        $myTask = Task::create([
            'project_id' => $project->id,
            'title' => 'وظیفه محوله به مدیر',
            'executor_user_id' => $user->id,
            'priority' => TaskPriority::URGENT->value,
            'status' => TaskStatus::TODO->value,
            'due_date' => now()->addDay(),
            'created_by' => $user->id,
        ]);

        // Task with supervisor review pending for user
        $reviewTask = Task::create([
            'project_id' => $project->id,
            'title' => 'وظیفه منتظر تایید ناظر',
            'executor_user_id' => $otherUser->id,
            'priority' => TaskPriority::HIGH->value,
            'status' => TaskStatus::PENDING_REVIEW->value,
            'due_date' => now()->addDays(5),
            'created_by' => $otherUser->id,
        ]);

        TaskSupervisor::create([
            'task_id' => $reviewTask->id,
            'stage_number' => 1,
            'stage_name' => 'مرحله کنترل کیفیت',
            'user_id' => $user->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('وظیفه محوله به مدیر');
        $response->assertSee('وظیفه منتظر تایید ناظر');
        $response->assertSee('مرحله کنترل کیفیت');
        $response->assertSee('تایید تسک');
    }
}
