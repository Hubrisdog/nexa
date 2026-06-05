<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_type'); // appointment_scheduled, deal_stage_changed
            $table->string('trigger_value')->nullable(); // stage name or other filter
            $table->timestamps();
        });

        Schema::create('sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('sequences')->onDelete('cascade');
            $table->integer('delay_hours')->default(0); // hours relative to trigger event or appointment start time
            $table->string('type'); // email, sms, whatsapp
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('sequence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('sequences')->onDelete('cascade');
            $table->foreignId('step_id')->constrained('sequence_steps')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('cascade');
            $table->foreignId('deal_id')->nullable()->constrained('deals')->onDelete('cascade');
            $table->string('recipient');
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->dateTime('scheduled_for');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_logs');
        Schema::dropIfExists('sequence_steps');
        Schema::dropIfExists('sequences');
    }
};
