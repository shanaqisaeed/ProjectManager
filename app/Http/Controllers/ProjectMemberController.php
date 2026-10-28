<?php

namespace App\Http\Controllers;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ProjectMemberController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService
    ) {}

    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'project_role' => ['required', new Enum(ProjectMemberRole::class)],
            'cooperation_title' => ['nullable', 'string', 'max:255'],
        ]);

        $role = ProjectMemberRole::from($validated['project_role']);
        $cooperationTitle = $validated['cooperation_title'] ?? null;
        $member = $this->projectService->addOrInviteMember($request->user(), $project, (int) $validated['user_id'], $role, $cooperationTitle);

        $msg = $member->status->value === 'ACCEPTED'
            ? 'عضو با موفقیت به پروژه اضافه شد.'
            : 'دعوت‌نامه عضویت ارسال گردید و در انتظار تایید مدیر مربوطه می‌باشد.';

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'members'])
            ->with('success', $msg);
    }

    public function approve(Request $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $this->projectService->approveInvitation($request->user(), $member);

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'members'])
            ->with('success', 'دعوت‌نامه عضویت با موفقیت تایید گردید.');
    }

    public function reject(Request $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $this->projectService->rejectInvitation($request->user(), $member);

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'members'])
            ->with('success', 'دعوت‌نامه عضویت رد گردید.');
    }

    public function destroy(Request $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $this->projectService->removeMember($request->user(), $project, $member);

        return redirect()->route('projects.show', ['project' => $project, 'tab' => 'members'])
            ->with('success', 'عضو با موفقیت از پروژه حذف گردید.');
    }
}
