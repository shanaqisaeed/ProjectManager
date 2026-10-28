<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add priority column to projects table
        Schema::table('projects', function (Blueprint $table) {
            $table->string('priority')->default('MEDIUM')->after('status');
            $table->index('priority');
        });

        // Add cooperation_title column to project_members table
        Schema::table('project_members', function (Blueprint $table) {
            $table->string('cooperation_title')->nullable()->after('project_role');
        });

        // Create project_templates table
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_node_id')->nullable()->constrained('organization_nodes')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('MEDIUM');
            $table->integer('duration_days')->default(30);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_node_id');
        });

        // Create project_template_roles table
        Schema::create('project_template_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id')->constrained('project_templates')->cascadeOnDelete();
            $table->string('project_role');
            $table->string('cooperation_title')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Create project_template_approval_stages table
        Schema::create('project_template_approval_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id')->constrained('project_templates')->cascadeOnDelete();
            $table->integer('stage_number');
            $table->string('stage_name');
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('supervisor_role')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        // Create project_approval_stages table
        Schema::create('project_approval_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->integer('stage_number');
            $table->string('stage_name');
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'stage_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_approval_stages');
        Schema::dropIfExists('project_template_approval_stages');
        Schema::dropIfExists('project_template_roles');
        Schema::dropIfExists('project_templates');

        Schema::table('project_members', function (Blueprint $table) {
            $table->dropColumn('cooperation_title');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
