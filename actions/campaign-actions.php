<?php

declare(strict_types=1);

/**
 * Campaign compose: save draft / send (queue).
 */

require_once __DIR__.'/../includes/auth.php';
$admin = require_auth();

if (! is_post()) {
    redirect('compose.php');
}
require_csrf();

$action = (string) ($_POST['action'] ?? 'draft'); // draft | send
$campaignId = (int) ($_POST['campaign_id'] ?? 0);

try {
    $pdo = db();
    $campaignName = trim((string) ($_POST['campaign_name'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $recipientIds = array_values(array_unique(array_map('intval', (array) ($_POST['recipients'] ?? []))));
    $groupId = (int) ($_POST['group_id'] ?? 0);

    if ($campaignName === '') {
        throw new InvalidArgumentException('Campaign name is required.');
    }

    // Expand group into recipients
    if ($groupId > 0) {
        $g = $pdo->prepare(
            'SELECT recipient_id FROM group_members gm
             INNER JOIN recipients r ON r.id = gm.recipient_id
             WHERE gm.group_id = ? AND r.status = \'active\''
        );
        $g->execute([$groupId]);
        foreach ($g->fetchAll(PDO::FETCH_COLUMN) as $rid) {
            $recipientIds[] = (int) $rid;
        }
        $recipientIds = array_values(array_unique($recipientIds));
    }

    $attachmentPath = null;
    if (! empty($_FILES['attachment']['name'])) {
        $attachmentPath = upload_attachment($_FILES['attachment']);
    } elseif (! empty($_POST['existing_attachment'])) {
        $attachmentPath = (string) $_POST['existing_attachment'];
    }

    if ($action === 'send') {
        if ($subject === '') {
            throw new InvalidArgumentException('Please enter an email subject.');
        }
        if ($message === '') {
            throw new InvalidArgumentException('Please enter an email message.');
        }
        if (count($recipientIds) === 0) {
            throw new InvalidArgumentException('Please select at least one recipient.');
        }
        require_once __DIR__.'/../config/mail.php';
        if (! get_smtp_settings()) {
            throw new RuntimeException('Configure SMTP settings before sending.');
        }
    }

    $pdo->beginTransaction();

    if ($campaignId > 0) {
        $chk = $pdo->prepare('SELECT id, status, attachment_path FROM campaigns WHERE id = ? LIMIT 1');
        $chk->execute([$campaignId]);
        $existing = $chk->fetch();
        if (! $existing || ! in_array($existing['status'], ['draft', 'pending'], true)) {
            throw new InvalidArgumentException('Only draft campaigns can be edited.');
        }
        if (! $attachmentPath) {
            $attachmentPath = $existing['attachment_path'];
        }
        // Clear previous recipients/queue for re-save
        $pdo->prepare('DELETE FROM email_queue WHERE campaign_id = ?')->execute([$campaignId]);
        $pdo->prepare('DELETE FROM campaign_recipients WHERE campaign_id = ?')->execute([$campaignId]);
    }

    $status = $action === 'send' ? 'pending' : 'draft';

    if ($campaignId > 0) {
        $upd = $pdo->prepare(
            'UPDATE campaigns SET campaign_name=?, subject=?, message=?, attachment_path=?, status=?, created_by=? WHERE id=?'
        );
        $upd->execute([$campaignName, $subject, $message, $attachmentPath, $status, $admin['id'], $campaignId]);
    } else {
        $ins = $pdo->prepare(
            'INSERT INTO campaigns (campaign_name, subject, message, attachment_path, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$campaignName, $subject, $message, $attachmentPath, $status, $admin['id']]);
        $campaignId = (int) $pdo->lastInsertId();
    }

    // Attach recipients
    if ($recipientIds) {
        $fetch = $pdo->prepare('SELECT id, email FROM recipients WHERE id = ? AND status = \'active\'');
        $insCr = $pdo->prepare(
            'INSERT INTO campaign_recipients (campaign_id, recipient_id, email, status) VALUES (?, ?, ?, ?)'
        );
        $insQ = $pdo->prepare(
            'INSERT INTO email_queue
             (campaign_id, campaign_recipient_id, recipient_email, subject, message, attachment_path, status, scheduled_at)
             VALUES (?, ?, ?, ?, ?, ?, \'pending\', NOW())'
        );
        $insHist = $pdo->prepare(
            'INSERT INTO email_history (campaign_id, recipient_id, recipient_email, subject, status, sent_at)
             VALUES (?, ?, ?, ?, \'pending\', NULL)'
        );

        foreach ($recipientIds as $rid) {
            $fetch->execute([$rid]);
            $rec = $fetch->fetch();
            if (! $rec) {
                continue;
            }
            $crStatus = $action === 'send' ? 'pending' : 'pending';
            $insCr->execute([$campaignId, $rec['id'], $rec['email'], $crStatus]);
            $crId = (int) $pdo->lastInsertId();

            if ($action === 'send') {
                $insQ->execute([
                    $campaignId,
                    $crId,
                    $rec['email'],
                    $subject,
                    $message,
                    $attachmentPath,
                ]);
                $insHist->execute([$campaignId, $rec['id'], $rec['email'], $subject]);
            }
        }
    }

    refresh_campaign_counts($campaignId);

    if ($action === 'send') {
        $pdo->prepare('UPDATE campaigns SET status = \'processing\' WHERE id = ?')->execute([$campaignId]);
    }

    $pdo->commit();

    if ($action === 'send') {
        flash('success', 'Campaign created — emails are being sent in the background. Refresh this page in a moment.');
        // Redirect immediately so browser doesn't freeze, then process queue in background
        $cronScript = dirname(__DIR__).'/cron/process-email-queue.php';
        // Fire-and-forget: spawn background PHP process (non-blocking)
        if (is_file($cronScript)) {
            $cmd = 'php '.escapeshellarg($cronScript).' > /dev/null 2>&1 &';
            @shell_exec($cmd);
        }
        redirect('campaign-details.php?id='.$campaignId);
    }

    flash('success', 'Draft saved successfully');
    redirect('compose.php?id='.$campaignId);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    store_old($_POST);
    flash('error', $e->getMessage());
    redirect($campaignId ? 'compose.php?id='.$campaignId : 'compose.php');
}
