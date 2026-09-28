<?php
declare(strict_types=1);

/** @var string $pageTitle */
/** @var string|null $pageScript */

require_once __DIR__.'/auth.php';
$admin = require_auth();
$appName = (string) app_config('app_name', 'MailFlow');
$pageTitle = $pageTitle ?? 'Dashboard';
$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — <?= e($appName) ?></title>
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
  <div class="app-wrapper">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
