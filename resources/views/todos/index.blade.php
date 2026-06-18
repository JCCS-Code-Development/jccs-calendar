<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            To-Do's
        </h2>
    </x-slot>

    <style>
        .events-user-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            align-items: start;
        }

        @media (min-width: 900px) {
            .events-user-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .events-user-grid-single {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            align-items: start;
        }

        .events-user-list-view {
            grid-template-columns: 1fr !important;
        }

        .events-user-list-view .events-user-card-clickable {
            min-height: auto !important;
        }

        .events-user-list-view .events-user-card-clickable > .px-5 {
            padding-bottom: 24px !important;
        }

        .events-user-list-view .events-user-card-clickable .bg-white\/85 {
            min-height: auto !important;
            margin-bottom: 12px !important;
        }

        .events-user-list-view .events-user-card-clickable .p-4.space-y-3 {
            padding: 10px 14px !important;
        }

        .events-user-list-view .events-user-card-clickable .events-event-card {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            padding: 10px 12px !important;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .events-user-list-view .events-user-card-clickable .events-event-card > .flex.justify-between {
            margin-bottom: 0 !important;
        }

        .events-user-list-view .events-user-card-clickable .events-card-title {
            margin: 2px 0 0 0 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .events-user-list-view .events-user-card-clickable .events-card-details {
            display: block !important;
        }

        .events-user-list-view .events-user-card-clickable .events-card-description {
            display: none !important;
        }

        .events-user-list-view .events-user-card-clickable .events-event-card > .events-card-details {
            min-width: 0;
        }

        .events-user-list-view .events-user-card-clickable .events-event-card > .flex.justify-center {
            margin-top: 0 !important;
            padding-top: 0 !important;
            border-top: 0 !important;
            justify-content: flex-end !important;
        }

        @media (max-width: 900px) {
            .events-user-list-view .events-user-card-clickable .events-event-card {
                grid-template-columns: 1fr;
            }
        }

        .events-user-card-clickable {
            transition: background-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .events-user-card-clickable:hover {
            background-color: color-mix(in srgb, var(--user-card-color, #f9fafb) 94%, #000 6%) !important;
            box-shadow: 0 12px 24px rgba(17, 24, 39, .14) !important;
            transform: translateY(-1px);
        }

        .events-event-card {
            transition: background-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .events-event-card .events-card-type {
            font-size: 14px !important;
            line-height: 1.1 !important;
        }

        .events-event-card .events-card-title {
            font-size: 17px !important;
            line-height: 1.2 !important;
        }

        .events-event-card .events-card-time-label {
            font-size: 16px !important;
            line-height: 1.05 !important;
        }

        .events-event-card .events-card-time-value {
            font-size: 14px !important;
            line-height: 1.1 !important;
        }

        .events-event-card .events-card-details {
            font-size: 14px !important;
            line-height: 1.3 !important;
        }

        .events-event-card .events-card-description {
            font-size: 13px !important;
            line-height: 1.3 !important;
        }

        .events-event-card .events-card-details span {
            font-size: 14px !important;
            line-height: 1.3 !important;
        }

        .events-event-card.events-event-card-past {
            background: #d1d5db !important;
            background-color: #d1d5db !important;
            border-color: #9ca3af !important;
            color: #374151 !important;
        }

        .events-event-card.events-event-card-past * {
            color: #374151 !important;
        }

        .events-user-card-clickable:hover .events-event-card {
            background-color: var(--event-card-color, #ffffff) !important;
            box-shadow: 0 4px 10px rgba(17, 24, 39, .10) !important;
        }

        .events-user-card-clickable:hover .events-event-card.events-event-card-past {
            background: #d1d5db !important;
            background-color: #d1d5db !important;
        }

        .todo-completed-text {
            text-decoration: line-through !important;
            text-decoration-thickness: 2px;
            opacity: .68;
        }

        .todo-check-form {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .todo-check-input {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            cursor: pointer;
            accent-color: #2563eb;
        }

        @media (min-width: 900px) {
            .events-user-grid-single {
                grid-template-columns: 1fr;
                max-width: none;
                width: 100%;
            }
        }

        .events-mini-calendar {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(17, 24, 39, .08);
            overflow: hidden;
            margin-bottom: 18px;
        }

        .events-mini-calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(to right, #ffffff, #f9fafb);
        }

        .events-week-user-key {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
            max-width: 52%;
        }

        .events-week-user-key-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #111827;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(17, 24, 39, .08);
        }

        .events-week-user-key-link:hover,
        .events-week-user-key-link-active {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .events-week-user-key-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
            flex: none;
        }

        @media (max-width: 900px) {
            .events-mini-calendar-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .events-week-user-key {
                justify-content: flex-start;
                max-width: 100%;
            }
        }

        .events-mini-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            overflow-x: hidden;
        }

        .events-mini-calendar-weekday {
            padding: 10px 8px;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .events-mini-calendar-day {
            min-height: 420px;
            padding: 8px;
            border-right: 1px solid #f3f4f6;
            border-bottom: 1px solid #f3f4f6;
            background: #ffffff;
        }

        .events-mini-calendar-day:nth-child(7n) {
            border-right: none;
        }

        .events-mini-calendar-day-today {
            background: #eff6ff;
            box-shadow: inset 0 0 0 2px #bfdbfe;
        }

        .events-mini-calendar-date {
            font-size: 12px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 8px;
        }

        .events-week-timeline {
            position: relative;
            min-height: 360px;
            margin-top: 10px;
            background: #ffffff;
            overflow: visible;
        }

        .events-week-time-label {
            display: none;
        }

        .events-week-event-line {
            position: absolute;
            left: 10px;
            width: 8px;
            border-radius: 999px;
            box-shadow: 0 1px 4px rgba(17, 24, 39, .20);
        }

        .events-week-event-label {
            position: absolute;
            left: 26px;
            right: 4px;
            transform: translateY(-2px);
            font-size: 10px;
            font-weight: 800;
            color: #111827;
            line-height: 1.15;
            background: rgba(255, 255, 255, .82);
            border-radius: 5px;
            padding: 3px 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .events-week-event-label span {
            display: block;
            font-size: 9px;
            font-weight: 700;
            color: #6b7280;
            margin-top: 1px;
        }

        .events-week-reminder-pill {
            position: relative;
            left: auto;
            transform: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: calc(100% - 16px);
            max-width: none;
            min-height: 32px;
            padding: 5px 12px;
            margin: 0 8px 6px 8px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 2px 6px rgba(15, 23, 42, .18);
            font-size: 12px;
            font-weight: 800;
            color: #111827;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            cursor: pointer;
        }

        .events-week-reminder-pill:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            box-shadow: 0 2px 6px rgba(15, 23, 42, .20);
        }

        .events-week-reminder-color-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .events-week-reminder-check-form {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: none;
            margin: 0;
        }

        .events-week-reminder-check-input {
            width: 15px;
            height: 15px;
            border-radius: 4px;
            cursor: pointer;
            accent-color: #2563eb;
        }

        .events-week-reminder-label {
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 12px;
            line-height: 1.15;
        }

        .events-week-priority-label {
            display: none;
        }

        .user-events-modal-day-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 12px;
        }

        .user-events-modal-day {
            min-height: 360px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .user-events-modal-day-header {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(to right, #ffffff, #f9fafb);
        }

        .user-events-modal-event {
            position: relative;
            padding: 12px 12px 12px 18px;
            border-bottom: 1px solid #f3f4f6;
        }

        .user-events-modal-event::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 12px;
            bottom: 12px;
            width: 6px;
            border-radius: 999px;
            background: var(--event-color, #6b7280);
        }

        @media (max-width: 1100px) {
            .user-events-modal-day-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @php
                $eventTimezone = 'America/New_York';
                $calendarDate = \Carbon\Carbon::today($eventTimezone);
                $calendarStart = $calendarDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                $calendarEnd = $calendarDate->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                $calendarStartHour = 0;
                $calendarEndHour = 24;
                $calendarTotalMinutes = ($calendarEndHour - $calendarStartHour) * 60;
                $calendarPixelsPerMinute = 1;
                $calendarTimelineHeight = $calendarTotalMinutes * $calendarPixelsPerMinute;
                $eventNow = \Carbon\Carbon::now($eventTimezone);

                $toEventDateTime = function ($value) use ($eventTimezone) {
                    return $value ? \Carbon\Carbon::parse($value, $eventTimezone) : null;
                };

                $eventsByDate = $events
                    ->groupBy(fn ($event) => $toEventDateTime($event->start_datetime)->format('Y-m-d'));

                $eventPrimaryDetail = function ($event) {
                    $details = is_array($event->details ?? null) ? $event->details : [];
                    $eventTypeName = strtolower($event->eventType?->name ?? '');

                    if ($eventTypeName === 'supplies') {
                        $items = collect($details['items'] ?? [])
                            ->map(function ($item) {
                                $name = $item['name'] ?? null;
                                $quantity = $item['quantity'] ?? null;

                                if (! $name && ! $quantity) {
                                    return null;
                                }

                                return trim(($name ?: 'Unnamed item') . ($quantity ? ' #' . $quantity : ''));
                            })
                            ->filter()
                            ->join(', ');

                        return ['label' => 'Item', 'value' => $items ?: 'N/A'];
                    }

                    if ($eventTypeName === 'communication') {
                        return ['label' => 'Person', 'value' => $details['person'] ?? $details['company'] ?? $details['participants'] ?? 'N/A'];
                    }

                    if ($eventTypeName === 'meeting') {
                        return ['label' => 'Participants', 'value' => $details['participants'] ?? $details['person'] ?? $details['company'] ?? 'N/A'];
                    }

                    if ($eventTypeName === 'logistics') {
                        return ['label' => 'Worker(s)', 'value' => $details['workers'] ?? $details['team_workers'] ?? 'N/A'];
                    }

                    if ($eventTypeName === 'site visit') {
                        return ['label' => 'Company / Project', 'value' => $details['company'] ?? $details['project'] ?? $details['person'] ?? 'N/A'];
                    }

                    if ($eventTypeName === 'estimate/invoice') {
                        return ['label' => 'Estimate / Invoice', 'value' => $details['invoice_or_estimate'] ?? $details['number'] ?? $details['name'] ?? 'N/A'];
                    }

                    if ($eventTypeName === 'payment') {
                        return ['label' => 'Payment', 'value' => $details['payment_name'] ?? $details['payment_document_number'] ?? $details['payment_amount'] ?? 'N/A'];
                    }

                    return ['label' => null, 'value' => null];
                };
            @endphp

            <div class="events-mini-calendar">
                <div class="events-mini-calendar-header">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-950">
                            Week of {{ $calendarStart->format('M d') }} - {{ $calendarEnd->format('M d, Y') }}
                        </h3>
                        <p class="text-xs font-semibold text-gray-500 mt-1">Each day is a blank priority space. To-do items are stacked by importance, not by exact time.</p>
                    </div>

                    @if (!($isMyEvents ?? false))
                        <div class="events-week-user-key" aria-label="User color key">
                            <a href="{{ route('events.todos', array_filter([
                                'event_type_id' => $selectedEventType,
                                'status' => $selectedStatus,
                                'date_range' => $selectedDateRange,
                            ], fn ($value) => $value !== null && $value !== '')) }}"
                               class="events-week-user-key-link {{ empty($selectedAssignedUser) ? 'events-week-user-key-link-active' : '' }}">
                                All
                            </a>

                            @foreach ($users as $keyUser)
                                <a href="{{ route('events.todos', array_filter([
                                    'event_type_id' => $selectedEventType,
                                    'status' => $selectedStatus,
                                    'date_range' => $selectedDateRange,
                                    'assigned_user_id' => $keyUser->id,
                                ], fn ($value) => $value !== null && $value !== '')) }}"
                                   class="events-week-user-key-link {{ (string) $selectedAssignedUser === (string) $keyUser->id ? 'events-week-user-key-link-active' : '' }}">
                                    <span class="events-week-user-key-dot" style="background-color: {{ $userCalendarColors[$keyUser->name] ?? '#6b7280' }};"></span>
                                    {{ $keyUser->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="events-mini-calendar-grid">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                        <div class="events-mini-calendar-weekday">{{ $weekday }}</div>
                    @endforeach

                    @for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay())
                        @php
                            $dateKey = $day->format('Y-m-d');
                            $prioritySortOrder = [
                                'urgent' => 0,
                                'high' => 1,
                                'medium' => 2,
                                'normal' => 3,
                                'low' => 4,
                            ];
                            $eventsForDay = $eventsByDate
                                ->get($dateKey, collect())
                                ->sortBy(fn ($event) => $prioritySortOrder[strtolower($event->priority ?? 'normal')] ?? 3)
                                ->values();
                        @endphp

                        <div class="events-mini-calendar-day {{ $day->isToday() ? 'events-mini-calendar-day-today' : '' }}">
                            <div class="events-mini-calendar-date">{{ $day->format('M j') }}</div>

                            <div class="events-week-timeline">

                                @foreach ($eventsForDay as $calendarEvent)
                                    @php
                                        $eventStart = $toEventDateTime($calendarEvent->start_datetime);
                                        $eventEnd = $calendarEvent->end_datetime
                                            ? $toEventDateTime($calendarEvent->end_datetime)
                                            : $eventStart->copy()->addHour();

                                        if ($eventEnd->lessThanOrEqualTo($eventStart)) {
                                            $eventEnd = $eventStart->copy()->addHour();
                                        }

                                        $dayStart = $day->copy()->setTime($calendarStartHour, 0);
                                        $dayEnd = $calendarEndHour >= 24
                                            ? $day->copy()->addDay()->startOfDay()
                                            : $day->copy()->setTime($calendarEndHour, 0);

                                        if (! $eventStart->isSameDay($eventEnd)) {
                                            $eventEnd = $eventStart->copy()->addHour();
                                        }

                                        $clampedStart = $eventStart->lt($dayStart) ? $dayStart : $eventStart;
                                        $clampedEnd = $eventEnd->gt($dayEnd) ? $dayEnd : $eventEnd;

                                        $startMinutes = max(0, min($calendarTotalMinutes, $dayStart->diffInMinutes($clampedStart, false)));
                                        $endMinutes = max($startMinutes + 15, min($calendarTotalMinutes, $dayStart->diffInMinutes($clampedEnd, false)));
                                        $durationMinutes = max(15, $endMinutes - $startMinutes);
                                        $eventTop = $startMinutes * $calendarPixelsPerMinute;
                                        $eventHeight = max(12, $durationMinutes * $calendarPixelsPerMinute);
                                        $calendarUserName = $calendarEvent->assignedUser?->name ?? 'Unassigned';
                                        $calendarEventColor = $userCalendarColors[$calendarUserName] ?? '#6b7280';
                                        $calendarEventIsPast = $eventEnd->lessThan($eventNow) || $day->lt($eventNow->copy()->startOfDay());
                                        $calendarEventIsCompleted = strtolower((string) $calendarEvent->status) === 'completed';
                                        $calendarEventIsReminder = strtolower($calendarEvent->eventType?->name ?? '') === 'reminder';
                                        $calendarEventIsAnyTimeTodo = ! $calendarEventIsReminder
                                            && $eventStart->format('H:i') === '00:00'
                                            && $calendarEvent->end_datetime
                                            && in_array($eventEnd->format('H:i'), ['23:58', '23:59'], true);
                                        $calendarEventShowsAtTop = $calendarEventIsReminder || $calendarEventIsAnyTimeTodo;
                                    @endphp

                                    <div class="events-week-reminder-pill {{ $calendarEventIsCompleted ? 'todo-completed-text' : '' }}"
                                         title="{{ $calendarEvent->title }} | {{ $calendarEvent->priority ?: 'Normal' }} priority"
                                         style="{{ $calendarEventIsPast ? 'opacity:.65;' : '' }}; cursor:pointer;"
                                         data-type="{{ $calendarEvent->eventType?->name ?? 'N/A' }}"
                                         data-subtype="{{ $calendarEvent->event_subtype }}"
                                         data-title="{{ $calendarEvent->title }}"
                                         data-description="{{ $calendarEvent->description }}"
                                         data-status="{{ $calendarEvent->status }}"
                                         data-priority="{{ $calendarEvent->priority }}"
                                         data-start="{{ $calendarEvent->start_datetime }}"
                                         data-end="{{ $calendarEvent->end_datetime }}"
                                         data-assigned-to="{{ $calendarEvent->assignedUser?->name ?? 'Unassigned' }}"
                                         data-location="{{ $calendarEvent->location }}"
                                         data-event-color="{{ $calendarEventColor }}"
                                         data-edit-url="{{ route('events.edit', $calendarEvent) }}"
                                         data-delete-url="{{ route('events.destroy', $calendarEvent) }}"
                                         data-mark-done-url="{{ route('events.mark-done', $calendarEvent) }}"
                                         onclick="openEventDetailsFromRow(this)">
                                        @if ($calendarEventIsReminder)
                                            <form method="POST"
                                                  action="{{ route('events.mark-done', $calendarEvent) }}"
                                                  class="events-week-reminder-check-form"
                                                  onclick="event.stopPropagation();">
                                                @csrf
                                                @method('PATCH')
                                                <input type="checkbox"
                                                       class="events-week-reminder-check-input"
                                                       title="{{ $calendarEventIsCompleted ? 'Undo done' : 'Mark done' }}"
                                                       @checked($calendarEventIsCompleted)
                                                       onchange="this.form.submit();">
                                            </form>
                                        @endif
                                        <span class="events-week-reminder-color-dot" style="background-color:{{ $calendarEventColor }};"></span>
                                        <span class="events-week-reminder-label">{{ $calendarEvent->title }}</span>
                                        <span class="events-week-priority-label">{{ $calendarEvent->priority ?: 'Normal' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-2xl font-extrabold text-gray-950">JCCS To-Do's</h3>
                    <p class="text-sm text-gray-500 mt-1">Manage reminders and assigned to-do items by assigned user and date range.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            onclick="toggleEventFilters()"
                            style="background-color:#ffffff; color:#111827; padding:12px 16px; border-radius:8px; font-weight:700; border:1px solid #d1d5db; box-shadow:0 8px 18px rgba(17,24,39,.08); display:flex; align-items:center; gap:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18l-7 8v6l-4 2v-8L3 4z" />
                        </svg>
                        Filter
                    </button>

                    <button id="eventsViewToggleButton"
                            type="button"
                            onclick="toggleEventsUserView()"
                            style="background-color:#e0f2fe; color:#075985; padding:12px 16px; border-radius:8px; font-weight:700; border:1px solid #7dd3fc; box-shadow:0 8px 18px rgba(14,165,233,.14); display:flex; align-items:center; gap:8px;">
                        List View
                    </button>

                    <a href="{{ route('events.create') }}" style="background-color:#2563eb; color:white; padding:12px 18px; border-radius:8px; font-weight:700; box-shadow:0 8px 18px rgba(37,99,235,.22);">
                        Create To-Do
                    </a>
                </div>
            </div>

            <div class="mb-6">
                <form id="eventFiltersPanel"
                      method="GET"
                      action="{{ route('events.todos') }}"
                      class="hidden bg-white border rounded-xl shadow-sm p-4">
                    <div class="grid grid-cols-1 {{ ($isMyEvents ?? false) ? 'md:grid-cols-4' : 'md:grid-cols-5' }} gap-4 items-end">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">To-Do Type</label>
                            <select name="event_type_id" class="w-full border-gray-300 rounded">
                                <option value="">All Types</option>
                                @foreach ($eventTypes as $eventType)
                                    <option value="{{ $eventType->id }}" @selected($selectedEventType == $eventType->id)>
                                        {{ $eventType->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full border-gray-300 rounded">
                                <option value="">All Statuses</option>
                                @foreach (['Scheduled', 'Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled', 'Rescheduled'] as $status)
                                    <option value="{{ $status }}" @selected($selectedStatus === $status)>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Date View</label>
                            <select name="date_range" class="w-full border-gray-300 rounded">
                                <option value="today_forward" @selected($selectedDateRange === 'today_forward')>Today Forward</option>
                                <option value="past" @selected($selectedDateRange === 'past')>Past</option>
                                <option value="today" @selected($selectedDateRange === 'today')>Today</option>
                                <option value="tomorrow" @selected($selectedDateRange === 'tomorrow')>Tomorrow</option>
                                <option value="this_week" @selected($selectedDateRange === 'this_week')>This Week</option>
                                <option value="this_month" @selected($selectedDateRange === 'this_month')>This Month</option>
                                <option value="rest" @selected($selectedDateRange === 'rest')>Rest</option>
                            </select>
                        </div>

                        @if (!($isMyEvents ?? false))
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Assigned To</label>
                                <select name="assigned_user_id" class="w-full border-gray-300 rounded">
                                    <option value="">All Users</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected($selectedAssignedUser == $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <button type="submit"
                                    style="background-color:#2563eb; color:white; padding:10px 16px; border-radius:6px; font-weight:600;">
                                Apply
                            </button>

                            <a href="{{ route('events.todos') }}"
                               style="background-color:#e5e7eb; color:#111827; padding:10px 16px; border-radius:6px; font-weight:600; text-decoration:none;">
                                Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div id="eventsUserViewGrid" class="{{ ($isMyEvents ?? false) ? 'events-user-grid-single' : 'events-user-grid' }}">
                @forelse ($eventGroups as $userName => $dateSections)
                    @php
                        $userEventCount = collect($dateSections)->flatten(2)->count();
                        $userModalEvents = collect($dateSections)
                            ->flatMap(fn ($days) => collect($days)->flatMap(fn ($dayEvents) => $dayEvents))
                            ->sortBy('start_datetime')
                            ->map(function ($event) use ($userCalendarColors, $eventTimezone, $eventPrimaryDetail) {
                                $assignedUserName = $event->assignedUser?->name ?? 'Unassigned';
                                $eventStartDateTime = \Carbon\Carbon::parse($event->start_datetime, $eventTimezone);
                                $eventEndDateTime = $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime, $eventTimezone) : null;
                                $primaryDetail = $eventPrimaryDetail($event);
                                $primaryDetailValue = $primaryDetail['value'];

                                $eventIsReminder = strtolower($event->eventType?->name ?? '') === 'reminder';
                                $isAnyTimeTodo = $eventIsReminder
                                    || ($eventStartDateTime->format('H:i') === '00:00'
                                        && $eventEndDateTime
                                        && in_array($eventEndDateTime->format('H:i'), ['23:58', '23:59'], true));

                                if (is_array($primaryDetailValue)) {
                                    $primaryDetailValue = collect($primaryDetailValue)->filter()->join(', ');
                                }

                                return [
                                    'type' => $event->eventType?->name ?? 'N/A',
                                    'subtype' => $event->event_subtype,
                                    'title' => $event->title,
                                    'primary_detail_label' => $primaryDetail['label'],
                                    'primary_detail_value' => $primaryDetailValue,
                                    'priority' => $event->priority,
                                    'location' => $event->location,
                                    'description' => $event->description,
                                    'status' => $event->status,
                                    'start' => $eventStartDateTime->toIso8601String(),
                                    'start_display' => $isAnyTimeTodo ? 'Any time' : $eventStartDateTime->format('g:i A'),
                                    'end' => $eventEndDateTime ? $eventEndDateTime->toIso8601String() : null,
                                    'end_display' => $isAnyTimeTodo ? '' : ($eventEndDateTime ? $eventEndDateTime->format('g:i A') : 'N/A'),
                                    'date_key' => $eventStartDateTime->format('Y-m-d'),
                                    'day_display' => $eventStartDateTime->format('D, M j'),
                                    'color' => $userCalendarColors[$assignedUserName] ?? '#6b7280',
                                ];
                            })
                            ->values();
                    @endphp

                    @if ($userEventCount > 0)
                        <section class="events-user-card-clickable border border-gray-300 shadow-lg overflow-hidden min-h-[360px] cursor-pointer"
                                 style="--user-card-color: {{ $userColors[$userName] ?? '#f9fafb' }}; background-color: var(--user-card-color); border-radius:8px; box-shadow:0 8px 18px rgba(17,24,39,.12);"
                                 data-user-name="{{ $userName }}"
                                 data-user-events='@json($userModalEvents)'
                                 onclick="openUserEventsModal(this)">
                            <div class="px-6 pt-6 pb-6 flex items-start justify-between">
                                <div>
                                    <h3 class="font-black text-gray-950 leading-tight" style="font-size:24px; line-height:1.05;">{{ $userName }}</h3>
                                    <p class="text-sm font-semibold text-blue-600 mt-2">{{ $userEventCount }} {{ Str::plural('event', $userEventCount) }}</p>
                                </div>
                            </div>

                            <div class="px-5 pb-12 space-y-4">
                                @foreach ($dateSections as $sectionTitle => $days)
                                    @php
                                        $sectionEventCount = collect($days)->flatten(1)->count();
                                    @endphp

                                    @if ($sectionEventCount > 0)
                                        <div class="bg-white/85 border border-gray-300 overflow-hidden shadow-sm flex flex-col min-h-[260px]" style="border-radius:8px; margin-bottom:12px;">
                                            <div class="px-5 py-4 border-b border-gray-300 bg-gradient-to-r from-white to-gray-50 shrink-0">
                                                <h4 class="font-extrabold text-gray-950 leading-tight" style="font-size:18px; line-height:1.1;">{{ $sectionTitle }}</h4>
                                                <span class="text-sm font-semibold text-blue-600">{{ $sectionEventCount }} {{ Str::plural('event', $sectionEventCount) }}</span>
                                            </div>

                                            <div class="p-4 space-y-3 overflow-y-auto flex-1">
                                                @foreach ($days as $dayLabel => $dayEvents)
                                                    <div>
                                                        <h5 class="text-[9px] font-extrabold text-gray-500 uppercase tracking-wide mb-3">{{ $dayLabel }}</h5>

                                                        <div class="space-y-3">
                                                            @foreach ($dayEvents as $event)
                                                                @php
                                                                    $eventStartDateTime = $event->start_datetime ? \Carbon\Carbon::parse($event->start_datetime, $eventTimezone) : null;
                                                                    $eventEndDateTime = $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime, $eventTimezone) : null;
                                                                    $eventPastCheckDateTime = $eventEndDateTime ?: $eventStartDateTime;
                                                                    $sectionIsPast = str_contains(strtolower($sectionTitle), 'past');
                                                                    $isCompletedTodo = strtolower((string) $event->status) === 'completed';
                                                                    $eventIsReminder = strtolower($event->eventType?->name ?? '') === 'reminder';
                                                                    $isPastEvent = $sectionIsPast || ($eventPastCheckDateTime && $eventPastCheckDateTime->lessThan($eventNow));
                                                                    $eventShouldUsePastStyle = $eventIsReminder ? $isCompletedTodo : $isPastEvent;
                                                                    $eventActionColor = $userCalendarColors[$event->assignedUser?->name ?? 'Unassigned'] ?? '#6b7280';
                                                                    $primaryDetail = $eventPrimaryDetail($event);
                                                                    $primaryDetailValue = $primaryDetail['value'];

                                                                    if (is_array($primaryDetailValue)) {
                                                                        $primaryDetailValue = collect($primaryDetailValue)->filter()->join(', ');
                                                                    }
                                                                @endphp

                                                                @php
                                                                    $eventIsAnyTimeTodo = $eventIsReminder
                                                                        || ($eventStartDateTime
                                                                            && $eventStartDateTime->format('H:i') === '00:00'
                                                                            && $eventEndDateTime
                                                                            && in_array($eventEndDateTime->format('H:i'), ['23:58', '23:59'], true));
                                                                @endphp

                                                                <div class="events-event-card {{ $eventShouldUsePastStyle ? 'events-event-card-past bg-gray-200 border-gray-400' : 'bg-white border-gray-300' }} border p-4 shadow-sm cursor-pointer w-full"
                                                                     style="--event-card-color: {{ $eventShouldUsePastStyle ? '#d1d5db' : '#ffffff' }}; border-radius:8px; {{ $eventShouldUsePastStyle ? 'background-color:#d1d5db !important; color:#374151;' : '' }}"
                                                                     data-type="{{ $event->eventType?->name ?? 'N/A' }}"
                                                                     data-subtype="{{ $event->event_subtype }}"
                                                                     data-title="{{ $event->title }}"
                                                                     data-description="{{ $event->description }}"
                                                                     data-status="{{ $event->status }}"
                                                                     data-priority="{{ $event->priority }}"
                                                                     data-start="{{ $event->start_datetime }}"
                                                                     data-end="{{ $event->end_datetime }}"
                                                                     data-assigned-to="{{ $event->assignedUser?->name ?? 'Unassigned' }}"
                                                                     data-location="{{ $event->location }}"
                                                                     data-event-color="{{ $eventActionColor }}"
                                                                     data-edit-url="{{ route('events.edit', $event) }}"
                                                                     data-delete-url="{{ route('events.destroy', $event) }}"
                                                                     onclick="event.stopPropagation(); openEventDetailsFromRow(this)">
                                                                    <div class="flex justify-between gap-3 mb-3 items-start">
                                                                        <div class="flex gap-3 items-start min-w-0 flex-1 pr-2">
                                                                            @if (!$eventIsReminder)
                                                                                <form method="POST" action="{{ route('events.mark-done', $event) }}" class="todo-check-form" onclick="event.stopPropagation();">
                                                                                    @csrf
                                                                                    @method('PATCH')
                                                                                    <input type="checkbox"
                                                                                           class="todo-check-input"
                                                                                           aria-label="{{ $isCompletedTodo ? 'Undo done' : 'Mark done' }} for {{ $event->title }}"
                                                                                           title="{{ $isCompletedTodo ? 'Undo done' : 'Mark done' }}"
                                                                                           @checked($isCompletedTodo)
                                                                                           onchange="this.form.submit()">
                                                                                </form>
                                                                            @endif

                                                                            <div class="min-w-0 flex-1">
                                                                                <div class="events-card-type-row mb-1">
                                                                                    @if ($eventIsReminder)
                                                                                        <form method="POST"
                                                                                              action="{{ route('events.mark-done', $event) }}"
                                                                                              class="events-card-reminder-check-form"
                                                                                              onclick="event.stopPropagation();">
                                                                                            @csrf
                                                                                            @method('PATCH')
                                                                                            <input type="checkbox"
                                                                                                   class="events-card-reminder-check-input"
                                                                                                   title="{{ $isCompletedTodo ? 'Undo done' : 'Mark done' }}"
                                                                                                   @checked($isCompletedTodo)
                                                                                                   onchange="this.form.submit();">
                                                                                        </form>
                                                                                    @endif

                                                                                    <p class="events-card-type font-extrabold {{ $eventShouldUsePastStyle ? 'text-gray-500' : 'text-blue-600' }} uppercase tracking-wide truncate {{ $isCompletedTodo ? 'todo-completed-text' : '' }}">{{ $event->eventType?->name ?? 'N/A' }}</p>
                                                                                </div>
                                                                                <p class="events-card-details truncate {{ $eventShouldUsePastStyle ? 'text-gray-500' : 'text-gray-700' }} {{ $isCompletedTodo ? 'todo-completed-text' : '' }}">
                                                                                    <span class="font-semibold text-gray-700">Sub-type:</span> {{ $event->event_subtype ?: 'N/A' }}
                                                                                </p>
                                                                                <h6 class="events-card-title font-semibold {{ $eventShouldUsePastStyle ? 'text-gray-700' : 'text-gray-950' }} mt-2 mb-4 truncate {{ $isCompletedTodo ? 'todo-completed-text' : '' }}">{{ $event->title }}</h6>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="events-card-details grid grid-cols-1 gap-1 {{ $eventShouldUsePastStyle ? 'text-gray-500' : 'text-gray-600' }} {{ $isCompletedTodo ? 'todo-completed-text' : '' }}">
                                                                        @if (!empty($primaryDetail['label']) && !empty($primaryDetailValue) && $primaryDetailValue !== 'N/A')
                                                                            <p class="truncate"><span class="font-semibold text-gray-700">{{ $primaryDetail['label'] }}:</span> {{ $primaryDetailValue }}</p>
                                                                        @endif
                                                                        <p class="truncate"><span class="font-semibold text-gray-700">Status:</span> {{ $event->status }}</p>
                                                                        <p class="truncate"><span class="font-semibold text-gray-700">Priority:</span> {{ $event->priority }}</p>
                                                                        <p class="truncate"><span class="font-semibold text-gray-700">Location:</span> {{ $event->location ?: 'No location provided' }}</p>
                                                                    </div>

                                                                    @if ($event->description)
                                                                        <p class="events-card-description {{ $eventShouldUsePastStyle ? 'text-gray-500' : 'text-gray-700' }} mt-2 line-clamp-2 {{ $isCompletedTodo ? 'todo-completed-text' : '' }}">{{ $event->description }}</p>
                                                                    @endif

                                                                    <div class="flex justify-center items-center gap-2 mt-3 border-t border-gray-300" style="padding-top:16px; padding-bottom:0;">
                                                                        <a href="{{ route('events.edit', $event) }}"
                                                                           onclick="event.stopPropagation()"
                                                                           style="background-color:#fef3c7; color:#92400e; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fde68a; box-shadow:0 2px 6px rgba(146,64,14,.08); text-decoration:none; font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                                                                            Edit
                                                                        </a>

                                                                        <button type="button"
                                                                                onclick="event.stopPropagation(); openDeleteModal('{{ route('events.destroy', $event) }}')"
                                                                                style="background-color:#fee2e2; color:#991b1b; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fecaca; box-shadow:0 2px 6px rgba(153,27,27,.08); font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                                                                            Delete
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    @endif
                @empty
                    <div class="bg-white border rounded-lg p-6 text-gray-600 shadow-sm col-span-full">
                        No to-do reminders found.
                    </div>
                @endforelse

                @if ($events->isEmpty())
                    <div class="bg-white border rounded-lg p-6 text-gray-600 shadow-sm col-span-full">
                        No current or upcoming to-do reminders found for the selected filters.
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div id="userEventsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9997; align-items:center; justify-content:center; padding:20px;">
        <div style="background:white; width:1200px; max-width:96vw; max-height:92vh; overflow:auto; border-radius:14px; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,.22);">
            <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:18px;">
                <div>
                    <p style="font-size:13px; font-weight:800; color:#2563eb; text-transform:uppercase; margin-bottom:4px;">User To-Do's</p>
                    <h2 id="userEventsModalTitle" style="font-size:26px; font-weight:900; color:#111827;"></h2>
                    <p id="userEventsModalWeek" style="font-size:13px; font-weight:700; color:#6b7280; margin-top:4px;"></p>
                </div>

                <button type="button"
                        onclick="closeUserEventsModal()"
                        style="background:#e5e7eb; color:#111827; padding:9px 14px; border-radius:8px; font-weight:700;">
                    Back
                </button>
            </div>

            <div id="userEventsModalGrid" class="user-events-modal-day-grid"></div>
        </div>
    </div>
    <div id="eventDetailsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9998; align-items:center; justify-content:center; padding:20px;">
        <div style="background:white; width:620px; max-width:95vw; border-radius:12px; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,.2);">
            <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:18px;">
                <div>
                    <p id="detailType" style="font-size:13px; font-weight:700; color:#2563eb; text-transform:uppercase; margin-bottom:4px;"></p>
                    <h2 id="detailTitle" style="font-size:24px; font-weight:800; color:#111827;"></h2>
                </div>

                <button type="button"
                        onclick="closeEventDetails()"
                        style="background:#e5e7eb; color:#111827; padding:8px 12px; border-radius:6px; font-weight:600;">
                    Back
                </button>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:18px;">
                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
                    <p style="font-size:12px; color:#6b7280; font-weight:700;">Status</p>
                    <p id="detailStatus" style="font-weight:700; color:#111827;"></p>
                </div>

                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
                    <p style="font-size:12px; color:#6b7280; font-weight:700;">Priority</p>
                    <p id="detailPriority" style="font-weight:700; color:#111827;"></p>
                </div>

                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
                    <p style="font-size:12px; color:#6b7280; font-weight:700;">Start</p>
                    <p id="detailStart" style="font-weight:700; color:#111827;"></p>
                </div>

                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
                    <p style="font-size:12px; color:#6b7280; font-weight:700;">Assigned To</p>
                    <p id="detailAssignedTo" style="font-weight:700; color:#111827;"></p>
                </div>

                <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px; grid-column:1 / -1;">
                    <p style="font-size:12px; color:#6b7280; font-weight:700;">Location</p>
                    <p id="detailLocation" style="font-weight:700; color:#111827;"></p>
                </div>
            </div>

            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin-bottom:22px;">
                <p style="font-size:12px; color:#6b7280; font-weight:700; margin-bottom:6px;">Description / Internal Notes</p>
                <p id="detailDescription" style="color:#111827; white-space:pre-wrap;"></p>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button"
                        onclick="closeEventDetails()"
                        style="background:#e5e7eb; color:#111827; padding:9px 14px; border-radius:6px; font-weight:600;">
                    Back
                </button>

                <a id="detailEditButton" href="#"
                   style="background-color:#fef3c7; color:#92400e; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fde68a; box-shadow:0 2px 6px rgba(146,64,14,.08); text-decoration:none; font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                    Edit To-Do
                </a>

                <button id="detailDeleteButton" type="button"
                        style="background-color:#fee2e2; color:#991b1b; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fecaca; box-shadow:0 2px 6px rgba(153,27,27,.08); font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                    Delete To-Do
                </button>
            </div>
        </div>
    </div>
    <div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; width:420px; border-radius:12px; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,.2);">
            <h2 style="font-size:20px; font-weight:700; margin-bottom:10px;">Confirm To-Do Deletion</h2>
            <p style="color:#4b5563; margin-bottom:24px;">
                This is a permanent action. Please confirm that you want to delete this to-do reminder from the JCCS schedule.
            </p>

            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button"
                            onclick="closeDeleteModal()"
                            style="background:#e5e7eb; color:#111827; padding:8px 14px; border-radius:6px;">
                        Cancel
                    </button>

                    <button type="submit"
                            style="background:#dc2626; color:white; padding:8px 14px; border-radius:6px;">
                        Yes, Delete To-Do
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function parseEventDateTimeForPastCheck(value) {
            if (!value) {
                return null;
            }

            const normalizedValue = String(value).replace(' ', 'T');
            const parsedDate = new Date(normalizedValue);

            if (Number.isNaN(parsedDate.getTime())) {
                return null;
            }

            return parsedDate;
        }

        function applyPastDueEventStyles() {
            const now = new Date();
            const todayStart = new Date(now);
            todayStart.setHours(0, 0, 0, 0);

            document.querySelectorAll('.events-event-card').forEach(function (card) {
                const endDate = parseEventDateTimeForPastCheck(card.dataset.end);
                const startDate = parseEventDateTimeForPastCheck(card.dataset.start);
                const checkDate = endDate || startDate;

                if (!checkDate) {
                    return;
                }

                const isReminder = (card.dataset.type || '').toLowerCase() === 'reminder';
                const isCompleted = (card.dataset.status || '').toLowerCase() === 'completed';
                const shouldUsePastStyle = isReminder ? isCompleted : checkDate < now;

                if (shouldUsePastStyle) {
                    card.classList.add('events-event-card-past', 'bg-gray-200', 'border-gray-400');
                    card.classList.remove('bg-white', 'border-gray-300');
                    card.style.setProperty('background', '#d1d5db', 'important');
                    card.style.setProperty('background-color', '#d1d5db', 'important');
                    card.style.setProperty('color', '#374151', 'important');
                    card.style.setProperty('--event-card-color', '#d1d5db');

                    card.querySelectorAll('*').forEach(function (child) {
                        child.style.setProperty('color', '#374151', 'important');
                    });
                } else {
                    card.classList.remove('events-event-card-past', 'bg-gray-200', 'border-gray-400');
                    card.classList.add('bg-white', 'border-gray-300');
                    card.style.removeProperty('background');
                    card.style.removeProperty('background-color');
                    card.style.removeProperty('color');
                    card.style.setProperty('--event-card-color', '#ffffff');

                    card.querySelectorAll('*').forEach(function (child) {
                        child.style.removeProperty('color');
                    });
                }
            });
        }

        function toggleEventFilters() {
            const panel = document.getElementById('eventFiltersPanel');
            panel.classList.toggle('hidden');
        }

        function setEventsUserView(isListView) {
            const grid = document.getElementById('eventsUserViewGrid');
            const button = document.getElementById('eventsViewToggleButton');

            if (!grid || !button) {
                return;
            }

            grid.classList.toggle('events-user-list-view', isListView);
            button.textContent = isListView ? 'Box View' : 'List View';
            localStorage.setItem('todosUserViewMode', isListView ? 'list' : 'boxes');
        }

        function toggleEventsUserView() {
            const grid = document.getElementById('eventsUserViewGrid');

            if (!grid) {
                return;
            }

            setEventsUserView(!grid.classList.contains('events-user-list-view'));
        }

        function formatModalDate(date) {
            return date.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric'
            });
        }

        function getCurrentWeekDays() {
            const today = new Date();
            const start = new Date(today);
            const dayOfWeek = today.getDay();
            const daysSinceMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;

            start.setDate(today.getDate() - daysSinceMonday);
            start.setHours(0, 0, 0, 0);

            return Array.from({ length: 7 }, function (_, index) {
                const day = new Date(start);
                day.setDate(start.getDate() + index);
                return day;
            });
        }

        function openUserEventsModal(section) {
            const userName = section.dataset.userName || 'User';
            const userEvents = JSON.parse(section.dataset.userEvents || '[]');
            const weekDays = getCurrentWeekDays();
            const weekStart = weekDays[0];
            const weekEnd = weekDays[6];
            const grid = document.getElementById('userEventsModalGrid');

            document.getElementById('userEventsModalTitle').textContent = userName;
            document.getElementById('userEventsModalWeek').textContent = `Week of ${formatModalDate(weekStart)} - ${formatModalDate(weekEnd)}`;
            grid.innerHTML = '';

            weekDays.forEach(function (day) {
                const dateKey = day.toISOString().slice(0, 10);
                const dayEvents = userEvents.filter(function (eventItem) {
                    return eventItem.date_key === dateKey;
                });

                const dayColumn = document.createElement('div');
                dayColumn.className = 'user-events-modal-day';

                const dayHeader = document.createElement('div');
                dayHeader.className = 'user-events-modal-day-header';
                dayHeader.innerHTML = `
                    <p style="font-size:12px; font-weight:900; color:#111827; text-transform:uppercase; letter-spacing:.04em;">${formatModalDate(day)}</p>
                    <p style="font-size:12px; font-weight:700; color:#2563eb; margin-top:3px;">${dayEvents.length} ${dayEvents.length === 1 ? 'to-do' : 'to-dos'}</p>
                `;
                dayColumn.appendChild(dayHeader);

                if (dayEvents.length === 0) {
                    const emptyMessage = document.createElement('div');
                    emptyMessage.style.padding = '14px';
                    emptyMessage.style.fontSize = '12px';
                    emptyMessage.style.fontWeight = '700';
                    emptyMessage.style.color = '#9ca3af';
                    emptyMessage.textContent = 'No to-do reminders scheduled.';
                    dayColumn.appendChild(emptyMessage);
                }

                dayEvents.forEach(function (eventItem) {
                    const eventBlock = document.createElement('div');
                    eventBlock.className = 'user-events-modal-event';
                    eventBlock.style.setProperty('--event-color', eventItem.color || '#6b7280');
                    eventBlock.innerHTML = `
                        <p style="font-size:10px; font-weight:900; color:#2563eb; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px;">${eventItem.type || 'N/A'}</p>
                        <p style="font-size:12px; font-weight:700; color:#374151; margin-bottom:4px;"><span style="color:#374151; font-weight:800;">Sub-type:</span> ${eventItem.subtype || 'N/A'}</p>
                        <h3 style="font-size:16px; font-weight:600; color:#111827; line-height:1.25; margin-bottom:12px;">${eventItem.title || 'Untitled To-Do'}</h3>
                        ${eventItem.primary_detail_label && eventItem.primary_detail_value && eventItem.primary_detail_value !== 'N/A' ? `<p style="font-size:12px; font-weight:700; color:#6b7280; margin-bottom:4px;"><span style="color:#374151; font-weight:800;">${eventItem.primary_detail_label}:</span> ${eventItem.primary_detail_value}</p>` : ''}
                        <p style="font-size:12px; font-weight:700; color:#6b7280; margin-bottom:4px;"><span style="color:#374151; font-weight:800;">Time:</span> ${eventItem.start_display === 'Any time' ? 'Any time' : `${eventItem.start_display || 'N/A'} - ${eventItem.end_display || 'N/A'}`}</p>
                        <p style="font-size:12px; font-weight:700; color:#6b7280; margin-bottom:4px;"><span style="color:#374151; font-weight:800;">Status:</span> ${eventItem.status || 'N/A'}</p>
                        <p style="font-size:12px; font-weight:700; color:#6b7280; margin-bottom:4px;"><span style="color:#374151; font-weight:800;">Priority:</span> ${eventItem.priority || 'N/A'}</p>
                        <p style="font-size:12px; font-weight:700; color:#6b7280; margin-bottom:4px;"><span style="color:#374151; font-weight:800;">Location:</span> ${eventItem.location || 'No location provided'}</p>
                        <p style="font-size:12px; font-weight:700; color:#6b7280; margin-bottom:4px;"><span style="color:#374151; font-weight:800;">Description:</span> ${eventItem.description || 'No description provided'}</p>
                    `;
                    dayColumn.appendChild(eventBlock);
                });

                grid.appendChild(dayColumn);
            });

            document.getElementById('userEventsModal').style.display = 'flex';
        }

        function closeUserEventsModal() {
            document.getElementById('userEventsModal').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            applyPastDueEventStyles();
            setEventsUserView(localStorage.getItem('todosUserViewMode') === 'list');
        });

        function openEventDetailsFromRow(element) {
            openEventDetails({
                type: element.dataset.type,
                subtype: element.dataset.subtype,
                title: element.dataset.title,
                description: element.dataset.description,
                status: element.dataset.status,
                priority: element.dataset.priority,
                start: element.dataset.start,
                end: element.dataset.end,
                assignedTo: element.dataset.assignedTo,
                location: element.dataset.location,
                editUrl: element.dataset.editUrl,
                deleteUrl: element.dataset.deleteUrl,
                eventColor: element.dataset.eventColor,
            });
        }

        function openEventDetails(eventData) {
            document.getElementById('detailType').textContent = eventData.subtype ? `${eventData.type || 'N/A'} - ${eventData.subtype}` : (eventData.type || 'N/A');
            document.getElementById('detailTitle').textContent = eventData.title || 'Untitled To-Do';
            document.getElementById('detailDescription').textContent = eventData.description || 'No description provided.';
            document.getElementById('detailStatus').textContent = eventData.status || 'N/A';
            document.getElementById('detailPriority').textContent = eventData.priority || 'N/A';
            const eventTypeName = (eventData.type || '').toLowerCase();
            const eventStartValue = String(eventData.start || '');
            const eventEndValue = String(eventData.end || '');
            const isAllDayReminder = eventTypeName === 'reminder' && eventStartValue.includes('00:00');
            const isAnyTimeTodo = eventStartValue.includes('00:00:00') && (eventEndValue.includes('23:58') || eventEndValue.includes('23:59'));

            document.getElementById('detailStart').textContent = (isAllDayReminder || isAnyTimeTodo) ? 'Any time' : (eventData.start || 'N/A');
            document.getElementById('detailAssignedTo').textContent = eventData.assignedTo || 'Unassigned';
            document.getElementById('detailLocation').textContent = eventData.location || 'No location provided.';
            document.getElementById('detailEditButton').setAttribute('href', eventData.editUrl);
            const detailDeleteButton = document.getElementById('detailDeleteButton');
            detailDeleteButton.onclick = function () {
                closeEventDetails();
                openDeleteModal(eventData.deleteUrl);
            };
            document.getElementById('eventDetailsModal').style.display = 'flex';
        }

        function closeEventDetails() {
            document.getElementById('eventDetailsModal').style.display = 'none';
        }

        function openDeleteModal(actionUrl) {
            document.getElementById('deleteForm').setAttribute('action', actionUrl);
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
    </script>
</x-app-layout>