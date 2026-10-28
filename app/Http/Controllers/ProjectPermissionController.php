<?php

namespace App\Http\Controllers;

use App\Enums\PermissionAccessType;
use App\Enums\ProjectPermission;
use App\Models\Project;
use App\Models\ProjectMemberPermission;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ProjectPermissionController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService
    ) {}

    public function setOverride(Request $request, Project $project): RedirectResponse
    {
        // Support checklist batch submission
        if ($request->has('permissions_matrix')) {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'permissions_matrix' => ['required', 'array'],
            ]);

            $this->projectService->batchSetPermissionOverrides(
                $request->user(),
                $project,
                (int) $validated['user_id'],
                $validated['permissions_matrix']
            );

            return redirect()->route('projects.show', ['project' => $project, 'tab' => 'permissions'])
                ->with('success', 'چک‌لیست دسترسی‌های مستقیم کاربر با موفقیت بروزرسانی شد.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permission' => ['required', new Enum(ProjectPermission::class)],
            'access_type' => ['required', new Enum(PermissionAccessType::class)],
        ]);

        $permission = ProjectPermission::from($validated['permission']);
        $this->projectService->setDirectPermissionOverride(
            $request->user(),
            $project,
            (int) $validated['user_id'],
            $permission,
            $validated['access_type']
        );

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'permissions'])
            ->with('success', 'مجوز کاربر در پروژه با موفقیت بروزرسانی شد.');
    }

    public function destroyOverride(Request $request, Project $project, ProjectMemberPermission $permissionOverride): RedirectResponse
    {
        $this->projectService->removeDirectPermissionOverride($request->user(), $project, $permissionOverride);

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'permissions'])
            ->with('success', 'دسترسی مستقیم کاربر حذف شد و به حالت پیش‌فرض نقش بازگشت.');
    }
}
