<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GanttController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProjectApprovalStageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectPermissionController;
use App\Http\Controllers\ProjectRolePermissionController;
use App\Http\Controllers\ProjectTemplateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Guest Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/projects', [ReportController::class, 'projects'])->name('projects');
        Route::get('/workload', [ReportController::class, 'workload'])->name('workload');
    });

    // Project Templates Routes
    Route::prefix('project-templates')->name('project-templates.')->group(function () {
        Route::get('/', [ProjectTemplateController::class, 'index'])->name('index');
        Route::get('/create', [ProjectTemplateController::class, 'create'])->name('create');
        Route::post('/', [ProjectTemplateController::class, 'store'])->name('store');
        Route::post('/{projectTemplate}/instantiate', [ProjectTemplateController::class, 'instantiate'])->name('instantiate');
        Route::get('/{projectTemplate}/edit', [ProjectTemplateController::class, 'edit'])->name('edit');
        Route::put('/{projectTemplate}', [ProjectTemplateController::class, 'update'])->name('update');
        Route::delete('/{projectTemplate}', [ProjectTemplateController::class, 'destroy'])->name('destroy');
        Route::get('/{projectTemplate}/json', [ProjectTemplateController::class, 'getTemplateJson'])->name('json');
    });

    // Project Management Routes
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('/create', [ProjectController::class, 'create'])->name('create');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::post('/{project}/save-as-template', [ProjectTemplateController::class, 'saveFromProject'])->name('save-as-template');
        Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
        Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
        Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
        Route::post('/{project}/archive', [ProjectController::class, 'archive'])->name('archive');
        Route::post('/{project}/unarchive', [ProjectController::class, 'unarchive'])->name('unarchive');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');

        // Gantt Chart Routes
        Route::get('/{project}/gantt', [GanttController::class, 'show'])->name('gantt');
        Route::get('/{project}/gantt/data', [GanttController::class, 'data'])->name('gantt.data');

        // Project Members & Approvals
        Route::post('/{project}/members', [ProjectMemberController::class, 'store'])->name('members.store');
        Route::post('/{project}/members/{member}/approve', [ProjectMemberController::class, 'approve'])->name('members.approve');
        Route::post('/{project}/members/{member}/reject', [ProjectMemberController::class, 'reject'])->name('members.reject');
        Route::delete('/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');

        // Supervisor Approval Stages
        Route::post('/{project}/approval-stages', [ProjectApprovalStageController::class, 'store'])->name('approval-stages.store');
        Route::post('/{project}/approval-stages/{stage}/approve', [ProjectApprovalStageController::class, 'approve'])->name('approval-stages.approve');
        Route::post('/{project}/approval-stages/{stage}/reject', [ProjectApprovalStageController::class, 'reject'])->name('approval-stages.reject');
        Route::delete('/{project}/approval-stages/{stage}', [ProjectApprovalStageController::class, 'destroy'])->name('approval-stages.destroy');

        // Project Direct Permission Overrides
        Route::post('/{project}/permissions/override', [ProjectPermissionController::class, 'setOverride'])->name('permissions.override');
        Route::delete('/{project}/permissions/{permissionOverride}', [ProjectPermissionController::class, 'destroyOverride'])->name('permissions.destroy');

        // Task Management Routes
        Route::get('/{project}/tasks/search-parents', [TaskController::class, 'searchParents'])->name('tasks.search-parents');
        Route::get('/{project}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/{project}/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::get('/{project}/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::get('/{project}/tasks/{task}/json', [TaskController::class, 'showJson'])->name('tasks.json');
        Route::put('/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/{project}/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
        Route::post('/{project}/tasks/{task}/review', [TaskController::class, 'review'])->name('tasks.review');
        Route::post('/{project}/tasks/{task}/chats', [TaskController::class, 'storeChat'])->name('tasks.chats.store');
        Route::post('/{project}/tasks/{task}/attachments', [TaskController::class, 'storeAttachment'])->name('tasks.attachments.store');
        Route::delete('/{project}/tasks/{task}/attachments/{attachment}', [TaskController::class, 'destroyAttachment'])->name('tasks.attachments.destroy');
    });

    // User Management (Super Admin, Executive, and Unit Managers)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::post('/{user}/status', [UserController::class, 'updateStatus'])->name('status');
        Route::post('/{user}/assign-role', [UserController::class, 'assignRole'])->name('assign-role');
        Route::delete('/role-assignment/{assignmentId}', [UserController::class, 'removeRole'])->name('remove-role');
    });

    // Organization Structure (View for all, Management for Super Admin only)
    Route::prefix('organization')->name('organization.')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->name('index');

        Route::middleware('role:SUPER_ADMIN')->group(function () {
            Route::post('/', [OrganizationController::class, 'store'])->name('store');
            Route::put('/{node}', [OrganizationController::class, 'update'])->name('update');
            Route::delete('/{node}', [OrganizationController::class, 'destroy'])->name('destroy');
        });
    });

    // Global Project Role Permissions Management (Super Admin only)
    Route::middleware('role:SUPER_ADMIN')->prefix('project-role-permissions')->name('project-role-permissions.')->group(function () {
        Route::get('/', [ProjectRolePermissionController::class, 'index'])->name('index');
        Route::put('/', [ProjectRolePermissionController::class, 'update'])->name('update');
    });
});
