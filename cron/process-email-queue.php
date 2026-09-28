<?php

declare(strict_types=1);

/**
 * Process pending emails from the queue via PHPMailer.
 *
 * Cron example:
 *   * * * * * php /path/to/cron/process-email-queue.php
 */

require_once dirname(__DIR__).'/config/config.php';
require_once dirname(__DIR__).'/config/database.php';
require_once dirname(__DIR__).'/config/mail.php';
require_once dirname(__DIR__).'/includes/functions.php';

function process_email_queue(?int $batchSize = null): int
{
    $batchSize ??= (int) app_config('queue_batch_size', 20);
    $maxAttempts = (int) app_config('queue_max_attempts', 3);
    $pdo = db();
    $processed = 0;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "SELECT * FROM email_queue
         WHERE status = 'pending' AND attempts < ?
         ORDER BY id ASC
         LIMIT ".(int) $batchSize.'
         FOR UPDATE'
    );
    $stmt->execute([$maxAttempts]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $pdo->prepare("UPDATE email_queue SET status = 'processing', attempts = attempts + 1 WHERE id = ?")
            ->execute([(int) $row['id']]);
    }
    $pdo->commit();

    foreach ($rows as $row) {
        $attach = null;
        if (! empty($row['attachment_path'])) {
            $attach = dirname(__DIR__).'/'.ltrim((string) $row['attachment_path'], '/');
        }

        $result = send_email_message(
            $row['recipient_email'],
            $row['subject'],
            $row['message'],
            $attach
        );

        if ($result['ok']) {
            $pdo->prepare("UPDATE email_queue SET status = 'sent', processed_at = NOW(), last_error = NULL WHERE id = ?")
                ->execute([(int) $row['id']]);
            $pdo->prepare("UPDATE campaign_recipients SET status = 'sent', sent_at = NOW(), error_message = NULL WHERE id = ?")
                ->execute([(int) $row['campaign_recipient_id']]);
            $pdo->prepare(
                "UPDATE email_history SET status = 'sent', sent_at = NOW(), error_message = NULL
                 WHERE id = (
                   SELECT id FROM (
                     SELECT id FROM email_history
                     WHERE campaign_id = ? AND recipient_email = ? AND status = 'pending'
                     ORDER BY id DESC LIMIT 1
                   ) t
                 )"
            )->execute([(int) $row['campaign_id'], $row['recipient_email']]);
        } else {
            $error = mb_substr((string) $result['error'], 0, 500);
            $failPermanently = ((int) $row['attempts'] + 1) >= $maxAttempts;
            $pdo->prepare(
                'UPDATE email_queue SET status = ?, last_error = ?, processed_at = NOW() WHERE id = ?'
            )->execute([$failPermanently ? 'failed' : 'pending', $error, (int) $row['id']]);

            if ($failPermanently) {
                $pdo->prepare(
                    "UPDATE campaign_recipients SET status = 'failed', error_message = ?, sent_at = NOW() WHERE id = ?"
                )->execute([$error, (int) $row['campaign_recipient_id']]);
                $pdo->prepare(
                    "UPDATE email_history SET status = 'failed', sent_at = NOW(), error_message = ?
                     WHERE id = (
                       SELECT id FROM (
                         SELECT id FROM email_history
                         WHERE campaign_id = ? AND recipient_email = ? AND status = 'pending'
                         ORDER BY id DESC LIMIT 1
                       ) t
                     )"
                )->execute([$error, (int) $row['campaign_id'], $row['recipient_email']]);
            }
        }

        refresh_campaign_counts((int) $row['campaign_id']);
        finalize_campaign_status((int) $row['campaign_id']);
        $processed++;
    }

    return $processed;
}

function finalize_campaign_status(int $campaignId): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM email_queue WHERE campaign_id = ? AND status IN ('pending','processing')"
    );
    $stmt->execute([$campaignId]);
    $pending = (int) $stmt->fetchColumn();

    if ($pending > 0) {
        $pdo->prepare("UPDATE campaigns SET status = 'processing' WHERE id = ? AND status <> 'cancelled'")
            ->execute([$campaignId]);

        return;
    }

    $c = $pdo->prepare('SELECT total_sent, total_failed FROM campaigns WHERE id = ?');
    $c->execute([$campaignId]);
    $row = $c->fetch();
    if (! $row) {
        return;
    }

    $status = ((int) $row['total_sent'] === 0 && (int) $row['total_failed'] > 0) ? 'failed' : 'completed';
    $pdo->prepare("UPDATE campaigns SET status = ? WHERE id = ? AND status IN ('pending','processing')")
        ->execute([$status, $campaignId]);
}

if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    echo 'Processed '.process_email_queue()." email(s)\n";
}
