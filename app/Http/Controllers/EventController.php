<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use App\Http\Requests\StoreEventRequest;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->canViewAllEvents(), 403);

        $query = Event::with(['eventType', 'assignedUser'])
            ->when($request->filled('event_type_id'), fn ($q) => $q->where('event_type_id', $request->event_type_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('assigned_user_id'), fn ($q) => $q->where('assigned_user_id', $request->assigned_user_id));

        $this->applyDateRangeFilter($query, $request->date_range);

        $events = $query->orderBy('start_datetime')->get();

        return view('events.index', [
            'events' => $events,
            'eventGroups' => $this->groupEventsByUserAndDate($events, $request->date_range),
            'userColors' => $this->buildUserColorMap($events),
            'userCalendarColors' => $this->buildUserCalendarColorMap(),
            'eventTypes' => EventType::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'selectedEventType' => $request->event_type_id,
            'selectedStatus' => $request->status,
            'selectedAssignedUser' => $request->assigned_user_id,
            'selectedDateRange' => $request->date_range ?: 'today_forward',
            'isMyEvents' => false,
        ]);
    }

    public function myEvents(Request $request)
    {
        $query = Event::with(['eventType', 'assignedUser'])
            ->where('assigned_user_id', auth()->id())
            ->when($request->filled('event_type_id'), fn ($q) => $q->where('event_type_id', $request->event_type_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        $this->applyDateRangeFilter($query, $request->date_range);

        $events = $query->orderBy('start_datetime')->get();

        return view('events.index', [
            'events' => $events,
            'eventGroups' => $this->groupEventsByUserAndDate($events, $request->date_range),
            'userColors' => $this->buildUserColorMap($events),
            'userCalendarColors' => $this->buildUserCalendarColorMap(),
            'eventTypes' => EventType::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'selectedEventType' => $request->event_type_id,
            'selectedStatus' => $request->status,
            'selectedAssignedUser' => null,
            'selectedDateRange' => $request->date_range ?: 'today_forward',
            'isMyEvents' => true,
        ]);
    }

    private function applyDateRangeFilter($query, ?string $dateRange): void
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $endOfWeek = $today->copy()->endOfWeek();
        $endOfMonth = $today->copy()->endOfMonth();

        match ($dateRange) {
            'past' => $query->where('start_datetime', '<', $today),
            'today' => $query->whereDate('start_datetime', $today),
            'tomorrow' => $query->whereDate('start_datetime', $tomorrow),
            'this_week' => $query->whereBetween('start_datetime', [
                $tomorrow->copy()->addDay()->startOfDay(),
                $endOfWeek,
            ]),
            'this_month' => $query->whereBetween('start_datetime', [
                $endOfWeek->copy()->addSecond(),
                $endOfMonth,
            ]),
            'rest' => $query->where('start_datetime', '>', $endOfMonth),
            'today_forward' => $query->where('start_datetime', '>=', $today),
            default => $query->where('start_datetime', '>=', $today),
        };
    }

    private function groupEventsByUserAndDate($events, ?string $dateRange = null)
    {
        return $events
            ->groupBy(fn ($event) => $event->assignedUser?->name ?? 'Unassigned')
            ->sortKeys()
            ->map(function ($userEvents) use ($dateRange) {
                if ($dateRange) {
                    return [
                        $this->dateRangeLabel($dateRange) => $this->groupEventsByDay($userEvents),
                    ];
                }

                return $this->splitEventsIntoDateSections($userEvents);
            });
    }

    private function splitEventsIntoDateSections($events): array
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $endOfWeek = $today->copy()->endOfWeek();
        $endOfMonth = $today->copy()->endOfMonth();

        return [
            'Today' => $this->groupEventsByDay(
                $events->filter(fn ($event) => Carbon::parse($event->start_datetime)->isSameDay($today))
            ),
            'Tomorrow' => $this->groupEventsByDay(
                $events->filter(fn ($event) => Carbon::parse($event->start_datetime)->isSameDay($tomorrow))
            ),
            'This Week' => $this->groupEventsByDay(
                $events->filter(function ($event) use ($tomorrow, $endOfWeek) {
                    $eventDate = Carbon::parse($event->start_datetime);
                    return $eventDate->greaterThan($tomorrow->copy()->endOfDay())
                        && $eventDate->lessThanOrEqualTo($endOfWeek);
                })
            ),
            'This Month' => $this->groupEventsByDay(
                $events->filter(function ($event) use ($endOfWeek, $endOfMonth) {
                    $eventDate = Carbon::parse($event->start_datetime);
                    return $eventDate->greaterThan($endOfWeek)
                        && $eventDate->lessThanOrEqualTo($endOfMonth);
                })
            ),
            'Rest' => $this->groupEventsByDay(
                $events->filter(fn ($event) => Carbon::parse($event->start_datetime)->gt($endOfMonth))
            ),
        ];
    }

    private function groupEventsByDay($events)
    {
        return $events
            ->sortBy('start_datetime')
            ->groupBy(fn ($event) => Carbon::parse($event->start_datetime)->format('l, M d, Y'));
    }

    private function dateRangeLabel(?string $dateRange): string
    {
        return match ($dateRange) {
            'past' => 'Past',
            'today' => 'Today',
            'tomorrow' => 'Tomorrow',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'rest' => 'Rest',
            'today_forward' => 'Today Forward',
            default => 'Events',
        };
    }

    private function buildUserColorMap($events): array
    {
        $userColors = User::orderBy('id')
            ->get()
            ->mapWithKeys(fn ($user) => [$user->name => $this->userColorFor($user)])
            ->toArray();

        $userColors['Unassigned'] = '#f3f4f6';

        return $userColors;
    }

    private function buildUserCalendarColorMap(): array
    {
        $userColors = User::orderBy('id')
            ->get()
            ->mapWithKeys(fn ($user) => [$user->name => $this->userCalendarColorFor($user)])
            ->toArray();

        $userColors['Unassigned'] = '#6b7280';

        return $userColors;
    }

    private function userColorFor(?User $user): string
    {
        if (! $user) {
            return '#f3f4f6';
        }

        $colors = [
            '#dbeafe',
            '#d1fae5',
            '#ffedd5',
            '#ede9fe',
            '#fee2e2',
            '#cffafe',
            '#fae8ff',
            '#ecfccb',
            '#e0e7ff',
            '#ffe4e6',
        ];

        return $colors[($user->id - 1) % count($colors)];
    }

    private function userCalendarColorFor(?User $user): string
    {
        if (! $user) {
            return '#6b7280';
        }

        $colors = [
            '#2563eb',
            '#16a34a',
            '#f97316',
            '#7c3aed',
            '#dc2626',
            '#0891b2',
            '#db2777',
            '#65a30d',
            '#4f46e5',
            '#e11d48',
        ];

        return $colors[($user->id - 1) % count($colors)];
    }

    public function calendarEvents(Request $request)
    {
        abort_unless(auth()->user()?->canViewAllEvents() || auth()->user()?->canManageEvents(), 403);

        $events = Event::with(['eventType', 'assignedUser'])
            ->when(! auth()->user()?->canViewAllEvents(), fn ($q) => $q->where('assigned_user_id', auth()->id()))
            ->when($request->filled('event_type_id'), fn ($q) => $q->where('event_type_id', $request->event_type_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('assigned_user_id') && auth()->user()?->canViewAllEvents(), fn ($q) => $q->where('assigned_user_id', $request->assigned_user_id))
            ->orderBy('start_datetime')
            ->get();

        return response()->json($events->map(fn ($event) => [
            'id' => (string) $event->id,
            'title' => $event->title,
            'start' => Carbon::parse($event->start_datetime)->toIso8601String(),
            'end' => $event->end_datetime
                ? Carbon::parse($event->end_datetime)->toIso8601String()
                : Carbon::parse($event->start_datetime)->addHour()->toIso8601String(),
            'description' => $event->description,
            'location' => $event->location,
            'status' => $event->status,
            'priority' => $event->priority,
            'event_type' => $event->eventType?->name,
            'assigned_user' => $event->assignedUser?->name ?? 'Unassigned',
            'backgroundColor' => $this->userCalendarColorFor($event->assignedUser),
            'borderColor' => $this->userCalendarColorFor($event->assignedUser),
            'textColor' => '#ffffff',
            'calendar_color' => $this->userCalendarColorFor($event->assignedUser),
        ])->values());
    }

    public function create()
    {
        abort_unless(auth()->user()?->canManageEvents(), 403);

        return view('events.create', [
            'eventTypes' => EventType::all(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreEventRequest $request)
    {
        abort_unless(auth()->user()?->canManageEvents(), 403);

        $validated = $request->validated();
        unset($validated['reminder_date']);

        $validated = $this->normalizeEventDateTimes($validated);

        Event::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('events.index');
    }

    public function edit(Event $event)
    {
        abort_unless(auth()->user()?->canManageEvents(), 403);

        return view('events.edit', [
            'event' => $event,
            'eventTypes' => EventType::all(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(StoreEventRequest $request, Event $event)
    {
        abort_unless(auth()->user()?->canManageEvents(), 403);

        $validated = $request->validated();
        unset($validated['reminder_date']);

        $validated = $this->normalizeEventDateTimes($validated);

        $event->update($validated);

        return redirect()->route('events.index');
    }

    private function normalizeEventDateTimes(array $validated): array
    {
        $eventTimezone = 'America/New_York';

        if (! empty($validated['start_datetime'])) {
            $validated['start_datetime'] = Carbon::parse($validated['start_datetime'], $eventTimezone)
                ->format('Y-m-d H:i:s');
        }

        if (! empty($validated['end_datetime'])) {
            $validated['end_datetime'] = Carbon::parse($validated['end_datetime'], $eventTimezone)
                ->format('Y-m-d H:i:s');
        }

        return $validated;
    }

    public function destroy(Event $event)
    {
        abort_unless(auth()->user()?->canManageEvents(), 403);

        $event->delete();

        return redirect()->route('events.index');
    }
}