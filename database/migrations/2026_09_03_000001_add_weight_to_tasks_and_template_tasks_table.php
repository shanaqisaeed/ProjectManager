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
            $table->unsignedInteger('weight')->default(1)->after('priority');
        });

        Schema::table('project_template_tasks', function (Blueprint $table) {
            $table->unsignedInteger('weight')->default(1)->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('weight');
        });

        Schema::table('project_template_tasks', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
