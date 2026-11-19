<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

$teacherId = require_login();

$id = 0;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
} else {
    $payload = json_input();
    if (isset($payload['id'])) {
        $id = (int)$payload['id'];
    }
}

if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'Invalid id'], 400);
    exit;
}

$stmt = db()->prepare('SELECT id, teacher_id FROM reservations WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    json_response(['ok' => false, 'error' => 'Not found'], 404);
    exit;
}

if ((int)$row['teacher_id'] !== $teacherId) {
    json_response(['ok' => false, 'error' => 'Forbidden'], 403);
    exit;
}

$del = db()->prepare('DELETE FROM reservations WHERE id = ?');
$del->execute([$id]);

json_response(['ok' => true]);
