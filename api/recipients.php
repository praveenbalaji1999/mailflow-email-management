<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_auth();

$pdo = db();
$q = trim((string) ($_GET['q'] ?? ''));
$sql = 'SELECT id, name, email, status FROM recipients';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE name LIKE ? OR email LIKE ?';
    $params = ['%'.$q.'%', '%'.$q.'%'];
}
$sql .= ' ORDER BY name LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
json_response(['data' => $stmt->fetchAll()]);
