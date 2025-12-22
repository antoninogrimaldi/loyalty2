<?php
require_once __DIR__ . '/../app/helpers/shopify.php';
require_once __DIR__ . '/../app/db/db.php';

$pdo = get_db();
$products = shopify_fetch_products();
foreach ($products as $p) {
    $stmt = $pdo->prepare('REPLACE INTO product_cache (shopify_product_id, shopify_variant_id, title, image_url, status, ean, updated_at) VALUES (:pid,:vid,:t,:img,:s,:ean,NOW())');
    $stmt->execute([
        ':pid'=>$p['shopify_product_id'],
        ':vid'=>$p['shopify_variant_id'],
        ':t'=>$p['title'],
        ':img'=>$p['image_url'],
        ':s'=>$p['status'],
        ':ean'=>$p['ean']
    ]);
}

echo "Prodotti sincronizzati: " . count($products) . "\n";
