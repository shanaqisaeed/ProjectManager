<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('project_id')->constrained('tasks')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'start_date')) {
                $table->date('start_date')->nullable()->after('status');
            }
            if (! Schema::hasColumn('tasks', 'version')) {
                $table->string('version', 30)->nullable()->after('due_date');
            }

            // Performance Composite Indexes
            $table->index(['project_id', 'status', 'due_date'], 'idx_task_proj_stat_due');
            $table->index(['parent_id', 'status'], 'idx_task_prnt_stat');
            $table->index(['executor_user_id', 'status'], 'idx_task_exec_stat');
            $table->index(['created_by', 'status'], 'idx_task_crtr_stat');
            $table->index('due_date', 'idx_task_due_date');
            $table->index('priority', 'idx_task_priority');
            $table->index('start_date', 'idx_task_start_date');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index(['parent_project_id', 'status'], 'idx_proj_parent_status');
            $table->index(['organization_node_id', 'status'], 'idx_proj_org_status');
            $table->index('deadline', 'idx_proj_deadline');
            $table->index('start_date', 'idx_proj_start_date');
            $table->index('priority', 'idx_proj_priority');
        });

        Schema::table('organization_nodes', function (Blueprint $table) {
            $table->index(['parent_id', 'sort_order'], 'idx_orgn_parent_sort');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_nodes', function (Blueprint $table) {
            $table->dropIndex('idx_org_nodes_parent_sort');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_parent_status');
            $table->dropIndex('idx_projects_org_status');
            $table->dropIndex('idx_projects_deadline');
            $table->dropIndex('idx_projects_start_date');
            $table->dropIndex('idx_projects_priority');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_project_status_due');
            $table->dropIndex('idx_tasks_parent_status');
            $table->dropIndex('idx_tasks_executor_status');
            $table->dropIndex('idx_tasks_creator_status');
            $table->dropIndex('idx_tasks_due_date');
            $table->dropIndex('idx_tasks_priority');
            $table->dropIndex('idx_tasks_start_date');

            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'start_date', 'version']);
        });
    }
};
