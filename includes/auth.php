<?php

declare(strict_types=1);

/**
 * Session auth helpers.
 */

require_once __DIR__.'/functions.php';

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $name = (string) app_config('session_name', 'mailflow_session');
    session_name($name);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function current_admin(): ?array
{
    start_app_session();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $admin = null;
    if ($admin !== null) {
        return $admin;
    }
    $stmt = db()->prepare('SELECT id, name, email FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: null;
    if (! $admin) {
        unset($_SESSION['admin_id']);
    }

    return $admin;
}

function require_auth(): array
{
    start_app_session();
    $admin = current_admin();
    if (! $admin) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }

    return $admin;
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, name, email, password FROM admins WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $admin = $stmt->fetch();
    if (! $admin || ! password_verify($password, $admin['password'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];

    return true;
}

function logout_admin(): void
{
    start_app_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}
