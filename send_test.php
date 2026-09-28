<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH.'/config/config.php';
require_once ROOT_PATH.'/config/database.php';
require_once ROOT_PATH.'/config/mail.php';

echo "Testing SMTP connection...\n";
$conn = test_smtp_connection();
echo ($conn['ok'] ? '✅ Connection OK' : '❌ Connection FAILED').': '.$conn['message']."\n\n";

if ($conn['ok']) {
    echo "Sending test email to praveenbalaji860@gmail.com...\n";
    $result = send_email_message(
        'praveenbalaji860@gmail.com',
        'MailFlow Test ✅ - It Works!',
        '<h2 style="color:#2563EB">Hello from MailFlow! 🎉</h2>
         <p>This is a real test email sent from your <strong>MailFlow Email Management System</strong>.</p>
         <p>Your SMTP configuration is working perfectly. You can now send campaigns!</p>
         <br><p style="color:#6b7280">Sent via MailFlow</p>'
    );
    echo $result['ok']
        ? "✅ Email SENT successfully!\nCheck praveenbalaji860@gmail.com inbox now.\n"
        : '❌ Send FAILED: '.$result['error']."\n";
}
