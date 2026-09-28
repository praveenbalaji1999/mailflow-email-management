<?php

declare(strict_types=1);

require_once __DIR__.'/includes/auth.php';
start_app_session();

if (current_admin()) {
    redirect('dashboard.php');
}
redirect('login.php');
