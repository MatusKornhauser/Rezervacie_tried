<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

$teacherId = require_login();

$stmt = db()->prepare(
    'SELECT r.id, r.teacher_id, r.classroom_id, c.name AS classroom_name, r.date, r.time_from, r.time_to, r.note
     FROM reservations r
     JOIN classrooms c ON c.id = r.classroom_id
     WHERE r.teacher_id = ?
     ORDER BY r.date DESC, r.time_from DESC'
);
$stmt->execute([$teacherId]);
$reservations = $stmt->fetchAll();

json_response(['ok' => true, 'reservations' => $reservations]);
