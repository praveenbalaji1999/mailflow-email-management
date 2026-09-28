<?php

declare(strict_types=1);

require_once __DIR__.'/includes/auth.php';
start_app_session();
logout_admin();
header('Location: login.php');
exit;
