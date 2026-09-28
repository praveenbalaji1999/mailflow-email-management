<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../config/mail.php';
require_auth();

if (! is_post()) {
    redirect('smtp-settings.php');
}
require_csrf();

$action = (string) ($_POST['action'] ?? 'save');

try {
    $pdo = db();

    if ($action === 'test') {
        $result = test_smtp_connection();
        flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('smtp-settings.php');
    }

    $host = trim((string) ($_POST['host'] ?? ''));
    $port = (int) ($_POST['port'] ?? 587);
    $encryption = (string) ($_POST['encryption'] ?? 'tls');
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $fromName = trim((string) ($_POST['from_name'] ?? ''));
    $fromEmail = trim((string) ($_POST['from_email'] ?? ''));

    if ($host === '' || $username === '' || $fromName === '' || $fromEmail === '') {
        throw new InvalidArgumentException('Please complete all required SMTP fields.');
    }
    if (! filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('From email is invalid.');
    }
    if (! in_array($encryption, ['tls', 'ssl', 'none'], true)) {
        $encryption = 'tls';
    }
    if ($port < 1 || $port > 65535) {
        throw new InvalidArgumentException('Invalid SMTP port.');
    }

    $existing = $pdo->query('SELECT id, password FROM smtp_settings ORDER BY id ASC LIMIT 1')->fetch();

    // Keep existing password if: field is blank AND user had an existing password saved
    $hasExistingPassword = ($_POST['has_existing_password'] ?? '0') === '1';
    if ($password === '' && $hasExistingPassword && $existing) {
        $password = $existing['password'];
    }

    if ($existing) {
        $stmt = $pdo->prepare(
            'UPDATE smtp_settings SET host=?, port=?, encryption=?, username=?, password=?, from_name=?, from_email=? WHERE id=?'
        );
        $stmt->execute([$host, $port, $encryption, $username, $password, $fromName, $fromEmail, $existing['id']]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO smtp_settings (host, port, encryption, username, password, from_name, from_email) VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([$host, $port, $encryption, $username, $password, $fromName, $fromEmail]);
    }

    flash('success', 'SMTP configuration saved successfully');
} catch (Throwable $e) {
    flash('error', $e->getMessage());
}

redirect('smtp-settings.php');
