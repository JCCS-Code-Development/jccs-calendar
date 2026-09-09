# JCCS Office Operations Board

A full-screen, self-updating dashboard for the office TV, built into **jccs-calendar**.
Route: **`/board`** (public, read-only). Editing is gated by a shared PIN.

---

## 1. What was built

### Board (Display Mode — public, no login)
- **Header** — JCCS logo (left, kept in proportion), "Office Operations Board" (centre),
  live date + clock (right). Clock ticks every second with no page reload and is fixed to
  **America/New_York** via `Intl.DateTimeFormat`.
- **Personnel band**
  - *On the clock* — everyone currently clocked in, pulled live from FieldClock
    (name · status · job · elapsed time). Names are draggable.
  - *At the shops* — head-count + names for **Carpinteria Mauldin**, **Carpinteria
    Principal**, **Oficina** (derived from the clock-in feed; site list is a constant at
    the top of `PersonnelBar.jsx`).
- **Column 1 – Awaiting PO / Needs Scheduling** — jobs with no received/approved PO
  *and* no confirmed schedule (a PO'd-but-unscheduled job stays here too). Shows job,
  client, location, estimate #, PO-status badge, priority, date received, **days waiting**
  (turns red ≥ 14 days), person responsible for the next action, notes, assigned crew.
  Sorted **urgent first, then longest-waiting first**. Amber/orange styling; red for
  overdue.
- **Column 2 – Scheduled Jobs** — jobs with a confirmed / in-progress / just-completed
  schedule. Shows job, client, location, scheduled date, start time, expected completion,
  crew, PO badge, schedule-status badge, notes. Left-border state colours: **blue**
  upcoming, **green** today, **red** delayed, **grey** completed (a finished job lingers
  3 days then drops off). A scheduled job still missing its PO shows a prominent red
  **"⚠ PO MISSING"** banner on the same card — **never a duplicate card**.
  Sorted chronologically, today first, completed sink to the bottom.
- **Column 3 – Important Appointments** — a **vertical day timeline** for today
  (6 AM–8 PM hour grid, appointments as positioned blocks, overlapping items placed
  side-by-side, a red **"now" line**, auto-scrolled so the current hour is in view).
  All-day items pin above the grid; future-day appointments list under **"Upcoming"**.
  Importance (critical / important) and state (tentative / confirmed / completed /
  canceled — canceled shown struck-through) are colour-coded. Today's appointments are
  highlighted.
- **Overflow** — each column measures how many cards fit and **rotates through pages**
  (dots + "Page 2 / 3") every 12 s rather than shrinking text.
- **Footer** — last successful sync time, online/offline indicator, **Full screen**
  button, **Edit Mode** button.
- **Continuous update** — polls `/api/ops-board` every **30 s**, re-renders in place
  (no flash). On failure it keeps the last good data on screen and shows an amber
  *"Can't reach the server — showing the last update from HH:MM"* banner; recovers
  automatically when the API returns. A stale response from any cache layer is caught
  by comparing the server's own `server_time`. The board never shows a blank screen.

### Edit Mode (PIN-gated)
- **Enter Edit Mode** → PIN prompt (checked against `OPS_BOARD_PIN`). PIN is held in
  `sessionStorage`, so closing the browser drops Edit Mode.
- A management overlay with **Jobs** and **Appointments** tabs, search/filter, and:
  - Add / edit a job; set PO status + PO number + PO received date; establish or change
    a schedule (status, date, start time, expected completion); change priority; edit
    notes and next-action person; assign crew; **mark completed**; **archive**
    (soft-delete, with confirmation).
  - Add / edit an appointment; set importance; mark **confirmed / tentative / completed
    / canceled**; link a related job; remove from board.
- **Stale-edit protection** — every save sends the `updated_at` it last saw; if the row
  changed meanwhile the API returns **409** and the form offers *"Load latest values"*
  instead of silently overwriting.

### Crew drag-and-drop (on the main board, no PIN — per request)
Drag a name from the *On the clock* band onto any job card (Awaiting or Scheduled) to
assign them; click the ✕ on a crew chip to remove them. This is the one board write that
isn't PIN-gated; it only ever touches `job_workers` and every change is written to
`ops_board_audit`.

---

## 2. Files changed

### jccs-calendar — new
```
api/migrations/2026-09-09_ops_board.sql     DB migration (run once, by hand)
api/ops-board/_helpers.php                  shared shaping / PIN check / FieldClock fetch
api/ops-board/index.php                     GET /ops-board  (public board payload)
api/ops-board/verify_pin.php                POST /ops-board/verify-pin
api/ops-board/refs.php                      GET  /ops-board/refs  (edit-mode data, PIN)
api/ops-board/jobs.php                      POST /ops-board/jobs
api/ops-board/job_item.php                  PUT/DELETE /ops-board/jobs/{id}
api/ops-board/job_crew.php                  POST/DELETE /ops-board/jobs/{id}/crew
api/ops-board/appointments.php              POST /ops-board/appointments
api/ops-board/appointment_item.php          PUT/DELETE /ops-board/appointments/{id}
src/api/board.js                            board API client (no JWT interceptor)
src/store/boardStore.js                     PIN + editMode (sessionStorage)
src/pages/board/OpsBoard.jsx                board container + 30s poll + offline handling
src/pages/board/BoardHeader.jsx             logo / title / live ET clock
src/pages/board/BoardFooter.jsx             sync time / online / fullscreen / Edit Mode
src/pages/board/BoardColumn.jsx             generic column + measured page-rotation
src/pages/board/PersonnelBar.jsx            "on the clock" + "at the shops"
src/pages/board/AppointmentTimeline.jsx     vertical day timeline for column 3
src/pages/board/useCrewDrop.js              drag-and-drop crew wiring
src/pages/board/board.css                   all board styling (scoped, theme-explicit)
src/pages/board/t.js                         every visible board string (Spanish)
src/pages/board/cards/AwaitingCard.jsx
src/pages/board/cards/ScheduledCard.jsx
src/pages/board/cards/CrewRow.jsx
src/pages/board/cards/helpers.js            date formatting + badge maps
src/pages/board/edit/PinGate.jsx
src/pages/board/edit/EditPanel.jsx
src/pages/board/edit/JobEditForm.jsx
src/pages/board/edit/AppointmentEditForm.jsx
```

### jccs-calendar — modified
```
api/router.php              + /ops-board/* dev routes
api/.htaccess               + /ops-board/* production rewrite rules
api/config/cors.php         allow the X-Board-Pin request header
api/config/config.example.php  + OPS_BOARD_PIN, + FIELDCLOCK_BOARD_TOKEN
src/App.jsx                 + public <Route path="/board">
src/sw.js                   note: /ops-board is deliberately NOT SW-cached
dist/**                     rebuilt (npm run build) — committed, per deploy convention
```

### jccs-fieldclock — new (read-only, changes nothing existing)
```
api/timeclock/board-active.php   GET, token-gated (X-Board-Token vs OPS_BOARD_TOKEN);
                                 returns clocked-in names + status + job. No writes,
                                 no schema change, no payroll data.
```

---

## 3. Database changes

**All additive.** No column is dropped or renamed, no row is rewritten. Existing
Calendar / Jobs / Timeline screens read only `status` / `projected_*` / `color` on `jobs`
and `status` / `priority` on `events` — none of which change.

Run once, by hand, against the production **`jccs_calendar`** database (this app has no
migration runner — same as jccs-inventory):

```
mysql -u <user> -p jccs_calendar < api/migrations/2026-09-09_ops_board.sql
```

It does:
1. **`jobs`** — adds `po_status`, `po_number`, `po_received_date`, `schedule_status`,
   `priority`, `date_received`, `scheduled_start_time`, `next_action_by`, `board_notes`,
   `archived_at`, plus an index.
2. **`events`** — adds `board_flag`, `board_importance`, `confirm_state`, `related_job_id`
   (FK → jobs), plus an index. Also makes `events.created_by` **NULL-able** (a board-added
   appointment has no logged-in FieldClock user; existing rows and code are unaffected).
3. **`job_workers`** — adds `name_cache` (label for a dragged-in worker who has no
   `calendar_user_roles` row yet).
4. **`ops_board_audit`** — new table; the only record of what changed from the TV, since
   the board runs on a shared PIN rather than per-person logins.

**Config** (not DB) — add to `api/config/config.php` on each server:
- Calendar: `define('OPS_BOARD_PIN', '…')` and `define('FIELDCLOCK_BOARD_TOKEN', '…')`.
- FieldClock: `define('OPS_BOARD_TOKEN', '…')` — **must equal** Calendar's
  `FIELDCLOCK_BOARD_TOKEN`. Leave `CHANGE_ME` to just disable the "On the clock" widget
  (the board still works, the widget shows "unavailable").

---

## 4. How to use it

### Open the board on the TV
1. Full-screen browser (or kiosk mode) → **`https://calendar.jccs-services.com/board`**.
2. No login required. Click **Full screen** in the footer (or F11 / kiosk).
3. It refreshes itself every 30 seconds. Leave it running.

Card density per page scales with screen height — it's tuned for 1920×1080 and 1366×768
**in full screen** (browser toolbars eat the vertical space that a second/third card needs).

### Enter Edit Mode
1. Click **Edit Mode** (footer, bottom-right).
2. Enter the office **PIN** (the `OPS_BOARD_PIN` value). It stays unlocked until the
   browser tab is closed; **Exit Edit Mode** locks it immediately.
3. Add/edit jobs and appointments from the Jobs / Appointments tabs. Changes appear on
   every open board within 30 seconds.

### Assign crew from the board (no PIN)
Drag a name from the **On the clock** strip onto a job card. Click the ✕ on a name chip
to unassign.

### Replace / update the logo
The board uses **`public/jccs-logo.jpg`** (same file the rest of the calendar app uses).
Replace that file (keep it a JPG, transparent or white artwork works — the board inverts
it for the dark header), then `npm run build` and commit `dist/`. No board code change.

---

## 5. Deploy

Standard for this app:
1. Apply the SQL migration to production `jccs_calendar` (section 3).
2. Add the config constants to `config.php` on both servers (section 3).
3. Copy `api/timeclock/board-active.php` to the FieldClock server (it's in the repo; a
   normal cPanel Git pull deploys it).
4. `npm install && npm run build` in jccs-calendar, **commit the `dist/` diff**, push,
   then cPanel → Git Version Control → pull (runs `.cpanel.yml`).

---

## 6. Test results

Local: fresh `jccs_calendar_test` DB from `api/schema.sql` + the migration, seeded across
every state; real API (`php -S … api/router.php`) + Vite + a local FieldClock API; driven
in Chrome.

| # | Scenario | Result |
|---|----------|--------|
| 1 | New job, no PO, no schedule | ✅ lands in Column 1 |
| 2 | Job with PO, no confirmed schedule | ✅ stays in Column 1, "PO Received" badge |
| 3 | Scheduled job with PO | ✅ Column 2, PO-approved badge |
| 4 | Scheduled job still missing PO | ✅ Column 2 with red "PO MISSING" banner, no duplicate |
| 5 | Job scheduled for today | ✅ Column 2, green border + "Today" badge |
| 6 | Overdue / delayed job | ✅ Column 2, red border + "Delayed" badge |
| 7 | Important appointment today | ✅ timeline block, highlighted, "now" line |
| 8 | Canceled appointment | ✅ shown struck-through / dimmed |
| 9 | Two users editing the same record | ✅ second save → 409 + "Load latest values" |
| 10 | Loss + restoration of connection | ✅ last data kept, amber banner, auto-recovers, no reload |
| 11 | Browser reload / API restart | ✅ records survive; board reloads and re-syncs |
| 12 | 1920×1080 and 1366×768 | ✅ 3 columns, no horizontal scroll, no text shrink (pages rotate) |
| — | Full lifecycle: create → PO received → schedule confirmed → mark complete | ✅ one record moves New→Awaiting→Scheduled→(grey, then off) with no re-entry |
| — | Crew drag-and-drop + ✕ remove | ✅ hits `/jobs/{id}/crew`, audited, board picks it up |
| — | PIN wrong / missing on a write | ✅ 401 |
| — | `npm run build` + `oxlint src/` | ✅ build passes; no new lint errors |

Time and dates are forced to America/New_York in both the API (`CALENDAR_TIMEZONE`) and
the browser (`Intl` with `timeZone`). Unauthorized users can view the board but every
write except crew-drag requires the PIN. No payroll, pay-rate, or financial data is
exposed anywhere in the board payload.

---

## 7. Assumptions & open questions

- **Board is Spanish-only** (pantalla de la oficina). Every UI string lives in
  `src/pages/board/t.js` — one file, easy to edit or turn into a per-language dictionary
  later. Dates/times are Spanish-formatted regardless of the app's `jccs_lang` setting.
  Job/appointment *content* (titles, notes) is whatever the office types; the native
  `<input type=date>` placeholder follows the browser's locale, not the page.
- **Crew drag-and-drop on the main board is not PIN-gated** (your call). Anyone on the
  office network who can open the TV URL can reassign crews. Every change is logged to
  `ops_board_audit` (with IP), but there's no per-person identity. If that's too open,
  the one-line change is to add `requireBoardPin()` to `api/ops-board/job_crew.php`.
- **"At the shops"** is derived from FieldClock clock-ins whose job name contains
  "Carpinteria Mauldin" / "Carpinteria Principal" / "Oficina". If the real FieldClock
  job names differ, edit `HOME_SITES` at the top of `src/pages/board/PersonnelBar.jsx`.
  (Local FieldClock test data only had "Oficina" and "Carpinteria 1200".)
- **"On the clock" names are draggable only when the person also exists in
  `calendar_user_roles`** with the same id — otherwise they still assign, but the card
  labels them from `name_cache`. In production FieldClock ids and calendar ids line up,
  so this is a non-issue; flagging it because the local test data had colliding ids with
  different names.
- **Days waiting / last-updated** are computed, not stored.
- **`next_action_by`** is free text (it may be a client or GC, not a JCCS user).
- The **appointment timeline window is 6 AM–8 PM.** Appointments outside that are clamped
  to the edge. Change `START_HOUR` / `END_HOUR` in `AppointmentTimeline.jsx` if needed.
- Completed jobs stay on the board (grey) for **3 days** after being marked complete,
  then drop off. There is no separate "completed jobs" archive view yet.
