<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationNodeType;
use App\Models\OrganizationNode;
use App\Services\AuthorizationService;
use App\Services\OrganizationScopeService;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService,
        private readonly AuthorizationService $authorizationService,
        private readonly OrganizationScopeService $organizationScopeService
    ) {}

    public function index(Request $request)
    {
        $currentUser = $request->user();

        // Load complete organization hierarchy tree so any user (at any level) can see full ancestors and descendants
        $tree = $this->organizationService->getTree();
        $flatNodes = $this->organizationService->getAllFlat();

        $nodeTypes = OrganizationNodeType::cases();

        $stats = [
            'total_nodes' => $flatNodes->count(),
            'departments_count' => $flatNodes->where('type', OrganizationNodeType::DEPARTMENT)->count(),
            'units_teams_count' => $flatNodes->whereIn('type', [OrganizationNodeType::UNIT, OrganizationNodeType::TEAM, OrganizationNodeType::GROUP])->count(),
            'total_projects' => $flatNodes->sum('projects_count'),
        ];

        $currentUserNode = null;
        if ($currentUser && $currentUser->activeRoleAssignments) {
            $activeAssignment = $currentUser->activeRoleAssignments->first(fn ($a) => ! empty($a->organization_node_id));
            if ($activeAssignment) {
                $currentUserNode = $flatNodes->firstWhere('id', $activeAssignment->organization_node_id);
            }
        }

        return view('organization.index', compact('tree', 'flatNodes', 'nodeTypes', 'stats', 'currentUserNode'));
    }

    public function store(Request $request)
    {
        $currentUser = $request->user();
        $isGlobal = $currentUser ? $this->authorizationService->isGlobalAdminOrManager($currentUser) : true;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(OrganizationNodeType::class)],
            'parent_id' => ['nullable', 'exists:organization_nodes,id'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ], [
            'name.required' => 'نام گره سازمانی الزامی است.',
        ]);

        if (! $isGlobal) {
            $allowedNodeIds = $this->organizationScopeService->getAuthorizedNodeIdsForUser($currentUser);
            if (! empty($validated['parent_id']) && ! in_array((int) $validated['parent_id'], $allowedNodeIds)) {
                return redirect()->route('organization.index')->with('error', 'شما مجاز به ایجاد گره جدید خارج از حوزه مسئولیت خود نیستید.');
            }
        }

        $this->organizationService->createNode($validated);

        return redirect()->route('organization.index')->with('success', 'گره سازمانی جدید با موفقیت ایجاد شد.');
    }

    public function update(Request $request, OrganizationNode $node)
    {
        $currentUser = $request->user();
        $isGlobal = $currentUser ? $this->authorizationService->isGlobalAdminOrManager($currentUser) : true;

        if (! $isGlobal && ! $this->authorizationService->canAccessNode($currentUser, $node->id)) {
            return redirect()->route('organization.index')->with('error', 'شما مجاز به ویرایش این گره سازمانی نیستید.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(OrganizationNodeType::class)],
            'parent_id' => ['nullable', 'exists:organization_nodes,id', Rule::notIn([$node->id])],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $this->organizationService->updateNode($node, $validated);

        return redirect()->route('organization.index')->with('success', 'گره سازمانی به روزرسانی شد.');
    }

    public function destroy(Request $request, OrganizationNode $node)
    {
        $currentUser = $request->user();
        $isGlobal = $currentUser ? $this->authorizationService->isGlobalAdminOrManager($currentUser) : true;

        if (! $isGlobal && ! $this->authorizationService->canAccessNode($currentUser, $node->id)) {
            return redirect()->route('organization.index')->with('error', 'شما مجاز به حذف این گره سازمانی نیستید.');
        }

        $this->organizationService->deleteNode($node);

        return redirect()->route('organization.index')->with('success', 'گره سازمانی حذف شد.');
    }
}
