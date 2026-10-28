<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OrganizationNode;
use App\Models\User;
use App\Models\UserRoleAssignment;
use App\Services\AuthorizationService;
use App\Services\OrganizationScopeService;
use App\Services\OrganizationService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly OrganizationService $organizationService,
        private readonly AuthorizationService $authorizationService,
        private readonly OrganizationScopeService $organizationScopeService
    ) {}

    private function authorizeUserManagement(Request $request): User
    {
        $user = $request->user();
        if (! $user->canManageUsers()) {
            abort(403, 'شما دسترسی لازم جهت مدیریت کاربران را ندارید.');
        }

        return $user;
    }

    public function index(Request $request)
    {
        $currentUser = $this->authorizeUserManagement($request);
        $isGlobal = $this->authorizationService->isGlobalAdminOrManager($currentUser);

        $allowedNodeIds = null;
        if (! $isGlobal) {
            $allowedNodeIds = $this->organizationScopeService->getAuthorizedNodeIdsForUser($currentUser);
        }

        $search = $request->query('search');
        $status = $request->query('status');
        $role = $request->query('role');
        $organizationNodeId = $request->query('organization_node_id') ? (int) $request->query('organization_node_id') : null;
        $perPage = (int) $request->query('per_page', 50);
        $sort = $request->query('sort', 'latest');

        if ($organizationNodeId && $allowedNodeIds !== null && ! in_array($organizationNodeId, $allowedNodeIds)) {
            $organizationNodeId = null;
        }

        $allowedPerPage = [10, 25, 50, 100, 200];
        if (! in_array($perPage, $allowedPerPage)) {
            $perPage = 50;
        }

        $users = $this->userService->getUsers($perPage, $search, $status, $role, $organizationNodeId, $sort, $allowedNodeIds);
        $users->appends($request->query());

        if ($isGlobal) {
            $organizationNodes = $this->organizationService->getAllFlat();
            $baseUserQuery = User::query();
        } else {
            $organizationNodes = OrganizationNode::whereIn('id', $allowedNodeIds)
                ->orderBy('sort_order', 'asc')
                ->orderBy('name', 'asc')
                ->get();

            $baseUserQuery = User::where(function ($q) use ($allowedNodeIds) {
                $q->whereHas('activeRoleAssignments', function ($sq) use ($allowedNodeIds) {
                    $sq->whereIn('organization_node_id', $allowedNodeIds);
                })->orDoesntHave('activeRoleAssignments');
            });
        }

        $roles = UserRole::cases();
        $userStatuses = UserStatus::cases();

        $totalUsers = (clone $baseUserQuery)->count();
        $activeUsers = (clone $baseUserQuery)->where('status', UserStatus::ACTIVE->value)->count();
        $inactiveUsers = (clone $baseUserQuery)->where('status', UserStatus::INACTIVE->value)->count();
        $suspendedUsers = (clone $baseUserQuery)->where('status', UserStatus::SUSPENDED->value)->count();

        return view('users.index', compact(
            'users',
            'organizationNodes',
            'roles',
            'userStatuses',
            'search',
            'status',
            'role',
            'organizationNodeId',
            'perPage',
            'sort',
            'totalUsers',
            'activeUsers',
            'inactiveUsers',
            'suspendedUsers',
            'isGlobal'
        ));
    }

    public function store(Request $request)
    {
        $currentUser = $this->authorizeUserManagement($request);
        $isGlobal = $this->authorizationService->isGlobalAdminOrManager($currentUser);

        $allowedNodeIds = null;
        if (! $isGlobal) {
            $allowedNodeIds = $this->organizationScopeService->getAuthorizedNodeIdsForUser($currentUser);
            if (empty($allowedNodeIds)) {
                return redirect()->route('users.index')->with('error', 'شما دارای واحد سازمانی تعریف شده جهت ایجاد کاربر جدید نیستید.');
            }
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'organization_node_id' => ['nullable', 'exists:organization_nodes,id'],
        ], [
            'username.unique' => 'این نام کاربری قبلا ثبت شده است.',
            'first_name.required' => 'وارد کردن نام الزامی است.',
            'last_name.required' => 'وارد کردن نام خانوادگی الزامی است.',
            'username.required' => 'وارد کردن نام کاربری الزامی است.',
            'password.required' => 'وارد کردن کلمه عبور الزامی است.',
        ]);

        $initialRole = ! empty($validated['role']) ? UserRole::from($validated['role']) : null;
        $initialNodeId = ! empty($validated['organization_node_id']) ? (int) $validated['organization_node_id'] : null;

        if (! $isGlobal) {
            if ($initialRole && ($initialRole === UserRole::SUPER_ADMIN || $initialRole === UserRole::ORGANIZATION_MANAGER)) {
                return redirect()->route('users.index')->with('error', 'شما مجاز به اعطای نقش مدیریت ارشد یا مدیر سازمان نیستید.');
            }

            if ($initialNodeId && ! in_array($initialNodeId, $allowedNodeIds)) {
                return redirect()->route('users.index')->with('error', 'انتخاب واحد سازمانی خارج از حوزه مجاز شما امکان‌پذیر نیست.');
            }

            if (! $initialNodeId && ! empty($allowedNodeIds)) {
                $initialNodeId = $allowedNodeIds[0];
            }
        }

        // Users without roles start as INACTIVE until assigned a role
        $validated['status'] = UserStatus::INACTIVE->value;
        unset($validated['role'], $validated['organization_node_id']);

        $newUser = $this->userService->createUser($validated);

        if ($initialRole || (! $isGlobal && $initialNodeId)) {
            $roleToAssign = $initialRole ? $initialRole->value : UserRole::EXPERT->value;
            $this->authorizationService->assignRoleToUser(
                userId: $newUser->id,
                role: $roleToAssign,
                organizationNodeId: $initialNodeId,
                isPrimary: true
            );

            return redirect()->route('users.index')->with('success', 'کاربر جدید تعریف شده و نقش/واحد سازمانی با موفقیت تخصیص یافت.');
        }

        return redirect()->route('users.index')->with('success', 'کاربر جدید با موفقیت ایجاد شد (وضعیت اولیه به دلیل نداشتن نقش، غیرفعال تعیین شد).');
    }

    public function updateStatus(Request $request, User $user)
    {
        $currentUser = $this->authorizeUserManagement($request);
        $isGlobal = $this->authorizationService->isGlobalAdminOrManager($currentUser);

        if (! $isGlobal) {
            $allowedNodeIds = $this->organizationScopeService->getAuthorizedNodeIdsForUser($currentUser);
            if (! $this->authorizationService->canManageTargetUser($currentUser, $user, $allowedNodeIds)) {
                return redirect()->route('users.index')->with('error', 'شما مجاز به تغییر وضعیت کاربر خارج از معاونت/واحد خود نیستید.');
            }
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(UserStatus::class)],
        ]);

        $statusEnum = UserStatus::from($validated['status']);

        if ($statusEnum === UserStatus::ACTIVE && $user->activeRoleAssignments()->count() === 0) {
            return redirect()->route('users.index')->with('error', 'امکان فعال‌سازی کاربری که هیچ نقش سازمانی ندارد وجود ندارد. ابتدا یک نقش به کاربر اختصاص دهید.');
        }

        $this->userService->updateUserStatus($user, $statusEnum);

        return redirect()->route('users.index')->with('success', 'وضعیت کاربر با موفقیت تغییر یافت.');
    }

    public function assignRole(Request $request, User $user)
    {
        $currentUser = $this->authorizeUserManagement($request);
        $isGlobal = $this->authorizationService->isGlobalAdminOrManager($currentUser);

        if (! $isGlobal) {
            $allowedNodeIds = $this->organizationScopeService->getAuthorizedNodeIdsForUser($currentUser);
            if (! $this->authorizationService->canManageTargetUser($currentUser, $user, $allowedNodeIds)) {
                return redirect()->route('users.index')->with('error', 'شما مجاز به تغییر نقش کاربر خارج از معاونت/واحد خود نیستید.');
            }
        }

        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'organization_node_id' => ['nullable', 'exists:organization_nodes,id'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $targetNodeId = $validated['organization_node_id'] ?? null;
        $targetRole = UserRole::from($validated['role']);

        if (! $isGlobal) {
            if ($targetRole === UserRole::SUPER_ADMIN || $targetRole === UserRole::ORGANIZATION_MANAGER) {
                return redirect()->route('users.index')->with('error', 'شما مجاز به اعطای نقش مدیریت ارشد یا مدیر سازمان نیستید.');
            }

            $allowedNodeIds = $this->organizationScopeService->getAuthorizedNodeIdsForUser($currentUser);
            if (! $targetNodeId || ! in_array((int) $targetNodeId, $allowedNodeIds)) {
                return redirect()->route('users.index')->with('error', 'انتخاب واحد سازمانی در محدوده مجاز معاونت/واحد شما الزامی است.');
            }
        }

        $this->authorizationService->assignRoleToUser(
            userId: $user->id,
            role: $validated['role'],
            organizationNodeId: $targetNodeId,
            isPrimary: (bool) ($validated['is_primary'] ?? false)
        );

        return redirect()->route('users.index')->with('success', 'نقش سازمانی به کاربر اختصاص داده شد.');
    }

    public function removeRole(Request $request, int $assignmentId)
    {
        $currentUser = $this->authorizeUserManagement($request);
        $isGlobal = $this->authorizationService->isGlobalAdminOrManager($currentUser);

        if (! $isGlobal) {
            $assignment = UserRoleAssignment::find($assignmentId);
            if ($assignment && $assignment->organization_node_id) {
                $allowedNodeIds = $this->organizationScopeService->getAuthorizedNodeIdsForUser($currentUser);
                if (! in_array($assignment->organization_node_id, $allowedNodeIds)) {
                    return redirect()->route('users.index')->with('error', 'شما مجاز به حذف نقش در این واحد سازمانی نیستید.');
                }
            }
        }

        $this->authorizationService->removeRoleAssignment($assignmentId);

        return redirect()->route('users.index')->with('success', 'نقش با موفقیت از کاربر حذف شد.');
    }
}
