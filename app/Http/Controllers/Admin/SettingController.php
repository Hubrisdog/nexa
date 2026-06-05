<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name' => ['nullable', 'string', 'max:255'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_hours' => ['nullable', 'string', 'max:255'],
            'booking_interval' => ['nullable', 'string', 'max:255'],
            'enable_notifications' => ['nullable', 'string'],
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json([
            'settings' => Setting::all()->pluck('value', 'key'),
            'message' => 'Settings updated successfully'
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $user = auth()->user();
        
        $data = $request->validate([
            'working_hours' => ['required', 'array'],
            'breaks' => ['nullable', 'array'],
            'buffer_time' => ['required', 'integer'],
            'timezone' => ['required', 'string'],
        ]);

        $availability = $user->availability()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'working_hours' => $data['working_hours'],
                'breaks' => $data['breaks'] ?? [],
                'buffer_time' => $data['buffer_time'],
                'timezone' => $data['timezone'],
                'tenant_id' => $user->tenant_id,
            ]
        );

        return response()->json([
            'availability' => $availability,
            'message' => 'Availability updated successfully'
        ]);
    }

    public function updateBranding(Request $request)
    {
        $tenant = auth()->user()->tenant;
        if (!$tenant) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'custom_domain' => ['nullable', 'string', 'unique:tenants,custom_domain,' . $tenant->id, 'max:255'],
            'custom_email_footer' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'], // Max 2MB
        ]);

        $tenant->name = $data['name'];
        $tenant->brand_color = $data['brand_color'];
        $tenant->custom_domain = $data['custom_domain'] ?? null;
        $tenant->custom_email_footer = $data['custom_email_footer'] ?? null;

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo_' . $tenant->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Create target folder if missing
            $dir = public_path('uploads/logos');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $file->move($dir, $filename);
            
            // Delete old logo file if it exists
            if ($tenant->logo_path && file_exists(public_path($tenant->logo_path))) {
                @unlink(public_path($tenant->logo_path));
            }

            $tenant->logo_path = 'uploads/logos/' . $filename;
        }

        $tenant->save();

        // Sync with app_name setting
        \App\Models\Setting::updateOrCreate(
            ['key' => 'app_name', 'tenant_id' => $tenant->id],
            ['value' => $tenant->name]
        );

        return response()->json([
            'tenant' => $tenant,
            'message' => 'Branding settings updated successfully.'
        ]);
    }
}
