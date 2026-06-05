<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return response()->json([
                'user' => Auth::user(),
                'message' => 'Logged in successfully'
            ]);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    public function register(Request $request)
    {
        $fields = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:tenants,slug'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string'],
        ]);

        // 1. Create the Tenant
        $tenant = \App\Models\Tenant::create([
            'name' => $fields['company_name'],
            'slug' => strtolower($fields['slug']),
            'plan' => 'free',
        ]);

        // 2. Initialize Default Settings for this Tenant
        \App\Models\Setting::create(['key' => 'app_name', 'value' => $fields['company_name'], 'tenant_id' => $tenant->id]);
        \App\Models\Setting::create(['key' => 'business_email', 'value' => $fields['email'], 'tenant_id' => $tenant->id]);
        \App\Models\Setting::create(['key' => 'business_hours', 'value' => '09:00 - 18:00', 'tenant_id' => $tenant->id]);
        \App\Models\Setting::create(['key' => 'booking_interval', 'value' => '30 mins', 'tenant_id' => $tenant->id]);
        \App\Models\Setting::create(['key' => 'enable_notifications', 'value' => 'true', 'tenant_id' => $tenant->id]);

        // 3. Create the Admin User
        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => Hash::make($fields['password']),
            'role' => 'admin',
            'phone' => $fields['phone'] ?? null,
            'tenant_id' => $tenant->id,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => $user,
            'message' => 'Business registered and logged in successfully'
        ], 201);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me()
    {
        if (Auth::check()) {
            return response()->json(Auth::user());
        }

        return response()->json(null, 401);
    }
}
