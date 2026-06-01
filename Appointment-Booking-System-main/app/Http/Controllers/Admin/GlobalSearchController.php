<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Appointment;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->query('q', '');
        if (strlen($q) < 2) {
            return response()->json([
                'users' => [],
                'companies' => [],
                'contacts' => [],
                'deals' => [],
                'appointments' => []
            ]);
        }

        // Search Users (Clients & Staff)
        $users = User::where(function($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%");
        })->limit(5)->get();

        // Search Companies
        $companies = Company::where(function($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('industry', 'like', "%{$q}%")
                  ->orWhere('website', 'like', "%{$q}%");
        })->limit(5)->get();

        // Search Contacts
        $contacts = Contact::where(function($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%")
                  ->orWhere('position', 'like', "%{$q}%");
        })->limit(5)->get();

        // Search Deals
        $deals = Deal::where(function($query) use ($q) {
            $query->where('title', 'like', "%{$q}%")
                  ->orWhere('stage', 'like', "%{$q}%");
        })->limit(5)->get();

        // Search Appointments
        $appointments = Appointment::with(['client', 'staff'])
            ->where(function($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                      ->orWhere('note', 'like', "%{$q}%")
                      ->orWhereHas('client', function($subQuery) use ($q) {
                          $subQuery->where('name', 'like', "%{$q}%");
                      })
                      ->orWhereHas('staff', function($subQuery) use ($q) {
                          $subQuery->where('name', 'like', "%{$q}%");
                      });
            })->limit(5)->get();

        return response()->json([
            'users' => $users,
            'companies' => $companies,
            'contacts' => $contacts,
            'deals' => $deals,
            'appointments' => $appointments
        ]);
    }
}
