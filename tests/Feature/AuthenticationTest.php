<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('ورود به سامانه');
    }

    public function test_user_can_authenticate_with_valid_credentials(): void
    {
        $user = User::create([
            'first_name' => 'تست',
            'last_name' => 'کاربر',
            'username' => 'testuser',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        User::create([
            'first_name' => 'تست',
            'last_name' => 'کاربر',
            'username' => 'testuser',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $response = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['username']);
    }

    public function test_user_cannot_authenticate_if_inactive(): void
    {
        User::create([
            'first_name' => 'غیرفعال',
            'last_name' => 'کاربر',
            'username' => 'inactiveuser',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::INACTIVE->value,
        ]);

        $response = $this->post('/login', [
            'username' => 'inactiveuser',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['username']);
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'first_name' => 'تست',
            'last_name' => 'کاربر',
            'username' => 'testuser',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $this->actingAs($user);

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_is_logged_out_if_status_becomes_inactive(): void
    {
        $user = User::create([
            'first_name' => 'تست',
            'last_name' => 'کاربر',
            'username' => 'testuser',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        $user->update(['status' => UserStatus::INACTIVE->value]);

        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_user_is_logged_out_if_status_becomes_suspended(): void
    {
        $user = User::create([
            'first_name' => 'تست',
            'last_name' => 'کاربر',
            'username' => 'testuser',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        $user->update(['status' => UserStatus::SUSPENDED->value]);

        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_can_authenticate_with_remember_me(): void
    {
        $user = User::create([
            'first_name' => 'تست',
            'last_name' => 'کاربر',
            'username' => 'testremember',
            'password_hash' => Hash::make('password123'),
            'status' => UserStatus::ACTIVE->value,
        ]);

        $response = $this->post('/login', [
            'username' => 'testremember',
            'password' => 'password123',
            'remember' => '1',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
        $this->assertNotNull($user->fresh()->remember_token);
    }
}
