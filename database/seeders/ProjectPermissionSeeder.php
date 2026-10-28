<?php

namespace Database\Seeders;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPermission;
use App\Models\ProjectRolePermission;
use Illuminate\Database\Seeder;

class ProjectPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $rolePermissions = [
            ProjectMemberRole::PROJECT_MANAGER->value => array_column(ProjectPermission::cases(), 'value'),
            ProjectMemberRole::MEMBER->value => [
                ProjectPermission::VIEW_PROJECT->value,
                ProjectPermission::VIEW_REPORTS->value,
            ],
            ProjectMemberRole::PROJECT_SUPERVISOR->value => [
                ProjectPermission::VIEW_PROJECT->value,
                ProjectPermission::UPDATE_TASK->value,
                ProjectPermission::ASSIGN_TASK->value,
                ProjectPermission::VIEW_REPORTS->value,
                ProjectPermission::MANAGE_APPROVAL_STAGES->value,
            ],
            ProjectMemberRole::VIEWER->value => [
                ProjectPermission::VIEW_PROJECT->value,
            ],
        ];

        foreach ($rolePermissions as $role => $permissions) {
            foreach ($permissions as $perm) {
                ProjectRolePermission::firstOrCreate([
                    'project_role' => $role,
                    'permission' => $perm,
                ]);
            }
        }
    }
}
