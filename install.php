<?php
declare(strict_types=1);

/**
 * One-time installer: creates DB (if possible), imports schema, seeds admin.
 * Visit: /install.php
 */

require_once __DIR__.'/config/config.php';

$messages = [];
$errors = [];
$done = false;

$adminEmail = 'admin@mailflow.local';
$adminPassword = 'Admin@123';
$adminName = 'Admin User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $port = trim($_POST['db_port'] ?? '3306');
    $name = trim($_POST['db_name'] ?? 'email_management');
    $user = trim($_POST['db_user'] ?? 'mailflow');
    $pass = (string) ($_POST['db_pass'] ?? '');
    $rootUser = trim($_POST['root_user'] ?? 'root');
    $rootPass = (string) ($_POST['root_pass'] ?? '');

    try {
        // Try connecting as provided app user first; fall back to root to create DB/user
        $adminDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        try {
            $adminPdo = new PDO($adminDsn, $rootUser, $rootPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $messages[] = "Database `{$name}` ensured.";

            // Create app user if using root
            if ($user !== $rootUser) {
                $adminPdo->exec("CREATE USER IF NOT EXISTS '{$user}'@'localhost' IDENTIFIED BY ".$adminPdo->quote($pass));
                $adminPdo->exec("CREATE USER IF NOT EXISTS '{$user}'@'%' IDENTIFIED BY ".$adminPdo->quote($pass));
                $adminPdo->exec("GRANT ALL PRIVILEGES ON `{$name}`.* TO '{$user}'@'localhost'");
                $adminPdo->exec("GRANT ALL PRIVILEGES ON `{$name}`.* TO '{$user}'@'%'");
                $adminPdo->exec('FLUSH PRIVILEGES');
                $messages[] = "User `{$user}` granted access.";
            }
        } catch (Throwable $e) {
            $messages[] = 'Could not use root credentials to create DB (will try app user): '.$e->getMessage();
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $sql = file_get_contents(__DIR__.'/database/schema.sql');
        // Strip CREATE DATABASE / USE so we run against selected DB
        $sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql) ?? $sql;
        $sql = preg_replace('/USE\s+\w+\s*;/i', '', $sql) ?? $sql;

        $pdo->exec($sql);
        $messages[] = 'Schema imported.';

        $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO admins (name, email, password) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password)'
        );
        $stmt->execute([$adminName, $adminEmail, $hash]);
        $messages[] = "Admin ready: {$adminEmail} / {$adminPassword}";

        // Seed sample recipients if empty
        $count = (int) $pdo->query('SELECT COUNT(*) FROM recipients')->fetchColumn();
        if ($count === 0) {
            $samples = [
                ['John Mitchell', 'john.mitchell@gmail.com', 'active', 'All Customers'],
                ['Kumar Sharma', 'kumar.sharma@company.com', 'active', 'Employees'],
                ['Priya Patel', 'priya.patel@gmail.com', 'active', 'Premium Customers'],
                ['Sarah Chen', 'sarah.chen@hr.company.com', 'active', 'HR Team'],
                ['Marcus Webb', 'marcus.webb@sales.company.com', 'inactive', 'Sales Team'],
                ['Aisha Rahman', 'aisha.rahman@gmail.com', 'active', 'Marketing'],
                ['David Okonkwo', 'david.okonkwo@outlook.com', 'active', 'All Customers'],
                ['Elena Rossi', 'elena.rossi@premium.io', 'active', 'Premium Customers'],
            ];
            $insR = $pdo->prepare('INSERT INTO recipients (name, email, status) VALUES (?, ?, ?)');
            $findG = $pdo->prepare('SELECT id FROM `groups` WHERE name = ? LIMIT 1');
            $insM = $pdo->prepare('INSERT IGNORE INTO group_members (group_id, recipient_id) VALUES (?, ?)');
            foreach ($samples as [$n, $em, $st, $g]) {
                $insR->execute([$n, $em, $st]);
                $rid = (int) $pdo->lastInsertId();
                $findG->execute([$g]);
                $gid = (int) ($findG->fetchColumn() ?: 0);
                if ($gid && $rid) {
                    $insM->execute([$gid, $rid]);
                }
            }
            $messages[] = 'Sample recipients seeded.';
        }

        // Write .env
        $env = "APP_NAME=MailFlow\nAPP_URL=http://127.0.0.1:8080\nAPP_DEBUG=true\n\n"
            ."DB_HOST={$host}\nDB_PORT={$port}\nDB_NAME={$name}\nDB_USER={$user}\nDB_PASS={$pass}\n\n"
            ."SESSION_NAME=mailflow_session\n\nQUEUE_BATCH_SIZE=20\nQUEUE_MAX_ATTEMPTS=3\n";
        file_put_contents(__DIR__.'/.env', $env);
        $messages[] = '.env updated.';
        $done = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Install — MailFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body{font-family:"Plus Jakarta Sans",sans-serif;background:#F8FAFC;min-height:100vh;display:flex;align-items:center}
    .card{border:none;border-radius:16px;box-shadow:0 12px 32px rgba(15,23,42,.08)}
    .btn-primary{background:#2563EB;border-color:#2563EB}
  </style>
</head>
<body>
  <div class="container py-5" style="max-width:640px">
    <div class="card p-4 p-md-5">
      <h1 class="h3 mb-1">MailFlow Installer</h1>
      <p class="text-secondary mb-4">Create the database, import schema, and seed the admin account.</p>

      <?php foreach ($errors as $err) { ?>
        <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
      <?php } ?>
      <?php foreach ($messages as $msg) { ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
      <?php } ?>

      <?php if ($done) { ?>
        <a class="btn btn-primary" href="login.php">Go to Login</a>
        <p class="small text-muted mt-3 mb-0">Delete or protect <code>install.php</code> after setup.</p>
      <?php } else { ?>
      <form method="post" class="row g-3">
        <div class="col-md-8">
          <label class="form-label">DB Host</label>
          <input class="form-control" name="db_host" value="127.0.0.1" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Port</label>
          <input class="form-control" name="db_port" value="3306" required>
        </div>
        <div class="col-12">
          <label class="form-label">Database Name</label>
          <input class="form-control" name="db_name" value="email_management" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">App DB User</label>
          <input class="form-control" name="db_user" value="mailflow" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">App DB Password</label>
          <input class="form-control" name="db_pass" value="mailflow123" required>
        </div>
        <div class="col-12"><hr><p class="small text-secondary mb-0">Optional: MySQL admin credentials to create DB/user automatically (e.g. root via socket may need sudo CLI instead).</p></div>
        <div class="col-md-6">
          <label class="form-label">MySQL Admin User</label>
          <input class="form-control" name="root_user" value="root">
        </div>
        <div class="col-md-6">
          <label class="form-label">MySQL Admin Password</label>
          <input class="form-control" name="root_pass" type="password" value="">
        </div>
        <div class="col-12">
          <button class="btn btn-primary w-100" type="submit">Install</button>
        </div>
      </form>
      <?php } ?>
    </div>
  </div>
</body>
</html>
