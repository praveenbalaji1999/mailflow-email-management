<?php

declare(strict_types=1);

/**
 * Shared helper functions.
 */
$config = require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/database.php';

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__.'/../config/config.php';
        // require returns true on second include if already loaded via require_once
        if ($cfg === true) {
            $cfg = [
                'app_name' => (string) env('APP_NAME', 'MailFlow'),
                'app_url' => rtrim((string) env('APP_URL', 'http://127.0.0.1:8080'), '/'),
                'debug' => (bool) env('APP_DEBUG', true),
                'session_name' => (string) env('SESSION_NAME', 'mailflow_session'),
                'queue_batch_size' => (int) env('QUEUE_BATCH_SIZE', 20),
                'queue_max_attempts' => (int) env('QUEUE_MAX_ATTEMPTS', 3),
                'upload_path' => ROOT_PATH.'/uploads/attachments',
                'upload_max_bytes' => 10 * 1024 * 1024,
            ];
        }
    }
    if ($key === null) {
        return $cfg;
    }

    return $cfg[$key] ?? $default;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) app_config('app_url', ''), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base : $base.'/'.$path;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    if (! preg_match('#^https?://#i', $path)) {
        $path = base_url($path);
    }
    header('Location: '.$path);
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function request(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">';
}

function verify_csrf(?string $token = null): bool
{
    $token ??= $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    return is_string($token)
        && isset($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

function require_csrf(): void
{
    if (! verify_csrf()) {
        flash('error', 'Invalid security token. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $flashes;
}

function old(string $key, mixed $default = ''): mixed
{
    $old = $_SESSION['_old'][$key] ?? $default;

    return $old;
}

function store_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function active_page(string $page): string
{
    $current = basename($_SERVER['PHP_SELF'] ?? '');

    return $current === $page ? 'active' : '';
}

function greeting(): string
{
    $hour = (int) date('G');
    if ($hour < 12) {
        return 'Good Morning';
    }
    if ($hour < 17) {
        return 'Good Afternoon';
    }

    return 'Good Evening';
}

function status_badge(string $status): string
{
    $map = [
        'completed' => 'badge-completed',
        'sent' => 'badge-completed',
        'active' => 'badge-completed',
        'processing' => 'badge-processing',
        'pending' => 'badge-pending',
        'draft' => 'badge-pending',
        'failed' => 'badge-failed',
        'inactive' => 'badge-failed',
        'cancelled' => 'badge-failed',
        'bounced' => 'badge-failed',
    ];
    $class = $map[strtolower($status)] ?? 'badge-pending';
    $label = ucfirst(strtolower($status));

    return '<span class="badge-status '.$class.'">'.e($label).'</span>';
}

function format_date(?string $datetime, string $format = 'M j, Y'): string
{
    if (! $datetime) {
        return '—';
    }
    $ts = strtotime($datetime);

    return $ts ? date($format, $ts) : '—';
}

function format_datetime(?string $datetime): string
{
    return format_date($datetime, 'M j, Y · g:i A');
}

function paginate(int $total, int $page, int $perPage = 10): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'from' => $total === 0 ? 0 : $offset + 1,
        'to' => min($offset + $perPage, $total),
    ];
}

function pagination_html(array $p, string $baseQuery = ''): string
{
    if ($p['total_pages'] <= 1) {
        return '';
    }

    $qs = $baseQuery !== '' ? '&'.ltrim($baseQuery, '&') : '';
    $html = '<nav aria-label="Pagination"><ul class="pagination pagination-sm mb-0">';

    $prevDisabled = $p['page'] <= 1 ? ' disabled' : '';
    $prevPage = max(1, $p['page'] - 1);
    $html .= '<li class="page-item'.$prevDisabled.'"><a class="page-link" href="?page='.$prevPage.$qs.'">Previous</a></li>';

    $start = max(1, $p['page'] - 2);
    $end = min($p['total_pages'], $p['page'] + 2);
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=1'.$qs.'">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">…</a></li>';
        }
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $p['page'] ? ' active' : '';
        $html .= '<li class="page-item'.$active.'"><a class="page-link" href="?page='.$i.$qs.'">'.$i.'</a></li>';
    }
    if ($end < $p['total_pages']) {
        if ($end < $p['total_pages'] - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">…</a></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="?page='.$p['total_pages'].$qs.'">'.$p['total_pages'].'</a></li>';
    }

    $nextDisabled = $p['page'] >= $p['total_pages'] ? ' disabled' : '';
    $nextPage = min($p['total_pages'], $p['page'] + 1);
    $html .= '<li class="page-item'.$nextDisabled.'"><a class="page-link" href="?page='.$nextPage.$qs.'">Next</a></li>';
    $html .= '</ul></nav>';

    return $html;
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function upload_attachment(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Attachment upload failed.');
    }

    $max = (int) app_config('upload_max_bytes', 10 * 1024 * 1024);
    if (($file['size'] ?? 0) > $max) {
        throw new RuntimeException('Attachment exceeds 10MB limit.');
    }

    $allowed = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'csv'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (! in_array($ext, $allowed, true)) {
        throw new RuntimeException('Attachment type not allowed.');
    }

    $dir = (string) app_config('upload_path');
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $name = date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$ext;
    $dest = $dir.'/'.$name;
    if (! move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save attachment.');
    }

    return 'uploads/attachments/'.$name;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }

    return $letters !== '' ? $letters : 'U';
}

/**
 * Recalculate campaign counters from campaign_recipients.
 */
function refresh_campaign_counts(int $campaignId): void
{
    $pdo = db();
    $stmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS total,
            SUM(status = 'sent') AS sent,
            SUM(status = 'failed') AS failed,
            SUM(status = 'pending') AS pending
         FROM campaign_recipients WHERE campaign_id = ?"
    );
    $stmt->execute([$campaignId]);
    $row = $stmt->fetch() ?: ['total' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0];

    $upd = $pdo->prepare(
        'UPDATE campaigns SET total_recipients = ?, total_sent = ?, total_failed = ?, total_pending = ? WHERE id = ?'
    );
    $upd->execute([
        (int) $row['total'],
        (int) $row['sent'],
        (int) $row['failed'],
        (int) $row['pending'],
        $campaignId,
    ]);
}
