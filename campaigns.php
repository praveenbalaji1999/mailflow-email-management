<?php
declare(strict_types=1);

$pageTitle = 'Campaigns';
$navTitle = 'Campaigns';

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/includes/navbar.php';

$pdo = db();
$status = (string) ($_GET['status'] ?? 'all');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where = '1=1';
$params = [];
$allowed = ['draft', 'pending', 'processing', 'completed', 'failed', 'cancelled'];
if (in_array(strtolower($status), $allowed, true)) {
    $where = 'status = ?';
    $params[] = strtolower($status);
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE {$where}");
$countStmt->execute($params);
$pager = paginate((int) $countStmt->fetchColumn(), $page, $perPage);

$list = $pdo->prepare(
    "SELECT * FROM campaigns WHERE {$where} ORDER BY created_at DESC LIMIT {$pager['per_page']} OFFSET {$pager['offset']}"
);
$list->execute($params);
$campaigns = $list->fetchAll();

$filters = ['all' => 'All', 'completed' => 'Completed', 'processing' => 'Processing', 'pending' => 'Pending', 'draft' => 'Draft', 'failed' => 'Failed'];
$queryBase = $status !== 'all' ? 'status='.urlencode($status) : '';
?>

        <div class="page-header">
          <div>
            <h2>Campaigns</h2>
            <p>Track and manage all your email campaigns.</p>
          </div>
          <div class="page-actions">
            <a href="compose.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Compose Email</a>
          </div>
        </div>

        <div class="filter-pills mb-3">
          <?php foreach ($filters as $key => $label) { ?>
            <a href="?status=<?= e($key) ?>" class="filter-pill <?= strtolower($status) === $key ? 'active' : '' ?>"><?= e($label) ?></a>
          <?php } ?>
        </div>

        <div class="card-surface">
          <?php if (! $campaigns) { ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-envelope"></i></div>
            <h3>No campaigns yet</h3>
            <p>Create your first email campaign<br>to start communicating with your recipients.</p>
            <a href="compose.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Compose Email</a>
          </div>
          <?php } else { ?>
          <div class="table-wrap">
            <table class="table-modern">
              <thead>
                <tr>
                  <th>Campaign</th>
                  <th>Recipients</th>
                  <th>Sent</th>
                  <th>Failed</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($campaigns as $c) { ?>
                <tr>
                  <td>
                    <div class="campaign-cell">
                      <strong><?= e($c['campaign_name']) ?></strong>
                      <span class="text-muted small d-block"><?= e($c['subject'] ?: '—') ?></span>
                    </div>
                  </td>
                  <td><?= (int) $c['total_recipients'] ?></td>
                  <td><?= (int) $c['total_sent'] ?></td>
                  <td><?= (int) $c['total_failed'] ?></td>
                  <td><?= status_badge($c['status']) ?></td>
                  <td><?= e(format_date($c['created_at'])) ?></td>
                  <td>
                    <div class="action-btns">
                      <?php if ($c['status'] === 'draft') { ?>
                        <a href="compose.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                      <?php } else { ?>
                        <a href="campaign-details.php?id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                      <?php } ?>
                      <form method="post" action="actions/email-actions.php" class="d-inline" onsubmit="return confirm('Delete this campaign?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button type="submit" class="btn-icon-sm text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <div class="pagination-bar">
            <span class="pagination-info">Showing <strong><?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?></strong> of <strong><?= (int) $pager['total'] ?></strong></span>
            <?= pagination_html($pager, $queryBase) ?>
          </div>
          <?php } ?>
        </div>

<?php require_once __DIR__.'/includes/footer.php'; ?>
