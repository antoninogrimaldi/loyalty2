<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/CustomerController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/OfferController.php';
require_once __DIR__ . '/../app/controllers/CouponController.php';
require_once __DIR__ . '/../app/controllers/RewardsController.php';
require_once __DIR__ . '/../app/controllers/PointsController.php';
require_once __DIR__ . '/../app/controllers/WebhookController.php';
require_once __DIR__ . '/../app/controllers/SyncController.php';

$route = $_GET['route'] ?? 'home';

$routes = [
    'home' => 'customer_home',
    'login' => 'handle_login',
    'register' => 'handle_register',
    'verify' => 'handle_verify',
    'logout' => 'handle_logout',
    'customer_offers' => 'customer_offers',
    'customer_coupons' => 'customer_coupons',
    'customer_points' => 'customer_points',
    'customer_rewards' => 'customer_rewards',
    'admin_dashboard' => 'admin_dashboard',
    'admin_users' => 'admin_users',
    'admin_points' => 'admin_points_adjust',
    'admin_rewards' => 'admin_rewards',
    'admin_coupons' => 'admin_coupons',
    'admin_offers' => 'admin_offers',
    'admin_offer_reset' => 'admin_offer_reset',
    'admin_settings' => 'admin_settings',
    'admin_sync' => 'admin_sync',
    'api_points' => 'api_points_balance',
    'webhook_shopify' => 'webhook_shopify',
];

if (!isset($routes[$route])) {
    http_response_code(404);
    echo 'Pagina non trovata';
    exit;
}

$response = call_user_func($routes[$route]);

if (is_array($response) && isset($response['view'])) {
    extract($response['data']);
    include __DIR__ . '/../app/views/' . $response['view'];
}
