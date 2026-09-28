<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_auth();

$pdo = db();
$rows = $pdo->query(
    'SELECT id, campaign_name, subject, status, total_recipients, total_sent, total_failed, total_pending, created_at
     FROM campaigns ORDER BY created_at DESC LIMIT 50'
)->fetchAll();
json_response(['data' => $rows]);
