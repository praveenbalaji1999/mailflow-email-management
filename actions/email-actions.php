<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_auth();

if (! is_post()) {
    redirect('campaigns.php');
}
require_csrf();

$action = (string) ($_POST['action'] ?? '');
$id = (int) ($_POST['id'] ?? 0);

try {
    $pdo = db();
    if ($id < 1) {
        throw new InvalidArgumentException('Invalid campaign.');
    }

    switch ($action) {
        case 'delete':
            $pdo->prepare('DELETE FROM campaigns WHERE id = ?')->execute([$id]);
            flash('success', 'Campaign deleted successfully');
            redirect('campaigns.php');

        case 'cancel':
            $pdo->prepare("UPDATE campaigns SET status = 'cancelled' WHERE id = ? AND status IN ('pending','processing')")
                ->execute([$id]);
            $pdo->prepare("UPDATE email_queue SET status = 'failed', last_error = 'Cancelled' WHERE campaign_id = ? AND status = 'pending'")
                ->execute([$id]);
            flash('success', 'Campaign cancelled');
            redirect('campaign-details.php?id='.$id);

        case 'process_queue':
            require_once __DIR__.'/../cron/process-email-queue.php';
            $n = process_email_queue((int) app_config('queue_batch_size', 20));
            flash('success', "Processed {$n} queued email(s)");
            redirect('campaign-details.php?id='.$id);

        default:
            throw new InvalidArgumentException('Unknown action.');
    }
} catch (Throwable $e) {
    flash('error', $e->getMessage());
    redirect('campaigns.php');
}
