
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Calendar') }}
        </h2>
    </x-slot>

    @php
        $calendarUsers = \App\Models\User::orderBy('name')->get();
        $calendarEvents = \App\Models\Event::with(['assignedUser', 'eventType'])
            ->orderBy('start_datetime')
            ->get();

        $userCalendarColors = $userCalendarColors ?? [
            'Camilo Calle' => '#60a5fa',
            'Juliana Restrepo' => '#4ade80',
            'Julianna Calle' => '#fb923c',
            'Laura Garcia' => '#a78bfa',
            'Santiago Calle' => '#f87171',
            'Unassigned' => '#9ca3af',
        ];

        foreach ($calendarUsers as $calendarUser) {
            $establishedColor = $userCalendarColors[$calendarUser->name]
                ?? data_get($calendarUser, 'calendar_color')
                ?? data_get($calendarUser, 'color')
                ?? data_get($calendarUser, 'user_color')
                ?? data_get($calendarUser, 'assigned_color')
                ?? '#9ca3af';

            $userCalendarColors[$calendarUser->name] = $establishedColor;
            $userCalendarColors[$calendarUser->id] = $establishedColor;
        }

        $userCalendarColors['Unassigned'] = $userCalendarColors['Unassigned'] ?? '#9ca3af';

        $eventTimezone = 'America/New_York';

        $toDashboardEventDateTime = function ($value) use ($eventTimezone) {
            return $value ? \Carbon\Carbon::parse($value, $eventTimezone) : null;
        };

        $calendarEventsByDate = $calendarEvents->groupBy(function ($event) use ($toDashboardEventDateTime) {
            return $event->start_datetime
                ? $toDashboardEventDateTime($event->start_datetime)->format('Y-m-d')
                : 'No Date';
        });

        $calendarDate = \Carbon\Carbon::today($eventTimezone);
        $calendarStart = $calendarDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $calendarStartHour = 0;
        $calendarEndHour = 24;
        $calendarTotalMinutes = ($calendarEndHour - $calendarStartHour) * 60;
        $eventNow = \Carbon\Carbon::now($eventTimezone);

        $weeklyCalendarEventsByDate = $calendarEvents
            ->filter(fn ($event) => $event->start_datetime)
            ->groupBy(fn ($event) => $toDashboardEventDateTime($event->start_datetime)->format('Y-m-d'));
    @endphp

    <div class="dashboard-calendar-page">
        <div class="dashboard-calendar-container max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="dashboard-calendar-shell bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="dashboard-calendar-content p-3">
                    <div class="dashboard-week-card bg-white shadow rounded-lg p-3 border border-gray-200">
                        <div class="dashboard-week-card-header">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">
                                    Weekly Calendar
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Current week schedule with event blocks shown by assigned user color.
                                </p>
                            </div>

                            <a href="{{ route('events.create') }}" class="dashboard-create-event-button">
                                Create Event
                            </a>
                        </div>

                        <div class="dashboard-calendar-main-row">
                        <div class="dashboard-week-calendar-wrapper">
                            <div class="dashboard-week-calendar-grid">
                                <div class="dashboard-week-time-header">
                                    Time
                                </div>

                                @for ($dayIndex = 0; $dayIndex < 7; $dayIndex++)
                                    @php
                                        $day = $calendarStart->copy()->addDays($dayIndex);
                                        $dayKey = $day->format('Y-m-d');
                                        $eventsForDay = $weeklyCalendarEventsByDate->get($dayKey, collect());
                                        $dayIsToday = $day->isSameDay($eventNow);
                                    @endphp

                                    <div class="dashboard-week-day-header {{ $dayIsToday ? 'dashboard-week-day-header-today' : '' }}">
                                        <div class="dashboard-week-day-name">
                                            {{ $day->format('D') }}
                                        </div>
                                        <div class="dashboard-week-day-date">
                                            {{ $day->format('M j') }}
                                        </div>
                                    </div>
                                @endfor

                                <div class="dashboard-week-time-column">
                                    @for ($hour = $calendarStartHour; $hour <= $calendarEndHour; $hour++)
                                        <div class="dashboard-week-time-slot">
                                            {{ \Carbon\Carbon::createFromTime($hour, 0)->format('g:i A') }}
                                        </div>
                                    @endfor
                                </div>

                                @for ($dayIndex = 0; $dayIndex < 7; $dayIndex++)
                                    @php
                                        $day = $calendarStart->copy()->addDays($dayIndex);
                                        $dayKey = $day->format('Y-m-d');
                                        $eventsForDay = $weeklyCalendarEventsByDate->get($dayKey, collect());
                                        $remindersForDay = $eventsForDay->filter(fn ($event) => strtolower($event->eventType?->name ?? '') === 'reminder');
                                        $timedEventsForDay = $eventsForDay->reject(fn ($event) => strtolower($event->eventType?->name ?? '') === 'reminder');
                                    @endphp

                                    <div class="dashboard-week-day-column">
                                        <div class="dashboard-week-reminder-strip">
                                            @foreach ($remindersForDay as $reminder)
                                                @php
                                                    $reminderStart = $toDashboardEventDateTime($reminder->start_datetime);
                                                    $reminderEnd = $reminder->end_datetime
                                                        ? $toDashboardEventDateTime($reminder->end_datetime)
                                                        : $reminderStart->copy()->addHour();
                                                    $reminderAssignedName = $reminder->assignedUser?->name ?? 'Unassigned';
                                                    $reminderColor = $userCalendarColors[$reminderAssignedName] ?? '#9ca3af';
                                                @endphp

                                                <button type="button"
                                                        class="dashboard-week-reminder-dot"
                                                        title="{{ $reminder->title }} | {{ $reminderStart->format('g:i A') }} - {{ $reminderEnd->format('g:i A') }}"
                                                        data-title="{{ $reminder->title }}"
                                                        data-type="{{ $reminder->eventType?->name ?? 'Reminder' }}"
                                                        data-assigned="{{ $reminderAssignedName }}"
                                                        data-status="{{ ucfirst($reminder->status ?? 'N/A') }}"
                                                        data-priority="{{ ucfirst($reminder->priority ?? 'N/A') }}"
                                                        data-location="{{ $reminder->location }}"
                                                        data-description="{{ $reminder->description }}"
                                                        data-start="{{ $reminderStart->format('g:i A') }}"
                                                        data-end="{{ $reminderEnd->format('g:i A') }}"
                                                        data-edit-url="{{ route('events.edit', $reminder) }}"
                                                        data-delete-url="{{ route('events.destroy', $reminder) }}"
                                                        data-event-id="{{ $reminder->id }}"
                                                        onmouseenter="highlightDashboardCalendarEvent('{{ $reminder->id }}')"
                                                        onmouseleave="unhighlightDashboardCalendarEvent('{{ $reminder->id }}')"
                                                        onclick="openDashboardEventDetails(this)">
                                                    <span class="dashboard-week-reminder-circle" style="background-color: {{ $reminderColor }};"></span>
                                                    <span class="dashboard-week-reminder-label">{{ $reminder->title }}</span>
                                                </button>
                                            @endforeach
                                        </div>

                                        @for ($hour = $calendarStartHour; $hour < $calendarEndHour; $hour++)
                                            <div class="dashboard-week-hour-line"></div>
                                        @endfor

                                        @foreach ($timedEventsForDay as $event)
                                            @php
                                                $eventStart = $toDashboardEventDateTime($event->start_datetime);
                                                $eventEnd = $event->end_datetime
                                                    ? $toDashboardEventDateTime($event->end_datetime)
                                                    : $eventStart->copy()->addHour();

                                                $dayStart = $day->copy()->setTime($calendarStartHour, 0);
                                                $dayEnd = $day->copy()->setTime($calendarEndHour, 0);
                                                $clampedStart = $eventStart->lt($dayStart) ? $dayStart : $eventStart;
                                                $clampedEnd = $eventEnd->gt($dayEnd) ? $dayEnd : $eventEnd;
                                                $startMinutes = max(0, $dayStart->diffInMinutes($clampedStart, false));
                                                $endMinutes = max($startMinutes + 15, $dayStart->diffInMinutes($clampedEnd, false));
                                                $eventTop = min(100, ($startMinutes / $calendarTotalMinutes) * 100);
                                                $eventHeight = max(8, min(100 - $eventTop, (($endMinutes - $startMinutes) / $calendarTotalMinutes) * 100));
                                                $assignedName = $event->assignedUser?->name ?? 'Unassigned';
                                                $eventColor = $userCalendarColors[$assignedName] ?? '#9ca3af';
                                                $calendarEventIsPast = $eventEnd->lessThan($eventNow) || $day->lt($eventNow->copy()->startOfDay());
                                            @endphp

                                            <button type="button"
                                                    class="dashboard-week-event-line"
                                                    title="{{ $event->title }} | {{ $eventStart->format('g:i A') }} - {{ $eventEnd->format('g:i A') }}"
                                                    style="top: {{ $eventTop }}%; height: {{ $eventHeight }}%; background-color: {{ $calendarEventIsPast ? '#9ca3af' : $eventColor }};"
                                                    data-title="{{ $event->title }}"
                                                    data-type="{{ $event->eventType?->name ?? 'Event' }}"
                                                    data-assigned="{{ $assignedName }}"
                                                    data-status="{{ ucfirst($event->status ?? 'N/A') }}"
                                                    data-priority="{{ ucfirst($event->priority ?? 'N/A') }}"
                                                    data-location="{{ $event->location }}"
                                                    data-description="{{ $event->description }}"
                                                    data-start="{{ $eventStart->format('g:i A') }}"
                                                    data-end="{{ $eventEnd->format('g:i A') }}"
                                                    data-edit-url="{{ route('events.edit', $event) }}"
                                                    data-delete-url="{{ route('events.destroy', $event) }}"
                                                    data-event-id="{{ $event->id }}"
                                                    onmouseenter="highlightDashboardCalendarEvent('{{ $event->id }}')"
                                                    onmouseleave="unhighlightDashboardCalendarEvent('{{ $event->id }}')"
                                                    onclick="openDashboardEventDetails(this)"></button>

                                            <button type="button"
                                                    class="dashboard-week-event-label {{ $calendarEventIsPast ? 'dashboard-week-event-past' : '' }}"
                                                    style="top: {{ $eventTop }}%;"
                                                    data-title="{{ $event->title }}"
                                                    data-type="{{ $event->eventType?->name ?? 'Event' }}"
                                                    data-assigned="{{ $assignedName }}"
                                                    data-status="{{ ucfirst($event->status ?? 'N/A') }}"
                                                    data-priority="{{ ucfirst($event->priority ?? 'N/A') }}"
                                                    data-location="{{ $event->location }}"
                                                    data-description="{{ $event->description }}"
                                                    data-start="{{ $eventStart->format('g:i A') }}"
                                                    data-end="{{ $eventEnd->format('g:i A') }}"
                                                    data-edit-url="{{ route('events.edit', $event) }}"
                                                    data-delete-url="{{ route('events.destroy', $event) }}"
                                                    data-event-id="{{ $event->id }}"
                                                    onmouseenter="highlightDashboardCalendarEvent('{{ $event->id }}')"
                                                    onmouseleave="unhighlightDashboardCalendarEvent('{{ $event->id }}')"
                                                    onclick="openDashboardEventDetails(this)">
                                                {{ $event->title }}
                                                <span>{{ $eventStart->format('g:i A') }} - {{ $eventEnd->format('g:i A') }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endfor
                            </div>
                        </div>
                            <div class="dashboard-color-key-card bg-white shadow rounded-lg border border-gray-200">
                                <div class="dashboard-color-key-header">
                                    <h3 class="text-xl font-bold text-gray-800">
                                        User Color Key
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Assigned users are identified by color.
                                    </p>
                                </div>

                                <div class="dashboard-color-key-pills">
                                    @foreach ($calendarUsers as $calendarUser)
                                        <button type="button"
                                                class="dashboard-color-key-pill"
                                                data-user-filter="{{ $calendarUser->name }}"
                                                onclick="toggleDashboardUserFilter(this)">
                                            <span style="width:12px; height:12px; border-radius:9999px; background-color:{{ $userCalendarColors[$calendarUser->name] ?? '#9ca3af' }}; display:inline-block;"></span>
                                            <span class="text-sm font-semibold text-gray-700">
                                                {{ $calendarUser->name }}
                                            </span>
                                        </button>
                                    @endforeach

                                    <button type="button"
                                            class="dashboard-color-key-pill"
                                            data-user-filter="Unassigned"
                                            onclick="toggleDashboardUserFilter(this)">
                                        <span style="width:12px; height:12px; border-radius:9999px; background-color:{{ $userCalendarColors['Unassigned'] ?? '#9ca3af' }}; display:inline-block;"></span>
                                        <span class="text-sm font-semibold text-gray-700">
                                            Unassigned
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div id="dashboardEventModal"
         style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9998; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#ffffff; width:100%; max-width:560px; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,.25); padding:24px;">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">
                        Event Details
                    </div>

                    <h3 id="dashboardEventModalTitle" style="font-size:22px; font-weight:900; color:#111827;"></h3>
                </div>

                <button type="button"
                        onclick="closeDashboardEventModal()"
                        style="background:#f3f4f6; color:#374151; width:36px; height:36px; border-radius:9999px; font-weight:900; border:1px solid #d1d5db; display:flex; align-items:center; justify-content:center;">
                    ×
                </button>
            </div>

            <div id="dashboardEventModalBody"></div>

            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:24px; padding-top:18px; border-top:1px solid #d1d5db;">
                <button type="button"
                        onclick="closeDashboardEventModal()"
                        style="background:#f3f4f6; color:#374151; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #d1d5db;">
                    Back
                </button>

                <a id="dashboardEventModalEdit"
                   href="#"
                   style="background-color:#fef3c7; color:#92400e; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fde68a; box-shadow:0 2px 6px rgba(146,64,14,.08); text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">
                    Edit
                </a>

                <button id="dashboardEventModalDelete"
                        type="button"
                        style="background-color:#fee2e2; color:#991b1b; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fecaca; box-shadow:0 2px 6px rgba(153,27,27,.08);">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <div id="dashboardDeleteModal"
         style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#ffffff; width:100%; max-width:420px; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,.25); padding:24px;">
            <h3 style="font-size:20px; font-weight:800; color:#111827; margin-bottom:10px;">
                Delete Event
            </h3>

            <p style="color:#4b5563; margin-bottom:22px;">
                Are you sure you want to delete this event? This action cannot be undone.
            </p>

            <form id="dashboardDeleteForm" method="POST" action="">
                @csrf
                @method('DELETE')

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button"
                            onclick="closeDashboardDeleteModal()"
                            style="background:#f3f4f6; color:#374151; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #d1d5db;">
                        Cancel
                    </button>

                    <button type="submit"
                            style="background-color:#fee2e2; color:#991b1b; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fecaca; box-shadow:0 2px 6px rgba(153,27,27,.08);">
                        Yes, Delete Event
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        body:has(.dashboard-calendar-page) {
            overflow: hidden;
        }

        .dashboard-calendar-page {
            height: calc(100dvh - 132px);
            max-height: calc(100dvh - 132px);
            overflow: hidden;
            background: transparent;
        }

        .dashboard-calendar-shell,
        .dashboard-calendar-content {
            height: 100%;
            max-height: 100%;
            min-height: 0;
        }

        .dashboard-calendar-container {
            height: 100%;
            max-height: 100%;
            min-height: 0;
            max-width: none !important;
            width: 100%;
        }

        .dashboard-calendar-shell {
            overflow: hidden !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .dashboard-calendar-content {
            display: flex;
            flex-direction: column;
            gap: 0;
            overflow: hidden;
            padding-top: 8px !important;
            padding-bottom: 8px !important;
            background: transparent !important;
        }

        .dashboard-week-card {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin-top: 12px;
            margin-bottom: 12px;
            padding: 18px !important;
        }

        .dashboard-week-card-header {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 8px;
        }

        .dashboard-calendar-main-row {
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
            display: flex;
            gap: 14px;
            overflow: hidden;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .dashboard-create-event-button {
            background-color: #2563eb;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(17,24,39,.10);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            white-space: nowrap;
        }

        .dashboard-color-key-card {
            flex: 0 0 240px;
            width: 240px;
            min-height: 0;
            height: 100%;
            padding: 10px;
            overflow: auto;
        }

        .dashboard-color-key-header {
            display: block;
            padding-bottom: 8px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .dashboard-color-key-pills {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .dashboard-color-key-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            border: 1px solid #d1d5db;
            background: #ffffff;
            padding: 7px 9px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(17,24,39,.08);
            white-space: nowrap;
            cursor: pointer;
            text-align: left;
            transition: background-color .15s ease, border-color .15s ease, opacity .15s ease, transform .15s ease, box-shadow .15s ease;
        }

        .dashboard-color-key-pill:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            transform: translateY(-1px);
        }

        .dashboard-color-key-pill.dashboard-user-filter-active {
            background: #eff6ff;
            border-color: #2563eb;
            box-shadow: 0 2px 8px rgba(37,99,235,.16);
        }

        .dashboard-color-key-pill.dashboard-user-filter-inactive {
            opacity: .55;
        }

        .dashboard-week-calendar-wrapper {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 0;
            overflow: auto;
            border: 1px solid #9ca3af;
            border-radius: 10px;
            background: #ffffff;
            padding: 0 10px 10px 10px;
        }

        .dashboard-week-calendar-grid {
            min-width: 1100px;
            display: grid;
            grid-template-columns: 95px repeat(7, minmax(135px, 1fr));
            grid-template-rows: 58px 1440px;
            background: #ffffff;
        }

        .dashboard-week-time-header,
        .dashboard-week-day-header {
            position: sticky;
            top: 0;
            z-index: 10;
            border-right:1px solid #9ca3af;
            border-bottom:1px solid #9ca3af;
            background:#f9fafb;
        }

        .dashboard-week-time-header {
            left: 0;
            z-index: 20;
        }

        .dashboard-week-time-header {
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:12px;
            font-weight:900;
            color:#4b5563;
            text-transform:uppercase;
            letter-spacing:.04em;
        }

        .dashboard-week-day-header {
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
        }

        .dashboard-week-day-header-today {
            background:#eff6ff;
        }

        .dashboard-week-day-name {
            color:#111827;
            font-weight:900;
            font-size:14px;
        }

        .dashboard-week-day-date {
            color:#6b7280;
            font-weight:800;
            font-size:12px;
            margin-top:3px;
        }

        .dashboard-week-time-column {
            position: sticky;
            left: 0;
            top: 58px;
            z-index: 8;
            border-right:1px solid #9ca3af;
            background:#f9fafb;
        }

        .dashboard-week-time-slot {
            height:60px;
            border-bottom:1px solid #d1d5db;
            display:flex;
            align-items:flex-start;
            justify-content:flex-end;
            padding:7px 10px;
            color:#6b7280;
            font-size:11px;
            font-weight:800;
        }

        .dashboard-week-day-column {
            position:relative;
            border-right:1px solid #9ca3af;
            background:#ffffff;
            height:1440px;
        }

        .dashboard-week-hour-line {
            height:60px;
            border-bottom:1px solid #d1d5db;
        }

        .dashboard-week-reminder-strip {
            position: sticky;
            top: 58px;
            height: 28px;
            min-height: 28px;
            margin-bottom: -28px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: rgba(255,255,255,.92);
            border-bottom: 1px solid #e5e7eb;
            z-index: 7;
            box-sizing: border-box;
        }

        .dashboard-week-reminder-dot {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            max-width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 9999px;
            background: #ffffff;
            box-shadow: 0 1px 4px rgba(17,24,39,.16);
            cursor: pointer;
            padding: 3px 7px 3px 4px;
            transition: transform .15s ease, filter .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .dashboard-week-reminder-circle {
            width: 14px;
            height: 14px;
            flex: 0 0 14px;
            border: 2px solid #ffffff;
            border-radius: 9999px;
            box-shadow: 0 1px 4px rgba(17,24,39,.24);
        }

        .dashboard-week-reminder-label {
            display: block;
            max-width: 92px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #111827;
            font-size: 11px;
            font-weight: 900;
            line-height: 1.1;
        }

        .dashboard-week-reminder-dot:hover,
        .dashboard-week-reminder-dot.dashboard-week-event-hover {
            background: #f3f4f6;
            filter: brightness(.98);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(17,24,39,.22);
        }

        .dashboard-week-event-line {
            position:absolute;
            left:8px;
            width:8px;
            border:0;
            padding:0;
            border-radius:9999px;
            box-shadow:0 2px 6px rgba(17,24,39,.18);
            z-index:4;
            cursor:pointer;
            transition:filter .15s ease, transform .15s ease, box-shadow .15s ease;
        }

        .dashboard-week-event-line:hover,
        .dashboard-week-event-line.dashboard-week-event-hover {
            filter:brightness(.9);
            transform:scaleX(1.35);
            box-shadow:0 3px 8px rgba(17,24,39,.24);
        }

        .dashboard-week-event-label {
            position:absolute;
            left:22px;
            right:8px;
            border:1px solid #d1d5db;
            background:#ffffff;
            border-radius:8px;
            box-shadow:0 2px 6px rgba(17,24,39,.10);
            padding:7px 8px;
            color:#111827;
            font-size:12px;
            font-weight:900;
            line-height:1.2;
            text-align:left;
            cursor:pointer;
            z-index:3;
            transition:background-color .15s ease, transform .15s ease;
        }

        .dashboard-week-event-label:hover,
        .dashboard-week-event-label.dashboard-week-event-hover {
            background:#f3f4f6;
            transform:translateY(-1px);
        }

        .dashboard-week-event-label span {
            display:block;
            color:#6b7280;
            font-size:11px;
            font-weight:800;
            margin-top:3px;
        }

        .dashboard-week-event-past {
            background:#e5e7eb !important;
            color:#374151 !important;
            border-color:#9ca3af !important;
        }

        .dashboard-week-event-past span {
            color:#4b5563 !important;
        }

        .dashboard-week-event-hidden {
            display: none !important;
        }

        .dashboard-event-modal-detail {
            margin-top:8px;
            color:#374151;
            font-size:14px;
        }

        .dashboard-event-modal-detail strong {
            color:#111827;
        }

        @media (max-width: 768px) {
            .dashboard-calendar-content {
                flex-direction: column;
            }

            .dashboard-week-card-header {
                align-items: stretch;
                flex-direction: column;
            }

            .dashboard-create-event-button {
                width: 100%;
            }

            .dashboard-calendar-main-row {
                flex-direction: column;
            }

            .dashboard-color-key-card {
                flex: 0 0 auto;
                width: 100%;
                max-width: none;
                max-height: 140px;
                height: auto;
            }

            .dashboard-color-key-pills {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .dashboard-color-key-pill {
                width: auto;
                border-radius: 9999px;
            }
        }
    </style>

    <script>
        function getDashboardSelectedUsers() {
            return Array.from(document.querySelectorAll('.dashboard-color-key-pill.dashboard-user-filter-active'))
                .map((button) => button.dataset.userFilter)
                .filter(Boolean);
        }

        function applyDashboardUserFilters() {
            const selectedUsers = getDashboardSelectedUsers();
            const hasActiveFilters = selectedUsers.length > 0;

            document.querySelectorAll('.dashboard-week-event-line, .dashboard-week-event-label, .dashboard-week-reminder-dot').forEach((eventElement) => {
                const assignedUser = eventElement.dataset.assigned || 'Unassigned';
                const shouldShow = !hasActiveFilters || selectedUsers.includes(assignedUser);

                eventElement.classList.toggle('dashboard-week-event-hidden', !shouldShow);
            });

            document.querySelectorAll('.dashboard-color-key-pill').forEach((button) => {
                button.classList.toggle('dashboard-user-filter-inactive', hasActiveFilters && !button.classList.contains('dashboard-user-filter-active'));
            });
        }

        function toggleDashboardUserFilter(button) {
            button.classList.toggle('dashboard-user-filter-active');
            applyDashboardUserFilters();
        }
        function highlightDashboardCalendarEvent(eventId) {
            document.querySelectorAll(`[data-event-id="${eventId}"]`).forEach((element) => {
                element.classList.add('dashboard-week-event-hover');
            });
        }

        function unhighlightDashboardCalendarEvent(eventId) {
            document.querySelectorAll(`[data-event-id="${eventId}"]`).forEach((element) => {
                element.classList.remove('dashboard-week-event-hover');
            });
        }

        function openDashboardDeleteModal(deleteUrl) {
            document.getElementById('dashboardDeleteForm').setAttribute('action', deleteUrl);
            document.getElementById('dashboardDeleteModal').style.display = 'flex';
        }

        function closeDashboardDeleteModal() {
            document.getElementById('dashboardDeleteModal').style.display = 'none';
            document.getElementById('dashboardDeleteForm').setAttribute('action', '');
        }

        function openDashboardEventDetails(button) {
            const modal = document.getElementById('dashboardEventModal');
            const title = document.getElementById('dashboardEventModalTitle');
            const body = document.getElementById('dashboardEventModalBody');
            const editButton = document.getElementById('dashboardEventModalEdit');
            const deleteButton = document.getElementById('dashboardEventModalDelete');

            title.textContent = button.dataset.title || 'Event Details';
            editButton.setAttribute('href', button.dataset.editUrl || '#');
            deleteButton.setAttribute('onclick', `closeDashboardEventModal(); openDashboardDeleteModal('${button.dataset.deleteUrl}')`);

            body.innerHTML = `
                <div class="dashboard-event-modal-detail"><strong>Type:</strong> ${button.dataset.type || 'Event'}</div>
                <div class="dashboard-event-modal-detail"><strong>Assigned:</strong> ${button.dataset.assigned || 'Unassigned'}</div>
                <div class="dashboard-event-modal-detail"><strong>Time:</strong> ${button.dataset.start || 'N/A'} - ${button.dataset.end || 'N/A'}</div>
                <div class="dashboard-event-modal-detail"><strong>Status:</strong> ${button.dataset.status || 'N/A'}</div>
                <div class="dashboard-event-modal-detail"><strong>Priority:</strong> ${button.dataset.priority || 'N/A'}</div>
                ${button.dataset.location ? `<div class="dashboard-event-modal-detail"><strong>Location:</strong> ${button.dataset.location}</div>` : ''}
                ${button.dataset.description ? `<div class="dashboard-event-modal-detail"><strong>Description:</strong> ${button.dataset.description}</div>` : ''}
            `;

            modal.style.display = 'flex';
        }

        function closeDashboardEventModal() {
            document.getElementById('dashboardEventModal').style.display = 'none';
        }
    </script>
</x-app-layout>
