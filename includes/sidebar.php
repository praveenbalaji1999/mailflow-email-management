<?php
declare(strict_types=1);
?>
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="bi bi-envelope-fill"></i></div>
        <div class="sidebar-brand-text">
          <h1>MailFlow</h1>
          <span>Email Management</span>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="dashboard.php" class="sidebar-link <?= active_page('dashboard.php') ?>"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>
        <a href="recipients.php" class="sidebar-link <?= active_page('recipients.php') ?>"><i class="bi bi-people"></i><span>Recipients</span></a>
        <a href="groups.php" class="sidebar-link <?= active_page('groups.php') ?>"><i class="bi bi-collection"></i><span>Groups</span></a>
        <a href="compose.php" class="sidebar-link <?= active_page('compose.php') ?>"><i class="bi bi-pencil-square"></i><span>Compose Email</span></a>
        <a href="campaigns.php" class="sidebar-link <?= (active_page('campaigns.php') || active_page('campaign-details.php')) ? 'active' : '' ?>"><i class="bi bi-megaphone"></i><span>Campaigns</span></a>
        <a href="email-history.php" class="sidebar-link <?= active_page('email-history.php') ?>"><i class="bi bi-clock-history"></i><span>Email History</span></a>
        <a href="smtp-settings.php" class="sidebar-link <?= active_page('smtp-settings.php') ?>"><i class="bi bi-server"></i><span>SMTP Settings</span></a>
      </nav>
      <div class="sidebar-footer">
        <a href="smtp-settings.php" class="sidebar-link <?= active_page('smtp-settings.php') ?>"><i class="bi bi-gear"></i><span>Settings</span></a>
        <a href="logout.php" class="sidebar-link" data-logout><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
      </div>
    </aside>
