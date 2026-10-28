<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Services\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeRoleAndScope
{
    public function __construct(
        private readonly AuthorizationService $authorizationService
    ) {}

    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($this->authorizationService->isSuperAdmin($user)) {
            return $next($request);
        }

        if (! empty($roles)) {
            $hasAnyRole = false;
            foreach ($roles as $roleString) {
                try {
                    $roleEnum = UserRole::from($roleString);
                    if ($this->authorizationService->hasRole($user, $roleEnum)) {
                        $hasAnyRole = true;
                        break;
                    }
                } catch (\ValueError $e) {
                    continue;
                }
            }

            if (! $hasAnyRole) {
                abort(403, 'شما دسترسی لازم برای ورود به این بخش را ندارید.');
            }
        }

        return $next($request);
    }
}
