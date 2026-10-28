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
        Schema::table('project_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('project_templates', 'parent_template_id')) {
                $table->foreignId('parent_template_id')->nullable()->after('id');
                $table->foreign('parent_template_id', 'fk_pt_parent_tmpl')->references('id')->on('project_templates')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('project_templates', 'offset_days')) {
                $table->integer('offset_days')->default(0)->after('priority');
            }
        });

        Schema::create('project_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id');
            $table->foreign('project_template_id', 'fk_ptt_template')->references('id')->on('project_templates')->cascadeOnDelete();

            $table->foreignId('parent_task_id')->nullable();
            $table->foreign('parent_task_id', 'fk_ptt_parent_task')->references('id')->on('project_template_tasks')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('MEDIUM');
            $table->integer('offset_days')->default(0);
            $table->integer('duration_days')->default(1);

            $table->foreignId('executor_user_id')->nullable();
            $table->foreign('executor_user_id', 'fk_ptt_executor_user')->references('id')->on('users')->nullOnDelete();

            $table->string('version', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_template_id', 'parent_task_id'], 'idx_ptt_tmpl_parent');
        });

        Schema::create('project_template_task_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_task_id');
            $table->foreign('project_template_task_id', 'fk_ptts_task')->references('id')->on('project_template_tasks')->cascadeOnDelete();

            $table->integer('stage_number')->default(1);
            $table->string('stage_name')->nullable();

            $table->foreignId('user_id');
            $table->foreign('user_id', 'fk_ptts_user')->references('id')->on('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_template_task_supervisors');
        Schema::dropIfExists('project_template_tasks');

        Schema::table('project_templates', function (Blueprint $table) {
            if (Schema::hasColumn('project_templates', 'parent_template_id')) {
                $table->dropForeign('fk_pt_parent_tmpl');
                $table->dropColumn('parent_template_id');
            }
            if (Schema::hasColumn('project_templates', 'offset_days')) {
                $table->dropColumn('offset_days');
            }
        });
    }
};
