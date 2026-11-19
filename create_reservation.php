<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

$teacherId = require_login();

$payload = $_SERVER['REQUEST_METHOD'] === 'POST' ? array_merge($_POST, json_input()) : json_input();

$classroomId = isset($payload['classroom_id']) ? (int)$payload['classroom_id'] : 0;
$date = (string)($payload['date'] ?? '');
$timeFrom = (string)($payload['time_from'] ?? '');
$timeTo = (string)($payload['time_to'] ?? '');
$note = trim((string)($payload['note'] ?? ''));

if ($classroomId <= 0) {
    json_response(['ok' => false, 'error' => 'Invalid classroom_id'], 400);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_response(['ok' => false, 'error' => 'Invalid date'], 400);
    exit;
}

if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $timeFrom) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $timeTo)) {
    json_response(['ok' => false, 'error' => 'Invalid time format'], 400);
    exit;
}

$fromDt = DateTime::createFromFormat('H:i', substr($timeFrom, 0, 5));
$toDt = DateTime::createFromFormat('H:i', substr($timeTo, 0, 5));

if (!$fromDt || !$toDt) {
    json_response(['ok' => false, 'error' => 'Invalid time'], 400);
    exit;
}

$fromMinutes = ((int)$fromDt->format('H')) * 60 + (int)$fromDt->format('i');
$toMinutes = ((int)$toDt->format('H')) * 60 + (int)$toDt->format('i');

if ($fromMinutes >= $toMinutes) {
    json_response(['ok' => false, 'error' => 'time_from must be before time_to'], 400);
    exit;
}

if (($fromMinutes % 30) !== 0 || ($toMinutes % 30) !== 0) {
    json_response(['ok' => false, 'error' => 'Times must be in 30-minute blocks'], 400);
    exit;
}

$stmt = db()->prepare('SELECT time_from, time_to FROM reservations WHERE classroom_id = ? AND date = ?');
$stmt->execute([$classroomId, $date]);
$existing = $stmt->fetchAll();

foreach ($existing as $r) {
    $exFrom = substr((string)$r['time_from'], 0, 5);
    $exTo = substr((string)$r['time_to'], 0, 5);
    $newFrom = substr($timeFrom, 0, 5);
    $newTo = substr($timeTo, 0, 5);
    if (!($exTo <= $newFrom || $exFrom >= $newTo)) {
        json_response(['ok' => false, 'error' => 'Časový konflikt: učebňa je v tomto čase už obsadená.'], 409);
        exit;
    }
}

$ins = db()->prepare('INSERT INTO reservations (teacher_id, classroom_id, date, time_from, time_to, note) VALUES (?, ?, ?, ?, ?, ?)');
$ins->execute([$teacherId, $classroomId, $date, $timeFrom, $timeTo, $note !== '' ? $note : null]);

json_response(['ok' => true, 'id' => (int)db()->lastInsertId()]);
