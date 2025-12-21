<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/logger.php';

function odoo_rpc($method, $params)
{
    $payload = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'call',
        'params' => $params,
        'id' => rand(1, 100000)
    ]);
    $ch = curl_init(ODOO_URL . '/jsonrpc');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        app_log('Odoo error: ' . curl_error($ch));
        return null;
    }
    return json_decode($resp, true)['result'] ?? null;
}

function odoo_authenticate()
{
    return odoo_rpc('login', [
        'db' => ODOO_DB,
        'login' => ODOO_USER,
        'password' => ODOO_PASSWORD
    ]);
}

function odoo_upsert_partner(array $profile)
{
    $uid = odoo_authenticate();
    if (!$uid) return null;
    $existing = odoo_rpc('call', [
        'model' => 'res.partner',
        'method' => 'search',
        'args' => [[[['email', '=', $profile['email']]]]],
        'kwargs' => ['offset' => 0, 'limit' => 1],
        'service' => 'object',
        'sid' => null,
        'context' => ['lang' => 'it_IT'],
        'db' => ODOO_DB,
        'uid' => $uid,
        'password' => ODOO_PASSWORD
    ]);
    $partnerId = $existing[0] ?? null;
    $data = [
        'name' => $profile['first_name'] . ' ' . $profile['last_name'],
        'email' => $profile['email'],
        'phone' => $profile['phone'] ?? null,
        'street' => $profile['address'] ?? null,
        'zip' => $profile['zip'] ?? null,
        'city' => $profile['city'] ?? null,
        'country_id' => null,
        'x_offer_prod1_ean' => $profile['product1_ean'] ?? null,
        'x_offer_prod2_ean' => $profile['product2_ean'] ?? null,
        'x_offer_discount_pct' => $profile['discount_pct'] ?? null,
        'x_offer_updated_at' => date('Y-m-d H:i:s')
    ];
    if ($partnerId) {
        odoo_rpc('call', [
            'model' => 'res.partner',
            'method' => 'write',
            'args' => [[$partnerId], $data],
            'service' => 'object',
            'db' => ODOO_DB,
            'uid' => $uid,
            'password' => ODOO_PASSWORD
        ]);
        return $partnerId;
    }
    $created = odoo_rpc('call', [
        'model' => 'res.partner',
        'method' => 'create',
        'args' => [$data],
        'service' => 'object',
        'db' => ODOO_DB,
        'uid' => $uid,
        'password' => ODOO_PASSWORD
    ]);
    return $created;
}

function odoo_update_partner_offer_fields($partnerId, array $fields)
{
    $uid = odoo_authenticate();
    return odoo_rpc('call', [
        'model' => 'res.partner',
        'method' => 'write',
        'args' => [[$partnerId], $fields],
        'service' => 'object',
        'db' => ODOO_DB,
        'uid' => $uid,
        'password' => ODOO_PASSWORD
    ]);
}

function odoo_fetch_orders_by_partner($partnerId, $since = null)
{
    $uid = odoo_authenticate();
    $domain = [['partner_id', '=', $partnerId]];
    if ($since) {
        $domain[] = ['write_date', '>=', $since];
    }
    return odoo_rpc('call', [
        'model' => 'sale.order',
        'method' => 'search_read',
        'args' => [$domain],
        'kwargs' => ['fields' => ['id', 'amount_total', 'date_order', 'state', 'name']],
        'service' => 'object',
        'db' => ODOO_DB,
        'uid' => $uid,
        'password' => ODOO_PASSWORD
    ]) ?? [];
}

function odoo_fetch_returns_by_partner($partnerId, $since = null)
{
    $uid = odoo_authenticate();
    $domain = [['partner_id', '=', $partnerId]];
    if ($since) {
        $domain[] = ['write_date', '>=', $since];
    }
    return odoo_rpc('call', [
        'model' => 'stock.return.picking',
        'method' => 'search_read',
        'args' => [$domain],
        'kwargs' => ['fields' => ['id', 'name', 'create_date', 'state']],
        'service' => 'object',
        'db' => ODOO_DB,
        'uid' => $uid,
        'password' => ODOO_PASSWORD
    ]) ?? [];
}
