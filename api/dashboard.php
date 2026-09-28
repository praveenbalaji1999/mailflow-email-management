<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_auth();

$pdo = db();
json_response([
    'recipients' => (int) $pdo->query('SELECT COUNT(*) FROM recipients')->fetchColumn(),
    'emails_sent' => (int) $pdo->query("SELECT COUNT(*) FROM email_history WHERE status = 'sent'")->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status IN ('pending','processing')")->fetchColumn(),
    'failed' => (int) $pdo->query("SELECT COUNT(*) FROM email_history WHERE status = 'failed'")->fetchColumn(),
]);
