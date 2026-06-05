<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'appointments',
            'companies',
            'contacts',
            'deals',
            'activities',
            'sequences',
            'sequence_logs',
            'settings',
            'availabilities',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'appointments',
            'companies',
            'contacts',
            'deals',
            'activities',
            'sequences',
            'sequence_logs',
            'settings',
            'availabilities',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('tenant_id');
                });
            }
        }
    }
};
