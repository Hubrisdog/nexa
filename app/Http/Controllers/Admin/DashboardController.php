<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Activity;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $user = auth()->user();
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        
        if ($user->role === 'client') {
            $statusCounts = Appointment::where('client_id', $user->id)
                ->selectRaw("status, count(*) as count")
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            $completedCount = $statusCounts['completed'] ?? 0;
            $cancelledCount = $statusCounts['cancelled'] ?? 0;
            $scheduledCount = $statusCounts['scheduled'] ?? 0;
            $confirmedCount = $statusCounts['confirmed'] ?? 0;
            $totalAppointments = array_sum($statusCounts);
            
            $totalClients = 1;
            $totalStaff = User::where('role', 'staff')->count();
            
            $recentAppointments = Appointment::with(['client', 'staff'])
                ->where('client_id', $user->id)
                ->orderBy('start_time', 'desc')
                ->limit(5)
                ->get();
                
            $appointmentsBreakdown = Appointment::selectRaw("date(start_time) as date, count(*) as count")
                ->where('client_id', $user->id)
                ->where('start_time', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            $todayCount = Appointment::where('client_id', $user->id)
                ->whereBetween('start_time', [$todayStart, $todayEnd])
                ->count();

            $todayAppointments = Appointment::with(['client', 'staff'])
                ->where('client_id', $user->id)
                ->whereBetween('start_time', [$todayStart, $todayEnd])
                ->orderBy('start_time', 'asc')
                ->get();

            $recentActivities = Activity::with(['company', 'contact', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        } elseif ($user->role === 'staff') {
            $statusCounts = Appointment::where('staff_id', $user->id)
                ->selectRaw("status, count(*) as count")
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            $completedCount = $statusCounts['completed'] ?? 0;
            $cancelledCount = $statusCounts['cancelled'] ?? 0;
            $scheduledCount = $statusCounts['scheduled'] ?? 0;
            $confirmedCount = $statusCounts['confirmed'] ?? 0;
            $totalAppointments = array_sum($statusCounts);

            $totalClients = Appointment::where('staff_id', $user->id)->distinct('client_id')->count('client_id');
            $totalStaff = User::where('role', 'staff')->count();
            
            $recentAppointments = Appointment::with(['client', 'staff'])
                ->where('staff_id', $user->id)
                ->orderBy('start_time', 'desc')
                ->limit(5)
                ->get();
                
            $appointmentsBreakdown = Appointment::selectRaw("date(start_time) as date, count(*) as count")
                ->where('staff_id', $user->id)
                ->where('start_time', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            $todayCount = Appointment::where('staff_id', $user->id)
                ->whereBetween('start_time', [$todayStart, $todayEnd])
                ->count();

            $todayAppointments = Appointment::with(['client', 'staff'])
                ->where('staff_id', $user->id)
                ->whereBetween('start_time', [$todayStart, $todayEnd])
                ->orderBy('start_time', 'asc')
                ->get();

            $recentActivities = Activity::with(['company', 'contact', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        } else {
            $statusCounts = Appointment::selectRaw("status, count(*) as count")
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            $completedCount = $statusCounts['completed'] ?? 0;
            $cancelledCount = $statusCounts['cancelled'] ?? 0;
            $scheduledCount = $statusCounts['scheduled'] ?? 0;
            $confirmedCount = $statusCounts['confirmed'] ?? 0;
            $totalAppointments = array_sum($statusCounts);

            $totalClients = User::where('role', 'client')->count();
            $totalStaff = User::where('role', 'staff')->count();
            
            $recentAppointments = Appointment::with(['client', 'staff'])
                ->orderBy('start_time', 'desc')
                ->limit(5)
                ->get();
                
            $appointmentsBreakdown = Appointment::selectRaw("date(start_time) as date, count(*) as count")
                ->where('start_time', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get();

            $todayCount = Appointment::whereBetween('start_time', [$todayStart, $todayEnd])->count();

            $todayAppointments = Appointment::with(['client', 'staff'])
                ->whereBetween('start_time', [$todayStart, $todayEnd])
                ->orderBy('start_time', 'asc')
                ->get();

            $recentActivities = Activity::with(['company', 'contact', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        }

        $completedRate = $totalAppointments > 0 
            ? round(($completedCount / $totalAppointments) * 100, 1) 
            : 0;

        return response()->json([
            'total_appointments' => $totalAppointments,
            'total_clients' => $totalClients,
            'total_staff' => $totalStaff,
            'completed_count' => $completedCount,
            'cancelled_count' => $cancelledCount,
            'scheduled_count' => $scheduledCount,
            'confirmed_count' => $confirmedCount,
            'completed_rate' => $completedRate,
            'recent_appointments' => $recentAppointments,
            'appointments_breakdown' => $appointmentsBreakdown,
            'today_count' => $todayCount,
            'today_appointments' => $todayAppointments,
            'recent_activities' => $recentActivities
        ]);
    }
}
