<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            All Events
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
            overflow: hidden;
        }

        .events-card-reminder-check-form {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 8px;
        }

        .events-card-reminder-check-input {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            cursor: pointer;
            accent-color: #2563eb;
        }

        .events-card-type-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .events-card-reminder-check-form {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-right: 8px;
        }

        .events-card-reminder-check-input {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            cursor: pointer;
            accent-color: #2563eb;
        }

        .events-card-type-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .events-event-card .events-card-type {
            font-size: clamp(10px, 1.1vw, 14px) !important;
            line-height: 1.1 !important;
        }

        .events-event-card .events-card-title {
            font-size: clamp(12px, 1.35vw, 17px) !important;
            line-height: 1.18 !important;
        }

        .events-event-card .events-card-time-label {
            font-size: clamp(9px, 1vw, 16px) !important;
            line-height: 1.05 !important;
        }

        .events-event-card .events-card-time-value {
            font-size: clamp(10px, 1.05vw, 14px) !important;
            line-height: 1.1 !important;
        }

        .events-event-card .events-card-details {
            font-size: clamp(10px, 1.05vw, 14px) !important;
            line-height: 1.28 !important;
        }

        .events-event-card .events-card-description {
            font-size: clamp(10px, 1vw, 13px) !important;
            line-height: 1.28 !important;
        }

        .events-event-card .events-card-details span {
            font-size: clamp(10px, 1.05vw, 14px) !important;
            line-height: 1.28 !important;
        }

        .events-event-card .text-right.shrink-0 {
            min-width: clamp(52px, 6vw, 74px) !important;
            max-width: clamp(52px, 6vw, 74px) !important;
            overflow: hidden;
        }

        .events-event-card .flex.justify-between {
            gap: clamp(8px, 1vw, 12px) !important;
        }

        .events-event-card button,
        .events-event-card a {
            font-size: clamp(11px, 1vw, 13px) !important;
            padding: clamp(7px, .9vw, 10px) clamp(12px, 1.2vw, 16px) !important;
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

        .events-event-card.events-event-completed p,
        .events-event-card.events-event-completed h6,
        .events-event-card.events-event-completed span,
        .events-week-event-label.events-event-completed .events-week-event-title-text,
        .events-week-event-label.events-event-completed .events-week-event-time-text,
        .events-week-reminder-pill.events-event-completed .events-week-reminder-label,
        .user-events-modal-event.events-event-completed p,
        .user-events-modal-event.events-event-completed h3,
        .user-events-modal-event.events-event-completed span {
            text-decoration: line-through !important;
        }

        .events-event-completed,
        .events-event-completed * {
            text-decoration-color: currentColor;
        }

        .events-user-card-clickable:hover .events-event-card {
            background-color: var(--event-card-color, #ffffff) !important;
            box-shadow: 0 4px 10px rgba(17, 24, 39, .10) !important;
        }

        .events-user-card-clickable:hover .events-event-card.events-event-card-past {
            background: #d1d5db !important;
            background-color: #d1d5db !important;
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
            max-height: 285px;
            overflow-y: auto;
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
            min-height: 1490px;
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
            height: 1440px;
            margin-top: 10px;
            border-left: 1px solid #e5e7eb;
            background: repeating-linear-gradient(
                to bottom,
                #ffffff 0,
                #ffffff 59px,
                #f3f4f6 60px
            );
            overflow: visible;
        }

        .events-week-time-label {
            position: absolute;
            left: 6px;
            transform: translateY(-50%);
            font-size: 9px;
            font-weight: 800;
            color: #9ca3af;
            background: rgba(255, 255, 255, .85);
            padding-right: 3px;
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
            right: 8px;
            transform: translateY(-2px);
            font-size: 10px;
            font-weight: 800;
            color: #111827;
            line-height: 1.15;
            background: rgba(255, 255, 255, .88);
            border-radius: 7px;
            padding: 4px 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            box-shadow: 0 2px 6px rgba(17, 24, 39, .12);
        }

        .events-week-event-label span {
            display: block;
            font-size: 9px;
            font-weight: 700;
            color: #6b7280;
            margin-top: 1px;
        }

        .events-week-event-type-text {
            display: block;
            font-size: 8px !important;
            font-weight: 900 !important;
            color: #2563eb !important;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .events-week-reminder-pill {
            position: absolute;
            left: 8px;
            right: 8px;
            transform: translateY(-2px);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: auto;
            max-width: none;
            min-height: 32px;
            padding: 5px 12px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 2px 6px rgba(15, 23, 42, .18);
            font-size: 10px;
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

        .events-week-reminder-pill.events-event-completed {
            background: #d1d5db !important;
            background-color: #d1d5db !important;
            border-color: #9ca3af !important;
            color: #374151 !important;
            opacity: 1 !important;
        }

        .events-week-reminder-pill.events-event-completed .events-week-reminder-label {
            color: #374151 !important;
        }

        .events-week-reminder-color-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            display: inline-block;
            flex: none;
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
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 800;
            color: #111827;
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

                if (($selectedDateRange ?? null) === 'past' && $events->isNotEmpty()) {
                    $calendarDate = $events
                        ->sortByDesc('start_datetime')
                        ->first()
                        ?->start_datetime
                            ? \Carbon\Carbon::parse($events->sortByDesc('start_datetime')->first()->start_datetime, $eventTimezone)
                            : $calendarDate;
                }
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
                        return ['label' => 'Person', 'value' => $details['person'] ?? $details['company'] ?? $details['participants'] ?? $event->assignedUser?->name ?? 'N/A'];
                    }

                    if ($eventTypeName === 'meeting') {
                        return ['label' => 'Participants', 'value' => $details['participants'] ?? $details['person'] ?? $details['company'] ?? $event->assignedUser?->name ?? 'N/A'];
                    }

                    if ($eventTypeName === 'logistics') {
                        return ['label' => 'Worker(s)', 'value' => $details['workers'] ?? $details['team_workers'] ?? $event->assignedUser?->name ?? 'N/A'];
                    }

                    if ($eventTypeName === 'site visit') {
                        return ['label' => 'Company / Project', 'value' => $details['company'] ?? $details['project'] ?? $details['person'] ?? $event->assignedUser?->name ?? 'N/A'];
                    }

                    if ($eventTypeName === 'estimate/invoice') {
                        return ['label' => 'Estimate / Invoice', 'value' => $details['invoice_or_estimate'] ?? $details['number'] ?? $details['name'] ?? 'N/A'];
                    }

                    if ($eventTypeName === 'payment') {
                        return ['label' => 'Payment', 'value' => $details['payment_name'] ?? $details['payment_document_number'] ?? $details['payment_amount'] ?? 'N/A'];
                    }

                    if ($eventTypeName === 'reminder') {
                        return ['label' => null, 'value' => null];
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
                        <p class="text-xs font-semibold text-gray-500 mt-1">
                            @if (($selectedDateRange ?? null) === 'past')
                                Showing the most recent week with past history from the selected results.
                            @else
                                Colored vertical bars show each event time slot from start to end time.
                            @endif
                        </p>
                    </div>

                    @if (!($isMyEvents ?? false))
                        <div class="events-week-user-key" aria-label="User color key">
                            <a href="{{ route('events.index', array_filter([
                                'event_type_id' => $selectedEventType,
                                'status' => $selectedStatus,
                                'date_range' => $selectedDateRange,
                            ], fn ($value) => $value !== null && $value !== '')) }}"
                               class="events-week-user-key-link {{ empty($selectedAssignedUser) ? 'events-week-user-key-link-active' : '' }}">
                                All
                            </a>

                            @foreach ($users as $keyUser)
                                <a href="{{ route('events.index', array_filter([
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
                    @else
                        <div class="events-week-user-key" aria-label="User color key">
                            <a href="{{ route('events.mine', array_filter([
                                'event_type_id' => $selectedEventType,
                                'status' => $selectedStatus,
                                'date_range' => $selectedDateRange,
                            ], fn ($value) => $value !== null && $value !== '')) }}"
                               class="events-week-user-key-link events-week-user-key-link-active">
                                <span class="events-week-user-key-dot" style="background-color: {{ $userCalendarColors[auth()->user()->name] ?? '#6b7280' }};"></span>
                                {{ auth()->user()->name }}
                            </a>
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
                            $eventsForDay = $eventsByDate->get($dateKey, collect());
                        @endphp

                        <div class="events-mini-calendar-day {{ $day->isToday() ? 'events-mini-calendar-day-today' : '' }}">
                            <div class="events-mini-calendar-date">{{ $day->format('M j') }}</div>

                            <div class="events-week-timeline" style="height: {{ $calendarTimelineHeight }}px;">
                                @foreach ([0 => '12a', 3 => '3a', 6 => '6a', 9 => '9a', 12 => '12p', 15 => '3p', 18 => '6p', 21 => '9p'] as $hour => $label)
                                    @php
                                        $labelTop = (($hour - $calendarStartHour) * 60) * $calendarPixelsPerMinute;
                                    @endphp
                                    <span class="events-week-time-label" style="top: {{ $labelTop }}px;">{{ $label }}</span>
                                @endforeach

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
                                        $endMinutes = max($startMinutes + 1, min($calendarTotalMinutes, $dayStart->diffInMinutes($clampedEnd, false)));
                                        $durationMinutes = max(1, $endMinutes - $startMinutes);
                                        $eventTop = $startMinutes * $calendarPixelsPerMinute;
                                        $eventHeight = max(1, $durationMinutes * $calendarPixelsPerMinute);
                                        $calendarUserName = $calendarEvent->assignedUser?->name ?? 'Unassigned';
                                        $calendarEventColor = $userCalendarColors[$calendarUserName] ?? '#6b7280';
                                        $calendarEventIsPast = $eventEnd->lessThan($eventNow) || $day->lt($eventNow->copy()->startOfDay());
                                        $calendarEventIsCompleted = strtolower($calendarEvent->status ?? '') === 'completed';
                                        $calendarEventIsReminder = strtolower($calendarEvent->eventType?->name ?? '') === 'reminder';
                                        $calendarEventIsAnyTimeTodo = ! $calendarEventIsReminder
                                            && $eventStart->format('H:i') === '00:00'
                                            && $calendarEvent->end_datetime
                                            && in_array($eventEnd->format('H:i'), ['23:58', '23:59'], true);
                                        $calendarEventShowsAtTop = $calendarEventIsReminder || $calendarEventIsAnyTimeTodo;
                                    @endphp

                                    @if ($calendarEventShowsAtTop)
                                        <div class="events-week-reminder-pill {{ $calendarEventIsCompleted ? 'events-event-completed' : '' }}"
                                             style="top: {{ $eventTop }}px;"
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
                                             data-details='@json($calendarEvent->details ?? [])'
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
                                        </div>
                                    @else
                                        <div class="events-week-event-line"
                                             title="{{ $calendarEvent->title }} | {{ $eventStart->format('g:i A') }} - {{ $eventEnd->format('g:i A') }}"
                                             style="top: {{ $eventTop }}px; height: {{ $eventHeight }}px; background-color: {{ $calendarEventColor }}; {{ $calendarEventIsPast ? 'opacity:.65;' : '' }}"></div>

                                        <div class="events-week-event-label {{ $calendarEventIsCompleted ? 'events-event-completed' : '' }}" style="top: {{ $eventTop }}px; {{ $calendarEventIsPast ? 'background:#e5e7eb; color:#374151;' : '' }}">
                                            <span class="events-week-event-type-text">
                                                {{ $calendarEvent->eventType?->name ?? 'N/A' }}{{ $calendarEvent->event_subtype ? ' - ' . $calendarEvent->event_subtype : '' }}
                                            </span>
                                            <span class="events-week-event-title-text">{{ $calendarEvent->title }}</span>
                                            <span class="events-week-event-time-text">{{ $eventStart->format('g:i A') }} - {{ $eventEnd->format('g:i A') }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-2xl font-extrabold text-gray-950">JCCS Schedule Events</h3>
                    <p class="text-sm text-gray-500 mt-1">Manage scheduled work by assigned user and date range.</p>
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
                        Create Event
                    </a>
                </div>
            </div>

            <div class="mb-6">
                <form id="eventFiltersPanel"
                      method="GET"
                      action="{{ ($isMyEvents ?? false) ? route('events.mine') : route('events.index') }}"
                      class="hidden bg-white border rounded-xl shadow-sm p-4">
                    <div class="grid grid-cols-1 {{ ($isMyEvents ?? false) ? 'md:grid-cols-4' : 'md:grid-cols-5' }} gap-4 items-end">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Event Type</label>
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
                                <option value="today_tomorrow" @selected($selectedDateRange === 'today_tomorrow')>Today & Tomorrow</option>
                                <option value="today_forward" @selected($selectedDateRange === 'today_forward')>All Upcoming</option>
                                <option value="past" @selected($selectedDateRange === 'past')>Past History</option>
                                <option value="today" @selected($selectedDateRange === 'today')>Today</option>
                                <option value="tomorrow" @selected($selectedDateRange === 'tomorrow')>Tomorrow</option>
                                <option value="this_week" @selected($selectedDateRange === 'this_week')>This Week</option>
                                <option value="this_month" @selected($selectedDateRange === 'this_month')>This Month</option>
                                <option value="rest" @selected($selectedDateRange === 'rest')>Future After This Month</option>
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

                            <a href="{{ ($isMyEvents ?? false) ? route('events.mine') : route('events.index') }}"
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
                                    'is_completed' => strtolower($event->status ?? '') === 'completed',
                                    'start' => $eventStartDateTime->toIso8601String(),
                                    'start_display' => $eventStartDateTime->format('g:i A'),
                                    'end' => $eventEndDateTime ? $eventEndDateTime->toIso8601String() : null,
                                    'end_display' => $eventEndDateTime ? $eventEndDateTime->format('g:i A') : 'N/A',
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
                                                                    $isPastEvent = $sectionIsPast || ($eventPastCheckDateTime && $eventPastCheckDateTime->lessThan($eventNow));
                                                                    $eventActionColor = $userCalendarColors[$event->assignedUser?->name ?? 'Unassigned'] ?? '#6b7280';
                                                                    $eventIsCompleted = strtolower($event->status ?? '') === 'completed';
                                                                    $eventIsReminder = strtolower($event->eventType?->name ?? '') === 'reminder';
                                                                    $eventShouldUsePastStyle = $eventIsReminder ? $eventIsCompleted : $isPastEvent;
                                                                    $eventIsAnyTimeTodo = $eventStartDateTime
                                                                        && ! $eventIsReminder
                                                                        && $eventStartDateTime->format('H:i') === '00:00'
                                                                        && $eventEndDateTime
                                                                        && in_array($eventEndDateTime->format('H:i'), ['23:58', '23:59'], true);
                                                                    $primaryDetail = $eventPrimaryDetail($event);
                                                                    $primaryDetailValue = $primaryDetail['value'];

                                                                    if (is_array($primaryDetailValue)) {
                                                                        $primaryDetailValue = collect($primaryDetailValue)->filter()->join(', ');
                                                                    }
                                                                @endphp

                                                                <div class="events-event-card {{ $eventIsCompleted ? 'events-event-completed' : '' }} {{ $eventShouldUsePastStyle ? 'events-event-card-past bg-gray-200 border-gray-400' : 'bg-white border-gray-300' }} border p-4 shadow-sm cursor-pointer w-full"
                                                                     style="--event-card-color: {{ $eventShouldUsePastStyle ? '#d1d5db' : '#ffffff' }}; border-radius:8px; {{ $eventShouldUsePastStyle ? 'background-color:#d1d5db !important; color:#374151;' : '' }}"
                                                                     data-type="{{ $event->eventType?->name ?? 'N/A' }}"
                                                                     data-subtype="{{ $event->event_subtype ?? '' }}"
                                                                     data-details='@json($event->details ?? [])'
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
                                                                     data-mark-done-url="{{ route('events.mark-done', $event) }}"
                                                                     onclick="event.stopPropagation(); openEventDetailsFromRow(this)">
                                                                    <div class="flex justify-between gap-3 mb-3 items-start">
                                                                        <div class="min-w-0 flex-1 pr-2">
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
                                                                                               title="{{ $eventIsCompleted ? 'Undo done' : 'Mark done' }}"
                                                                                               @checked($eventIsCompleted)
                                                                                               onchange="this.form.submit();">
                                                                                    </form>
                                                                                @endif

                                                                                <p class="events-card-type font-extrabold {{ $eventShouldUsePastStyle ? 'text-gray-500' : 'text-blue-600' }} uppercase tracking-wide truncate">
                                                                                    {{ $event->eventType?->name ?? 'N/A' }}
                                                                                </p>
                                                                            </div>
                                                                            <p class="events-card-details truncate {{ $eventShouldUsePastStyle ? 'text-gray-500' : 'text-gray-700' }}">
                                                                                <span class="font-semibold text-gray-700">Sub-type:</span> {{ $event->event_subtype ?: 'N/A' }}
                                                                            </p>
                                                                            <h6 class="events-card-title font-semibold {{ $eventShouldUsePastStyle ? 'text-gray-700' : 'text-gray-950' }} mt-2 mb-4 truncate">{{ $event->title }}</h6>
                                                                        </div>
                                                                        <div class="text-right shrink-0 leading-tight" style="min-width:clamp(52px, 6vw, 74px);">
                                                                            @if ($eventIsAnyTimeTodo)
                                                                                <p class="events-card-time-label font-bold text-gray-500 uppercase tracking-wide">Time</p>
                                                                                <p class="events-card-time-value font-extrabold {{ $eventShouldUsePastStyle ? 'text-gray-700' : 'text-gray-900' }}">Any time</p>
                                                                            @else
                                                                                <p class="events-card-time-label font-bold text-gray-500 uppercase tracking-wide">Start</p>
                                                                                <p class="events-card-time-value font-extrabold {{ $eventShouldUsePastStyle ? 'text-gray-700' : 'text-gray-900' }}">{{ \Carbon\Carbon::parse($event->start_datetime, $eventTimezone)->format('g:i A') }}</p>

                                                                                <p class="events-card-time-label font-bold text-gray-500 uppercase tracking-wide mt-2">End</p>
                                                                                <p class="events-card-time-value font-extrabold {{ $eventShouldUsePastStyle ? 'text-gray-700' : 'text-gray-900' }}">
                                                                                    {{ $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime, $eventTimezone)->format('g:i A') : 'N/A' }}
                                                                                </p>
                                                                            @endif
                                                                        </div>
                                                                    </div>

                                                                    <div class="events-card-details grid grid-cols-1 gap-1 {{ $eventShouldUsePastStyle ? 'text-gray-500' : 'text-gray-600' }}">
                                                                        @if (!empty($primaryDetail['label']) && !empty($primaryDetailValue) && $primaryDetailValue !== 'N/A')
                                                                            <p class="truncate"><span class="font-semibold text-gray-700">{{ $primaryDetail['label'] }}:</span> {{ $primaryDetailValue }}</p>
                                                                        @endif
                                                                        <p class="truncate"><span class="font-semibold text-gray-700">Status:</span> {{ $event->status }}</p>
                                                                        <p class="truncate"><span class="font-semibold text-gray-700">Priority:</span> {{ $event->priority }}</p>
                                                                        <p class="truncate"><span class="font-semibold text-gray-700">Location:</span> {{ $event->location ?: 'No location provided' }}</p>
                                                                        <p class="truncate"><span class="font-semibold text-gray-700">Description:</span> {{ $event->description ?: 'No description provided' }}</p>
                                                                    </div>

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
                        No events found.
                    </div>
                @endforelse

                @if ($events->isEmpty())
                    <div class="bg-white border rounded-lg p-6 text-gray-600 shadow-sm col-span-full">
                        No current or upcoming events found for the selected filters.
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div id="userEventsModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9997; align-items:center; justify-content:center; padding:20px;">
        <div style="background:white; width:1200px; max-width:96vw; max-height:92vh; overflow:auto; border-radius:14px; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,.22);">
            <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:18px;">
                <div>
                    <p style="font-size:13px; font-weight:800; color:#2563eb; text-transform:uppercase; margin-bottom:4px;">User Schedule</p>
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

            <div id="detailExtraDetailsWrapper" style="display:none; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin-bottom:22px;">
                <p style="font-size:12px; color:#6b7280; font-weight:700; margin-bottom:6px;">Event Details</p>
                <div id="detailExtraDetails" style="display:grid; grid-template-columns:1fr; gap:8px; color:#111827; font-size:14px;"></div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button"
                        onclick="closeEventDetails()"
                        style="background:#e5e7eb; color:#111827; padding:9px 14px; border-radius:6px; font-weight:600;">
                    Back
                </button>

                <form id="detailMarkDoneForm" method="POST" action="#" style="display:inline-flex;">
                    @csrf
                    @method('PATCH')
                    <button id="detailMarkDoneButton" type="submit"
                            style="background-color:#dcfce7; color:#166534; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #bbf7d0; box-shadow:0 2px 6px rgba(22,101,52,.08); font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                        Mark Done
                    </button>
                </form>

                <a id="detailEditButton" href="#"
                   style="background-color:#fef3c7; color:#92400e; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fde68a; box-shadow:0 2px 6px rgba(146,64,14,.08); text-decoration:none; font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                    Edit Event
                </a>

                <button id="detailDeleteButton" type="button"
                        style="background-color:#fee2e2; color:#991b1b; padding:10px 16px; border-radius:8px; font-weight:700; border:1px solid #fecaca; box-shadow:0 2px 6px rgba(153,27,27,.08); font-size:13px; line-height:1; display:inline-flex; align-items:center; justify-content:center;">
                    Delete Event
                </button>
            </div>
        </div>
    </div>
    <div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; width:420px; border-radius:12px; padding:24px; box-shadow:0 20px 40px rgba(0,0,0,.2);">
            <h2 style="font-size:20px; font-weight:700; margin-bottom:10px;">Confirm Event Deletion</h2>
            <p style="color:#4b5563; margin-bottom:24px;">
                This is a permanent action. Please confirm that you want to delete this event from the JCCS schedule.
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
                        Yes, Delete Event
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
            localStorage.setItem('eventsUserViewMode', isListView ? 'list' : 'boxes');
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

        const displayedCalendarWeekStart = @json($calendarStart->toDateString());

        function getCurrentWeekDays() {
            const start = new Date(`${displayedCalendarWeekStart}T00:00:00`);
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
                    <p style="font-size:12px; font-weight:700; color:#2563eb; margin-top:3px;">${dayEvents.length} ${dayEvents.length === 1 ? 'event' : 'events'}</p>
                `;
                dayColumn.appendChild(dayHeader);

                if (dayEvents.length === 0) {
                    const emptyMessage = document.createElement('div');
                    emptyMessage.style.padding = '14px';
                    emptyMessage.style.fontSize = '12px';
                    emptyMessage.style.fontWeight = '700';
                    emptyMessage.style.color = '#9ca3af';
                    emptyMessage.textContent = 'No events scheduled.';
                    dayColumn.appendChild(emptyMessage);
                }

                dayEvents.forEach(function (eventItem) {
                    const eventBlock = document.createElement('div');
                    eventBlock.className = `user-events-modal-event ${eventItem.is_completed ? 'events-event-completed' : ''}`;
                    eventBlock.style.setProperty('--event-color', eventItem.color || '#6b7280');
                    eventBlock.innerHTML = `
                        <p style="font-size:10px; font-weight:900; color:#2563eb; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px;">${eventItem.type || 'N/A'}</p>
                        <p style="font-size:12px; font-weight:700; color:#374151; margin-bottom:4px;"><span style="color:#374151; font-weight:800;">Sub-type:</span> ${eventItem.subtype || 'N/A'}</p>
                        <h3 class="user-events-modal-event-title" style="font-size:16px; font-weight:600; color:#111827; line-height:1.25; margin-bottom:12px;">${eventItem.title || 'Untitled Event'}</h3>
                        ${eventItem.primary_detail_label && eventItem.primary_detail_value && eventItem.primary_detail_value !== 'N/A' ? `<p style="font-size:12px; font-weight:700; color:#6b7280; margin-bottom:4px;"><span style="color:#374151; font-weight:800;">${eventItem.primary_detail_label}:</span> ${eventItem.primary_detail_value}</p>` : ''}
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
            const hasActiveFilters = @json((bool) ($selectedEventType || $selectedStatus || ($selectedDateRange && $selectedDateRange !== 'today_tomorrow') || $selectedAssignedUser));

            if (hasActiveFilters) {
                toggleEventFilters();
            }

            applyPastDueEventStyles();
            setEventsUserView(localStorage.getItem('eventsUserViewMode') === 'list');
        });

        function openEventDetailsFromRow(element) {
            openEventDetails({
                type: element.dataset.type,
                subtype: element.dataset.subtype,
                details: parseEventDetails(element.dataset.details),
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
                markDoneUrl: element.dataset.markDoneUrl,
                eventColor: element.dataset.eventColor,
            });
        }

        function parseEventDetails(detailsJson) {
            if (!detailsJson) {
                return {};
            }

            try {
                return JSON.parse(detailsJson);
            } catch (error) {
                return {};
            }
        }

        function formatEventDetailLabel(key) {
            const customLabels = {
                engineer: 'Engineer',
                participants: 'Participants',
                person: 'Person',
                company: 'Company',
                workers: 'Worker(s)',
                project: 'Project',
                estimated_due_date: 'Estimated Due Date',
                logistics_location: 'Logistics Location',
                team_workers: 'Worker(s)',
                invoice_or_estimate: 'Invoice or Estimate',
                number: 'Number',
                name: 'Name',
                payment_amount: 'Payment Amount',
                payment_date: 'Payment Date / Due Date',
                payment_document_number: 'Invoice or Estimate Number',
                payment_name: 'Project / Client Name',
                payment_method: 'Payment Method',
                payment_status: 'Payment Status',
            };

            return customLabels[key] || key.replaceAll('_', ' ').replace(/\b\w/g, function (letter) {
                return letter.toUpperCase();
            });
        }

        function formatEventDetailValue(key, value) {
            if (Array.isArray(value)) {
                if (key === 'items') {
                    return value.map(function (item) {
                        const name = item.name || 'Unnamed item';
                        const quantity = item.quantity ? ` #${item.quantity}` : '';

                        return `${name}${quantity}`;
                    }).join('<br>');
                }

                return value.join('<br>');
            }

            if (key === 'payment_amount' && value !== null && value !== '') {
                const amount = Number(value);

                if (!Number.isNaN(amount)) {
                    return `$${amount.toFixed(2)}`;
                }
            }

            return value;
        }

        function renderEventExtraDetails(details) {
            const wrapper = document.getElementById('detailExtraDetailsWrapper');
            const detailsContainer = document.getElementById('detailExtraDetails');
            const entries = Object.entries(details || {}).filter(function ([key, value]) {
                if (Array.isArray(value)) {
                    return value.length > 0;
                }

                return value !== null && value !== '';
            });

            detailsContainer.innerHTML = '';

            if (entries.length === 0) {
                wrapper.style.display = 'none';
                return;
            }

            entries.forEach(function ([key, value]) {
                const row = document.createElement('div');
                row.style.borderBottom = '1px solid #e5e7eb';
                row.style.paddingBottom = '8px';
                row.innerHTML = `
                    <p style="font-size:12px; color:#6b7280; font-weight:700;">${formatEventDetailLabel(key)}</p>
                    <p style="font-weight:700; color:#111827; white-space:pre-wrap;">${formatEventDetailValue(key, value)}</p>
                `;
                detailsContainer.appendChild(row);
            });

            wrapper.style.display = 'block';
        }

        function openEventDetails(eventData) {
            document.getElementById('detailType').textContent = eventData.subtype ? `${eventData.type || 'N/A'} - ${eventData.subtype}` : (eventData.type || 'N/A');
            document.getElementById('detailTitle').textContent = eventData.title || 'Untitled Event';
            document.getElementById('detailDescription').textContent = eventData.description || 'No description provided.';
            document.getElementById('detailStatus').textContent = eventData.status || 'N/A';
            document.getElementById('detailPriority').textContent = eventData.priority || 'N/A';
            const eventStartDate = parseEventDateTimeForPastCheck(eventData.start);
            const eventEndDate = parseEventDateTimeForPastCheck(eventData.end);
            const isAnyTimeEvent = eventStartDate
                && eventStartDate.getHours() === 0
                && eventStartDate.getMinutes() === 0
                && (!eventEndDate || (eventEndDate.getHours() === 23 && eventEndDate.getMinutes() >= 58));

            document.getElementById('detailStart').textContent = eventStartDate
                ? `${eventStartDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}${isAnyTimeEvent ? ' / Any Time' : ` ${eventStartDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`}`
                : 'N/A';
            document.getElementById('detailAssignedTo').textContent = eventData.assignedTo || 'Unassigned';
            document.getElementById('detailLocation').textContent = eventData.location || 'No location provided.';
            renderEventExtraDetails(eventData.details || {});
            document.getElementById('detailEditButton').setAttribute('href', eventData.editUrl);
            const markDoneForm = document.getElementById('detailMarkDoneForm');
            const markDoneButton = document.getElementById('detailMarkDoneButton');
            const isCompleted = (eventData.status || '').toLowerCase() === 'completed';

            markDoneForm.setAttribute('action', eventData.markDoneUrl || '#');
            markDoneButton.disabled = false;
            markDoneButton.textContent = isCompleted ? 'Undo Done' : 'Mark Done';
            markDoneButton.style.opacity = '1';
            markDoneButton.style.cursor = 'pointer';
            markDoneButton.style.backgroundColor = isCompleted ? '#e5e7eb' : '#dcfce7';
            markDoneButton.style.color = isCompleted ? '#111827' : '#166534';
            markDoneButton.style.borderColor = isCompleted ? '#d1d5db' : '#bbf7d0';

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