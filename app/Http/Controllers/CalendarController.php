<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function events()
    {
        $events = Event::with('eventType')->get();

        return response()->json(
            $events->map(function ($event) {
                return [
                    'id' => (string) $event->id,
                    'title' => $event->title,
                    'start' => Carbon::parse($event->start_datetime)->format('Y-m-d'),
                    'end' => Carbon::parse($event->end_datetime ?? $event->start_datetime)->format('Y-m-d'),
                    'calendarId' => $event->eventType?->name ?? 'default',
                ];
            })
        );
    }
}