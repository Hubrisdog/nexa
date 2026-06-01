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
}
