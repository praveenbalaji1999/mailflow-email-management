<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_auth();

$pdo = db();
$rows = $pdo->query(
    'SELECT g.id, g.name, g.description, COUNT(gm.id) AS member_count
     FROM `groups` g LEFT JOIN group_members gm ON gm.group_id = g.id
     GROUP BY g.id ORDER BY g.name'
)->fetchAll();
json_response(['data' => $rows]);
