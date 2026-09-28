<?php
declare(strict_types=1);

$pageTitle = 'Compose Email';
$navTitle = 'Compose Email';

require_once __DIR__.'/includes/header.php';
require_once __DIR__.'/includes/sidebar.php';
require_once __DIR__.'/includes/navbar.php';
require_once __DIR__.'/config/mail.php';

$pdo = db();
$editId = (int) ($_GET['id'] ?? 0);
$preselectGroup = (int) ($_GET['group_id'] ?? 0);

$campaign = [
    'id' => 0,
    'campaign_name' => old('campaign_name', 'Monthly Customer Update'),
    'subject' => old('subject', 'Important Company Update'),
    'message' => old('message', '<p>Dear Customer,</p><p>We would like to inform you about important updates to our services this month.</p><p>Regards,<br>MailFlow Team</p>'),
    'attachment_path' => null,
];
$selectedIds = array_map('intval', (array) (old('recipients', [])));

if ($editId > 0) {
    $st = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND status = 'draft' LIMIT 1");
    $st->execute([$editId]);
    $row = $st->fetch();
    if ($row) {
        $campaign = $row;
        $rs = $pdo->prepare('SELECT recipient_id FROM campaign_recipients WHERE campaign_id = ? AND recipient_id IS NOT NULL');
        $rs->execute([$editId]);
        $selectedIds = array_map('intval', $rs->fetchAll(PDO::FETCH_COLUMN));
    }
}

$groups = $pdo->query('SELECT id, name FROM `groups` ORDER BY name')->fetchAll();
$recipients = $pdo->query("SELECT id, name, email FROM recipients WHERE status = 'active' ORDER BY name LIMIT 200")->fetchAll();

$smtp = get_smtp_settings();
$fromDisplay = $smtp
    ? e($smtp['from_name']).' &lt;'.e($smtp['from_email']).'&gt;'
    : 'MailFlow &lt;admin@example.com&gt;';

clear_old();
?>

        <div class="page-header">
          <div>
            <h2>Compose Email</h2>
            <p>Create and send a new email campaign.</p>
          </div>
        </div>

        <form method="post" action="actions/campaign-actions.php" enctype="multipart/form-data" id="composeForm">
          <?= csrf_field() ?>
          <input type="hidden" name="campaign_id" value="<?= (int) $campaign['id'] ?>">
          <input type="hidden" name="existing_attachment" value="<?= e((string) ($campaign['attachment_path'] ?? '')) ?>">

          <div class="card-surface compose-card">
            <div class="mb-4">
              <label for="campaignName" class="form-label">Campaign Name</label>
              <input type="text" class="form-control form-control-lg" id="campaignName" name="campaign_name"
                     value="<?= e($campaign['campaign_name']) ?>" required>
            </div>

            <div class="mb-3">
              <label for="recipientGroup" class="form-label">Recipients (Group)</label>
              <select class="form-select" id="recipientGroup" name="group_id">
                <option value="0">Select Group (optional)</option>
                <?php foreach ($groups as $g) { ?>
                  <option value="<?= (int) $g['id'] ?>" <?= $preselectGroup === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                <?php } ?>
              </select>
              <div class="form-text">Selecting a group includes all active members (duplicates are removed).</div>
            </div>

            <div class="mb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="selected-count" id="selectedCount"><strong>0</strong> recipients selected</div>
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="selectAllRecipients">
                <label class="form-check-label" for="selectAllRecipients">Select all listed</label>
              </div>
            </div>

            <div class="chips-wrap mb-3" id="recipientChips"></div>

            <div class="recipient-list mb-4">
              <?php foreach ($recipients as $r) {
                  $checked = in_array((int) $r['id'], $selectedIds, true);
                  ?>
              <label class="recipient-row">
                <input type="checkbox" class="form-check-input recipient-check" name="recipients[]"
                       value="<?= (int) $r['id'] ?>" data-name="<?= e($r['name']) ?>" <?= $checked ? 'checked' : '' ?>>
                <div class="avatar sm"><?= e(initials($r['name'])) ?></div>
                <div class="recipient-meta">
                  <strong><?= e($r['name']) ?></strong>
                  <span><?= e($r['email']) ?></span>
                </div>
              </label>
              <?php } ?>
              <?php if (! $recipients) { ?>
                <div class="p-3 text-muted">No active recipients. <a href="recipients.php">Add recipients</a> first.</div>
              <?php } ?>
            </div>

            <div class="mb-4">
              <label for="emailSubject" class="form-label">Subject</label>
              <input type="text" class="form-control" id="emailSubject" name="subject" value="<?= e($campaign['subject']) ?>">
            </div>

            <div class="mb-4">
              <label class="form-label">Message</label>
              <div class="rich-editor">
                <div class="editor-toolbar">
                  <button type="button" data-command="bold"><i class="bi bi-type-bold"></i></button>
                  <button type="button" data-command="italic"><i class="bi bi-type-italic"></i></button>
                  <button type="button" data-command="underline"><i class="bi bi-type-underline"></i></button>
                  <span class="toolbar-divider"></span>
                  <button type="button" data-command="insertUnorderedList"><i class="bi bi-list-ul"></i></button>
                  <button type="button" data-command="createLink"><i class="bi bi-link-45deg"></i></button>
                </div>
                <div class="editor-body" id="emailEditor" contenteditable="true"><?= $campaign['message'] ?></div>
                <textarea name="message" id="messageField" class="d-none"><?= e($campaign['message']) ?></textarea>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label">Attachment</label>
              <div class="attachment-zone">
                <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.gif,.txt,.csv">
                <?php if (! empty($campaign['attachment_path'])) { ?>
                  <div class="small text-muted mt-2">Current: <?= e(basename($campaign['attachment_path'])) ?></div>
                <?php } ?>
              </div>
            </div>

            <div class="compose-actions">
              <button type="submit" name="action" value="draft" class="btn btn-outline-secondary" id="btnSaveDraft">
                <i class="bi bi-file-earmark"></i> Save Draft
              </button>
              <button type="button" class="btn btn-outline-primary" id="btnPreview" data-bs-toggle="modal" data-bs-target="#previewModal">
                <i class="bi bi-eye"></i> Preview
              </button>
              <button type="submit" name="action" value="send" class="btn btn-primary" id="btnSendEmail">
                Send Email <i class="bi bi-send-fill"></i>
              </button>
            </div>
          </div>
        </form>

  <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content email-preview-modal">
        <div class="modal-header">
          <h5 class="modal-title">Email Preview</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="email-preview-meta">
          <div><span class="meta-label">From:</span> <?= $fromDisplay ?></div>
          <div><span class="meta-label">To:</span> <span id="previewTo">0 recipients</span></div>
          <div><span class="meta-label">Subject:</span> <span id="previewSubject"></span></div>
        </div>
        <div class="modal-body email-preview-body" id="previewBody"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="btnConfirmSend">Confirm &amp; Send <i class="bi bi-send-fill"></i></button>
        </div>
      </div>
    </div>
  </div>

