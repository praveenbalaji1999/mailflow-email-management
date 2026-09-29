<?php
declare(strict_types=1);

/**
 * Mail / PHPMailer helpers.
 */

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/database.php';

function get_smtp_settings(): ?array
{
    $stmt = db()->query('SELECT * FROM smtp_settings ORDER BY id ASC LIMIT 1');
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_mailer(?array $smtp = null): PHPMailer
{
    $smtp ??= get_smtp_settings();
    if (!$smtp) {
        throw new RuntimeException('SMTP settings are not configured.');
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtp['host'];
    $mail->Port = (int) $smtp['port'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp['username'];
    $mail->Password = $smtp['password'];

    $enc = strtolower((string) $smtp['encryption']);
    if ($enc === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($enc === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
    }

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($smtp['from_email'], $smtp['from_name']);
    $mail->isHTML(true);

    return $mail;
}

/**
 * @return array{ok:bool,message:string}
 */
function test_smtp_connection(): array
{
    try {
        $smtp = get_smtp_settings();
        if (!$smtp) {
            return ['ok' => false, 'message' => 'No SMTP settings found.'];
        }

        $mail = create_mailer($smtp);
        if (!$mail->smtpConnect()) {
            return ['ok' => false, 'message' => 'Could not connect to SMTP server.'];
        }
        $mail->smtpClose();
        return ['ok' => true, 'message' => 'SMTP connection successful.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Send a single email via PHPMailer.
 *
 * @return array{ok:bool,error:?string}
 */
function send_email_message(string $to, string $subject, string $htmlBody, ?string $attachmentPath = null): array
{
    try {
        $mail = create_mailer();
        $mail->clearAddresses();
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        if ($attachmentPath && is_file($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (MailException | Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}
