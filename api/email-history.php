<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_auth();

$pdo = db();
$rows = $pdo->query(
    'SELECT eh.id, eh.recipient_email, eh.subject, eh.status, eh.sent_at, eh.error_message, c.campaign_name
     FROM email_history eh
     LEFT JOIN campaigns c ON c.id = eh.campaign_id
     ORDER BY eh.id DESC LIMIT 50'
)->fetchAll();
json_response(['data' => $rows]);
