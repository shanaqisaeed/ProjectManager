<?php

namespace App\Http\Controllers;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPermission;
use App\Models\ProjectRolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectRolePermissionController extends Controller
{
    public function index(): View
    {
        $roles = ProjectMemberRole::cases();
        $permissions = ProjectPermission::cases();

        $existing = ProjectRolePermission::all();
        $rolePermissions = [];

        foreach ($existing as $item) {
            $roleValue = $item->project_role instanceof ProjectMemberRole ? $item->project_role->value : $item->project_role;
            $permValue = $item->permission instanceof ProjectPermission ? $item->permission->value : $item->permission;
            $rolePermissions[$roleValue][] = $permValue;
        }

        return view('project-role-permissions.index', compact('roles', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request): RedirectResponse
    {
        $matrix = $request->input('matrix', []);

        DB::transaction(function () use ($matrix) {
            ProjectRolePermission::truncate();

            foreach ($matrix as $roleValue => $permMap) {
                if (! ProjectMemberRole::tryFrom($roleValue)) {
                    continue;
                }

                foreach ($permMap as $permValue => $checked) {
                    if ($checked && ProjectPermission::tryFrom($permValue)) {
                        ProjectRolePermission::create([
                            'project_role' => $roleValue,
                            'permission' => $permValue,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('project-role-permissions.index')
            ->with('success', 'ماتریس دسترسی‌های پیش‌فرض نقش‌های پروژه با موفقیت بروزرسانی شد.');
    }
}
