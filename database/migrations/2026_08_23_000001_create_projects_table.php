<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_project_id')->nullable()->constrained('projects')->onDelete('restrict');
            $table->foreignId('organization_node_id')->constrained('organization_nodes')->onDelete('restrict');
            $table->string('title');
            $table->string('project_key')->unique();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_project_id');
            $table->index('organization_node_id');
            $table->index('status');
            $table->index('project_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
