-- ─────────────────────────────────────────────────────────────────────────────
-- JCCS Calendar — Office Operations Board
-- Run once, by hand, against the production `jccs_calendar` database (this app
-- has no migration runner, same as jccs-inventory — see its api/migrations/).
--
-- Everything here is additive. No column is dropped or renamed, no row is
-- rewritten. The existing Calendar, Jobs and Timeline screens read only
-- status / projected_start / projected_end / color on `jobs` and
-- status / priority on `events`, none of which change, so they are unaffected.
-- ─────────────────────────────────────────────────────────────────────────────

-- 1. jobs: the PO lifecycle + the intake fields the board's "Awaiting PO /
--    Needs Scheduling" column needs. A job can now exist before it is
--    scheduled (projected_start / projected_end were already NULL-able).
ALTER TABLE `jobs`
  ADD COLUMN `po_status`            ENUM('none','requested','received','approved')
                                    NOT NULL DEFAULT 'none'        AFTER `status`,
  ADD COLUMN `po_number`            VARCHAR(60)  NULL              AFTER `po_status`,
  ADD COLUMN `po_received_date`     DATE         NULL              AFTER `po_number`,
  ADD COLUMN `schedule_status`      ENUM('unscheduled','tentative','confirmed','in_progress','completed')
                                    NOT NULL DEFAULT 'unscheduled' AFTER `po_received_date`,
  ADD COLUMN `priority`             ENUM('low','normal','high','urgent')
                                    NOT NULL DEFAULT 'normal'      AFTER `schedule_status`,
  ADD COLUMN `date_received`        DATE         NULL              AFTER `priority`,
  ADD COLUMN `scheduled_start_time` TIME         NULL              AFTER `projected_start`,
  ADD COLUMN `next_action_by`       VARCHAR(120) NULL              AFTER `date_received`,
  ADD COLUMN `board_notes`          VARCHAR(500) NULL              AFTER `next_action_by`,
  ADD COLUMN `archived_at`          TIMESTAMP    NULL DEFAULT NULL AFTER `board_notes`;

-- Helps the board's two job queries (which both filter on these).
ALTER TABLE `jobs`
  ADD KEY `idx_jobs_board` (`archived_at`, `schedule_status`, `po_status`);

-- 2. events: which appointments are pinned to the board, and their state.
ALTER TABLE `events`
  ADD COLUMN `board_flag`       TINYINT(1) NOT NULL DEFAULT 0 AFTER `priority`,
  ADD COLUMN `board_importance` ENUM('normal','important','critical') NOT NULL DEFAULT 'normal' AFTER `board_flag`,
  ADD COLUMN `confirm_state`    ENUM('tentative','confirmed','completed','canceled') NOT NULL DEFAULT 'tentative' AFTER `board_importance`,
  ADD COLUMN `related_job_id`   INT UNSIGNED NULL AFTER `confirm_state`,
  ADD KEY `idx_events_board` (`board_flag`, `start_datetime`),
  ADD CONSTRAINT `fk_event_related_job`
      FOREIGN KEY (`related_job_id`) REFERENCES `jobs` (`id`) ON DELETE SET NULL;

-- 2b. events.created_by becomes NULL-able: an appointment added from the board
--     has no logged-in FieldClock user behind it (the board runs on a shared
--     PIN). Existing rows keep their value; the events/*.php endpoints still
--     always set it, so nothing else changes. The fk_event_creator FK stays
--     (a foreign key simply doesn't check NULLs).
ALTER TABLE `events`
  MODIFY COLUMN `created_by` INT UNSIGNED NULL;

-- 2c. job_workers.name_cache: the board's crew drag-and-drop can drop a
--     FieldClock worker who has no calendar_user_roles row yet. Store the
--     name they were dropped with so the card can still label them.
ALTER TABLE `job_workers`
  ADD COLUMN `name_cache` VARCHAR(150) NULL AFTER `fieldclock_user_id`;

-- 3. A lightweight record of edits made from the board. The board is gated
--    by a shared PIN, not per-person logins, so this is the only trail of
--    what changed from the TV / an office edit session.
CREATE TABLE `ops_board_audit` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `action`      VARCHAR(40)  NOT NULL,   -- job.create, job.update, job.archive, appt.create, appt.update, appt.cancel
  `entity_type` VARCHAR(20)  NOT NULL,   -- job | appointment
  `entity_id`   INT UNSIGNED NULL,
  `detail`      VARCHAR(255) NULL,       -- short human summary, e.g. "PO received; schedule confirmed"
  `actor_ip`    VARCHAR(45)  NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ops_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
