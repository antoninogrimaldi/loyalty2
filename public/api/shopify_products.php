<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/shopify.php';

$cfg = shopify_config();
$secret = $cfg['sync_secret'] ?? '';

if ($secret === '' || ($_GET['token'] ?? '') !== $secret) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$result = shopify_sync_products($mysqli);
header('Content-Type: application/json');
echo json_encode($result);
