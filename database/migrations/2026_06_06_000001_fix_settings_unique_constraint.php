<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Drop unique key constraint on key column
            $table->dropUnique('settings_key_unique');
            // Add composite unique key on key and tenant_id
            $table->unique(['key', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key', 'tenant_id']);
            $table->unique('key');
        });
    }
};
