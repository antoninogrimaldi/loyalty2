<?php
require_once __DIR__ . '/../helpers/logger.php';

function webhook_shopify()
{
    $payload = file_get_contents('php://input');
    app_log('Shopify webhook ricevuto: ' . $payload);
    http_response_code(200);
    echo 'ok';
    exit;
}
