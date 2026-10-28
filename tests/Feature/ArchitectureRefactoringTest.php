<?php

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->orgNode = OrganizationNode::create([
        'name' => 'واحد فناوری اطلاعات',
        'type' => 'DEPARTMENT',
        'sort_order' => 1,
    ]);

    $this->admin = User::factory()->create(['status' => 'ACTIVE']);
    $this->admin->roleAssignments()->create([
        'role' => UserRole::SUPER_ADMIN->value,
        'organization_node_id' => $this->orgNode->id,
        'is_primary' => true,
    ]);

    $this->parentProject = Project::create([
        'organization_node_id' => $this->orgNode->id,
        'title' => 'پروژه مادر اصلی',
        'project_key' => 'PRJ-PARENT',
        'start_date' => '2026-01-01',
        'deadline' => '2026-12-31',
        'status' => ProjectStatus::ACTIVE->value,
        'created_by' => $this->admin->id,
    ]);

    ProjectMember::create([
        'project_id' => $this->parentProject->id,
        'user_id' => $this->admin->id,
        'project_role' => ProjectMemberRole::PROJECT_MANAGER->value,
        'status' => ProjectMemberStatus::ACCEPTED->value,
    ]);
});

test('subproject deadline cannot exceed parent project deadline', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('projects.store'), [
        'organization_node_id' => $this->orgNode->id,
        'parent_project_id' => $this->parentProject->id,
        'title' => 'زیرپروژه با ددلاین نامعتبر',
        'project_key' => 'SUB-INVALID',
        'start_date' => '2026-06-01',
        'deadline' => '2027-06-01', // After parent project deadline 2026-12-31
    ]);

    $response->assertSessionHasErrors(['deadline']);
});

test('task deadline cannot exceed project deadline', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('projects.tasks.store', $this->parentProject), [
        'title' => 'تسک خارج از مهلت',
        'priority' => TaskPriority::HIGH->value,
        'status' => TaskStatus::TODO->value,
        'start_date' => '1404/10/01',
        'due_date' => '1406/01/01', // Exceeds project deadline
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422);
});

test('can create valid subtasks and prevents circular subtask references', function () {
    $this->actingAs($this->admin);

    $taskA = Task::create([
        'project_id' => $this->parentProject->id,
        'title' => 'تسک اصلی A',
        'priority' => TaskPriority::HIGH->value,
        'status' => TaskStatus::TODO->value,
        'created_by' => $this->admin->id,
    ]);

    $taskB = Task::create([
        'project_id' => $this->parentProject->id,
        'parent_id' => $taskA->id,
        'title' => 'زیرتسک B',
        'priority' => TaskPriority::MEDIUM->value,
        'status' => TaskStatus::TODO->value,
        'created_by' => $this->admin->id,
    ]);

    expect($taskB->parent_id)->toBe($taskA->id);
    expect($taskA->isDescendantOf($taskB->id))->toBeTrue();

    // Attempt circular reference (make A a child of B)
    $response = $this->put(route('projects.tasks.update', [$this->parentProject, $taskA]), [
        'title' => 'تسک اصلی A',
        'parent_id' => $taskB->id,
        'priority' => TaskPriority::HIGH->value,
    ], ['Accept' => 'application/json']);

    $response->assertStatus(422);
});

test('task deadline status indicator returns correct badge state', function () {
    $taskOverdue = Task::create([
        'project_id' => $this->parentProject->id,
        'title' => 'تسک معوقه',
        'status' => TaskStatus::TODO->value,
        'due_date' => now()->subDays(3)->format('Y-m-d'),
        'created_by' => $this->admin->id,
    ]);

    expect($taskOverdue->deadline_status['status_key'])->toBe('overdue');

    $taskUrgent = Task::create([
        'project_id' => $this->parentProject->id,
        'title' => 'تسک فوری',
        'status' => TaskStatus::TODO->value,
        'due_date' => now()->addDays(1)->format('Y-m-d'),
        'created_by' => $this->admin->id,
    ]);

    expect($taskUrgent->deadline_status['status_key'])->toBe('urgent');
});

test('file upload attachment rejects files over 20MB or forbidden mime types', function () {
    Storage::fake('public');
    $this->actingAs($this->admin);

    $task = Task::create([
        'project_id' => $this->parentProject->id,
        'title' => 'تسک تست فایل',
        'created_by' => $this->admin->id,
    ]);

    $forbiddenFile = UploadedFile::fake()->create('script.exe', 500, 'application/x-msdownload');

    $response = $this->post(route('projects.tasks.attachments.store', [$this->parentProject, $task]), [
        'file' => $forbiddenFile,
    ]);

    $response->assertSessionHasErrors(['file']);
});

test('gantt chart and reports endpoints respond successfully', function () {
    $this->actingAs($this->admin);

    $ganttView = $this->get(route('projects.gantt', $this->parentProject));
    $ganttView->assertStatus(200);

    $ganttJson = $this->get(route('projects.gantt.data', $this->parentProject));
    $ganttJson->assertStatus(200)->assertJsonStructure(['project_id', 'title', 'items']);

    $projectsReport = $this->get(route('reports.projects'));
    $projectsReport->assertStatus(200)
        ->assertViewHas([
            'projects',
            'organizationNodes',
            'totalProjects',
            'activeProjects',
            'completedProjects',
            'forceMajeureProjects',
            'highPriorityProjects',
            'overdueProjects',
        ]);

    // Test with filters
    $filteredProjects = $this->get(route('reports.projects', [
        'status' => 'ACTIVE',
        'priority' => 'HIGH',
        'health' => 'overdue',
        'search' => 'Project',
    ]));
    $filteredProjects->assertStatus(200);

    $workloadReport = $this->get(route('reports.workload'));
    $workloadReport->assertStatus(200)
        ->assertViewHas([
            'workloadData',
            'totalMembers',
            'overloadedCount',
            'balancedCount',
            'underutilizedCount',
            'totalActiveTasks',
        ]);
});
