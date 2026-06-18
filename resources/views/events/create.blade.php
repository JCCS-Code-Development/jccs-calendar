<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Event
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                <form method="POST" action="{{ route('events.store') }}">
                    @csrf
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 text-red-700 p-4 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="mb-4">
                        <label class="block font-medium">Title</label>
                        <input name="title" class="w-full border-gray-300 rounded" required>
                    </div>

                    <div class="mb-4">
                        <label for="event_type_id" class="block font-medium">Event Type</label>
                        <select id="event_type_id" name="event_type_id" class="w-full border-gray-300 rounded" required>
                            <option value="" data-event-type-name="" @selected(old('event_type_id') === null || old('event_type_id') === '')>
                                -- Select Event Type --
                            </option>

                            @forelse ($eventTypes as $eventType)
                                <option value="{{ $eventType->id }}"
                                        data-event-type-name="{{ strtolower(trim($eventType->name)) }}"
                                        @selected((string) old('event_type_id') === (string) $eventType->id)>
                                    {{ $eventType->name }}
                                </option>
                            @empty
                                <option value="" disabled>No event types available</option>
                            @endforelse
                        </select>

                        @if ($eventTypes->isEmpty())
                            <p class="mt-2 text-sm text-red-600">
                                No event types are available. Please add event types before creating an event.
                            </p>
                        @endif
                    </div>
                    <div class="mb-4">
                        <label class="block font-medium">Assigned To</label>
                        <select name="assigned_user_id" class="w-full border-gray-300 rounded">
                            <option value="">-- Unassigned --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('assigned_user_id') == $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="start_datetime_group" class="mb-4">
                        <label class="block font-medium">Start Date/Time</label>
                        <input id="start_datetime" type="datetime-local" name="start_datetime" value="{{ old('start_datetime') }}" class="w-full border-gray-300 rounded" required>
                    </div>

                    <div id="reminder_date_group" class="mb-4 hidden">
                        <label class="block font-medium">Reminder Date</label>
                        <input id="reminder_date" type="date" name="reminder_date" value="{{ old('reminder_date') }}" class="w-full border-gray-300 rounded">
                    </div>

                    <div id="end_datetime_group" class="mb-4">
                        <label class="block font-medium">End Date/Time</label>
                        <input id="end_datetime" type="datetime-local" name="end_datetime" value="{{ old('end_datetime') }}" class="w-full border-gray-300 rounded">
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Status</label>
                        <select name="status" class="w-full border-gray-300 rounded">
                            <option>Scheduled</option>
                            <option>Pending</option>
                            <option>Confirmed</option>
                            <option>In Progress</option>
                            <option>Completed</option>
                            <option>Cancelled</option>
                            <option>Rescheduled</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Priority</label>
                        <select name="priority" class="w-full border-gray-300 rounded">
                            <option>Normal</option>
                            <option>Low</option>
                            <option>High</option>
                            <option>Urgent</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Location / Facility</label>
                        <input name="location" class="w-full border-gray-300 rounded">
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Description / Internal Notes</label>
                        <textarea name="description" class="w-full border-gray-300 rounded" rows="4"></textarea>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('events.index') }}"
                           style="background-color:#e5e7eb; color:#111827; padding:12px 24px; border-radius:6px; text-decoration:none;">
                            Cancel
                        </a>

                        <button type="submit" style="background-color:#2563eb; color:white; padding:12px 24px; border-radius:6px;">
                            Save Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
    function isReminderEventType() {
        const eventTypeSelect = document.getElementById('event_type_id');

        if (!eventTypeSelect || !eventTypeSelect.value) {
            return false;
        }

        const selectedOption = eventTypeSelect.options[eventTypeSelect.selectedIndex];
        const eventTypeName = selectedOption?.dataset?.eventTypeName || '';

        return eventTypeName === 'reminder';
    }

    function toggleReminderDateFields() {
        const isReminder = isReminderEventType();
        const startGroup = document.getElementById('start_datetime_group');
        const endGroup = document.getElementById('end_datetime_group');
        const reminderGroup = document.getElementById('reminder_date_group');
        const startInput = document.getElementById('start_datetime');
        const endInput = document.getElementById('end_datetime');
        const reminderInput = document.getElementById('reminder_date');

        startGroup.classList.toggle('hidden', isReminder);
        endGroup.classList.toggle('hidden', isReminder);
        reminderGroup.classList.toggle('hidden', !isReminder);

        startInput.required = !isReminder;
        reminderInput.required = isReminder;

        if (isReminder) {
            endInput.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const eventTypeSelect = document.getElementById('event_type_id');

        if (!eventTypeSelect) {
            return;
        }

        const form = eventTypeSelect.closest('form');

        eventTypeSelect.addEventListener('change', toggleReminderDateFields);
        toggleReminderDateFields();

        form.addEventListener('submit', function () {
            if (isReminderEventType()) {
                const reminderDate = document.getElementById('reminder_date').value;
                const startInput = document.getElementById('start_datetime');
                const endInput = document.getElementById('end_datetime');

                if (reminderDate) {
                    startInput.value = `${reminderDate}T00:00`;
                }

                endInput.value = '';
            }
        });
    });
</script>
</x-app-layout>