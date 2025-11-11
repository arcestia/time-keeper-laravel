<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_job_runs')) {
            Schema::create('user_job_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('job_id')->constrained('jobs_catalog')->cascadeOnDelete();
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('claimed_at')->nullable();
                $table->timestamps();
                $table->index(['user_id','job_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_job_runs');
    }
};
