<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/logger.php';

function shopify_request($method, $endpoint, $data = null)
{
    $url = 'https://' . SHOPIFY_SHOP . '/admin/api/' . SHOPIFY_API_VERSION . '/' . ltrim($endpoint, '/');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Shopify-Access-Token: ' . SHOPIFY_ADMIN_TOKEN,
        'Content-Type: application/json'
    ]);
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        app_log('Shopify error: ' . curl_error($ch));
        return null;
    }
    return json_decode($response, true);
}

function shopify_upsert_customer(array $profile)
{
    $data = [
        'customer' => [
            'first_name' => $profile['first_name'],
            'last_name' => $profile['last_name'],
            'email' => $profile['email'],
            'phone' => $profile['phone'] ?? null,
            'verified_email' => true,
            'accepts_marketing' => !empty($profile['consent_marketing'])
        ]
    ];
    $resp = shopify_request('POST', 'customers.json', $data);
    return $resp['customer']['id'] ?? null;
}

function shopify_fetch_products($since = null)
{
    $endpoint = 'products.json?status=active&limit=100';
    if ($since) {
        $endpoint .= '&updated_at_min=' . urlencode($since);
    }
    $resp = shopify_request('GET', $endpoint);
    $products = [];
    foreach ($resp['products'] ?? [] as $product) {
        foreach ($product['variants'] as $variant) {
            $products[] = [
                'shopify_product_id' => $product['id'],
                'shopify_variant_id' => $variant['id'],
                'title' => $product['title'],
                'image_url' => $product['image']['src'] ?? null,
                'status' => $product['status'],
                'ean' => $variant['barcode'] ?? null,
            ];
        }
    }
    return $products;
}

function shopify_create_or_update_discount_code_for_offer(array $user, array $offer)
{
    $code = 'BEST-' . $user['id'] . '-' . substr(md5($user['email'] . time()), 0, 6);
    // Fase 1: creiamo un price rule generico sconto percentuale su carrello
    $priceRule = shopify_request('POST', 'price_rules.json', [
        'price_rule' => [
            'title' => $code,
            'target_type' => 'line_item',
            'target_selection' => 'all',
            'allocation_method' => 'across',
            'value_type' => 'percentage',
            'value' => '-' . ($offer['discount_pct'] ?? 10),
            'customer_selection' => 'all',
            'once_per_customer' => false,
            'usage_limit' => null,
            'starts_at' => date('c')
        ]
    ]);
    $priceRuleId = $priceRule['price_rule']['id'] ?? null;
    if ($priceRuleId) {
        shopify_request('POST', "price_rules/{$priceRuleId}/discount_codes.json", [
            'discount_code' => ['code' => $code]
        ]);
    }
    return $code;
}

function shopify_fetch_orders_by_customer($customerId, $since = null)
{
    $endpoint = 'orders.json?customer_id=' . urlencode($customerId) . '&status=any&limit=50';
    if ($since) {
        $endpoint .= '&updated_at_min=' . urlencode($since);
    }
    $resp = shopify_request('GET', $endpoint);
    return $resp['orders'] ?? [];
}

function shopify_fetch_refunds($since = null)
{
    $endpoint = 'refunds.json';
    if ($since) {
        $endpoint .= '?updated_at_min=' . urlencode($since);
    }
    $resp = shopify_request('GET', $endpoint);
    return $resp['refunds'] ?? [];
}
