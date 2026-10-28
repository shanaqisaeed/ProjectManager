<?php

use App\Enums\OrganizationNodeType;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectMemberStatus;
use App\Enums\ProjectPermission;
use App\Enums\ProjectStatus;
use App\Enums\RoleAssignmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\ProjectPermissionResolver;
use App\Services\ProjectService;
use Database\Seeders\ProjectPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed default role permissions
    $this->seed(ProjectPermissionSeeder::class);

    // Setup Organization Hierarchy
    // Root Org -> Tech Dept -> Software Unit -> Backend Team & Frontend Team
    $this->rootOrg = OrganizationNode::create([
        'name' => 'شرکت اصلی',
        'type' => OrganizationNodeType::ORGANIZATION->value,
    ]);

    $this->techDept = OrganizationNode::create([
        'parent_id' => $this->rootOrg->id,
        'name' => 'معاونت فنی',
        'type' => OrganizationNodeType::DEPARTMENT->value,
    ]);

    $this->softwareUnit = OrganizationNode::create([
        'parent_id' => $this->techDept->id,
        'name' => 'واحد نرم‌افزار',
        'type' => OrganizationNodeType::UNIT->value,
    ]);

    $this->backendTeam = OrganizationNode::create([
        'parent_id' => $this->softwareUnit->id,
        'name' => 'تیم بک‌اند',
        'type' => OrganizationNodeType::TEAM->value,
    ]);

    $this->frontendTeam = OrganizationNode::create([
        'parent_id' => $this->softwareUnit->id,
        'name' => 'تیم فرانت‌اند',
        'type' => OrganizationNodeType::TEAM->value,
    ]);

    // Setup Users with System Roles
    $this->superAdmin = User::create([
        'first_name' => 'Super', 'last_name' => 'Admin', 'username' => 'superadmin',
        'password_hash' => bcrypt('password'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->superAdmin->id, 'role' => UserRole::SUPER_ADMIN->value,
        'organization_node_id' => $this->rootOrg->id, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $this->orgManager = User::create([
        'first_name' => 'Org', 'last_name' => 'Manager', 'username' => 'orgmanager',
        'password_hash' => bcrypt('password'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->orgManager->id, 'role' => UserRole::ORGANIZATION_MANAGER->value,
        'organization_node_id' => $this->techDept->id, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $this->unitManager = User::create([
        'first_name' => 'Unit', 'last_name' => 'Manager', 'username' => 'unitmanager',
        'password_hash' => bcrypt('password'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->unitManager->id, 'role' => UserRole::UNIT_MANAGER->value,
        'organization_node_id' => $this->softwareUnit->id, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $this->backendLead = User::create([
        'first_name' => 'Backend', 'last_name' => 'Lead', 'username' => 'backendlead',
        'password_hash' => bcrypt('password'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->backendLead->id, 'role' => UserRole::TEAM_LEAD->value,
        'organization_node_id' => $this->backendTeam->id, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $this->frontendLead = User::create([
        'first_name' => 'Frontend', 'last_name' => 'Lead', 'username' => 'frontendlead',
        'password_hash' => bcrypt('password'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->frontendLead->id, 'role' => UserRole::TEAM_LEAD->value,
        'organization_node_id' => $this->frontendTeam->id, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $this->backendDev = User::create([
        'first_name' => 'Backend', 'last_name' => 'Dev', 'username' => 'backenddev',
        'password_hash' => bcrypt('password'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->backendDev->id, 'role' => UserRole::EXPERT->value,
        'organization_node_id' => $this->backendTeam->id, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $this->frontendDev = User::create([
        'first_name' => 'Frontend', 'last_name' => 'Dev', 'username' => 'frontenddev',
        'password_hash' => bcrypt('password'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $this->frontendDev->id, 'role' => UserRole::MEMBER->value,
        'organization_node_id' => $this->frontendTeam->id, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);
});

// Scenario 1: SUPER_ADMIN project creation
test('1. SUPER_ADMIN can create project anywhere in organization', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه تست مدیر ارشد',
    ]);

    expect($project)->not->toBeNull()
        ->and($project->organization_node_id)->toBe($this->backendTeam->id);
});

// Scenario 2: ORGANIZATION_MANAGER creation within allowed scope
test('2. ORGANIZATION_MANAGER can create project within allowed scope', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->orgManager, [
        'organization_node_id' => $this->softwareUnit->id,
        'title' => 'پروژه مدیر سازمان',
    ]);

    expect($project->title)->toBe('پروژه مدیر سازمان');
});

// Scenario 3: UNIT_MANAGER creation within Unit and descendants
test('3. UNIT_MANAGER can create project within Unit and descendants', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->unitManager, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه مدیر واحد',
    ]);

    expect($project->organization_node_id)->toBe($this->backendTeam->id);
});

// Scenario 4: TEAM_LEAD creation within Team and descendants
test('4. TEAM_LEAD can create project within Team', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه سرپرست بک‌اند',
    ]);

    expect($project->title)->toBe('پروژه سرپرست بک‌اند');
});

// Scenario 5: TEAM_LEAD denied from creating project in sibling team
test('5. TEAM_LEAD denied from creating project in sibling team', function () {
    $service = app(ProjectService::class);

    expect(fn () => $service->createProject($this->backendLead, [
        'organization_node_id' => $this->frontendTeam->id,
        'title' => 'پروژه غیرمجاز',
    ]))->toThrow(ValidationException::class);
});

// Scenario 6: PROJECT_MANAGER cannot create project
test('6. PROJECT_MANAGER cannot create project', function () {
    $pmUser = User::create([
        'first_name' => 'Project', 'last_name' => 'Manager', 'username' => 'pmuser',
        'password_hash' => bcrypt('password'), 'status' => UserStatus::ACTIVE->value,
    ]);
    UserRoleAssignment::create([
        'user_id' => $pmUser->id, 'role' => UserRole::PROJECT_MANAGER->value,
        'organization_node_id' => $this->backendTeam->id, 'status' => RoleAssignmentStatus::ACTIVE->value,
    ]);

    $service = app(ProjectService::class);

    // Denied even in assigned node
    expect(fn () => $service->createProject($pmUser, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه مدیر پروژه مجاز',
    ]))->toThrow(ValidationException::class);
});

// Scenario 7: Automatic Project Manager membership for creator
test('7. Creator automatically becomes Project Manager with ACCEPTED status', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه جدید',
    ]);

    $member = ProjectMember::where('project_id', $project->id)
        ->where('user_id', $this->backendLead->id)
        ->first();

    expect($member)->not->toBeNull()
        ->and($member->project_role->value)->toBe(ProjectMemberRole::PROJECT_MANAGER->value)
        ->and($member->status->value)->toBe(ProjectMemberStatus::ACCEPTED->value);
});

// Scenario 8: Multiple Project Managers support
test('8. Multiple Project Managers supported on a single project', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه با چند مدیر',
    ]);

    // Add second project manager
    $service->addOrInviteMember($this->backendLead, $project, $this->backendDev->id, ProjectMemberRole::PROJECT_MANAGER);

    $pms = $project->projectManagers;
    expect($pms->count())->toBe(2);
});

