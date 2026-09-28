<?php
declare(strict_types=1);
/** @var array $admin */
/** @var string $pageTitle */
$navTitle = $navTitle ?? $pageTitle ?? 'Dashboard';
?>
    <div class="main-content">
      <header class="top-navbar">
        <div class="topbar-left">
          <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
          </button>
          <h1 class="page-title-nav"><?= e($navTitle) ?></h1>
        </div>
        <div class="topbar-right">
          <button class="btn-icon" type="button" data-bs-toggle="tooltip" title="Notifications" aria-label="Notifications">
            <i class="bi bi-bell"></i>
          </button>
          <div class="dropdown profile-dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="avatar"><?= e(initials($admin['name'] ?? 'AU')) ?></div>
              <div class="profile-meta">
                <span class="name"><?= e($admin['name'] ?? 'Admin User') ?></span>
                <span class="role">Administrator</span>
              </div>
              <i class="bi bi-chevron-down profile-chevron"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="smtp-settings.php"><i class="bi bi-person"></i> Profile</a></li>
              <li><a class="dropdown-item" href="smtp-settings.php"><i class="bi bi-gear"></i> Settings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php" data-logout><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
          </div>
        </div>
      </header>
      <main class="page-content">
<?php if (! empty($flashes)) { ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  <?php foreach ($flashes as $flash) { ?>
  if (window.MailFlow) {
    MailFlow.showToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type'] === 'error' ? 'danger' : $flash['type']) ?>);
  }
  <?php } ?>
});
</script>
<?php } ?>
