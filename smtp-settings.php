<?php
declare(strict_types=1);

$pageTitle = 'SMTP Settings';
$navTitle = 'SMTP Settings';

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/includes/navbar.php';
require_once __DIR__.'/config/mail.php';

$smtp = get_smtp_settings() ?: [
    'host' => 'smtp.mailflow.example.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'admin@example.com',
    'password' => '',
    'from_name' => 'MailFlow',
    'from_email' => 'admin@example.com',
];
$hasPassword = ($smtp['password'] ?? '') !== '';
?>

        <div class="page-header">
          <div>
            <h2>SMTP Settings</h2>
            <p>Configure your outgoing mail server for campaign delivery.</p>
          </div>
        </div>

        <div class="alert-security mb-4">
          <i class="bi bi-shield-lock-fill"></i>
          <div>
            <strong>Your SMTP credentials are sensitive information.</strong>
            <p class="mb-0">Never share them publicly. These values are stored in the database.</p>
          </div>
        </div>

        <form method="post" action="actions/smtp-actions.php" id="smtpForm">
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-lg-7">
              <div class="card-surface mb-3">
                <div class="card-header-row"><h3>SMTP Configuration</h3></div>
                <div class="row g-3 px-3 pb-3">
                  <div class="col-md-8">
                    <label class="form-label" for="smtpHost">SMTP Host <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="smtpHost" name="host" value="<?= e($smtp['host']) ?>" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="smtpPort">SMTP Port <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="smtpPort" name="port" value="<?= (int) $smtp['port'] ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="smtpEncryption">Encryption</label>
                    <select class="form-select" id="smtpEncryption" name="encryption">
                      <option value="tls" <?= $smtp['encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                      <option value="ssl" <?= $smtp['encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                      <option value="none" <?= $smtp['encryption'] === 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="smtpUsername">SMTP Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="smtpUsername" name="username" value="<?= e($smtp['username']) ?>" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="smtpPassword">SMTP Password</label>
                    <div class="input-group">
                      <input type="password" class="form-control" id="smtpPassword" name="password"
                             value=""
                             placeholder="<?= $hasPassword ? 'Leave blank to keep existing password' : 'Enter your SMTP password' ?>">
                      <button class="btn btn-outline-secondary" type="button" data-password-toggle="#smtpPassword"><i class="bi bi-eye"></i></button>
                    </div>
                    <?php if ($hasPassword) { ?>
                    <div class="form-text text-success"><i class="bi bi-check-circle"></i> Password saved. Leave blank to keep it, or type a new one to replace it.</div>
                    <?php } else { ?>
                    <div class="form-text text-warning"><i class="bi bi-exclamation-circle"></i> No password saved yet. Enter your SMTP password above.</div>
                    <?php } ?>
                    <input type="hidden" name="has_existing_password" value="<?= $hasPassword ? '1' : '0' ?>">
                  </div>
                </div>
              </div>

              <div class="card-surface">
                <div class="card-header-row"><h3>Sender Information</h3></div>
                <div class="row g-3 px-3 pb-3">
                  <div class="col-md-6">
                    <label class="form-label" for="fromName">From Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="fromName" name="from_name" value="<?= e($smtp['from_name']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="fromEmail">From Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="fromEmail" name="from_email" value="<?= e($smtp['from_email']) ?>" required>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-5">
              <div class="card-surface h-100">
                <div class="card-header-row"><h3>Connection Status</h3></div>
                <div class="smtp-status-panel">
                  <div class="status-pill <?= $hasPassword ? 'success' : '' ?>" style="<?= ! $hasPassword ? 'background:var(--warning-soft);color:#D97706' : '' ?>">
                    <i class="bi bi-<?= $hasPassword ? 'check-circle-fill' : 'exclamation-circle-fill' ?>"></i>
                    <?= $hasPassword ? 'Credentials saved' : 'Password not set yet' ?>
                  </div>
                  <ul class="smtp-tips">
                    <li>Use port <strong>587</strong> with TLS for most providers.</li>
                    <li>Use port <strong>465</strong> with SSL if TLS fails.</li>
                    <li>Ensure the from email matches your authenticated domain.</li>
                    <li>Test the connection after any credential change.</li>
                  </ul>
                  <div class="smtp-actions">
                    <button type="submit" name="action" value="test" class="btn btn-outline-primary w-100" id="btnTestSmtp">
                      <i class="bi bi-plug"></i> Test Connection
                    </button>
                    <button type="submit" name="action" value="save" class="btn btn-primary w-100" id="btnSaveSmtp">
                      <i class="bi bi-check-lg"></i> Save Configuration
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>

<?php require_once __DIR__.'/includes/footer.php'; ?>
