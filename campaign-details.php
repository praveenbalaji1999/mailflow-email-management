<?php
declare(strict_types=1);

$pageTitle = 'Campaign Details';
$navTitle = 'Campaign Details';

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/includes/navbar.php';

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$campaign = $stmt->fetch();

if (! $campaign) {
    flash('error', 'Campaign not found.');
    redirect('campaigns.php');
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$q = trim((string) ($_GET['q'] ?? ''));
$where = 'campaign_id = ?';
$params = [$id];
if ($q !== '') {
    $where .= ' AND email LIKE ?';
    $params[] = '%'.$q.'%';
}
$count = $pdo->prepare("SELECT COUNT(*) FROM campaign_recipients WHERE {$where}");
$count->execute($params);
$pager = paginate((int) $count->fetchColumn(), $page, 10);

$list = $pdo->prepare(
    "SELECT * FROM campaign_recipients WHERE {$where} ORDER BY id ASC LIMIT {$pager['per_page']} OFFSET {$pager['offset']}"
);
$list->execute($params);
$deliveries = $list->fetchAll();

$successRate = $campaign['total_recipients'] > 0
    ? round(($campaign['total_sent'] / $campaign['total_recipients']) * 100, 1)
    : 0;
?>

        <div class="page-header">
          <div>
            <nav class="breadcrumb-nav mb-2">
              <a href="campaigns.php">Campaigns</a>
              <i class="bi bi-chevron-right"></i>
              <span><?= e($campaign['campaign_name']) ?></span>
            </nav>
            <h2><?= e($campaign['campaign_name']) ?></h2>
            <p>Campaign delivery summary and recipient status.</p>
          </div>
          <div class="page-actions">
            <?= status_badge($campaign['status']) ?>
            <?php if (in_array($campaign['status'], ['pending', 'processing'], true)) { ?>
            <form method="post" action="actions/email-actions.php" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="process_queue">
              <input type="hidden" name="id" value="<?= (int) $id ?>">
              <button class="btn btn-outline-primary" type="submit"><i class="bi bi-play-fill"></i> Process Queue</button>
            </form>
            <?php } ?>
            <a href="compose.php" class="btn btn-outline-primary"><i class="bi bi-files"></i> New Campaign</a>
          </div>
        </div>

        <div class="row g-3 gap-section">
          <div class="col-6 col-lg-3"><div class="stat-card compact"><div class="stat-label">Total</div><div class="stat-value"><?= (int) $campaign['total_recipients'] ?></div></div></div>
          <div class="col-6 col-lg-3"><div class="stat-card compact"><div class="stat-label">Sent</div><div class="stat-value text-success"><?= (int) $campaign['total_sent'] ?></div></div></div>
          <div class="col-6 col-lg-3"><div class="stat-card compact"><div class="stat-label">Failed</div><div class="stat-value text-danger"><?= (int) $campaign['total_failed'] ?></div></div></div>
          <div class="col-6 col-lg-3"><div class="stat-card compact"><div class="stat-label">Pending</div><div class="stat-value text-warning"><?= (int) $campaign['total_pending'] ?></div></div></div>
        </div>

        <div class="row g-3 gap-section">
          <div class="col-lg-5">
            <div class="card-surface h-100">
              <div class="card-header-row"><h3>Campaign Information</h3></div>
              <dl class="info-list">
                <div><dt>Campaign Name</dt><dd><?= e($campaign['campaign_name']) ?></dd></div>
                <div><dt>Subject</dt><dd><?= e($campaign['subject'] ?: '—') ?></dd></div>
                <div><dt>Created</dt><dd><?= e(format_datetime($campaign['created_at'])) ?></dd></div>
                <div><dt>Updated</dt><dd><?= e(format_datetime($campaign['updated_at'])) ?></dd></div>
              </dl>
            </div>
          </div>
          <div class="col-lg-7">
            <div class="card-surface h-100">
              <div class="card-header-row"><h3>Delivery Progress</h3></div>
              <div class="progress-block">
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-secondary">Success rate</span>
                  <strong><?= $successRate ?>%</strong>
                </div>
                <div class="progress progress-lg mb-4">
                  <div class="progress-bar bg-success" style="width:<?= min(100, $successRate) ?>%"></div>
                </div>
                <div class="delivery-breakdown">
                  <div class="breakdown-item"><span class="legend-dot" style="background:#16A34A"></span><span>Sent</span><strong><?= (int) $campaign['total_sent'] ?></strong></div>
                  <div class="breakdown-item"><span class="legend-dot" style="background:#DC2626"></span><span>Failed</span><strong><?= (int) $campaign['total_failed'] ?></strong></div>
                  <div class="breakdown-item"><span class="legend-dot" style="background:#F59E0B"></span><span>Pending</span><strong><?= (int) $campaign['total_pending'] ?></strong></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card-surface">
          <div class="card-header-row">
            <h3>Recipient Delivery</h3>
            <form class="search-box sm" method="get">
              <input type="hidden" name="id" value="<?= (int) $id ?>">
              <i class="bi bi-search"></i>
              <input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search email...">
            </form>
          </div>
          <div class="table-wrap">
            <table class="table-modern">
              <thead><tr><th>Email</th><th>Status</th><th>Sent At</th><th>Error</th></tr></thead>
              <tbody>
                <?php foreach ($deliveries as $d) { ?>
                <tr>
                  <td><?= e($d['email']) ?></td>
                  <td><?= status_badge($d['status']) ?></td>
                  <td><?= e(format_datetime($d['sent_at'])) ?></td>
                  <td><?= $d['error_message'] ? '<span class="error-text">'.e($d['error_message']).'</span>' : '<span class="text-muted">—</span>' ?></td>
                </tr>
                <?php } ?>
                <?php if (! $deliveries) { ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No delivery rows.</td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <div class="pagination-bar">
            <span class="pagination-info">Showing <strong><?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?></strong> of <strong><?= (int) $pager['total'] ?></strong></span>
            <?= pagination_html($pager, 'id='.$id.($q !== '' ? '&q='.urlencode($q) : '')) ?>
          </div>
        </div>

<?php require_once __DIR__.'/includes/footer.php'; ?>
