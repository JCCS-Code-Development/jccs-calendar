<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CalendarExportController extends Controller
{
    /**
     * Full iCal feed for the authenticated user (webcal:// subscribe URL).
     */
    public function feed(Request $request)
    {
        $user = auth()->user();

        $events = Event::with(['eventType', 'assignedUser'])
            ->when(
                ! $user->canViewAllEvents(),
                fn ($q) => $q->where('assigned_user_id', $user->id)
            )
            ->orderBy('start_datetime')
            ->get();

        $ical = $this->buildCalendar(
            config('app.name').' — '.$user->name,
            $events
        );

        return response($ical, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="jccs-calendar.ics"',
        ]);
    }

    /**
     * Single-event .ics download (no auth required — open from EventCard).
     */
    public function single(Request $request, Event $event)
    {
        $canView = auth()->user()?->canViewAllEvents()
            || $event->assigned_user_id === auth()->id();

        abort_unless($canView, 403);

        $ical = $this->buildCalendar(config('app.name'), collect([$event]));

        $slug = Str::slug($event->title ?: 'event');

        return response($ical, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$slug}.ics\"",
        ]);
    }

    private function buildCalendar(string $calName, $events): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//JCCS Services//JCCS Calendar//EN',
            'X-WR-CALNAME:'.self::escape($calName),
            'X-WR-TIMEZONE:America/New_York',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($events as $event) {
            $lines = array_merge($lines, $this->buildEvent($event));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    private function buildEvent(Event $event): array
    {
        $tz    = 'America/New_York';
        $start = Carbon::parse($event->start_datetime, $tz);
        $end   = $event->end_datetime
            ? Carbon::parse($event->end_datetime, $tz)
            : $start->copy()->addHour();

        $uid     = 'event-'.$event->id.'@jccs-calendar';
        $created = Carbon::parse($event->created_at)->utc()->format('Ymd\THis\Z');
        $dtStamp = now()->utc()->format('Ymd\THis\Z');
        $dtStart = $start->utc()->format('Ymd\THis\Z');
        $dtEnd   = $end->utc()->format('Ymd\THis\Z');

        $description = collect([
            $event->description,
            $event->event_subtype ? 'Sub-type: '.$event->event_subtype : null,
            $event->assignedUser ? 'Assigned to: '.$event->assignedUser->name : null,
            $event->status ? 'Status: '.$event->status : null,
        ])->filter()->implode('\\n');

        $lines = [
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$dtStamp,
            'CREATED:'.$created,
            'DTSTART:'.$dtStart,
            'DTEND:'.$dtEnd,
            'SUMMARY:'.self::escape($event->title),
        ];

        if ($description) {
            $lines[] = 'DESCRIPTION:'.self::escape($description);
        }

        if ($event->location) {
            $lines[] = 'LOCATION:'.self::escape($event->location);
        }

        if ($event->eventType) {
            $lines[] = 'CATEGORIES:'.self::escape($event->eventType->name);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    private static function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\\;', '\\,', '\\n'],
            $value
        );
    }
}
