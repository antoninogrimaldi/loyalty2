<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/logger.php';

function sync_manual()
{
    require_login(true);
    $output = shell_exec('php ' . __DIR__ . '/../../scripts/run_sync.php');
    return ['view' => 'admin/sync.php', 'data' => ['output' => nl2br($output)]];
}
