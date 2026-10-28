<?php

namespace App\Http\Controllers;

use App\Enums\ProjectPermission;
use App\Models\Project;
use App\Models\ProjectApprovalStage;
use App\Services\ProjectPermissionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectApprovalStageController extends Controller
{
    public function __construct(
        private readonly ProjectPermissionResolver $permissionResolver
    ) {}

    public function approve(Request $request, Project $project, ProjectApprovalStage $stage): RedirectResponse
    {
        $user = $request->user();

        // Check if user is supervisor for this stage OR has super admin / project management permissions
        if ($stage->supervisor_user_id !== $user->id &&
            ! $user->isSuperAdmin() &&
            ! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_APPROVAL_STAGES)) {
            abort(403, 'شما مجاز به تایید این مرحله از پروژه نیستید.');
        }

        $validated = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $stage->update([
            'status' => 'APPROVED',
            'comments' => $validated['comments'] ?? null,
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', "مرحله '{$stage->stage_name}' با موفقیت تایید شد.");
    }

    public function reject(Request $request, Project $project, ProjectApprovalStage $stage): RedirectResponse
    {
        $user = $request->user();

        if ($stage->supervisor_user_id !== $user->id &&
            ! $user->isSuperAdmin() &&
            ! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_APPROVAL_STAGES)) {
            abort(403, 'شما مجاز به تغییر وضعیت این مرحله از پروژه نیستید.');
        }

        $validated = $request->validate([
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $stage->update([
            'status' => 'REJECTED',
            'comments' => $validated['comments'] ?? null,
            'approved_at' => null,
        ]);

        return redirect()->back()->with('error', "مرحله '{$stage->stage_name}' رد شد.");
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $user = $request->user();

        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_APPROVAL_STAGES) && ! $user->isSuperAdmin()) {
            abort(403, 'شما دسترسی کافی برای اضافه کردن مرحله تایید به این پروژه را ندارید.');
        }

        $validated = $request->validate([
            'stage_name' => ['required', 'string', 'max:255'],
            'supervisor_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $nextStageNumber = ($project->approvalStages()->max('stage_number') ?? 0) + 1;

        ProjectApprovalStage::create([
            'project_id' => $project->id,
            'stage_number' => $nextStageNumber,
            'stage_name' => $validated['stage_name'],
            'supervisor_user_id' => $validated['supervisor_user_id'] ?? null,
            'status' => 'PENDING',
        ]);

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'approval_stages'])
            ->with('success', 'مرحله تایید جدید اضافه گردید.');
    }

    public function destroy(Request $request, Project $project, ProjectApprovalStage $stage): RedirectResponse
    {
        $user = $request->user();

        if (! $this->permissionResolver->hasPermission($user, $project, ProjectPermission::MANAGE_APPROVAL_STAGES) && ! $user->isSuperAdmin()) {
            abort(403, 'شما دسترسی کافی برای حذف مرحله تایید را ندارید.');
        }

        $stage->delete();

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'approval_stages'])
            ->with('success', 'مرحله تایید حذف گردید.');
    }
}
