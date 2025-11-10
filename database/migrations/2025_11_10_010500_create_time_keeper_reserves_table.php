<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('time_keeper_reserves', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('balance_seconds');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_keeper_reserves');
    }
};
