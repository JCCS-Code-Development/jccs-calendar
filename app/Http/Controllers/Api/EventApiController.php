<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventApiController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->canViewAllEvents(), 403);

        $selectedDateRange = $request->date_range ?: 'today_tomorrow';

        $events = Event::with(['eventType', 'assignedUser.role'])
            ->when($request->filled('event_type_id'), fn ($q) => $q->where('event_type_id', $request->event_type_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('assigned_user_id'), fn ($q) => $q->where('assigned_user_id', $request->assigned_user_id))
            ->tap(fn ($q) => $this->applyDateRangeFilter($q, $selectedDateRange))
            ->orderBy('start_datetime')
            ->get();

        return response()->json($events->map(fn ($e) => $this->formatEvent($e)));
    }

    public function myEvents(Request $request)
    {
        $selectedDateRange = $request->date_range ?: 'today_tomorrow';

        $events = Event::with(['eventType', 'assignedUser.role'])
            ->where('assigned_user_id', auth()->id())
            ->when($request->filled('event_type_id'), fn ($q) => $q->where('event_type_id', $request->event_type_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->tap(fn ($q) => $this->applyDateRangeFilter($q, $selectedDateRange))
            ->orderBy('start_datetime')
            ->get();

        return response()->json($events->map(fn ($e) => $this->formatEvent($e)));
    }

    public function todos(Request $request)
    {
        $canViewAll = auth()->user()?->canViewAllEvents() || auth()->user()?->canManageEvents();
        abort_unless($canViewAll, 403);

        $selectedDateRange = $request->date_range ?: 'today_tomorrow';

        $events = Event::with(['eventType', 'assignedUser.role'])
            ->when($request->filled('event_type_id'), fn ($q) => $q->where('event_type_id', $request->event_type_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('assigned_user_id') && $canViewAll, fn ($q) => $q->where('assigned_user_id', $request->assigned_user_id))
            ->tap(fn ($q) => $this->applyDateRangeFilter($q, $selectedDateRange))
            ->orderBy('start_datetime')
            ->get();

        return response()->json($events->map(fn ($e) => $this->formatEvent($e)));
    }

    public function calendarEvents(Request $request)
    {
        $canView = auth()->user()?->canViewAllEvents() || auth()->user()?->canManageEvents();
        abort_unless($canView, 403);

        $events = Event::with(['eventType', 'assignedUser'])
            ->when(! auth()->user()?->canViewAllEvents(), fn ($q) => $q->where('assigned_user_id', auth()->id()))
            ->when($request->filled('event_type_id'), fn ($q) => $q->where('event_type_id', $request->event_type_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('assigned_user_id') && auth()->user()?->canViewAllEvents(), fn ($q) => $q->where('assigned_user_id', $request->assigned_user_id))
            ->orderBy('start_datetime')
            ->get();

        return response()->json($events->map(fn ($event) => [
            'id'             => (string) $event->id,
            'title'          => $event->title,
            'start'          => Carbon::parse($event->start_datetime)->toIso8601String(),
            'end'            => $event->end_datetime
                ? Carbon::parse($event->end_datetime)->toIso8601String()
                : Carbon::parse($event->start_datetime)->addHour()->toIso8601String(),
            'status'         => $event->status,
            'event_type'     => $event->eventType?->name,
            'assigned_user'  => $event->assignedUser?->name ?? 'Unassigned',
            'color'          => $this->calendarColorFor($event->assignedUser),
        ])->values());
    }

    public function eventTypes()
    {
        return response()->json(EventType::orderBy('name')->get(['id', 'name']));
    }

    public function show(Event $event)
    {
        $event->load(['eventType', 'assignedUser.role']);

        $canView = auth()->user()?->canViewAllEvents()
            || $event->assigned_user_id === auth()->id();

        abort_unless($canView, 403);

        return response()->json($this->formatEvent($event));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->canManageEvents(), 403);

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'event_type_id'   => ['required', 'exists:event_types,id'],
            'event_subtype'   => ['nullable', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'start_datetime'  => ['required', 'date'],
            'end_datetime'    => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'status'          => ['nullable', 'string', 'max:50'],
            'priority'        => ['nullable', 'string', 'max:50'],
            'location'        => ['nullable', 'string', 'max:255'],
            'assigned_user_id'=> ['nullable', 'exists:users,id'],
            'details'         => ['nullable', 'array'],
        ]);

        $validated = $this->normalizeDateTimes($validated);

        $event = Event::create([
            ...$validated,
            'created_by' => auth()->id(),
            'status'     => $validated['status'] ?? 'Scheduled',
            'priority'   => $validated['priority'] ?? 'Normal',
        ]);

        $event->load(['eventType', 'assignedUser.role']);

        return response()->json($this->formatEvent($event), 201);
    }

    public function update(Request $request, Event $event)
    {
        abort_unless(auth()->user()?->canManageEvents(), 403);

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'event_type_id'   => ['required', 'exists:event_types,id'],
            'event_subtype'   => ['nullable', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'start_datetime'  => ['required', 'date'],
            'end_datetime'    => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'status'          => ['nullable', 'string', 'max:50'],
            'priority'        => ['nullable', 'string', 'max:50'],
            'location'        => ['nullable', 'string', 'max:255'],
            'assigned_user_id'=> ['nullable', 'exists:users,id'],
            'details'         => ['nullable', 'array'],
        ]);

        $validated = $this->normalizeDateTimes($validated);
        $event->update($validated);
        $event->load(['eventType', 'assignedUser.role']);

        return response()->json($this->formatEvent($event));
    }

    public function markDone(Event $event)
    {
        abort_unless(auth()->user()?->canManageEvents(), 403);

        $wasCompleted = strtolower($event->status ?? '') === 'completed';
        $event->update(['status' => $wasCompleted ? 'Scheduled' : 'Completed']);
        $event->load(['eventType', 'assignedUser.role']);

        return response()->json($this->formatEvent($event));
    }

    public function destroy(Event $event)
    {
        abort_unless(auth()->user()?->canManageEvents(), 403);

        $event->delete();

        return response()->json(['message' => 'Deleted'], 200);
    }

    private function applyDateRangeFilter($query, ?string $dateRange): void
    {
        $today     = Carbon::today();
        $tomorrow  = $today->copy()->addDay();
        $endOfWeek = $today->copy()->endOfWeek();
        $endOfMonth = $today->copy()->endOfMonth();

        match ($dateRange) {
            'past'           => $query->where('start_datetime', '<', $today),
            'today'          => $query->whereDate('start_datetime', $today),
            'tomorrow'       => $query->whereDate('start_datetime', $tomorrow),
            'today_tomorrow' => $query->whereBetween('start_datetime', [
                $today->copy()->startOfDay(),
                $tomorrow->copy()->endOfDay(),
            ]),
            'this_week'  => $query->whereBetween('start_datetime', [
                $tomorrow->copy()->addDay()->startOfDay(),
                $endOfWeek,
            ]),
            'this_month' => $query->whereBetween('start_datetime', [
                $endOfWeek->copy()->addSecond(),
                $endOfMonth,
            ]),
            'rest'         => $query->where('start_datetime', '>', $endOfMonth),
            'today_forward'=> $query->where('start_datetime', '>=', $today->copy()->startOfDay()),
            default        => $query->whereBetween('start_datetime', [
                $today->copy()->startOfDay(),
                $tomorrow->copy()->endOfDay(),
            ]),
        };
    }

    private function normalizeDateTimes(array $data): array
    {
        $tz = 'America/New_York';
        if (! empty($data['start_datetime'])) {
            $data['start_datetime'] = Carbon::parse($data['start_datetime'], $tz)->format('Y-m-d H:i:s');
        }
        if (! empty($data['end_datetime'])) {
            $data['end_datetime'] = Carbon::parse($data['end_datetime'], $tz)->format('Y-m-d H:i:s');
        }
        return $data;
    }

    private function formatEvent(Event $event): array
    {
        return [
            'id'              => $event->id,
            'title'           => $event->title,
            'event_type_id'   => $event->event_type_id,
            'event_type'      => $event->eventType?->name,
            'event_subtype'   => $event->event_subtype,
            'description'     => $event->description,
            'start_datetime'  => $event->start_datetime,
            'end_datetime'    => $event->end_datetime,
            'status'          => $event->status,
            'priority'        => $event->priority,
            'location'        => $event->location,
            'details'         => $event->details,
            'assigned_user_id'=> $event->assigned_user_id,
            'assigned_user'   => $event->assignedUser ? [
                'id'   => $event->assignedUser->id,
                'name' => $event->assignedUser->name,
                'role' => $event->assignedUser->role?->name,
            ] : null,
            'color'           => $this->calendarColorFor($event->assignedUser),
            'created_at'      => $event->created_at,
        ];
    }

    private function calendarColorFor($user): string
    {
        if (! $user) return '#6b7280';
        $colors = ['#2563eb','#16a34a','#f97316','#7c3aed','#dc2626','#0891b2','#db2777','#65a30d','#4f46e5','#e11d48'];
        return $colors[($user->id - 1) % count($colors)];
    }
}
