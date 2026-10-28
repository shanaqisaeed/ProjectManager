<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('project_role');
            $table->string('permission');
            $table->timestamps();

            $table->unique(['project_role', 'permission'], 'uq_role_perm');
        });

        Schema::create('project_member_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission');
            $table->string('access_type')->default('ALLOW'); // ALLOW / DENY
            $table->timestamps();

            $table->unique(['project_id', 'user_id', 'permission'], 'uq_proj_user_perm');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_member_permissions');
        Schema::dropIfExists('project_role_permissions');
    }
};
