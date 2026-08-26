<?php

use App\Models\Event;
use App\Models\User;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send push reminders for events starting in ~60 minutes (runs every 5 minutes via scheduler)
Schedule::call(function () {
    $windowStart = Carbon::now()->addMinutes(55);
    $windowEnd   = Carbon::now()->addMinutes(65);

    $events = Event::with(['assignedUser'])
        ->whereNotNull('assigned_user_id')
        ->whereNotIn('status', ['Completed', 'Cancelled'])
        ->whereBetween('start_datetime', [$windowStart, $windowEnd])
        ->get();

    $push = app(PushNotificationService::class);

    foreach ($events as $event) {
        if (! $event->assignedUser) {
            continue;
        }

        $time = Carbon::parse($event->start_datetime)->format('g:i A');

        $push->notifyUser(
            $event->assignedUser,
            'Upcoming: '.$event->title,
            "Starting at {$time}",
            "/events/{$event->id}/edit"
        );
    }
})->everyFiveMinutes()->name('calendar:push-reminders');
