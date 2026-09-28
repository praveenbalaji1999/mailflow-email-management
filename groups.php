<?php
declare(strict_types=1);

$pageTitle = 'Groups';
$navTitle = 'Groups';

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/includes/navbar.php';

$pdo = db();
$viewId = (int) ($_GET['view'] ?? 0);

$groups = $pdo->query(
    'SELECT g.*, COUNT(gm.id) AS member_count
     FROM `groups` g
     LEFT JOIN group_members gm ON gm.group_id = g.id
     GROUP BY g.id
     ORDER BY g.name'
)->fetchAll();

$viewGroup = null;
$members = [];
$availableRecipients = [];
if ($viewId > 0) {
    $st = $pdo->prepare('SELECT * FROM `groups` WHERE id = ?');
    $st->execute([$viewId]);
    $viewGroup = $st->fetch();
    if ($viewGroup) {
        $m = $pdo->prepare(
            'SELECT r.* FROM recipients r
             INNER JOIN group_members gm ON gm.recipient_id = r.id
             WHERE gm.group_id = ? ORDER BY r.name'
        );
        $m->execute([$viewId]);
        $members = $m->fetchAll();

        $a = $pdo->prepare(
            'SELECT r.* FROM recipients r
             WHERE r.id NOT IN (SELECT recipient_id FROM group_members WHERE group_id = ?)
             ORDER BY r.name'
        );
        $a->execute([$viewId]);
        $availableRecipients = $a->fetchAll();
    }
}

$icons = ['people-fill', 'briefcase-fill', 'person-badge-fill', 'graph-up-arrow', 'star-fill', 'megaphone-fill'];
$colors = [
    ['rgba(37,99,235,0.1)', '#2563EB'],
    ['rgba(22,163,74,0.1)', '#16A34A'],
    ['rgba(245,158,11,0.12)', '#D97706'],
    ['rgba(37,99,235,0.1)', '#2563EB'],
    ['rgba(220,38,38,0.1)', '#DC2626'],
    ['rgba(22,163,74,0.1)', '#16A34A'],
];
?>

        <div class="page-header">
          <div>
            <h2>Recipient Groups</h2>
            <p>Organize recipients into reusable groups.</p>
          </div>
          <div class="page-actions">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
              <i class="bi bi-plus-lg"></i> Create Group
            </button>
          </div>
        </div>

        <?php if ($viewGroup) { ?>
        <div class="card-surface mb-4">
          <div class="card-header-row">
            <div>
              <nav class="breadcrumb-nav mb-1">
                <a href="groups.php">Groups</a>
                <i class="bi bi-chevron-right"></i>
                <span><?= e($viewGroup['name']) ?></span>
              </nav>
              <h3 class="mb-0"><?= e($viewGroup['name']) ?> · <?= count($members) ?> members</h3>
            </div>
            <a href="groups.php" class="btn btn-sm btn-outline-secondary">Back</a>
          </div>
          <div class="p-3 border-bottom">
            <form method="post" action="actions/group-actions.php" class="row g-2 align-items-end">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add_member">
              <input type="hidden" name="group_id" value="<?= (int) $viewGroup['id'] ?>">
              <div class="col-md-8">
                <label class="form-label">Add member</label>
                <select class="form-select" name="recipient_id" required>
                  <option value="">Select recipient…</option>
                  <?php foreach ($availableRecipients as $r) { ?>
                    <option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?> (<?= e($r['email']) ?>)</option>
                  <?php } ?>
                </select>
              </div>
              <div class="col-md-4">
                <button class="btn btn-primary w-100" type="submit">Add Member</button>
              </div>
            </form>
          </div>
          <div class="table-wrap">
            <table class="table-modern">
              <thead><tr><th>Name</th><th>Email</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php if (! $members) { ?>
                  <tr><td colspan="4" class="text-center text-muted py-4">No members yet.</td></tr>
                <?php } ?>
                <?php foreach ($members as $m) { ?>
                <tr>
                  <td><?= e($m['name']) ?></td>
                  <td><?= e($m['email']) ?></td>
                  <td><?= status_badge($m['status']) ?></td>
                  <td>
                    <form method="post" action="actions/group-actions.php" onsubmit="return confirm('Remove this member?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="remove_member">
                      <input type="hidden" name="group_id" value="<?= (int) $viewGroup['id'] ?>">
                      <input type="hidden" name="recipient_id" value="<?= (int) $m['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                    </form>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php } ?>

        <div class="row g-3">
          <?php foreach ($groups as $i => $g) {
              $color = $colors[$i % count($colors)];
              $icon = $icons[$i % count($icons)];
              ?>
          <div class="col-sm-6 col-xl-4">
            <div class="group-card">
              <div class="group-card-top">
                <div class="group-icon" style="background:<?= $color[0] ?>;color:<?= $color[1] ?>">
                  <i class="bi bi-<?= $icon ?>"></i>
                </div>
                <div class="dropdown">
                  <button class="btn-icon-sm" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="groups.php?view=<?= (int) $g['id'] ?>"><i class="bi bi-eye"></i> View Members</a></li>
                    <li>
                      <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editGroupModal"
                        data-id="<?= (int) $g['id'] ?>" data-name="<?= e($g['name']) ?>" data-description="<?= e((string) $g['description']) ?>">
                        <i class="bi bi-pencil"></i> Edit
                      </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form method="post" action="actions/group-actions.php" onsubmit="return confirm('Delete group <?= e(addslashes($g['name'])) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i> Delete</button>
                      </form>
                    </li>
                  </ul>
                </div>
              </div>
              <h3><?= e($g['name']) ?></h3>
              <p class="group-count"><?= (int) $g['member_count'] ?> Recipients</p>
              <div class="group-card-footer">
                <a href="groups.php?view=<?= (int) $g['id'] ?>" class="btn btn-sm btn-outline-primary">View Members</a>
                <a href="compose.php?group_id=<?= (int) $g['id'] ?>" class="btn btn-sm btn-ghost" title="Compose to group"><i class="bi bi-send"></i></a>
              </div>
            </div>
          </div>
          <?php } ?>
        </div>

  <div class="modal fade" id="createGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="actions/group-actions.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="create">
          <div class="modal-header">
            <h5 class="modal-title">Create Group</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Group Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-0">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Create Group</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="actions/group-actions.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" id="editGroupId">
          <div class="modal-header">
            <h5 class="modal-title">Edit Group</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Group Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="name" id="editGroupName" required>
            </div>
            <div class="mb-0">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" id="editGroupDescription" rows="3"></textarea>
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

<?php
$pageScript = <<<'JS'
document.getElementById('editGroupModal')?.addEventListener('show.bs.modal', function (e) {
  const b = e.relatedTarget; if (!b) return;
  document.getElementById('editGroupId').value = b.dataset.id || '';
  document.getElementById('editGroupName').value = b.dataset.name || '';
  document.getElementById('editGroupDescription').value = b.dataset.description || '';
});
JS;
require_once __DIR__.'/includes/footer.php';
