<?php
require_once __DIR__ . '/env.php';
renewdesk_bootstrap_env();

if (session_status() === PHP_SESSION_ACTIVE) {
    return;
}


$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

$forceSecure = renewdesk_env('SESSION_COOKIE_SECURE', null);
$secure = $forceSecure !== null
    ? filter_var($forceSecure, FILTER_VALIDATE_BOOLEAN)
    : $https;

$params = [
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
];
$domain = renewdesk_env('SESSION_COOKIE_DOMAIN', '');
if ($domain !== null && $domain !== '') {
    $params['domain'] = $domain;
}

session_set_cookie_params($params);
session_start();