// Scenario 9: Unlimited SubProject nesting supported
test('9. Unlimited SubProject nesting supported', function () {
    $service = app(ProjectService::class);

    $p1 = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه سطح 1',
    ]);

    $p2 = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->techDept->id,
        'parent_project_id' => $p1->id,
        'title' => 'زیرپروژه سطح 2',
    ]);

    $p3 = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->softwareUnit->id,
        'parent_project_id' => $p2->id,
        'title' => 'زیرپروژه سطح 3',
    ]);

    expect($p3->parent_project_id)->toBe($p2->id)
        ->and($p2->parent_project_id)->toBe($p1->id)
        ->and($p1->getAllDescendantIds())->toContain($p2->id, $p3->id);
});

// Scenario 10: Prevention of circular project relationships
test('10. Prevention of circular project relationships', function () {
    $service = app(ProjectService::class);

    $p1 = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه 1',
    ]);

    $p2 = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'parent_project_id' => $p1->id,
        'title' => 'پروژه 2',
    ]);

    // Try setting p1 parent to p2
    expect(fn () => $service->updateProject($this->superAdmin, $p1, [
        'title' => $p1->title,
        'organization_node_id' => $p1->organization_node_id,
        'project_key' => $p1->project_key,
        'status' => $p1->status->value,
        'parent_project_id' => $p2->id,
    ]))->toThrow(ValidationException::class);
});

