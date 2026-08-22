<?php
/**
 * Load .env from project root and expose renewdesk_env() for config.
 * Server env vars (SetEnv, Docker, PaaS) take precedence over .env file.
 */
function renewdesk_bootstrap_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === '') {
            continue;
        }
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        if (getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}

function renewdesk_env(string $key, ?string $default = null): ?string
{
    renewdesk_bootstrap_env();
    $v = getenv($key);
    if ($v !== false) {
        return $v;
    }
    if (array_key_exists($key, $_ENV)) {
        return (string) $_ENV[$key];
    }
    return $default;
}

function renewdesk_debug(): bool
{
    return filter_var(renewdesk_env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
}
