import './bootstrap';
import 'temporal-polyfill/global';

import Alpine from 'alpinejs';

import {
    createCalendar,
    createViewDay,
    createViewWeek,
    createViewMonthGrid,
} from '@schedule-x/calendar';
import '@schedule-x/theme-default/dist/index.css';

window.Alpine = Alpine;

Alpine.start();

const calendarElement = document.getElementById('calendar');

if (calendarElement) {
    calendarElement.style.minHeight = '700px';

    fetch('/calendar/events')
        .then((response) => response.json())
        .then((events) => {
            const calendarEvents = events.map((event) => ({
                ...event,
                start: Temporal.ZonedDateTime.from(`${event.start}T00:00:00+00:00[UTC]`),
                end: Temporal.ZonedDateTime.from(`${event.end}T23:59:00+00:00[UTC]`),
            }));

            const calendar = createCalendar({
                views: [
                    createViewDay(),
                    createViewWeek(),
                    createViewMonthGrid(),
                ],
                defaultView: 'month-grid',
                events: calendarEvents,
            });

            calendar.render(calendarElement);
        })
        .catch((error) => {
            console.error('Calendar events could not be loaded:', error);
        });
}