// Scenario 11: Unique project_key generation
test('11. Unique project_key generation default PRJ-000001', function () {
    $service = app(ProjectService::class);

    $p1 = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه 1',
    ]);

    $p2 = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه 2',
    ]);

    expect($p1->project_key)->toBe('PRJ-000001')
        ->and($p2->project_key)->toBe('PRJ-000002');
});

// Scenario 12: Manual project_key uniqueness validation
test('12. Manual project_key uniqueness validation', function () {
    $service = app(ProjectService::class);

    $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه 1',
        'project_key' => 'CUSTOM-KEY-1',
    ]);

    expect(fn () => $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه 2',
        'project_key' => 'CUSTOM-KEY-1',
    ]))->toThrow(ValidationException::class);
});

// Scenario 13: Direct member assignment inside authorized scope
test('13. Direct member assignment inside authorized scope results in ACCEPTED status', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه بک‌اند',
    ]);

    // backendLead adds backendDev (both in backendTeam)
    $member = $service->addOrInviteMember($this->backendLead, $project, $this->backendDev->id, ProjectMemberRole::MEMBER);

    expect($member->status->value)->toBe(ProjectMemberStatus::ACCEPTED->value);
});

// Scenario 14: Cross-scope invitation
test('14. Cross-scope invitation results in INVITED status', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه بک‌اند',
    ]);

    // backendLead invites frontendDev (outside backendLead scope)
    $member = $service->addOrInviteMember($this->backendLead, $project, $this->frontendDev->id, ProjectMemberRole::MEMBER);

    expect($member->status->value)->toBe(ProjectMemberStatus::INVITED->value);
});

// Scenario 15: Cross-scope approval
test('15. Cross-scope approval by responsible manager transitions status to ACCEPTED', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه بک‌اند',
    ]);

    $member = $service->addOrInviteMember($this->backendLead, $project, $this->frontendDev->id, ProjectMemberRole::MEMBER);

    // frontendLead (responsible manager of frontendDev) approves
    $approvedMember = $service->approveInvitation($this->frontendLead, $member);

    expect($approvedMember->status->value)->toBe(ProjectMemberStatus::ACCEPTED->value)
        ->and($approvedMember->approved_by)->toBe($this->frontendLead->id);
});

// Scenario 16: Invitation rejection
test('16. Invitation rejection transitions status to REJECTED', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه بک‌اند',
    ]);

    $member = $service->addOrInviteMember($this->backendLead, $project, $this->frontendDev->id, ProjectMemberRole::MEMBER);

    $rejected = $service->rejectInvitation($this->frontendDev, $member);

    expect($rejected->status->value)->toBe(ProjectMemberStatus::REJECTED->value);
});

// Scenario 17: Removed membership behavior
test('17. Removed membership sets status to REMOVED', function () {
    $service = app(ProjectService::class);
    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه بک‌اند',
    ]);

    $member = $service->addOrInviteMember($this->backendLead, $project, $this->backendDev->id, ProjectMemberRole::MEMBER);

    $service->removeMember($this->backendLead, $project, $member);

    expect($member->fresh()->status->value)->toBe(ProjectMemberStatus::REMOVED->value);
});

// Scenario 18: Project Role default permissions
test('18. Project Role default permissions evaluated correctly', function () {
    $service = app(ProjectService::class);
    $resolver = app(ProjectPermissionResolver::class);

    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه تست مجوز نقش',
    ]);

    $service->addOrInviteMember($this->backendLead, $project, $this->backendDev->id, ProjectMemberRole::MEMBER);

    expect($resolver->hasPermission($this->backendDev, $project, ProjectPermission::VIEW_REPORTS))->toBeTrue()
        ->and($resolver->hasPermission($this->backendDev, $project, ProjectPermission::DELETE_PROJECT))->toBeFalse();
});

