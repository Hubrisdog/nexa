<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DemoService
{
    /**
     * Resets the Demo workspace by running the demo:reset Artisan command.
     */
    public function resetWorkspace(): bool
    {
        Log::info("DemoService: Initiating workspace reset...");
        
        try {
            Artisan::call('demo:reset');
            return true;
        } catch (\Exception $e) {
            Log::error("DemoService Reset Failure: " . $e->getMessage());
            throw $e;
        }
    }
}
