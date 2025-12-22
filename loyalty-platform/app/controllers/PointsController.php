<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/points.php';

function api_points_balance()
{
    require_login();
    $user = current_user();
    header('Content-Type: application/json');
    echo json_encode(['balance' => points_balance($user['id'])]);
    exit;
}
