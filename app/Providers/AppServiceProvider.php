<?php

namespace App\Providers;

use App\Contracts\AuthenticationProviderInterface;
use App\Contracts\OrganizationNodeRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\UserRoleAssignmentRepositoryInterface;
use App\Repositories\EloquentOrganizationNodeRepository;
use App\Repositories\EloquentUserRepository;
use App\Repositories\EloquentUserRoleAssignmentRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(OrganizationNodeRepositoryInterface::class, EloquentOrganizationNodeRepository::class);
        $this->app->bind(UserRoleAssignmentRepositoryInterface::class, EloquentUserRoleAssignmentRepository::class);
        $this->app->bind(AuthenticationProviderInterface::class, UsernamePasswordAuthenticationProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
