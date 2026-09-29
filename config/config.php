<?php
declare(strict_types=1);

/**
 * Application bootstrap & configuration.
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

if (class_exists(Dotenv\Dotenv::class) && file_exists(ROOT_PATH . '/.env')) {
    Dotenv\Dotenv::createImmutable(ROOT_PATH)->safeLoad();
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            default => $value,
        };
    }
}

return [
    'app_name' => (string) env('APP_NAME', 'MailFlow'),
    'app_url' => rtrim((string) env('APP_URL', 'http://127.0.0.1:8080'), '/'),
    'debug' => (bool) env('APP_DEBUG', true),
    'session_name' => (string) env('SESSION_NAME', 'mailflow_session'),
    'queue_batch_size' => (int) env('QUEUE_BATCH_SIZE', 20),
    'queue_max_attempts' => (int) env('QUEUE_MAX_ATTEMPTS', 3),
    'upload_path' => ROOT_PATH . '/uploads/attachments',
    'upload_max_bytes' => 10 * 1024 * 1024,
];
