<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserCalendarConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\Demo;

class OAuthController extends Controller
{
    /**
     * Redirects to Google OAuth screen, or falls back to a simulated mock
     * if the Google Calendar app credentials are not defined in .env.
     */
    public function googleRedirect()
    {
        if (Demo::active()) {
            return redirect('/demo/oauth/google');
        }

        $clientId = env('GOOGLE_CALENDAR_CLIENT_ID');
        $clientSecret = env('GOOGLE_CALENDAR_CLIENT_SECRET');

        if (empty($clientId) || empty($clientSecret)) {
            // Local / Test Mock Mode
            Log::info("Google OAuth Redirect [MOCK]: Navigating mock OAuth flow.");
            $callbackUrl = route('oauth.google.callback', [
                'code' => 'mock-google-auth-code',
                'state' => 'mock-state'
            ]);
            return redirect($callbackUrl);
        }

        $redirectUri = route('oauth.google.callback');
        $scope = implode(' ', [
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/calendar.events',
            'openid',
            'email',
            'profile'
        ]);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => csrf_token(),
        ]);

        return redirect("https://accounts.google.com/o/oauth2/v2/auth?{$query}");
    }

    /**
     * Handles Google Calendar OAuth redirect callback.
     * Exchages code for access/refresh tokens and stores them securely.
     */
    public function googleCallback(Request $request)
    {
        $code = $request->query('code');
        $user = auth()->user();

        if (!$user) {
            return redirect('/login');
        }

        if ($code === 'mock-google-auth-code') {
            // Simulated OAuth payload for local sandbox testing
            UserCalendarConnection::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'google',
                ],
                [
                    'email' => 'mock.' . strtolower(str_replace(' ', '.', $user->name)) . '@gmail.com',
                    'access_token' => 'mock-google-access-token-' . time(),
                    'refresh_token' => 'mock-google-refresh-token-' . time(),
                    'expires_at' => now()->addHour(),
                    'calendar_id' => 'primary',
                    'tenant_id' => $user->tenant_id,
                ]
            );

            return redirect('/admin/settings?sync=success');
        }

        try {
            $clientId = env('GOOGLE_CALENDAR_CLIENT_ID');
            $clientSecret = env('GOOGLE_CALENDAR_CLIENT_SECRET');
            $redirectUri = route('oauth.google.callback');

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

            if ($response->failed()) {
                Log::error("Google OAuth token exchange failed: " . $response->body());
                return redirect('/admin/settings?sync=error');
            }

            $tokens = $response->json();
            $accessToken = $tokens['access_token'] ?? null;
            $refreshToken = $tokens['refresh_token'] ?? null;
            $expiresIn = $tokens['expires_in'] ?? 3600;

            // Fetch user profile email to store connected address
            $profileResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
            $email = 'unknown@gmail.com';
            if ($profileResponse->successful()) {
                $email = $profileResponse->json()['email'] ?? $email;
            }

            // Look up existing connection
            $existing = UserCalendarConnection::where('user_id', $user->id)
                ->where('provider', 'google')
                ->first();

            // Refresh token is only sent on first prompt/consent, reuse existing if not provided
            $storedRefreshToken = $refreshToken ?? ($existing ? $existing->refresh_token : null);

            UserCalendarConnection::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'google',
                ],
                [
                    'email' => $email,
                    'access_token' => $accessToken,
                    'refresh_token' => $storedRefreshToken,
                    'expires_at' => now()->addSeconds($expiresIn - 60), // 1-minute buffer
                    'calendar_id' => 'primary',
                    'tenant_id' => $user->tenant_id,
                ]
            );

            return redirect('/admin/settings?sync=success');

        } catch (\Exception $e) {
            Log::error("Google Calendar OAuth exception: " . $e->getMessage());
            return redirect('/admin/settings?sync=error');
        }
    }

    /**
     * Fetches current connection statuses (excluding sensitive tokens).
     */
    public function getConnections()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([], 401);
        }

        $connections = $user->calendarConnections()
            ->get()
            ->map(function ($conn) {
                return [
                    'id' => $conn->id,
                    'provider' => $conn->provider,
                    'email' => $conn->email,
                    'is_connected' => true,
                    'created_at' => $conn->created_at->toIso8601String(),
                ];
            });

        return response()->json($connections);
    }

    /**
     * Disconnects a specific calendar sync connection.
     */
    public function disconnect($provider)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $connection = $user->calendarConnections()
            ->where('provider', $provider)
            ->first();

        if ($connection) {
            $connection->delete();
            return response()->json(['message' => "Disconnected {$provider} calendar successfully."]);
        }

        return response()->json(['message' => 'Connection not found.'], 404);
    }
}
