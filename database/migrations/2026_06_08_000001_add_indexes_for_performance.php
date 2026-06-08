<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('role');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('staff_id');
            $table->index('client_id');
            $table->index('start_time');
            $table->index('end_time');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->index('tenant_id');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('company_id');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('company_id');
            $table->index('contact_id');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->index('company_id');
            $table->index('contact_id');
        });

        Schema::table('availabilities', function (Blueprint $table) {
            $table->index('tenant_id');
        });

        Schema::table('sequences', function (Blueprint $table) {
            $table->index('tenant_id');
        });

        Schema::table('sequence_logs', function (Blueprint $table) {
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['role']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['staff_id']);
            $table->dropIndex(['client_id']);
            $table->dropIndex(['start_time']);
            $table->dropIndex(['end_time']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['company_id']);
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['contact_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['contact_id']);
        });

        Schema::table('availabilities', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
        });

        Schema::table('sequences', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
        });

        Schema::table('sequence_logs', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
        });
    }
};
