<?php
require_once __DIR__ . '/../app/helpers/shopify.php';
require_once __DIR__ . '/../app/helpers/odoo.php';
require_once __DIR__ . '/../app/helpers/logger.php';
require_once __DIR__ . '/../app/db/db.php';

$pdo = get_db();
$offers = $pdo->query('SELECT po.*, u.email, u.id as user_id, cp.first_name, cp.last_name, cp.shopify_customer_id, cp.odoo_partner_id
    FROM personalized_offers po
    JOIN users u ON u.id = po.user_id
    JOIN customer_profiles cp ON cp.user_id = po.user_id
    WHERE (po.shopify_discount_code IS NULL OR po.shopify_discount_code = "")')->fetchAll();

foreach ($offers as $offer) {
    // crea codice sconto Shopify
    $code = shopify_create_or_update_discount_code_for_offer($offer, $offer);
    if ($code) {
        $stmt = $pdo->prepare('UPDATE personalized_offers SET shopify_discount_code=:c WHERE id=:id');
        $stmt->execute([':c' => $code, ':id' => $offer['id']]);
    } else {
        app_log('Sync offer: impossibile creare codice per offer ' . $offer['id']);
    }
    // aggiorna Odoo campi custom se partner noto
    if (!empty($offer['odoo_partner_id'])) {
        odoo_update_partner_offer_fields($offer['odoo_partner_id'], [
            'x_offer_prod1_ean' => $offer['product1_ean'],
            'x_offer_prod2_ean' => $offer['product2_ean'],
            'x_offer_discount_pct' => $offer['discount_pct'],
            'x_offer_updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

echo "Sync offerte completata\n";
