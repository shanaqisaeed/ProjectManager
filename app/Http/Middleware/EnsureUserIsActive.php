<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user || ! $user->status || ! $user->status->canLogin()) {
                Auth::logout();

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'حساب کاربری شما غیرفعال یا معلق شده است.',
                    ], 401);
                }

                return redirect()->route('login')->withErrors([
                    'username' => 'حساب کاربری شما غیرفعال یا معلق شده است و امکان دسترسی وجود ندارد.',
                ]);
            }
        }

        return $next($request);
    }
}
