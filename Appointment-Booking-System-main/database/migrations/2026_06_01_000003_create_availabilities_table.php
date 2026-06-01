<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->json('working_hours')->nullable(); // e.g. {"monday": {"start": "09:00", "end": "17:00"}, ...}
            $table->json('breaks')->nullable(); // e.g. [{"start": "12:00", "end": "13:00"}]
            $table->json('holidays')->nullable(); // e.g. ["2026-12-25"]
            $table->integer('buffer_time')->default(0); // in minutes
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
