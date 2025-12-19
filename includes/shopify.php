<?php
require_once __DIR__ . '/helpers.php';

function shopify_config(): array
{
    return app_config()['shopify'] ?? [];
}

function shopify_enabled(): bool
{
    $cfg = shopify_config();
    return !empty($cfg['domain']) && !empty($cfg['access_token']);
}

function shopify_request(string $method, string $path, array $payload = [], array $query = []): array
{
    $cfg = shopify_config();
    if (!shopify_enabled()) {
        return ['ok' => false, 'error' => 'Shopify non configurato'];
    }

    $url = "https://{$cfg['domain']}/admin/api/" . ($cfg['api_version'] ?? '2024-04') . '/' . ltrim($path, '/');
    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);
    $headers = [
        'Content-Type: application/json',
        'X-Shopify-Access-Token: ' . $cfg['access_token']
    ];
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    if (in_array(strtoupper($method), ['POST','PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $raw = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'error' => 'Errore cURL: ' . $err];
    }
    $data = json_decode($body, true);
    $nextPage = null;
    if (preg_match('/<[^>]*[?&]page_info=([^&>]*)[^>]*>; rel="next"/', $header, $m)) {
        $nextPage = $m[1];
    }

    return [
        'ok' => $httpCode >= 200 && $httpCode < 300,
        'status' => $httpCode,
        'data' => $data,
        'raw' => $body,
        'next_page_info' => $nextPage
    ];
}

function shopify_find_customer_id(string $email): ?int
{
    $response = shopify_request('GET', 'customers/search.json', [], ['query' => "email:$email", 'limit' => 1]);
    if (!$response['ok'] || empty($response['data']['customers'][0])) {
        return null;
    }
    return (int) $response['data']['customers'][0]['id'];
}

function shopify_upsert_customer(array $user, array $consents): array
{
    if (!shopify_enabled()) {
        return ['ok' => false, 'error' => 'Shopify non configurato'];
    }

    $marketing = !empty($consents['marketing']);
    $customer = [
        'email' => $user['email'],
        'first_name' => $user['first_name'] ?? '',
        'last_name' => $user['last_name'] ?? '',
        'phone' => $user['phone'] ?? null,
        'note' => !empty($user['tax_code']) ? ('CF: ' . $user['tax_code']) : null,
        'accepts_marketing' => $marketing,
        'accepts_marketing_updated_at' => gmdate('c'),
        'marketing_opt_in_level' => $marketing ? 'single_opt_in' : 'unknown'
    ];

    $customerId = shopify_find_customer_id($user['email']);
    if ($customerId) {
        $existing = shopify_request('GET', "customers/{$customerId}.json");
        $tags = [];
        if ($existing['ok'] && !empty($existing['data']['customer']['tags'])) {
            $tags = array_filter(array_map('trim', explode(',', $existing['data']['customer']['tags'])));
        }
        if (!in_array('fidelity', $tags, true)) {
            $tags[] = 'fidelity';
        }
        $customer['tags'] = implode(', ', $tags);
        $response = shopify_request('PUT', "customers/{$customerId}.json", ['customer' => array_merge($customer, ['id' => $customerId])]);
    } else {
        $customer['tags'] = 'fidelity';
        $response = shopify_request('POST', 'customers.json', ['customer' => $customer]);
    }

    return $response;
}

function shopify_lookup_discount_code(string $code): ?int
{
    $resp = shopify_request('GET', 'discount_codes/lookup.json', [], ['code' => $code]);
    if (!$resp['ok'] || empty($resp['data']['discount_code']['price_rule_id'])) {
        return null;
    }
    return (int) $resp['data']['discount_code']['price_rule_id'];
}

function shopify_sync_offer_discount(int $customerId, array $productShopifyIds, ?string $existingCode = null, int $discountPercent = 10): array
{
    if (!shopify_enabled()) {
        return ['ok' => false, 'error' => 'Shopify non configurato'];
    }
    $productIds = array_values(array_filter($productShopifyIds, fn($id) => !empty($id)));
    if (count($productIds) === 0) {
        return ['ok' => false, 'error' => 'Nessun prodotto Shopify da scontare'];
    }

    $code = $existingCode ?: 'OP-' . strtoupper(bin2hex(random_bytes(3)));
    $priceRuleId = $existingCode ? shopify_lookup_discount_code($code) : null;

    $priceRulePayload = [
        'price_rule' => [
            'title' => $code,
            'target_type' => 'line_item',
            'target_selection' => 'entitled',
            'allocation_method' => 'across',
            'value_type' => 'percentage',
            'value' => -1 * $discountPercent,
            'customer_selection' => 'prerequisite',
            'prerequisite_customer_ids' => [$customerId],
            'entitled_product_ids' => $productIds,
            'usage_limit' => null,
            'once_per_customer' => false,
            'starts_at' => gmdate('c')
        ]
    ];

    if ($priceRuleId) {
        $update = shopify_request('PUT', "price_rules/{$priceRuleId}.json", $priceRulePayload);
        if (!$update['ok']) {
            return ['ok' => false, 'error' => 'Aggiornamento rule fallito'];
        }
    } else {
        $createRule = shopify_request('POST', 'price_rules.json', $priceRulePayload);
        if (!$createRule['ok'] || empty($createRule['data']['price_rule']['id'])) {
            return ['ok' => false, 'error' => 'Creazione rule fallita'];
        }
        $priceRuleId = (int) $createRule['data']['price_rule']['id'];
        $createCode = shopify_request('POST', "price_rules/{$priceRuleId}/discount_codes.json", ['discount_code' => ['code' => $code]]);
        if (!$createCode['ok']) {
            return ['ok' => false, 'error' => 'Creazione codice fallita'];
        }
    }

    return ['ok' => true, 'code' => $code];
}

function shopify_fetch_products_page(?string $pageInfo = null): array
{
    $query = ['limit' => 250, 'fields' => 'id,title,body_html,product_type,vendor,variants,image'];
    if ($pageInfo) { $query['page_info'] = $pageInfo; }
    $response = shopify_request('GET', 'products.json', [], $query);
    return $response;
}

function shopify_sync_products(mysqli $mysqli): array
{
    if (!shopify_enabled()) {
        return ['ok' => false, 'error' => 'Shopify non configurato'];
    }

    $inserted = 0;
    $updated = 0;
    $pageInfo = null;
    $pages = 0;
    $seenShopifyIds = [];

    do {
        $resp = shopify_fetch_products_page($pageInfo);
        if (!$resp['ok']) { return ['ok' => false, 'error' => 'Sync prodotti fallito: ' . ($resp['status'] ?? '')]; }
        $products = $resp['data']['products'] ?? [];
        foreach ($products as $product) {
            $title = sanitize_field($product['title'] ?? 'Prodotto Shopify', 120);
            $brand = sanitize_field($product['vendor'] ?? '', 120);
            $category = sanitize_field($product['product_type'] ?? '', 120);
            $description = sanitize_field(html_entity_decode(strip_tags($product['body_html'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 255);
            $imageUrl = sanitize_field($product['image']['src'] ?? '', 255);
            $shopifyId = (string) ($product['id'] ?? '');
            if ($shopifyId !== '') { $seenShopifyIds[] = $shopifyId; }

            if (empty($product['variants'])) { continue; }
            foreach ($product['variants'] as $variant) {
                $variantName = $title;
                $variantTitle = trim($variant['title'] ?? '');
                if ($variantTitle && strtolower($variantTitle) !== 'default title') {
                    $variantName = sanitize_field($title . ' - ' . $variantTitle, 120);
                }
                $ean = sanitize_field($variant['barcode'] ?? $variant['sku'] ?? '', 32);
                $sku = sanitize_field($variant['sku'] ?? $variant['barcode'] ?? '', 60);
                if ($ean === '' && $sku === '') { continue; }
                $price = (float) ($variant['price'] ?? 0);

                $stmt = $mysqli->prepare('INSERT INTO products (name, sku, ean, brand, category, description, image_url, price, active, odoo_product_id, shopify_product_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NULL, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), brand=VALUES(brand), category=VALUES(category), description=VALUES(description), image_url=VALUES(image_url), price=VALUES(price), active=VALUES(active), shopify_product_id=VALUES(shopify_product_id)');
                $stmt->bind_param('sssssssds', $variantName, $sku, $ean, $brand, $category, $description, $imageUrl, $price, $shopifyId);
                $stmt->execute();

                if ($stmt->affected_rows === 1) {
                    $inserted++;
                } else {
                    $updated++;
                }
            }
        }
        $pages++;
        $pageInfo = $resp['next_page_info'] ?? null;
    } while ($pageInfo && $pages < 10); // safety guard

    // Elimina prodotti non più presenti su Shopify
    $seen = array_unique($seenShopifyIds);
    $existing = $mysqli->query("SELECT shopify_product_id FROM products WHERE shopify_product_id IS NOT NULL AND shopify_product_id != ''")->fetch_all(MYSQLI_ASSOC);
    if ($existing) {
        $deleteStmt = $mysqli->prepare('DELETE FROM products WHERE shopify_product_id = ?');
        foreach ($existing as $row) {
            $id = (string) $row['shopify_product_id'];
            if ($id === '' || in_array($id, $seen, true)) {
                continue;
            }
            $deleteStmt->bind_param('s', $id);
            $deleteStmt->execute();
        }
    }

    return ['ok' => true, 'inserted' => $inserted, 'updated' => $updated];
}