<?php
$pageScript = <<<'JS'
(function () {
  const editor = document.getElementById('emailEditor');
  const messageField = document.getElementById('messageField');
  const form = document.getElementById('composeForm');

  function syncMessage() {
    if (editor && messageField) messageField.value = editor.innerHTML;
  }

  document.querySelectorAll('.editor-toolbar button[data-command]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const cmd = btn.getAttribute('data-command');
      if (cmd === 'createLink') {
        const url = prompt('Enter URL');
        if (url) document.execCommand('createLink', false, url);
      } else {
        document.execCommand(cmd, false, null);
      }
      editor?.focus();
    });
  });

  form?.addEventListener('submit', syncMessage);

  document.getElementById('btnPreview')?.addEventListener('click', function () {
    syncMessage();
    const checked = document.querySelectorAll('.recipient-check:checked').length;
    document.getElementById('previewSubject').textContent = document.getElementById('emailSubject')?.value || '(No subject)';
    document.getElementById('previewBody').innerHTML = editor?.innerHTML || '';
    document.getElementById('previewTo').textContent = checked + ' recipients';
  });

  document.getElementById('btnConfirmSend')?.addEventListener('click', function () {
    syncMessage();
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'action';
    input.value = 'send';
    form.appendChild(input);
    form.submit();
  });

  // chips
  function updateChips() {
    const wrap = document.getElementById('recipientChips');
    const countEl = document.getElementById('selectedCount');
    if (!wrap || !countEl) return;
    wrap.innerHTML = '';
    let count = 0;
    document.querySelectorAll('.recipient-check').forEach(function (cb) {
      if (!cb.checked) return;
      count++;
      const chip = document.createElement('span');
      chip.className = 'chip';
      chip.innerHTML = (cb.dataset.name || 'Recipient') + ' <button type="button" aria-label="Remove"><i class="bi bi-x"></i></button>';
      chip.querySelector('button').addEventListener('click', function () {
        cb.checked = false;
        updateChips();
      });
      wrap.appendChild(chip);
    });
    countEl.innerHTML = '<strong>' + count + '</strong> recipients selected';
  }
  document.querySelectorAll('.recipient-check').forEach(function (cb) {
    cb.addEventListener('change', updateChips);
  });
  document.getElementById('selectAllRecipients')?.addEventListener('change', function () {
    const on = this.checked;
    document.querySelectorAll('.recipient-check').forEach(function (cb) { cb.checked = on; });
    updateChips();
  });
  updateChips();
})();
JS;
require_once __DIR__.'/includes/footer.php';
