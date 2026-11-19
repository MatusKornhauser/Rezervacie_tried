<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

require_login();

$stmt = db()->query('SELECT id, name, note FROM classrooms ORDER BY name');
$classrooms = $stmt->fetchAll();

json_response(['ok' => true, 'classrooms' => $classrooms]);
