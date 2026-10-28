<?php

namespace App\Http\Controllers;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectPriority;
use App\Models\OrganizationNode;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateApprovalStage;
use App\Models\ProjectTemplateRole;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\ProjectTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectTemplateController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly ProjectTemplateService $templateService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = ProjectTemplate::whereNull('parent_template_id')
            ->with(['organizationNode', 'creator', 'templateRoles.user', 'approvalStages.supervisor', 'children', 'tasks']);

        $selectedNodeId = $request->query('organization_node_id');
        if (! empty($selectedNodeId)) {
            $query->where(function ($q) use ($selectedNodeId) {
                $q->where('organization_node_id', $selectedNodeId)
                    ->orWhereNull('organization_node_id');
            });
        }

        if (! $user->isSuperAdmin()) {
            $authorizedNodeIds = [];
            foreach ($user->activeRoleAssignments as $assignment) {
                if ($assignment->organization_node_id) {
                    $authorizedNodeIds[] = $assignment->organization_node_id;
                    if ($assignment->organizationNode) {
                        $authorizedNodeIds = array_merge($authorizedNodeIds, $assignment->organizationNode->getAllDescendantIds());
                    }
                }
            }
            $authorizedNodeIds = array_unique($authorizedNodeIds);

            $query->where(function ($q) use ($authorizedNodeIds, $user) {
                $q->whereNull('organization_node_id')
                    ->orWhereIn('organization_node_id', $authorizedNodeIds)
                    ->orWhere('created_by', $user->id);
            });
        }

        $templates = $query->orderBy('created_at', 'desc')->paginate(12);
        $organizationNodes = OrganizationNode::orderBy('name')->get();

        return view('templates.index', compact('templates', 'organizationNodes', 'selectedNodeId'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $organizationNodes = OrganizationNode::orderBy('name')->get();
        $users = User::orderBy('first_name')->get();

        return view('templates.create', [
            'organizationNodes' => $organizationNodes,
            'users' => $users,
            'priorities' => ProjectPriority::cases(),
            'projectMemberRoles' => ProjectMemberRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'organization_node_id' => ['nullable', 'integer', 'exists:organization_nodes,id'],
            'priority' => ['required', 'string'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'roles' => ['nullable', 'array'],
            'roles.*.project_role' => ['required', 'string'],
            'roles.*.cooperation_title' => ['nullable', 'string', 'max:255'],
            'roles.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'approval_stages' => ['nullable', 'array'],
            'approval_stages.*.stage_name' => ['required', 'string', 'max:255'],
            'approval_stages.*.supervisor_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $template = ProjectTemplate::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'organization_node_id' => $validated['organization_node_id'] ?? null,
                'priority' => $validated['priority'],
                'duration_days' => $validated['duration_days'],
                'created_by' => $request->user()->id,
            ]);

            if (! empty($validated['roles'])) {
                foreach ($validated['roles'] as $roleData) {
                    if (! empty($roleData['project_role'])) {
                        ProjectTemplateRole::create([
                            'project_template_id' => $template->id,
                            'project_role' => $roleData['project_role'],
                            'cooperation_title' => $roleData['cooperation_title'] ?? null,
                            'user_id' => $roleData['user_id'] ?? null,
                        ]);
                    }
                }
            }

            if (! empty($validated['approval_stages'])) {
                foreach ($validated['approval_stages'] as $index => $stageData) {
                    if (! empty($stageData['stage_name'])) {
                        ProjectTemplateApprovalStage::create([
                            'project_template_id' => $template->id,
                            'stage_number' => $index + 1,
                            'stage_name' => $stageData['stage_name'],
                            'supervisor_user_id' => $stageData['supervisor_user_id'] ?? null,
                            'is_required' => true,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('project-templates.index')
            ->with('success', 'تمپلیت جدید با موفقیت ایجاد گردید.');
    }

    public function edit(ProjectTemplate $projectTemplate): View
    {
        $projectTemplate->load(['templateRoles', 'approvalStages']);

        $organizationNodes = OrganizationNode::orderBy('name')->get();
        $users = User::orderBy('first_name')->get();

        return view('templates.edit', [
            'template' => $projectTemplate,
            'organizationNodes' => $organizationNodes,
            'users' => $users,
            'priorities' => ProjectPriority::cases(),
            'projectMemberRoles' => ProjectMemberRole::cases(),
        ]);
    }

    public function update(Request $request, ProjectTemplate $projectTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'organization_node_id' => ['nullable', 'integer', 'exists:organization_nodes,id'],
            'priority' => ['required', 'string'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'roles' => ['nullable', 'array'],
            'roles.*.project_role' => ['required', 'string'],
            'roles.*.cooperation_title' => ['nullable', 'string', 'max:255'],
            'roles.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'approval_stages' => ['nullable', 'array'],
            'approval_stages.*.stage_name' => ['required', 'string', 'max:255'],
            'approval_stages.*.supervisor_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        DB::transaction(function () use ($validated, $projectTemplate) {
            $projectTemplate->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'organization_node_id' => $validated['organization_node_id'] ?? null,
                'priority' => $validated['priority'],
                'duration_days' => $validated['duration_days'],
            ]);

            $projectTemplate->templateRoles()->delete();
            if (! empty($validated['roles'])) {
                foreach ($validated['roles'] as $roleData) {
                    if (! empty($roleData['project_role'])) {
                        ProjectTemplateRole::create([
                            'project_template_id' => $projectTemplate->id,
                            'project_role' => $roleData['project_role'],
                            'cooperation_title' => $roleData['cooperation_title'] ?? null,
                            'user_id' => $roleData['user_id'] ?? null,
                        ]);
                    }
                }
            }

            $projectTemplate->approvalStages()->delete();
            if (! empty($validated['approval_stages'])) {
                foreach ($validated['approval_stages'] as $index => $stageData) {
                    if (! empty($stageData['stage_name'])) {
                        ProjectTemplateApprovalStage::create([
                            'project_template_id' => $projectTemplate->id,
                            'stage_number' => $index + 1,
                            'stage_name' => $stageData['stage_name'],
                            'supervisor_user_id' => $stageData['supervisor_user_id'] ?? null,
                            'is_required' => true,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('project-templates.index')
            ->with('success', 'تمپلیت بروزرسانی گردید.');
    }

    public function destroy(ProjectTemplate $projectTemplate): RedirectResponse
    {
        $projectTemplate->delete();

        return redirect()->route('project-templates.index')
            ->with('success', 'تمپلیت حذف گردید.');
    }

    public function getTemplateJson(ProjectTemplate $projectTemplate): JsonResponse
    {
        $projectTemplate->load(['templateRoles', 'approvalStages', 'tasks']);

        return response()->json([
            'id' => $projectTemplate->id,
            'title' => $projectTemplate->title,
            'description' => $projectTemplate->description,
            'priority' => $projectTemplate->priority->value,
            'duration_days' => $projectTemplate->duration_days,
            'organization_node_id' => $projectTemplate->organization_node_id,
            'roles' => $projectTemplate->templateRoles->map(fn ($r) => [
                'project_role' => $r->project_role->value,
                'cooperation_title' => $r->cooperation_title,
                'user_id' => $r->user_id,
            ]),
            'approval_stages' => $projectTemplate->approvalStages->map(fn ($s) => [
                'stage_number' => $s->stage_number,
                'stage_name' => $s->stage_name,
            ]),
            'tasks_count' => $projectTemplate->tasks->count(),
        ]);
    }

    public function saveFromProject(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $template = $this->templateService->saveProjectAsTemplate(
            $request->user(),
            $project,
            $validated['title'] ?? null,
            $validated['description'] ?? null
        );

        return redirect()->route('projects.show', [$project, 'tab' => 'template'])
            ->with('success', "پروژه و کلیه زیرپروژه‌ها و فعالیت‌های آن با عنوان «{$template->title}» به عنوان الگو ذخیره گردید.");
    }

    public function instantiate(Request $request, ProjectTemplate $projectTemplate): RedirectResponse
    {
        $newProject = $this->templateService->createProjectFromTemplate(
            $request->user(),
            $projectTemplate
        );

        return redirect()->route('projects.show', $newProject)
            ->with('success', "پروژه جدید با کلید {$newProject->project_key} به همراه زیرپروژه‌ها و تسک‌ها با موفقیت از روی الگو ایجاد گردید.");
    }
}