// Scenario 19: Direct ALLOW permission override
test('19. Direct ALLOW permission override grants permission to user', function () {
    $service = app(ProjectService::class);
    $resolver = app(ProjectPermissionResolver::class);

    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه تست ALLOW',
    ]);

    $service->addOrInviteMember($this->backendLead, $project, $this->backendDev->id, ProjectMemberRole::MEMBER);

    // Give direct ALLOW for DELETE_PROJECT
    $service->setDirectPermissionOverride($this->backendLead, $project, $this->backendDev->id, ProjectPermission::DELETE_PROJECT, 'ALLOW');

    expect($resolver->hasPermission($this->backendDev, $project, ProjectPermission::DELETE_PROJECT))->toBeTrue();
});

// Scenario 20: Direct DENY permission override
test('20. Direct DENY permission override revokes permission from user', function () {
    $service = app(ProjectService::class);
    $resolver = app(ProjectPermissionResolver::class);

    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه تست DENY',
    ]);

    $service->addOrInviteMember($this->backendLead, $project, $this->backendDev->id, ProjectMemberRole::MEMBER);

    // Give direct DENY for CREATE_TASK
    $service->setDirectPermissionOverride($this->backendLead, $project, $this->backendDev->id, ProjectPermission::CREATE_TASK, 'DENY');

    expect($resolver->hasPermission($this->backendDev, $project, ProjectPermission::CREATE_TASK))->toBeFalse();
});

// Scenario 21: DENY overriding inherited permission
test('21. Direct DENY overrides inherited parent permission', function () {
    $service = app(ProjectService::class);
    $resolver = app(ProjectPermissionResolver::class);

    $parent = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه والد',
    ]);

    $child = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'parent_project_id' => $parent->id,
        'title' => 'پروژه فرزند',
    ]);

    // User is MEMBER in parent
    $service->addOrInviteMember($this->superAdmin, $parent, $this->backendDev->id, ProjectMemberRole::MEMBER);

    // Direct DENY on child for CREATE_TASK
    $service->setDirectPermissionOverride($this->superAdmin, $child, $this->backendDev->id, ProjectPermission::CREATE_TASK, 'DENY');

    expect($resolver->hasPermission($this->backendDev, $child, ProjectPermission::CREATE_TASK))->toBeFalse();
});

// Scenario 22: Permission inheritance from Parent Project
test('22. SubProject inherits permission from Parent Project', function () {
    $service = app(ProjectService::class);
    $resolver = app(ProjectPermissionResolver::class);

    $parent = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه والد',
    ]);

    $child = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'parent_project_id' => $parent->id,
        'title' => 'پروژه فرزند',
    ]);

    // User is MEMBER in parent
    $service->addOrInviteMember($this->superAdmin, $parent, $this->backendDev->id, ProjectMemberRole::MEMBER);

    expect($resolver->hasPermission($this->backendDev, $child, ProjectPermission::VIEW_REPORTS))->toBeTrue();
});

// Scenario 23: Permission override on SubProject
test('23. Permission override on SubProject takes priority', function () {
    $service = app(ProjectService::class);
    $resolver = app(ProjectPermissionResolver::class);

    $parent = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه والد',
    ]);

    $child = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'parent_project_id' => $parent->id,
        'title' => 'پروژه فرزند',
    ]);

    // User is VIEWER in parent (no CREATE_TASK)
    $service->addOrInviteMember($this->superAdmin, $parent, $this->backendDev->id, ProjectMemberRole::VIEWER);

    // Direct ALLOW on child for CREATE_TASK
    $service->setDirectPermissionOverride($this->superAdmin, $child, $this->backendDev->id, ProjectPermission::CREATE_TASK, 'ALLOW');

    expect($resolver->hasPermission($this->backendDev, $child, ProjectPermission::CREATE_TASK))->toBeTrue();
});

// Scenario 24: Unauthorized project visibility prevention
test('24. Unauthorized project visibility prevented in listing queries', function () {
    $service = app(ProjectService::class);

    $p1 = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه بک‌اند',
    ]);

    $p2 = $service->createProject($this->frontendLead, [
        'organization_node_id' => $this->frontendTeam->id,
        'title' => 'پروژه فرانت‌اند',
    ]);

    $visibleForBackendDev = $service->scopeVisibleProjects(Project::query(), $this->backendDev)->pluck('id')->toArray();

    expect($visibleForBackendDev)->toContain($p1->id)
        ->and($visibleForBackendDev)->not->toContain($p2->id);
});

