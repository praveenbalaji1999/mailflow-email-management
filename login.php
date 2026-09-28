<?php
declare(strict_types=1);

require_once __DIR__.'/includes/auth.php';
start_app_session();

if (current_admin()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

if (is_post()) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        try {
            if (! attempt_login($email, $password)) {
                $error = 'Invalid email or password.';
            } else {
                flash('success', 'Welcome back!');
                redirect('dashboard.php');
            }
        } catch (Throwable $e) {
            $error = 'Database is not ready. Run this in a terminal, then try again:<br><code>sudo bash ~/email-management-ui/setup-db.sh</code>';
            if ((bool) env('APP_DEBUG', false)) {
                $error .= '<br><span class="small">'.e($e->getMessage()).'</span>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — MailFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    .login-page {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 1.5rem;
      background:
        radial-gradient(ellipse at top left, rgba(37,99,235,.12), transparent 50%),
        radial-gradient(ellipse at bottom right, rgba(37,99,235,.08), transparent 45%),
        #F8FAFC;
    }
    .login-card {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border: 1px solid #E2E8F0;
      border-radius: 16px;
      box-shadow: 0 12px 32px rgba(15,23,42,.08);
      padding: 2rem;
    }
    .login-brand {
      display: flex;
      align-items: center;
      gap: .85rem;
      margin-bottom: 1.75rem;
    }
    .login-brand-icon {
      width: 48px; height: 48px; border-radius: 12px;
      background: linear-gradient(145deg, #3B82F6, #1D4ED8);
      color: #fff; display: grid; place-items: center; font-size: 1.25rem;
    }
  </style>
</head>
<body>
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <div class="login-brand-icon"><i class="bi bi-envelope-fill"></i></div>
        <div>
          <h1 class="h4 mb-0 fw-bold">MailFlow</h1>
          <span class="text-secondary small">Email Management</span>
        </div>
      </div>
      <h2 class="h5 mb-1">Sign in</h2>
      <p class="text-secondary mb-4">Access your email campaign dashboard.</p>

      <?php if ($error) { ?>
        <div class="alert alert-danger py-2"><?= $error ?></div>
      <?php } ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus placeholder="admin@mailflow.local">
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign in</button>
      </form>
      <p class="small text-muted mt-4 mb-0 text-center">Default: admin@mailflow.local / Admin@123</p>
    </div>
  </div>
</body>
</html>
