<?php
require_once __DIR__ . '/../app/helpers/shopify.php';
require_once __DIR__ . '/../app/helpers/odoo.php';
require_once __DIR__ . '/../app/helpers/points.php';
require_once __DIR__ . '/../app/db/db.php';

$pdo = get_db();
$users = $pdo->query('SELECT id, shopify_customer_id, odoo_partner_id FROM customer_profiles')->fetchAll();
foreach ($users as $user) {
    if ($user['shopify_customer_id']) {
        $refunds = shopify_fetch_refunds();
        foreach ($refunds as $r) {
            $stmt = $pdo->prepare('REPLACE INTO returns_unified (source, source_return_id, user_id, amount, status, created_at, raw_json) VALUES ("shopify", :id, :u, :a, :s, :created, :raw)');
            $stmt->execute([
                ':id'=>$r['id'], ':u'=>$user['id'], ':a'=>$r['transactions'][0]['amount'] ?? 0, ':s'=>$r['status'] ?? 'done', ':created'=>$r['created_at'] ?? date('Y-m-d H:i:s'), ':raw'=>json_encode($r)
            ]);
            adjust_points($user['id'], $r['transactions'][0]['amount'] ?? 0, 'refund', 'refund', $r['id'], 'Reso Shopify');
        }
    }
    if ($user['odoo_partner_id']) {
        $returns = odoo_fetch_returns_by_partner($user['odoo_partner_id']);
        foreach ($returns as $r) {
            $stmt = $pdo->prepare('REPLACE INTO returns_unified (source, source_return_id, user_id, amount, status, created_at, raw_json) VALUES ("odoo", :id, :u, :a, :s, :created, :raw)');
            $stmt->execute([
                ':id'=>$r['id'], ':u'=>$user['id'], ':a'=>0, ':s'=>$r['state'] ?? 'done', ':created'=>$r['create_date'] ?? date('Y-m-d H:i:s'), ':raw'=>json_encode($r)
            ]);
        }
    }
}

echo "Sync resi completata\n";
