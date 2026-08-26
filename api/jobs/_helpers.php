<?php
const JOB_SELECT = '
    SELECT j.*, c.name AS client_name
    FROM jobs j
    LEFT JOIN clients c ON c.id = j.client_id
';

function formatJob(PDO $pdo, array $row): array {
    $stmt = $pdo->prepare(
        'SELECT jw.fieldclock_user_id AS id, cur.name FROM job_workers jw
         LEFT JOIN calendar_user_roles cur ON cur.fieldclock_user_id = jw.fieldclock_user_id
         WHERE jw.job_id = ?'
    );
    $stmt->execute([$row['id']]);
    $workers = $stmt->fetchAll();

    return [
        'id'              => (int)$row['id'],
        'client_id'       => $row['client_id'] !== null ? (int)$row['client_id'] : null,
        'client_name'     => $row['client_name'],
        'title'           => $row['title'],
        'estimate_number' => $row['estimate_number'],
        'address'         => $row['address'],
        'scope'           => $row['scope'],
        'projected_start' => $row['projected_start'],
        'projected_end'   => $row['projected_end'],
        'status'          => $row['status'],
        'color'           => $row['color'],
        'photo_path'      => $row['photo_path'],
        'photo_url'       => $row['photo_path'] ? APP_URL . '/uploads/' . $row['photo_path'] : null,
        'lead_time_days'  => $row['lead_time_days'] !== null ? (int)$row['lead_time_days'] : null,
        'workers'         => array_map(fn($w) => ['id' => (int)$w['id'], 'name' => $w['name']], $workers),
        'worker_ids'      => array_map(fn($w) => (int)$w['id'], $workers),
    ];
}

function syncJobWorkers(PDO $pdo, int $jobId, array $workerIds): void {
    $pdo->prepare('DELETE FROM job_workers WHERE job_id = ?')->execute([$jobId]);
    $stmt = $pdo->prepare('INSERT IGNORE INTO job_workers (job_id, fieldclock_user_id) VALUES (?, ?)');
    foreach (array_unique(array_map('intval', $workerIds)) as $workerId) {
        $stmt->execute([$jobId, $workerId]);
    }
}
