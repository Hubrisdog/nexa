<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('logo_path')->nullable();
            $table->string('brand_color')->default('#4f46e5');
            $table->string('custom_domain')->nullable()->unique();
            $table->text('custom_email_footer')->nullable();
            $table->text('booking_page_theme')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'brand_color',
                'custom_domain',
                'custom_email_footer',
                'booking_page_theme',
            ]);
        });
    }
};
