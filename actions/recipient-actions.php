<?php

declare(strict_types=1);

/**
 * Recipient CRUD + CSV import actions.
 */

require_once __DIR__.'/../includes/auth.php';
require_auth();

if (! is_post()) {
    redirect('recipients.php');
}
require_csrf();

$action = (string) ($_POST['action'] ?? '');

try {
    $pdo = db();

    switch ($action) {
        case 'create':
        case 'update':
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
            $groupId = (int) ($_POST['group_id'] ?? 0);

            if ($name === '' || $email === '') {
                throw new InvalidArgumentException('Name and email are required.');
            }
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Please enter a valid email address.');
            }

            // Duplicate check
            $dup = $pdo->prepare('SELECT id FROM recipients WHERE email = ? AND id <> ? LIMIT 1');
            $dup->execute([$email, $action === 'update' ? $id : 0]);
            if ($dup->fetch()) {
                throw new InvalidArgumentException('A recipient with this email already exists.');
            }

            $pdo->beginTransaction();
            if ($action === 'create') {
                $stmt = $pdo->prepare('INSERT INTO recipients (name, email, status) VALUES (?, ?, ?)');
                $stmt->execute([$name, $email, $status]);
                $id = (int) $pdo->lastInsertId();
                flash('success', 'Recipient added successfully');
            } else {
                if ($id < 1) {
                    throw new InvalidArgumentException('Invalid recipient.');
                }
                $stmt = $pdo->prepare('UPDATE recipients SET name = ?, email = ?, status = ? WHERE id = ?');
                $stmt->execute([$name, $email, $status, $id]);
                // Reset group memberships for single-group UI
                $pdo->prepare('DELETE FROM group_members WHERE recipient_id = ?')->execute([$id]);
                flash('success', 'Recipient updated successfully');
            }

            if ($groupId > 0) {
                $pdo->prepare('INSERT IGNORE INTO group_members (group_id, recipient_id) VALUES (?, ?)')
                    ->execute([$groupId, $id]);
            }
            $pdo->commit();
            break;

        case 'delete':
            $id = (int) ($_POST['id'] ?? 0);
            if ($id < 1) {
                throw new InvalidArgumentException('Invalid recipient.');
            }
            $pdo->prepare('DELETE FROM recipients WHERE id = ?')->execute([$id]);
            flash('success', 'Recipient deleted successfully');
            break;

        case 'import_csv':
            if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Please upload a CSV file.');
            }
            $tmp = $_FILES['csv_file']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                throw new InvalidArgumentException('Only CSV files are allowed.');
            }

            $fh = fopen($tmp, 'r');
            if (! $fh) {
                throw new RuntimeException('Could not read CSV file.');
            }

            $header = fgetcsv($fh);
            if (! $header) {
                throw new InvalidArgumentException('CSV is empty.');
            }
            $header = array_map(static fn ($h) => strtolower(trim((string) $h)), $header);
            $nameIdx = array_search('name', $header, true);
            $emailIdx = array_search('email', $header, true);
            $groupIdx = array_search('group', $header, true);
            if ($nameIdx === false || $emailIdx === false) {
                throw new InvalidArgumentException('CSV must include name and email columns.');
            }

            $imported = 0;
            $duplicates = 0;
            $invalid = 0;

            $findEmail = $pdo->prepare('SELECT id FROM recipients WHERE email = ? LIMIT 1');
            $ins = $pdo->prepare('INSERT INTO recipients (name, email, status) VALUES (?, ?, ?)');
            $findGroup = $pdo->prepare('SELECT id FROM `groups` WHERE name = ? LIMIT 1');
            $insGroup = $pdo->prepare('INSERT INTO `groups` (name, description) VALUES (?, ?)');
            $insMember = $pdo->prepare('INSERT IGNORE INTO group_members (group_id, recipient_id) VALUES (?, ?)');

            $pdo->beginTransaction();
            while (($row = fgetcsv($fh)) !== false) {
                if (count(array_filter($row, static fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $name = trim((string) ($row[$nameIdx] ?? ''));
                $email = strtolower(trim((string) ($row[$emailIdx] ?? '')));
                $groupName = $groupIdx !== false ? trim((string) ($row[$groupIdx] ?? '')) : '';

                if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalid++;

                    continue;
                }

                $findEmail->execute([$email]);
                if ($findEmail->fetch()) {
                    $duplicates++;

                    continue;
                }

                $ins->execute([$name, $email, 'active']);
                $rid = (int) $pdo->lastInsertId();
                $imported++;

                if ($groupName !== '') {
                    $findGroup->execute([$groupName]);
                    $gid = (int) ($findGroup->fetchColumn() ?: 0);
                    if (! $gid) {
                        $insGroup->execute([$groupName, 'Imported from CSV']);
                        $gid = (int) $pdo->lastInsertId();
                    }
                    $insMember->execute([$gid, $rid]);
                }
            }
            $pdo->commit();
            fclose($fh);

            flash('success', "Imported: {$imported} · Duplicates: {$duplicates} · Invalid: {$invalid}");
            break;

        default:
            throw new InvalidArgumentException('Unknown action.');
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', $e->getMessage());
}

redirect('recipients.php');
