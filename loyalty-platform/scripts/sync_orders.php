<?php
require_once __DIR__ . '/../app/helpers/shopify.php';
require_once __DIR__ . '/../app/helpers/odoo.php';
require_once __DIR__ . '/../app/helpers/logger.php';
require_once __DIR__ . '/../app/helpers/points.php';
require_once __DIR__ . '/../app/db/db.php';

$pdo = get_db();
$users = $pdo->query('SELECT user_id AS id, shopify_customer_id, odoo_partner_id FROM customer_profiles')->fetchAll();
foreach ($users as $user) {
    if ($user['shopify_customer_id']) {
        $orders = shopify_fetch_orders_by_customer($user['shopify_customer_id']);
        foreach ($orders as $o) {
            $stmt = $pdo->prepare('REPLACE INTO orders_unified (source, source_order_id, user_id, total_amount, currency, status, created_at, raw_json) VALUES ("shopify", :id, :u, :t, :c, :s, :created, :raw)');
            $stmt->execute([
                ':id'=>$o['id'], ':u'=>$user['id'], ':t'=>$o['total_price'], ':c'=>$o['currency'], ':s'=>$o['financial_status'], ':created'=>$o['created_at'], ':raw'=>json_encode($o)
            ]);
            $points = (int) ($o['total_price'] * get_setting('points_per_eur',1));
            adjust_points($user['id'], $points, 'earn', 'order', $o['id'], 'Shopify order');
        }
    }
    if ($user['odoo_partner_id']) {
        $orders = odoo_fetch_orders_by_partner($user['odoo_partner_id']);
        foreach ($orders as $o) {
            $stmt = $pdo->prepare('REPLACE INTO orders_unified (source, source_order_id, user_id, total_amount, currency, status, created_at, raw_json) VALUES ("odoo", :id, :u, :t, :c, :s, :created, :raw)');
            $stmt->execute([
                ':id'=>$o['id'], ':u'=>$user['id'], ':t'=>$o['amount_total'], ':c'=>'EUR', ':s'=>$o['state'], ':created'=>$o['date_order'], ':raw'=>json_encode($o)
            ]);
        }
    }
}

echo "Sync ordini completata\n";
