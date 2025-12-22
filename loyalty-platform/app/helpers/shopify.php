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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch) || $httpCode >= 300) {
        app_log('Shopify error (' . $httpCode . '): ' . curl_error($ch) . ' body:' . substr($response, 0, 500));
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

function shopify_generate_offer_code(int $userId, string $email): string
{
    return 'BEST-' . $userId . '-' . substr(md5($email . microtime(true)), 0, 6);
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

function shopify_create_or_update_discount_code_for_offer(array $user, array $offer, string $code, array $variantIds)
{
    if (empty($offer['shopify_customer_id'])) {
        app_log('Shopify discount skipped: missing shopify_customer_id for user '.$user['id']);
        return null;
    }
    $targetSelection = $variantIds ? 'entitled' : 'all';
    $priceRuleBody = [
        'title' => $code,
        'target_type' => 'line_item',
        'target_selection' => $targetSelection,
        'allocation_method' => 'across',
        'value_type' => 'percentage',
        'value' => '-' . ($offer['discount_pct'] ?? 10),
        'customer_selection' => 'prerequisite',
        'prerequisite_customer_ids' => [$offer['shopify_customer_id']],
        'once_per_customer' => true,
        'usage_limit' => null,
        'starts_at' => date('c')
    ];
    if ($variantIds) {
        $priceRuleBody['entitled_variant_ids'] = $variantIds;
    }
    $priceRule = shopify_request('POST', 'price_rules.json', [
        'price_rule' => $priceRuleBody
    ]);
    $priceRuleId = $priceRule['price_rule']['id'] ?? null;
    if (!$priceRuleId) {
        app_log('Shopify discount creation failed for user '.$user['id'].' code '.$code);
        return null;
    }
    $discountResp = shopify_request('POST', "price_rules/{$priceRuleId}/discount_codes.json", [
        'discount_code' => ['code' => $code]
    ]);
    if (!isset($discountResp['discount_code']['code'])) {
        app_log('Shopify discount code creation failed for user '.$user['id'].' price_rule '.$priceRuleId);
        return null;
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
