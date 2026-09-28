<?php
declare(strict_types=1);

$pageTitle = 'Recipients';
$navTitle = 'Recipients';

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/includes/navbar.php';

$pdo = db();
$q = trim((string) ($_GET['q'] ?? ''));
$groupFilter = (int) ($_GET['group'] ?? 0);
$statusFilter = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(r.name LIKE ? OR r.email LIKE ?)';
    $params[] = '%'.$q.'%';
    $params[] = '%'.$q.'%';
}
if ($groupFilter > 0) {
    $where[] = 'EXISTS (SELECT 1 FROM group_members gm WHERE gm.recipient_id = r.id AND gm.group_id = ?)';
    $params[] = $groupFilter;
}
if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $where[] = 'r.status = ?';
    $params[] = $statusFilter;
}

$sqlWhere = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM recipients r WHERE {$sqlWhere}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pager = paginate($total, $page, $perPage);

$listSql = "SELECT r.*,
    (SELECT g.name FROM group_members gm2
      INNER JOIN `groups` g ON g.id = gm2.group_id
      WHERE gm2.recipient_id = r.id ORDER BY g.name LIMIT 1) AS group_name,
    (SELECT g.id FROM group_members gm2
      INNER JOIN `groups` g ON g.id = gm2.group_id
      WHERE gm2.recipient_id = r.id ORDER BY g.name LIMIT 1) AS group_id
  FROM recipients r
  WHERE {$sqlWhere}
  ORDER BY r.created_at DESC
  LIMIT {$pager['per_page']} OFFSET {$pager['offset']}";
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$recipients = $listStmt->fetchAll();

$groups = $pdo->query('SELECT id, name FROM `groups` ORDER BY name')->fetchAll();

$queryBase = http_build_query(array_filter([
    'q' => $q !== '' ? $q : null,
    'group' => $groupFilter ?: null,
    'status' => $statusFilter !== '' ? $statusFilter : null,
]));
?>

        <div class="page-header">
          <div>
            <h2>Recipients</h2>
            <p>Manage your email recipients</p>
          </div>
          <div class="page-actions">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importCsvModal">
              <i class="bi bi-upload"></i> Import CSV
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecipientModal">
              <i class="bi bi-plus-lg"></i> Add Recipient
            </button>
          </div>
        </div>

        <div class="card-surface">
          <form class="toolbar-row" method="get">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search by name or email..." aria-label="Search recipients">
            </div>
            <div class="toolbar-filters">
              <select class="form-select form-select-sm" name="group" onchange="this.form.submit()">
                <option value="0">All Groups</option>
                <?php foreach ($groups as $g) { ?>
                  <option value="<?= (int) $g['id'] ?>" <?= $groupFilter === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                <?php } ?>
              </select>
              <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
              </select>
              <button class="btn btn-sm btn-primary" type="submit">Search</button>
            </div>
          </form>

          <?php if (! $recipients) { ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-people"></i></div>
            <h3>No recipients found</h3>
            <p>Add your first recipient or import a CSV to get started.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecipientModal">
              <i class="bi bi-plus-lg"></i> Add Recipient
            </button>
          </div>
          <?php } else { ?>
          <div class="table-wrap">
            <table class="table-modern">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Group</th>
                  <th>Status</th>
                  <th>Added</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recipients as $r) { ?>
                <tr>
                  <td>
                    <div class="user-cell">
                      <div class="avatar sm"><?= e(initials($r['name'])) ?></div>
                      <span><?= e($r['name']) ?></span>
                    </div>
                  </td>
                  <td><?= e($r['email']) ?></td>
                  <td><?= e($r['group_name'] ?: '—') ?></td>
                  <td><?= status_badge($r['status']) ?></td>
                  <td><?= e(format_date($r['created_at'])) ?></td>
                  <td>
                    <div class="action-btns">
                      <button type="button" class="btn-icon-sm" title="Edit"
                        data-bs-toggle="modal" data-bs-target="#editRecipientModal"
                        data-id="<?= (int) $r['id'] ?>"
                        data-name="<?= e($r['name']) ?>"
                        data-email="<?= e($r['email']) ?>"
                        data-status="<?= e($r['status']) ?>"
                        data-group="<?= (int) ($r['group_id'] ?? 0) ?>">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <form method="post" action="actions/recipient-actions.php" class="d-inline" onsubmit="return confirm('Delete <?= e(addslashes($r['name'])) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
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

  <!-- Add -->
  <div class="modal fade" id="addRecipientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="actions/recipient-actions.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create">
          <div class="modal-header">
            <h5 class="modal-title">Add Recipient</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Group</label>
              <select class="form-select" name="group_id">
                <option value="0">No group</option>
                <?php foreach ($groups as $g) { ?>
                  <option value="<?= (int) $g['id'] ?>"><?= e($g['name']) ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="mb-0">
              <label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Recipient</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit -->
  <div class="modal fade" id="editRecipientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="actions/recipient-actions.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" id="editRecipientId">
          <div class="modal-header">
            <h5 class="modal-title">Edit Recipient</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="editRecipientName" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email" id="editRecipientEmail" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Group</label>
              <select class="form-select" name="group_id" id="editRecipientGroup">
                <option value="0">No group</option>
                <?php foreach ($groups as $g) { ?>
                  <option value="<?= (int) $g['id'] ?>"><?= e($g['name']) ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="mb-0">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="editRecipientStatus">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Import -->
  <div class="modal fade" id="importCsvModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="actions/recipient-actions.php" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="import_csv">
          <div class="modal-header">
            <h5 class="modal-title">Import CSV</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="upload-zone mb-3">
              <i class="bi bi-cloud-arrow-up"></i>
              <p class="mb-1"><strong>Choose a CSV file</strong></p>
              <p class="text-muted small mb-2">Required columns: name, email, group</p>
              <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Import</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php
$pageScript = <<<'JS'
document.getElementById('editRecipientModal')?.addEventListener('show.bs.modal', function (event) {
  const btn = event.relatedTarget;
  if (!btn) return;
  document.getElementById('editRecipientId').value = btn.dataset.id || '';
  document.getElementById('editRecipientName').value = btn.dataset.name || '';
  document.getElementById('editRecipientEmail').value = btn.dataset.email || '';
  document.getElementById('editRecipientStatus').value = btn.dataset.status || 'active';
  document.getElementById('editRecipientGroup').value = btn.dataset.group || '0';
});
JS;
require_once __DIR__.'/includes/footer.php';
