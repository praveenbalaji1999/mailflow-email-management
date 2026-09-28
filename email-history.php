<?php
declare(strict_types=1);

$pageTitle = 'Email History';
$navTitle = 'Email History';

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/includes/navbar.php';

$pdo = db();
$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$campaignId = (int) ($_GET['campaign'] ?? 0);
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(eh.recipient_email LIKE ? OR eh.subject LIKE ?)';
    $params[] = '%'.$q.'%';
    $params[] = '%'.$q.'%';
}
if (in_array($status, ['sent', 'pending', 'failed'], true)) {
    $where[] = 'eh.status = ?';
    $params[] = $status;
}
if ($campaignId > 0) {
    $where[] = 'eh.campaign_id = ?';
    $params[] = $campaignId;
}
if ($from !== '') {
    $where[] = 'DATE(COALESCE(eh.sent_at, eh.created_at)) >= ?';
    $params[] = $from;
}
if ($to !== '') {
    $where[] = 'DATE(COALESCE(eh.sent_at, eh.created_at)) <= ?';
    $params[] = $to;
}
$sqlWhere = implode(' AND ', $where);

$count = $pdo->prepare("SELECT COUNT(*) FROM email_history eh WHERE {$sqlWhere}");
$count->execute($params);
$pager = paginate((int) $count->fetchColumn(), $page, 15);

$list = $pdo->prepare(
    "SELECT eh.*, c.campaign_name
     FROM email_history eh
     LEFT JOIN campaigns c ON c.id = eh.campaign_id
     WHERE {$sqlWhere}
     ORDER BY eh.id DESC
     LIMIT {$pager['per_page']} OFFSET {$pager['offset']}"
);
$list->execute($params);
$rows = $list->fetchAll();

$campaigns = $pdo->query('SELECT id, campaign_name FROM campaigns ORDER BY created_at DESC LIMIT 100')->fetchAll();
$queryBase = http_build_query(array_filter([
    'q' => $q ?: null,
    'status' => $status ?: null,
    'campaign' => $campaignId ?: null,
    'from' => $from ?: null,
    'to' => $to ?: null,
]));
?>

        <div class="page-header">
          <div>
            <h2>Email History</h2>
            <p>Detailed log of every email delivery attempt.</p>
          </div>
        </div>

        <div class="card-surface">
          <form class="toolbar-row wrap" method="get">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search recipient or subject...">
            </div>
            <div class="toolbar-filters">
              <input type="date" class="form-control form-control-sm" name="from" value="<?= e($from) ?>" aria-label="From date">
              <span class="text-muted small">to</span>
              <input type="date" class="form-control form-control-sm" name="to" value="<?= e($to) ?>" aria-label="To date">
              <select class="form-select form-select-sm" name="status">
                <option value="">All Status</option>
                <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Sent</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
              </select>
              <select class="form-select form-select-sm" name="campaign">
                <option value="0">All Campaigns</option>
                <?php foreach ($campaigns as $c) { ?>
                  <option value="<?= (int) $c['id'] ?>" <?= $campaignId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['campaign_name']) ?></option>
                <?php } ?>
              </select>
              <button class="btn btn-sm btn-primary" type="submit">Filter</button>
            </div>
          </form>

          <div class="table-wrap">
            <table class="table-modern">
              <thead>
                <tr>
                  <th>Recipient</th>
                  <th>Campaign</th>
                  <th>Subject</th>
                  <th>Status</th>
                  <th>Sent At</th>
                  <th>Error</th>
                </tr>
              </thead>
              <tbody>
                <?php if (! $rows) { ?>
                  <tr><td colspan="6" class="text-center text-muted py-4">No history found.</td></tr>
                <?php } ?>
                <?php foreach ($rows as $r) { ?>
                <tr>
                  <td><?= e($r['recipient_email']) ?></td>
                  <td><?= e($r['campaign_name'] ?: '—') ?></td>
                  <td><?= e($r['subject']) ?></td>
                  <td><?= status_badge($r['status']) ?></td>
                  <td><?= e(format_datetime($r['sent_at'])) ?></td>
                  <td><?= $r['error_message'] ? '<span class="error-text">'.e($r['error_message']).'</span>' : '<span class="text-muted">—</span>' ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <div class="pagination-bar">
            <span class="pagination-info">Showing <strong><?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?></strong> of <strong><?= (int) $pager['total'] ?></strong></span>
            <?= pagination_html($pager, $queryBase) ?>
          </div>
        </div>

<?php require_once __DIR__.'/includes/footer.php'; ?>
