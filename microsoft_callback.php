<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

start_app_session();

$config = require __DIR__ . '/config.php';

$error = (string)($_GET['error'] ?? '');
$errorDescription = (string)($_GET['error_description'] ?? '');
$code = (string)($_GET['code'] ?? '');
$state = (string)($_GET['state'] ?? '');

// Verify state parameter to prevent CSRF attacks
$expectedState = (string)($_SESSION['microsoft_oauth_state'] ?? '');
unset($_SESSION['microsoft_oauth_state']);

if ($error !== '') {
    $errorMsg = $errorDescription !== '' ? $errorDescription : $error;
    header("Location: auth.php?tab=login&error=" . urlencode("Microsoft prihlásenie zlyhalo: {$errorMsg}"));
    exit;
}

if ($state === '' || $state !== $expectedState) {
    header("Location: auth.php?tab=login&error=" . urlencode("Bezpečnostná kontrola zlyhala."));
    exit;
}

if ($code === '') {
    header("Location: auth.php?tab=login&error=" . urlencode("Neplatná odpoveď od Microsoft."));
    exit;
}

// Get code verifier for PKCE
$codeVerifier = (string)($_SESSION['microsoft_code_verifier'] ?? '');
unset($_SESSION['microsoft_code_verifier']);

if ($codeVerifier === '') {
    header("Location: auth.php?tab=login&error=" . urlencode("Bezpečnostná chyba PKCE."));
    exit;
}

// Exchange authorization code for tokens
$tenantId = $config['microsoft_tenant_id'] ?? 'common';
$tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

$postData = [
    'client_id' => $config['microsoft_client_id'],
    'client_secret' => $config['microsoft_client_secret'],
    'code' => $code,
    'redirect_uri' => $config['microsoft_redirect_uri'],
    'grant_type' => 'authorization_code',
    'code_verifier' => $codeVerifier,
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $curlError !== '') {
    header("Location: auth.php?tab=login&error=" . urlencode("Chyba spojenia s Microsoft."));
    exit;
}

if ($httpCode !== 200) {
    header("Location: auth.php?tab=login&error=" . urlencode("Nepodarilo sa získať token od Microsoft."));
    exit;
}

$tokenData = json_decode($response, true);
if (!is_array($tokenData) || !isset($tokenData['id_token'])) {
    header("Location: auth.php?tab=login&error=" . urlencode("Neplatná odpoveď od Microsoft."));
    exit;
}

// Parse ID token to get user information
$idToken = $tokenData['id_token'];
$tokenParts = explode('.', $idToken);

if (count($tokenParts) !== 3) {
    header("Location: auth.php?tab=login&error=" . urlencode("Neplatný ID token."));
    exit;
}

$payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/') . str_repeat('=', (4 - strlen($tokenParts[1]) % 4) % 4)), true);

if (!is_array($payload)) {
    header("Location: auth.php?tab=login&error=" . urlencode("Nepodarilo sa dekódovať token."));
    exit;
}

$email = isset($payload['email']) ? normalize_email($payload['email']) : '';
$preferredUsername = isset($payload['preferred_username']) ? normalize_email($payload['preferred_username']) : '';
$oid = isset($payload['oid']) ? $payload['oid'] : '';
$name = isset($payload['name']) ? $payload['name'] : '';

// Use preferred_username if email is not available
if ($email === '' && $preferredUsername !== '') {
    $email = $preferredUsername;
}

if ($email === '' || !is_allowed_teacher_email($email)) {
    header("Location: auth.php?tab=login&error=" . urlencode("Email nie je platný alebo nie je z domény @centrumnadania.sk."));
    exit;
}

// Check if teacher exists by email first, then by microsoft_id if column exists
$teacher = null;

try {
    $stmt = db()->prepare('SELECT id, full_name, microsoft_id FROM teachers WHERE username = ? OR microsoft_id = ?');
    $stmt->execute([$email, $oid]);
    $teacher = $stmt->fetch();
} catch (PDOException $e) {
    // microsoft_id column might not exist yet, try without it
    $stmt = db()->prepare('SELECT id, full_name FROM teachers WHERE username = ?');
    $stmt->execute([$email]);
    $teacher = $stmt->fetch();
}

if ($teacher) {
    // Update Microsoft ID if not set
    if ($oid !== '' && empty($teacher['microsoft_id'])) {
        $upd = db()->prepare('UPDATE teachers SET microsoft_id = ? WHERE id = ?');
        $upd->execute([$oid, $teacher['id']]);
    }
    $_SESSION['teacher_id'] = (int)$teacher['id'];
} else {
    // Create new teacher
    $displayName = $name !== '' ? $name : $email;
    try {
        $ins = db()->prepare('INSERT INTO teachers (username, full_name, microsoft_id, password_hash) VALUES (?, ?, ?, ?)');
        $ins->execute([$email, $displayName, $oid, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]);
    } catch (PDOException $e) {
        // microsoft_id column might not exist, insert without it
        $ins = db()->prepare('INSERT INTO teachers (username, full_name, password_hash) VALUES (?, ?, ?)');
        $ins->execute([$email, $displayName, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]);
    }
    $_SESSION['teacher_id'] = (int)db()->lastInsertId();
}

header('Location: index.php');
exit;
