<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Deal;
use App\Models\Contact;
use App\Models\Company;
use App\Models\Setting;
use App\Models\Availability;
use App\Models\UserCalendarConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DemoResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Wipes demo tenant data and re-seeds it with clean sample records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Disable tenant scope during seeding/resetting to bypass global scopes
        config(['app.ignore_tenant_scope' => true]);

        $this->info('Starting Demo Workspace reset...');

        $tenant = Tenant::where('slug', 'demo')->orWhere('is_demo', true)->first();

        if (!$tenant) {
            $this->warn('Demo tenant not found. Creating it first...');
            $tenant = Tenant::create([
                'name' => 'Demo Workspace',
                'slug' => 'demo',
                'plan' => 'enterprise',
                'is_demo' => true,
            ]);
        }

        $tenantId = $tenant->id;

        DB::transaction(function () use ($tenantId) {
            $this->comment('Wiping old demo activities, appointments, and CRM pipeline...');
            Activity::where('tenant_id', $tenantId)->delete();
            Appointment::where('tenant_id', $tenantId)->delete();
            Deal::where('tenant_id', $tenantId)->delete();
            Contact::where('tenant_id', $tenantId)->delete();
            Company::where('tenant_id', $tenantId)->delete();

            $this->comment('Wiping old settings and connections...');
            Setting::where('tenant_id', $tenantId)->delete();
            Availability::where('tenant_id', $tenantId)->delete();
            UserCalendarConnection::where('tenant_id', $tenantId)->delete();
            DB::table('sequence_logs')->where('tenant_id', $tenantId)->delete();
            DB::table('sequences')->where('tenant_id', $tenantId)->delete();

            $this->comment('Wiping old demo users (preserving administrator)...');
            User::where('tenant_id', $tenantId)
                ->where('email', '!=', 'demo@example.com')
                ->delete();
        });

        $this->comment('Running DemoTenantSeeder to rebuild sample data...');
        Artisan::call('db:seed', [
            '--class' => 'DemoTenantSeeder',
            '--force' => true
        ]);

        $this->info('Demo Workspace successfully reset and re-seeded!');
        return Command::SUCCESS;
    }
}
