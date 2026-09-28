<?php

declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_auth();

if (! is_post()) {
    redirect('groups.php');
}
require_csrf();

$action = (string) ($_POST['action'] ?? '');
$redirectTo = 'groups.php';

try {
    $pdo = db();

    switch ($action) {
        case 'create':
        case 'update':
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            if ($name === '') {
                throw new InvalidArgumentException('Please enter a group name.');
            }

            $dup = $pdo->prepare('SELECT id FROM `groups` WHERE name = ? AND id <> ? LIMIT 1');
            $dup->execute([$name, $action === 'update' ? $id : 0]);
            if ($dup->fetch()) {
                throw new InvalidArgumentException('A group with this name already exists.');
            }

            if ($action === 'create') {
                $pdo->prepare('INSERT INTO `groups` (name, description) VALUES (?, ?)')
                    ->execute([$name, $description !== '' ? $description : null]);
                flash('success', 'Group created successfully');
            } else {
                if ($id < 1) {
                    throw new InvalidArgumentException('Invalid group.');
                }
                $pdo->prepare('UPDATE `groups` SET name = ?, description = ? WHERE id = ?')
                    ->execute([$name, $description !== '' ? $description : null, $id]);
                flash('success', 'Group updated successfully');
            }
            break;

        case 'delete':
            $id = (int) ($_POST['id'] ?? 0);
            if ($id < 1) {
                throw new InvalidArgumentException('Invalid group.');
            }
            $pdo->prepare('DELETE FROM `groups` WHERE id = ?')->execute([$id]);
            flash('success', 'Group deleted successfully');
            break;

        case 'add_member':
            $groupId = (int) ($_POST['group_id'] ?? 0);
            $recipientId = (int) ($_POST['recipient_id'] ?? 0);
            $redirectTo = 'groups.php?view='.$groupId;
            if ($groupId < 1 || $recipientId < 1) {
                throw new InvalidArgumentException('Select a recipient to add.');
            }
            $pdo->prepare('INSERT IGNORE INTO group_members (group_id, recipient_id) VALUES (?, ?)')
                ->execute([$groupId, $recipientId]);
            flash('success', 'Member added to group');
            break;

        case 'remove_member':
            $groupId = (int) ($_POST['group_id'] ?? 0);
            $recipientId = (int) ($_POST['recipient_id'] ?? 0);
            $redirectTo = 'groups.php?view='.$groupId;
            $pdo->prepare('DELETE FROM group_members WHERE group_id = ? AND recipient_id = ?')
                ->execute([$groupId, $recipientId]);
            flash('success', 'Member removed from group');
            break;

        default:
            throw new InvalidArgumentException('Unknown action.');
    }
} catch (Throwable $e) {
    flash('error', $e->getMessage());
}

redirect($redirectTo);
