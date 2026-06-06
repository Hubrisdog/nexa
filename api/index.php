<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Run migrations and seeders on boot if enabled
if (env('RUN_MIGRATIONS_ON_BOOT', 'true') === 'true') {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    try {
        // Run database migrations
        Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        
        // Seed database if empty
        if (\App\Models\User::count() === 0) {
            Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        }
    } catch (\Exception $e) {
        // Log migration error and continue request execution
        error_log("Laravel auto-migration exception: " . $e->getMessage());
    }
}

// Forward request to HTTP kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

$kernel->terminate($request, $response);
