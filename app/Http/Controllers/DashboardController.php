<?php

namespace App\Http\Controllers;

use App\Models\Event;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'totalEvents' => Event::count(),
            'upcomingEvents' => Event::where('start_datetime', '>=', now())->count(),
        ]);
    }
}