// Scenario 25: Unauthorized project update prevention
test('25. Unauthorized project update prevention', function () {
    $service = app(ProjectService::class);

    $project = $service->createProject($this->backendLead, [
        'organization_node_id' => $this->backendTeam->id,
        'title' => 'پروژه اصلی',
    ]);

    expect(fn () => $service->updateProject($this->frontendDev, $project, [
        'title' => 'تلاش غیرمجاز برای تغییر عنوان',
        'organization_node_id' => $project->organization_node_id,
        'project_key' => $project->project_key,
        'status' => $project->status->value,
    ]))->toThrow(ValidationException::class);
});

// Scenario 26: Parent Project archive requirement before deletion
test('26. Parent project must be ARCHIVED before deletion when it has children', function () {
    $service = app(ProjectService::class);

    $parent = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه والد فعال',
    ]);

    $child = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'parent_project_id' => $parent->id,
        'title' => 'پروژه فرزند',
    ]);

    // Try deleting ACTIVE parent with child -> throws ValidationException
    expect(fn () => $service->deleteProject($this->superAdmin, $parent))->toThrow(ValidationException::class);
});

// Scenario 27: Parent Project deletion prevention when active children exist
test('27. Parent project deletion prevented when active children exist', function () {
    $service = app(ProjectService::class);

    $parent = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه والد',
    ]);

    $child = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'parent_project_id' => $parent->id,
        'title' => 'پروژه فرزند فعال',
    ]);

    // Archive parent
    $service->archiveProject($this->superAdmin, $parent);

    // Try deleting archived parent while child is still ACTIVE -> throws ValidationException
    expect(fn () => $service->deleteProject($this->superAdmin, $parent))->toThrow(ValidationException::class);

    // Archive child first
    $service->archiveProject($this->superAdmin, $child);

    // Now parent deletion succeeds
    expect($service->deleteProject($this->superAdmin, $parent))->toBeTrue();
});

// Scenario 28: Soft deleted projects not appearing in normal listings
test('28. Soft deleted projects do not appear in normal listings', function () {
    $service = app(ProjectService::class);

    $project = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه قابل حذف',
    ]);

    $service->archiveProject($this->superAdmin, $project);
    $service->deleteProject($this->superAdmin, $project);

    expect(Project::find($project->id))->toBeNull()
        ->and(Project::withTrashed()->find($project->id))->not->toBeNull();
});

// Scenario 29: Completed projects lock rule (cannot edit, archive, or delete)
test('29. Completed projects cannot be edited, archived, or deleted', function () {
    $service = app(ProjectService::class);

    $project = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه تکمیل شده',
        'status' => ProjectStatus::COMPLETED->value,
    ]);

    // Cannot edit COMPLETED project
    expect(fn () => $service->updateProject($this->superAdmin, $project, ['title' => 'عنوان جدید']))
        ->toThrow(ValidationException::class);

    // Cannot archive COMPLETED project
    expect(fn () => $service->archiveProject($this->superAdmin, $project))
        ->toThrow(ValidationException::class);

    // Cannot delete COMPLETED project
    expect(fn () => $service->deleteProject($this->superAdmin, $project))
        ->toThrow(ValidationException::class);
});

// Scenario 30: Unarchiving an archived project restores its status to ACTIVE
test('30. Archived projects can be unarchived back to ACTIVE status', function () {
    $service = app(ProjectService::class);

    $project = $service->createProject($this->superAdmin, [
        'organization_node_id' => $this->rootOrg->id,
        'title' => 'پروژه آرشیو شده',
    ]);

    $service->archiveProject($this->superAdmin, $project);
    expect($project->fresh()->status)->toBe(ProjectStatus::ARCHIVED);

    $service->unarchiveProject($this->superAdmin, $project);
    expect($project->fresh()->status)->toBe(ProjectStatus::ACTIVE);
});
