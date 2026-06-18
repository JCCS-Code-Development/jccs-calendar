<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Event
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">

                <form method="POST" action="{{ route('events.update', $event) }}">
                    @csrf
                    @method('PUT')
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 text-red-700 p-4 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php
                        $eventDetails = old('details', $event->details ?? []);
                        $selectedEventTypeId = old('event_type_id', $event->event_type_id);
                        $selectedEventType = $eventTypes->firstWhere('id', (int) $selectedEventTypeId);
                        $isReminderEvent = strtolower($selectedEventType?->name ?? '') === 'reminder';
                        $eventStartDateTime = \Carbon\Carbon::parse($event->start_datetime);
                        $eventEndDateTime = $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime) : null;
                        $isAllDayTodoEvent = ! $isReminderEvent
                            && $eventStartDateTime->format('H:i') === '00:00'
                            && $eventEndDateTime
                            && in_array($eventEndDateTime->format('H:i'), ['23:58', '23:59'], true);
                        $selectedSubtype = old('event_subtype', $event->event_subtype);

                        $eventCategoryOrder = [
                            'Reminder',
                            'Site Visit',
                            'Meeting',
                            'Communication',
                            'Supplies',
                            'Logistics',
                            'Estimate/Invoice',
                            'Payment',
                        ];

                        $eventCategoryOptions = $eventTypes
                            ->filter(fn ($eventType) => in_array($eventType->name, $eventCategoryOrder, true))
                            ->sortBy(fn ($eventType) => array_search($eventType->name, $eventCategoryOrder, true))
                            ->values();

                        $supplyItems = old('details.items', $eventDetails['items'] ?? []);

                        if (empty($supplyItems) || ! is_array($supplyItems)) {
                            $supplyItems = [['name' => '', 'quantity' => '']];
                        }
                    @endphp

                    <div class="mb-4">
                        <label for="event_type_id" class="block font-medium">Event Type</label>
                        <select id="event_type_id" name="event_type_id" class="w-full border-gray-300 rounded" required>
                            <option value="" data-event-type-name="" @selected($selectedEventTypeId === null || $selectedEventTypeId === '')>
                                -- Select Event Type --
                            </option>

                            @forelse ($eventCategoryOptions as $eventType)
                                <option value="{{ $eventType->id }}"
                                        data-event-type-name="{{ strtolower(trim($eventType->name)) }}"
                                        @selected((string) $selectedEventTypeId === (string) $eventType->id)>
                                    {{ $eventType->name }}
                                </option>
                            @empty
                                <option value="" disabled>No event types available</option>
                            @endforelse
                        </select>

                        @if ($eventCategoryOptions->isEmpty())
                            <p class="mt-2 text-sm text-red-600">
                                No event types are available. Please add the main event types before editing an event.
                            </p>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label for="event_subtype" class="block font-medium">Event Sub-Type</label>
                        <select id="event_subtype" name="event_subtype" class="w-full border-gray-300 rounded" required>
                            <option value="">-- Select Event Sub-Type --</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Title</label>
                        <input name="title" value="{{ old('title', $event->title) }}" class="w-full border-gray-300 rounded" required>
                    </div>

                    <div id="dynamic_event_details" class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4 hidden">
                        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-700">Required Details</h3>

                        <div class="dynamic-field-group hidden" data-required-for="site visit:quote walk-through,site visit:job set-up,site visit:final walk-through">
                            <label class="block font-medium">Engineer</label>
                            <input type="text" name="details[engineer]" value="{{ old('details.engineer', $eventDetails['engineer'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                        </div>

                        <div class="dynamic-field-group hidden" data-required-for="meeting:virtual video call,meeting:in-person meeting">
                            <label class="block font-medium">Participants</label>
                            <textarea name="details[participants]" rows="3" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true" placeholder="Enter one participant per line">{{ old('details.participants', $eventDetails['participants'] ?? '') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Use one line per participant.</p>
                        </div>

                        <div class="dynamic-field-group hidden" data-required-for="communication:text,communication:email,communication:call">
                            <label class="block font-medium">Person</label>
                            <input type="text" name="details[person]" value="{{ old('details.person', $eventDetails['person'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                        </div>

                        <div class="dynamic-field-group hidden" data-required-for="supplies:order,supplies:pick up,supplies:return,supplies:exchange,supplies:buy in store">
                            <label class="block font-medium">Company</label>
                            <input type="text" name="details[company]" value="{{ old('details.company', $eventDetails['company'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">

                            <div class="mt-4">
                                <label class="block font-medium">Item(s) and Quantity</label>
                                <div id="supply_items" class="space-y-3">
                                    @foreach ($supplyItems as $index => $item)
                                        <div class="supply-item-row" style="display: grid; grid-template-columns: minmax(0, 1fr) 80px auto; align-items: center; gap: 12px;">
                                            <input type="text" name="details[items][{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" class="supply-item-input w-full border-gray-300 rounded" placeholder="Item name">
                                            <input type="number" name="details[items][{{ $index }}][quantity]" value="{{ $item['quantity'] ?? '' }}" class="supply-item-input w-full border-gray-300 rounded text-center" placeholder="#" min="1" step="1">
                                            <button type="button" class="delete-supply-item rounded bg-red-100 px-3 py-2 text-sm font-bold text-red-700">
                                                Delete
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" id="add_supply_item" class="mt-3 rounded bg-gray-200 px-4 py-2 text-sm font-bold text-gray-800">
                                    Add Another Item
                                </button>
                            </div>
                        </div>

                        <div class="dynamic-field-group hidden" data-required-for="logistics:coordinate workers">
                            <label class="block font-medium">Worker(s)</label>
                            <textarea name="details[workers]" rows="3" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true" placeholder="Enter one worker per line">{{ old('details.workers', $eventDetails['workers'] ?? '') }}</textarea>

                            <div class="mt-4">
                                <label class="block font-medium">Project</label>
                                <input type="text" name="details[project]" value="{{ old('details.project', $eventDetails['project'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                            </div>

                            <div class="mt-4">
                                <label class="block font-medium">Estimated Due Date</label>
                                <input type="date" name="details[estimated_due_date]" value="{{ old('details.estimated_due_date', $eventDetails['estimated_due_date'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                            </div>

                            <div class="mt-4">
                                <label class="block font-medium">Location</label>
                                <input type="text" name="details[logistics_location]" value="{{ old('details.logistics_location', $eventDetails['logistics_location'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                            </div>
                        </div>

                        <div class="dynamic-field-group hidden" data-required-for="logistics:lead-team member meeting">
                            <label class="block font-medium">Worker(s)</label>
                            <textarea name="details[team_workers]" rows="3" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true" placeholder="Enter one worker per line">{{ old('details.team_workers', $eventDetails['team_workers'] ?? '') }}</textarea>
                        </div>

                        <div class="dynamic-field-group hidden" data-required-for="estimate/invoice:send,estimate/invoice:change price,estimate/invoice:change details,estimate/invoice:create an add-on">
                            <label class="block font-medium">Invoice or Estimate</label>
                            <select name="details[invoice_or_estimate]" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                                <option value="">-- Select --</option>
                                <option value="Invoice" @selected(old('details.invoice_or_estimate', $eventDetails['invoice_or_estimate'] ?? '') === 'Invoice')>Invoice</option>
                                <option value="Estimate" @selected(old('details.invoice_or_estimate', $eventDetails['invoice_or_estimate'] ?? '') === 'Estimate')>Estimate</option>
                            </select>

                            <div class="mt-4">
                                <label class="block font-medium">Number</label>
                                <input type="text" name="details[number]" value="{{ old('details.number', $eventDetails['number'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                            </div>

                            <div class="mt-4">
                                <label class="block font-medium">Name</label>
                                <input type="text" name="details[name]" value="{{ old('details.name', $eventDetails['name'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                            </div>
                        </div>

                        <div class="dynamic-field-group hidden" data-required-for="payment:routine payment,payment:payment due,payment:cash checks,payment:register payment,payment:ask for payment">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block font-medium">Payment Amount</label>
                                    <input type="number" name="details[payment_amount]" value="{{ old('details.payment_amount', $eventDetails['payment_amount'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true" placeholder="0.00" min="0" step="0.01">
                                </div>

                                <div>
                                    <label class="block font-medium">Payment Date / Due Date</label>
                                    <input type="date" name="details[payment_date]" value="{{ old('details.payment_date', $eventDetails['payment_date'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                                </div>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block font-medium">Invoice or Estimate Number</label>
                                    <input type="text" name="details[payment_document_number]" value="{{ old('details.payment_document_number', $eventDetails['payment_document_number'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                                </div>

                                <div>
                                    <label class="block font-medium">Project / Client Name</label>
                                    <input type="text" name="details[payment_name]" value="{{ old('details.payment_name', $eventDetails['payment_name'] ?? '') }}" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                                </div>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block font-medium">Payment Method</label>
                                    <select name="details[payment_method]" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                                        <option value="">-- Select --</option>
                                        <option value="Cash" @selected(old('details.payment_method', $eventDetails['payment_method'] ?? '') === 'Cash')>Cash</option>
                                        <option value="Check" @selected(old('details.payment_method', $eventDetails['payment_method'] ?? '') === 'Check')>Check</option>
                                        <option value="Card" @selected(old('details.payment_method', $eventDetails['payment_method'] ?? '') === 'Card')>Card</option>
                                        <option value="ACH / Bank Transfer" @selected(old('details.payment_method', $eventDetails['payment_method'] ?? '') === 'ACH / Bank Transfer')>ACH / Bank Transfer</option>
                                        <option value="Other" @selected(old('details.payment_method', $eventDetails['payment_method'] ?? '') === 'Other')>Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-medium">Payment Status</label>
                                    <select name="details[payment_status]" class="dynamic-required w-full border-gray-300 rounded" data-required-when-visible="true">
                                        <option value="">-- Select --</option>
                                        <option value="Pending" @selected(old('details.payment_status', $eventDetails['payment_status'] ?? '') === 'Pending')>Pending</option>
                                        <option value="Requested" @selected(old('details.payment_status', $eventDetails['payment_status'] ?? '') === 'Requested')>Requested</option>
                                        <option value="Received" @selected(old('details.payment_status', $eventDetails['payment_status'] ?? '') === 'Received')>Received</option>
                                        <option value="Deposited" @selected(old('details.payment_status', $eventDetails['payment_status'] ?? '') === 'Deposited')>Deposited</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Assigned To</label>
                        <select name="assigned_user_id" class="w-full border-gray-300 rounded">
                            <option value="">-- Unassigned --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('assigned_user_id', $event->assigned_user_id) == $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="start_datetime_group" class="mb-4 {{ $isReminderEvent || $isAllDayTodoEvent ? 'hidden' : '' }}">
                        <label class="block font-medium">Start Date/Time</label>
                        <input id="start_datetime" type="datetime-local" name="start_datetime" value="{{ old('start_datetime', \Carbon\Carbon::parse($event->start_datetime)->format('Y-m-d\TH:i')) }}" class="w-full border-gray-300 rounded" required>
                    </div>

                    <div id="reminder_date_group" class="mb-4 {{ $isReminderEvent ? '' : 'hidden' }}">
                        <label class="block font-medium">Reminder Date</label>
                        <input id="reminder_date" type="date" name="reminder_date" value="{{ old('reminder_date', \Carbon\Carbon::parse($event->start_datetime)->format('Y-m-d')) }}" class="w-full border-gray-300 rounded">
                    </div>

                    <div id="any_time_todo_group" class="mb-4 rounded-lg border border-blue-100 bg-blue-50 p-4">
                        <label class="flex items-start gap-3 font-medium text-gray-800">
                            <input id="is_all_day_todo" type="checkbox" name="is_all_day_todo" value="1" class="mt-1 rounded border-gray-300" @checked(old('is_all_day_todo', $isAllDayTodoEvent))>
                            <span>
                                Any time during the day / To-do item
                                <span class="block text-sm font-normal text-gray-600">Use this when the item does not need an exact time. It will appear at the top of the calendar with reminders and in the To-Do List tab.</span>
                            </span>
                        </label>
                    </div>

                    <div id="all_day_date_group" class="mb-4 {{ $isAllDayTodoEvent && ! $isReminderEvent ? '' : 'hidden' }}">
                        <label class="block font-medium">To-Do Date</label>
                        <input id="all_day_date" type="date" name="all_day_date" value="{{ old('all_day_date', \Carbon\Carbon::parse($event->start_datetime)->format('Y-m-d')) }}" class="w-full border-gray-300 rounded">
                    </div>

                    <div id="end_datetime_group" class="mb-4 {{ $isReminderEvent || $isAllDayTodoEvent ? 'hidden' : '' }}">
                        <label class="block font-medium">End Date/Time</label>
                        <input id="end_datetime" type="datetime-local" name="end_datetime" value="{{ old('end_datetime', $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime)->format('Y-m-d\TH:i') : '') }}" class="w-full border-gray-300 rounded">
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Status</label>
                        <select name="status" class="w-full border-gray-300 rounded">
                            @foreach (['Scheduled', 'Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled', 'Rescheduled'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $event->status) === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Priority</label>
                        <select name="priority" class="w-full border-gray-300 rounded">
                            @foreach (['Normal', 'Low', 'High', 'Urgent'] as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', $event->priority) === $priority)>{{ $priority }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Location / Facility</label>
                        <input name="location" value="{{ old('location', $event->location) }}" class="w-full border-gray-300 rounded">
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">Description / Internal Notes</label>
                        <textarea name="description" class="w-full border-gray-300 rounded" rows="4">{{ old('description', $event->description) }}</textarea>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <a href="{{ route('events.index') }}"
                           style="background-color:#e5e7eb; color:#111827; padding:12px 24px; border-radius:6px; text-decoration:none;">
                            Cancel
                        </a>

                        <button type="submit" style="background-color:#2563eb; color:white; padding:12px 24px; border-radius:6px;">
                            Update Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
    const eventSubtypeOptions = {
        'reminder': ['General', 'Personal'],
        'site visit': ['Quote Walk-Through', 'Job Set-Up', 'Final Walk-Through'],
        'meeting': ['Virtual Video Call', 'In-Person Meeting'],
        'communication': ['Text', 'Email', 'Call'],
        'supplies': ['Order', 'Pick Up', 'Return', 'Exchange', 'Buy In Store'],
        'logistics': ['Coordinate Workers', 'Lead-Team Member Meeting', 'Office Meeting'],
        'estimate/invoice': ['Send', 'Change Price', 'Change Details', 'Create an Add-On'],
        'payment': ['Routine Payment', 'Payment Due', 'Cash Checks', 'Register Payment', 'Ask for Payment'],
    };

    const oldSubtype = @json($selectedSubtype);

    function getSelectedEventTypeName() {
        const eventTypeSelect = document.getElementById('event_type_id');

        if (!eventTypeSelect || !eventTypeSelect.value) {
            return '';
        }

        const selectedOption = eventTypeSelect.options[eventTypeSelect.selectedIndex];

        return selectedOption?.dataset?.eventTypeName || '';
    }

    function isReminderEventType() {
        return getSelectedEventTypeName() === 'reminder';
    }

    function isAnyTimeTodoChecked() {
        return document.getElementById('is_all_day_todo')?.checked || false;
    }

    function populateSubtypeOptions() {
        const eventTypeName = getSelectedEventTypeName();
        const subtypeSelect = document.getElementById('event_subtype');
        const selectedOptions = eventSubtypeOptions[eventTypeName] || [];

        subtypeSelect.innerHTML = '<option value="">-- Select Event Sub-Type --</option>';

        selectedOptions.forEach(function (subtype) {
            const option = document.createElement('option');
            option.value = subtype;
            option.textContent = subtype;

            if (oldSubtype === subtype) {
                option.selected = true;
            }

            subtypeSelect.appendChild(option);
        });

        subtypeSelect.disabled = selectedOptions.length === 0;
        toggleDynamicEventDetails();
    }

    function toggleReminderDateFields() {
        const isReminder = isReminderEventType();
        const isAnyTimeTodo = isAnyTimeTodoChecked();
        const startGroup = document.getElementById('start_datetime_group');
        const endGroup = document.getElementById('end_datetime_group');
        const reminderGroup = document.getElementById('reminder_date_group');
        const allDayDateGroup = document.getElementById('all_day_date_group');
        const startInput = document.getElementById('start_datetime');
        const endInput = document.getElementById('end_datetime');
        const reminderInput = document.getElementById('reminder_date');
        const allDayDateInput = document.getElementById('all_day_date');

        startGroup.classList.toggle('hidden', isReminder || isAnyTimeTodo);
        endGroup.classList.toggle('hidden', isReminder || isAnyTimeTodo);
        reminderGroup.classList.toggle('hidden', !isReminder);
        allDayDateGroup.classList.toggle('hidden', !isAnyTimeTodo || isReminder);

        startInput.required = !isReminder && !isAnyTimeTodo;
        reminderInput.required = isReminder;
        allDayDateInput.required = isAnyTimeTodo && !isReminder;

        if (isReminder || isAnyTimeTodo) {
            endInput.value = '';
        }
    }

    function normalizeValue(value) {
        return (value || '').toString().trim().toLowerCase();
    }

    function toggleDynamicEventDetails() {
        const category = normalizeValue(getSelectedEventTypeName());
        const subtype = normalizeValue(document.getElementById('event_subtype')?.value);
        const detailsWrapper = document.getElementById('dynamic_event_details');
        let hasVisibleDetails = false;

        document.querySelectorAll('.dynamic-field-group').forEach(function (group) {
            const requiredFor = group.dataset.requiredFor || '';
            const allowedPairs = requiredFor.split(',').map(function (pair) {
                return pair.trim();
            });
            const shouldShow = allowedPairs.includes(`${category}:${subtype}`);

            group.classList.toggle('hidden', !shouldShow);

            group.querySelectorAll('.dynamic-required').forEach(function (field) {
                field.required = shouldShow && field.dataset.requiredWhenVisible === 'true';
            });

            if (shouldShow) {
                hasVisibleDetails = true;
            }
        });

        document.querySelectorAll('.supply-item-row').forEach(function (row, index) {
            const isFirstRow = index === 0;

            row.querySelectorAll('.supply-item-input').forEach(function (field) {
                field.required = hasVisibleDetails && category === 'supplies' && isFirstRow;
            });

            const deleteButton = row.querySelector('.delete-supply-item');

            if (deleteButton) {
                deleteButton.disabled = document.querySelectorAll('.supply-item-row').length <= 1;
                deleteButton.classList.toggle('opacity-50', deleteButton.disabled);
                deleteButton.classList.toggle('cursor-not-allowed', deleteButton.disabled);
            }
        });

        detailsWrapper.classList.toggle('hidden', !hasVisibleDetails);
    }

    function addSupplyItemRow() {
        const supplyItems = document.getElementById('supply_items');
        const rowCount = supplyItems.querySelectorAll('.supply-item-row').length;
        const row = document.createElement('div');
        row.className = 'supply-item-row';
        row.style.display = 'grid';
        row.style.gridTemplateColumns = 'minmax(0, 1fr) 80px auto';
        row.style.alignItems = 'center';
        row.style.gap = '12px';
        row.innerHTML = `
            <input type="text" name="details[items][${rowCount}][name]" class="supply-item-input w-full border-gray-300 rounded" placeholder="Item name">
            <input type="number" name="details[items][${rowCount}][quantity]" class="supply-item-input w-full border-gray-300 rounded text-center" placeholder="#" min="1" step="1">
            <button type="button" class="delete-supply-item rounded bg-red-100 px-3 py-2 text-sm font-bold text-red-700">
                Delete
            </button>
        `;

        supplyItems.appendChild(row);
        toggleDynamicEventDetails();
    }

    function deleteSupplyItemRow(event) {
        const button = event.target.closest('.delete-supply-item');

        if (!button || button.disabled) {
            return;
        }

        const rows = document.querySelectorAll('.supply-item-row');

        if (rows.length <= 1) {
            return;
        }

        button.closest('.supply-item-row').remove();
        toggleDynamicEventDetails();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const eventTypeSelect = document.getElementById('event_type_id');
        const subtypeSelect = document.getElementById('event_subtype');
        const addSupplyItemButton = document.getElementById('add_supply_item');
        const anyTimeTodoCheckbox = document.getElementById('is_all_day_todo');

        if (!eventTypeSelect || !subtypeSelect) {
            return;
        }

        const form = eventTypeSelect.closest('form');

        eventTypeSelect.addEventListener('change', function () {
            populateSubtypeOptions();
            toggleReminderDateFields();
        });

        subtypeSelect.addEventListener('change', toggleDynamicEventDetails);

        if (anyTimeTodoCheckbox) {
            anyTimeTodoCheckbox.addEventListener('change', toggleReminderDateFields);
        }

        if (addSupplyItemButton) {
            addSupplyItemButton.addEventListener('click', addSupplyItemRow);
        }

        document.getElementById('supply_items')?.addEventListener('click', deleteSupplyItemRow);

        populateSubtypeOptions();
        toggleReminderDateFields();
        toggleDynamicEventDetails();

        form.addEventListener('submit', function () {
            const startInput = document.getElementById('start_datetime');
            const endInput = document.getElementById('end_datetime');

            if (isReminderEventType()) {
                const reminderDate = document.getElementById('reminder_date').value;

                if (reminderDate) {
                    startInput.value = `${reminderDate}T00:00`;
                }

                endInput.value = '';
                return;
            }

            if (isAnyTimeTodoChecked()) {
                const allDayDate = document.getElementById('all_day_date').value;

                if (allDayDate) {
                    startInput.value = `${allDayDate}T00:00`;
                    endInput.value = `${allDayDate}T23:59`;
                }
            }
        });
    });
</script>
</x-app-layout>