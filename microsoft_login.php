<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';

start_app_session();

$config = require __DIR__ . '/config.php';

// Generate random state parameter for security
$state = bin2hex(random_bytes(16));
$_SESSION['microsoft_oauth_state'] = $state;

// PKCE code verifier for enhanced security
$codeVerifier = bin2hex(random_bytes(32));
$_SESSION['microsoft_code_verifier'] = $codeVerifier;

// Generate code challenge (SHA256 hash of verifier, base64url encoded)
$codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

// Build Microsoft OAuth URL
$tenantId = $config['microsoft_tenant_id'] ?? 'common';
$authorizeUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize";

$params = [
    'client_id' => $config['microsoft_client_id'],
    'response_type' => 'code',
    'redirect_uri' => $config['microsoft_redirect_uri'],
    'scope' => 'openid email profile User.Read',
    'state' => $state,
    'code_challenge' => $codeChallenge,
    'code_challenge_method' => 'S256',
    'response_mode' => 'query',
];

$authUrl = $authorizeUrl . '?' . http_build_query($params);

header("Location: {$authUrl}");
exit;
