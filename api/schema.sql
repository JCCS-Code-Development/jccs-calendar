-- JCCS Calendar — separate database from FieldClock.
-- Auth is not stored here: staff authenticate against FieldClock's existing
-- login, and this API validates the resulting JWT with a shared JWT_SECRET
-- (see config/config.php). calendar_user_roles is the only "user" table —
-- it maps a FieldClock user id to a Calendar-specific role.
--
-- Four roles (carried forward from the v1 Laravel app):
--   Admin — manage users, manage events, view all events
--   Office — manage events, view all events (cannot manage users)
--   Lead / Crew — read-only, scoped to their own assigned events; behave
--     identically today (no distinct permissions between the two yet)

CREATE TABLE `calendar_user_roles` (
  `fieldclock_user_id` INT UNSIGNED NOT NULL,
  `name`               VARCHAR(150) NOT NULL,
  `role`               ENUM('Admin','Office','Lead','Crew') NOT NULL DEFAULT 'Crew',
  `is_active`          TINYINT(1) NOT NULL DEFAULT 1,
  -- Optional per-person Outlook "publish calendar" .ics link. When set, that
  -- person's Outlook events are overlaid (read-only) on the home calendar in
  -- their colour. Managed on the Users page (Admin only).
  `outlook_ics_url`    VARCHAR(1024) NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`fieldclock_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Existing installs: ALTER TABLE calendar_user_roles
--   ADD COLUMN outlook_ics_url VARCHAR(1024) NULL AFTER is_active;

CREATE TABLE `event_types` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `color`      VARCHAR(20)  NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_type_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `details` carries the type/subtype-specific fields the frontend's
-- FIELD_MATRIX collects (engineer, participants, items, payment_*, etc.) —
-- free-form JSON, validated loosely in PHP, not by the schema, matching v1.
--
-- `is_all_day` replaces v1's fragile "start=00:00 and end=23:58/23:59 means
-- to-do" inference with a real flag set directly by the form, so the To-Do
-- List view and the calendar renderer don't have to pattern-match datetimes.
CREATE TABLE `events` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type_id`      INT UNSIGNED NOT NULL,
  `event_subtype`      VARCHAR(100) NULL,
  `assigned_user_id`   INT UNSIGNED NULL,
  `created_by`         INT UNSIGNED NOT NULL,
  `title`              VARCHAR(255) NOT NULL,
  `description`        TEXT NULL,
  `location`           VARCHAR(255) NULL,
  `start_datetime`     DATETIME NOT NULL,
  `end_datetime`       DATETIME NULL,
  `is_all_day`         TINYINT(1) NOT NULL DEFAULT 0,
  `status`             VARCHAR(30) NOT NULL DEFAULT 'Scheduled',
  `priority`           VARCHAR(20) NOT NULL DEFAULT 'Normal',
  `details`            JSON NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_events_assigned_user` (`assigned_user_id`),
  KEY `idx_events_start` (`start_datetime`),
  KEY `idx_events_type` (`event_type_id`),
  CONSTRAINT `fk_event_type`     FOREIGN KEY (`event_type_id`)    REFERENCES `event_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_assignee` FOREIGN KEY (`assigned_user_id`) REFERENCES `calendar_user_roles` (`fieldclock_user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_creator`  FOREIGN KEY (`created_by`)       REFERENCES `calendar_user_roles` (`fieldclock_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `clients` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- A "job" here is a scheduled project timeline, distinct from FieldClock's
-- own operational `jobs` (GPS clock-in radius, etc.) — `fieldclock_job_id`
-- is only a soft de-dupe key for the sync-from-FieldClock action, not a
-- foreign key (the two databases are never joined directly).
--
-- `photo_path` and `lead_time_days` back the Carpentry Production Calendar:
-- the recommended production start date (`projected_end` − `lead_time_days`)
-- is derived client-side, never stored.
CREATE TABLE `jobs` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id`          INT UNSIGNED NULL,
  `title`              VARCHAR(255) NOT NULL,
  `estimate_number`    VARCHAR(20)  NULL,
  `address`            VARCHAR(255) NULL,
  `scope`              TEXT NULL,
  `projected_start`    DATE NULL,
  `projected_end`      DATE NULL,
  `status`             VARCHAR(30) NOT NULL DEFAULT 'Active',
  `color`              VARCHAR(20) NULL,
  `photo_path`         VARCHAR(255) NULL,
  `lead_time_days`     INT UNSIGNED NULL,
  `fieldclock_job_id`  INT UNSIGNED NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jobs_client` (`client_id`),
  KEY `idx_jobs_projected_end` (`projected_end`),
  CONSTRAINT `fk_job_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `job_workers` (
  `job_id`             INT UNSIGNED NOT NULL,
  `fieldclock_user_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`job_id`, `fieldclock_user_id`),
  CONSTRAINT `fk_job_worker_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `push_subscriptions` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fieldclock_user_id` INT UNSIGNED NOT NULL,
  `endpoint`           TEXT NOT NULL,
  `p256dh_key`         VARCHAR(255) NOT NULL,
  `auth_key`           VARCHAR(64)  NOT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_push_user_endpoint` (`fieldclock_user_id`, `endpoint`(255)),
  CONSTRAINT `fk_push_user` FOREIGN KEY (`fieldclock_user_id`) REFERENCES `calendar_user_roles` (`fieldclock_user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data — the real 8-value event type taxonomy the UI actually uses
-- (v1's original seeder had drifted from this; see plan notes).
INSERT INTO `event_types` (`name`, `color`) VALUES
  ('Reminder',         '#dc2626'),
  ('Site Visit',       '#0891b2'),
  ('Meeting',          '#7c3aed'),
  ('Communication',    '#f97316'),
  ('Supplies',         '#16a34a'),
  ('Logistics',        '#2563eb'),
  ('Estimate/Invoice', '#ca8a04'),
  ('Payment',          '#db2777');

-- Seed the first Admin manually after import:
--   INSERT INTO calendar_user_roles (fieldclock_user_id, name, role)
--   VALUES (<their FieldClock user id>, '<their name>', 'Admin');
