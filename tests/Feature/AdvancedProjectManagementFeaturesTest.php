<?php

use App\Enums\OrganizationNodeType;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\RoleAssignmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\ProjectApprovalStage;
use App\Models\ProjectMember;
use App\Models\ProjectTemplate;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Database\Seeders\ProjectPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProjectPermissionSeeder::class);

    $this->orgNode = OrganizationNode::create([
        'name' => 'واحد عمران و ساخت‌وساز',
        'type' => OrganizationNodeType::UNIT->value,
    ]);

    $this->user = User::create([
        'first_name' => 'مدیر',
        'last_name' => 'پروژه',
        'username' => 'pm_user',
        'password_hash' => bcrypt('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    UserRoleAssignment::create([
        'user_id' => $this->user->id,
        'role' => UserRole::SUPER_ADMIN->value,
        'organization_node_id' => $this->orgNode->id,
        'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $this->actingAs($this->user);
});

test('can create project with priority including force majeure', function () {
    $response = $this->post(route('projects.store'), [
        'title' => 'پروژه اضطراری بیمارستان',
        'organization_node_id' => $this->orgNode->id,
        'priority' => ProjectPriority::FORCE_MAJEURE->value,
        'status' => ProjectStatus::ACTIVE->value,
        'start_date' => '1405/06/01',
        'deadline' => '1405/06/30',
    ]);

    $response->assertRedirect();
    $project = Project::where('title', 'پروژه اضطراری بیمارستان')->first();
    expect($project)->not->toBeNull();
    expect($project->priority)->toBe(ProjectPriority::FORCE_MAJEURE);
});

test('can filter projects by priority and toggle grid table layout views', function () {
    Project::create([
        'title' => 'پروژه عادی',
        'project_key' => 'PRJ-NORM',
        'organization_node_id' => $this->orgNode->id,
        'priority' => ProjectPriority::MEDIUM->value,
        'created_by' => $this->user->id,
    ]);

    Project::create([
        'title' => 'پروژه فورس ماژور',
        'project_key' => 'PRJ-FORCE',
        'organization_node_id' => $this->orgNode->id,
        'priority' => ProjectPriority::FORCE_MAJEURE->value,
        'created_by' => $this->user->id,
    ]);

    // Test priority filter
    $response = $this->get(route('projects.index', ['priority' => ProjectPriority::FORCE_MAJEURE->value, 'view' => 'grid']));
    $response->assertOk();
    $response->assertSee('PRJ-FORCE');
    $response->assertDontSee('PRJ-NORM');

    // Test table layout view
    $tableResponse = $this->get(route('projects.index', ['view' => 'table']));
    $tableResponse->assertOk();
    $tableResponse->assertSee('نمای جدولی (Table)');
});

test('subprojects are hidden by default in project list and shown when filter enabled', function () {
    $parent = Project::create([
        'title' => 'پروژه والد',
        'project_key' => 'PRJ-PARENT',
        'organization_node_id' => $this->orgNode->id,
        'created_by' => $this->user->id,
    ]);

    $child = Project::create([
        'parent_project_id' => $parent->id,
        'title' => 'زیرپروژه فرعی',
        'project_key' => 'PRJ-CHILD',
        'organization_node_id' => $this->orgNode->id,
        'created_by' => $this->user->id,
    ]);

    // Default: subprojects hidden
    $responseDefault = $this->get(route('projects.index'));
    $responseDefault->assertOk();
    $responseDefault->assertSee('PRJ-PARENT');
    $responseDefault->assertDontSee('PRJ-CHILD');

    // With subprojects=include
    $responseInclude = $this->get(route('projects.index', ['subprojects' => 'include']));
    $responseInclude->assertOk();
    $responseInclude->assertSee('PRJ-PARENT');
    $responseInclude->assertSee('PRJ-CHILD');
});

test('can create project template for organizational unit and instantiate a project from it', function () {
    // 1. Create Template
    $templateResponse = $this->post(route('project-templates.store'), [
        'title' => 'الگوی احداث سوله',
        'organization_node_id' => $this->orgNode->id,
        'priority' => ProjectPriority::HIGH->value,
        'duration_days' => 45,
        'roles' => [
            ['project_role' => ProjectMemberRole::MEMBER->value, 'cooperation_title' => 'اکیپ فونداسیون', 'user_id' => $this->user->id],
        ],
        'approval_stages' => [
            ['stage_name' => 'تایید ناظر ارشد سازه', 'supervisor_user_id' => $this->user->id],
        ],
    ]);

    $templateResponse->assertRedirect(route('project-templates.index'));
    $template = ProjectTemplate::where('title', 'الگوی احداث سوله')->first();
    expect($template)->not->toBeNull();
    expect($template->duration_days)->toBe(45);

    // 2. Instantiate Project using template_id
    $projResponse = $this->post(route('projects.store'), [
        'template_id' => $template->id,
        'title' => 'پروژه خروجی از تمپلیت',
        'organization_node_id' => $this->orgNode->id,
        'priority' => ProjectPriority::HIGH->value,
        'status' => ProjectStatus::ACTIVE->value,
    ]);

    $projResponse->assertRedirect();
    $project = Project::where('title', 'پروژه خروجی از تمپلیت')->first();
    expect($project)->not->toBeNull();
    expect($project->priority)->toBe(ProjectPriority::HIGH);

    // Verify approval stages instantiated
    $stages = $project->approvalStages;
    expect($stages)->toHaveCount(1);
    expect($stages->first()->stage_name)->toBe('تایید ناظر ارشد سازه');

    // Verify start_date and deadline calculated from template duration_days (45 days)
    expect($project->start_date)->not->toBeNull();
    expect($project->deadline)->not->toBeNull();
    expect($project->start_date->diffInDays($project->deadline))->toEqual(45);
});

test('correctly calculates 365 days deadline from template', function () {
    $template = ProjectTemplate::create([
        'title' => 'تمپلیت یکساله (۳۶۵ روز)',
        'organization_node_id' => $this->orgNode->id,
        'priority' => ProjectPriority::HIGH->value,
        'duration_days' => 365,
        'created_by' => $this->user->id,
    ]);

    $response = $this->post(route('projects.store'), [
        'template_id' => $template->id,
        'title' => 'پروژه یکساله',
        'organization_node_id' => $this->orgNode->id,
        'start_date' => '1405/01/01',
    ]);

    $response->assertRedirect();
    $project = Project::where('title', 'پروژه یکساله')->first();
    expect($project)->not->toBeNull();
    expect($project->start_date)->not->toBeNull();
    expect($project->deadline)->not->toBeNull();
    expect((int) $project->start_date->diffInDays($project->deadline))->toBe(365);
});

test('can assign cooperation title to project members', function () {
    $project = Project::create([
        'title' => 'پروژه با اکیپ‌های مختلف',
        'project_key' => 'PRJ-TEAMS',
        'organization_node_id' => $this->orgNode->id,
        'created_by' => $this->user->id,
    ]);

    $newUser = User::create([
        'first_name' => 'علی',
        'last_name' => 'مجری',
        'username' => 'ali_executor',
        'password_hash' => bcrypt('password'),
        'status' => UserStatus::ACTIVE->value,
    ]);

    $response = $this->post(route('projects.members.store', $project), [
        'user_id' => $newUser->id,
        'project_role' => ProjectMemberRole::MEMBER->value,
        'cooperation_title' => 'اکیپ تاسیسات مکانیکی',
    ]);

    $response->assertRedirect();
    $member = ProjectMember::where('project_id', $project->id)->where('user_id', $newUser->id)->first();
    expect($member)->not->toBeNull();
    expect($member->cooperation_title)->toBe('اکیپ تاسیسات مکانیکی');
});

test('supervisor can approve and reject project approval stages', function () {
    $project = Project::create([
        'title' => 'پروژه با مراحل نظارت',
        'project_key' => 'PRJ-SUPERVISION',
        'organization_node_id' => $this->orgNode->id,
        'created_by' => $this->user->id,
    ]);

    $stage = ProjectApprovalStage::create([
        'project_id' => $project->id,
        'stage_number' => 1,
        'stage_name' => 'تایید کیفیت بتن‌ریزی',
        'supervisor_user_id' => $this->user->id,
        'status' => 'PENDING',
    ]);

    // Approve
    $approveRes = $this->post(route('projects.approval-stages.approve', [$project, $stage]), [
        'comments' => 'تایید شد با رعایت استانداردهای لازم',
    ]);

    $approveRes->assertRedirect();
    $stage->refresh();
    expect($stage->status)->toBe('APPROVED');
    expect($stage->comments)->toBe('تایید شد با رعایت استانداردهای لازم');

    // Reject
    $rejectRes = $this->post(route('projects.approval-stages.reject', [$project, $stage]), [
        'comments' => 'نیاز به اصلاح مجدد دارد',
    ]);

    $rejectRes->assertRedirect();
    $stage->refresh();
    expect($stage->status)->toBe('REJECTED');
});

test('calculates 80% time warning and overdue alerts correctly', function () {
    $overdueProject = Project::create([
        'title' => 'پروژه منقضی شده',
        'project_key' => 'PRJ-OVERDUE',
        'organization_node_id' => $this->orgNode->id,
        'start_date' => now()->subDays(20),
        'deadline' => now()->subDays(5),
        'created_by' => $this->user->id,
    ]);

    expect($overdueProject->deadline_alert)->not->toBeNull();
    expect($overdueProject->deadline_alert['type'])->toBe('overdue');

    $warningProject = Project::create([
        'title' => 'پروژه در آستانه مهلت',
        'project_key' => 'PRJ-WARN',
        'organization_node_id' => $this->orgNode->id,
        'start_date' => now()->subDays(9),
        'deadline' => now()->addDays(1),
        'created_by' => $this->user->id,
    ]);

    expect($warningProject->deadline_alert)->not->toBeNull();
    expect($warningProject->deadline_alert['type'])->toBe('warning');
});

test('multi-level subproject nesting is supported', function () {
    $parent = Project::create([
        'title' => 'پروژه اصلی والد',
        'project_key' => 'PRJ-MAIN-ROOT',
        'organization_node_id' => $this->orgNode->id,
        'created_by' => $this->user->id,
    ]);

    $subprojectLevel1 = Project::create([
        'parent_project_id' => $parent->id,
        'title' => 'زیرپروژه سطح یک',
        'project_key' => 'PRJ-SUB-ONE',
        'organization_node_id' => $this->orgNode->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->post(route('projects.store'), [
        'title' => 'زیرپروژه سطح دو',
        'organization_node_id' => $this->orgNode->id,
        'parent_project_id' => $subprojectLevel1->id,
    ]);

    $response->assertRedirect();
    $subLevel2 = Project::where('title', 'زیرپروژه سطح دو')->first();
    expect($subLevel2)->not->toBeNull();
    expect($subLevel2->parent_project_id)->toBe($subprojectLevel1->id);
});
