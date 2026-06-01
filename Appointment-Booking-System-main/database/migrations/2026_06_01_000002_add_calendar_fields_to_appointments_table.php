<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('google_event_id')->nullable()->after('note');
            $table->string('outlook_event_id')->nullable()->after('google_event_id');
            $table->string('meeting_link')->nullable()->after('outlook_event_id');
            $table->string('calendar_provider')->nullable()->after('meeting_link');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['google_event_id', 'outlook_event_id', 'meeting_link', 'calendar_provider']);
        });
    }
};
