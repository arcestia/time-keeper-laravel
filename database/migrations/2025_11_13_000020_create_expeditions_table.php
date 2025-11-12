<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expeditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level'); // 1..5
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('min_duration_seconds');
            $table->unsignedInteger('max_duration_seconds');
            $table->unsignedInteger('cost_seconds');
            $table->unsignedTinyInteger('energy_cost_pct');
            $table->timestamps();
            $table->index(['level']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('expeditions');
    }
};
