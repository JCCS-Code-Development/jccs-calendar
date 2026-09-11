-- ─────────────────────────────────────────────────────────────────────────────
-- JCCS Calendar — Operations Board: job-site photos
-- Run once, by hand, against the production `jccs_calendar` database (this app
-- has no migration runner — same as 2026-09-09_ops_board.sql).
--
-- Additive only. The existing single `jobs.photo_path` (the Carpentry
-- Production Calendar's one reference photo) is left exactly as it is; this
-- table is a separate, many-per-job set that only the Operations Board reads
-- and writes.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE `job_photos` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id`      INT UNSIGNED NOT NULL,
  `file_path`   VARCHAR(255) NOT NULL,          -- relative to api/uploads/, e.g. jobs/board/42-9f3a….jpg
  `caption`     VARCHAR(300) NULL,
  `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_ip` VARCHAR(45)  NULL,
  PRIMARY KEY (`id`),
  KEY `idx_job_photos_job` (`job_id`, `sort_order`),
  CONSTRAINT `fk_job_photo_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
