<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

require_login();

$date = (string)($_GET['date'] ?? '');
$startDate = (string)($_GET['start_date'] ?? '');
$endDate = (string)($_GET['end_date'] ?? '');

// Single date query (backward compatible)
if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $stmt = db()->prepare(
        'SELECT r.id, r.teacher_id, t.full_name AS teacher_name, r.classroom_id, c.name AS classroom_name, r.date, r.time_from, r.time_to, r.note
         FROM reservations r
         JOIN teachers t ON t.id = r.teacher_id
         JOIN classrooms c ON c.id = r.classroom_id
         WHERE r.date = ?
         ORDER BY c.name, r.time_from'
    );
    $stmt->execute([$date]);
    $reservations = $stmt->fetchAll();
    json_response(['ok' => true, 'reservations' => $reservations]);
    exit;
}

// Date range query
if ($startDate !== '' && $endDate !== '' &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $stmt = db()->prepare(
        'SELECT r.id, r.teacher_id, t.full_name AS teacher_name, r.classroom_id, c.name AS classroom_name, r.date, r.time_from, r.time_to, r.note
         FROM reservations r
         JOIN teachers t ON t.id = r.teacher_id
         JOIN classrooms c ON c.id = r.classroom_id
         WHERE r.date >= ? AND r.date <= ?
         ORDER BY r.date, c.name, r.time_from'
    );
    $stmt->execute([$startDate, $endDate]);
    $reservations = $stmt->fetchAll();
    json_response(['ok' => true, 'reservations' => $reservations]);
    exit;
}

json_response(['ok' => false, 'error' => 'Invalid date or date range'], 400);
