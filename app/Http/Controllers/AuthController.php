<?php

namespace App\Http\Controllers;

use App\DTOs\CredentialsDto;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'وارد کردن نام کاربری الزامی است.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
        ]);

        $credentials = new CredentialsDto(
            username: $validated['username'],
            password: $validated['password'],
            remember: $request->boolean('remember')
        );

        $this->authService->login($credentials);

        return redirect()->intended(route('dashboard'));
    }

    public function logout()
    {
        $this->authService->logout();

        return redirect()->route('login');
    }
}
