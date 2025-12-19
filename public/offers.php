<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/shopify.php';
require_login();
$user = current_user();
$message = $error = null;

$search = sanitize_field($_GET['q'] ?? '', 120);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = 'WHERE active = 1';
$params = [];
$types = '';
if ($search !== '') {
    $where .= ' AND (name LIKE ? OR brand LIKE ? OR sku LIKE ? OR ean LIKE ?)';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
    $types = 'ssss';
}

$countSql = "SELECT COUNT(*) AS total FROM products $where";
$countStmt = $mysqli->prepare($countSql);
if ($types) { $countStmt->bind_param($types, ...$params); }
$countStmt->execute();
$totalProducts = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);

$sql = "SELECT name, ean, sku, brand, category, price, image_url, description, shopify_product_id FROM products $where ORDER BY name ASC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
if ($types) {
    $bindParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($types . 'ii', ...$bindParams);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$productRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$productCount = $totalProducts;

if (!$productRows) {
    $productRows = [
        ['name' => 'Caffè in grani', 'ean' => '8000000000011', 'sku' => 'CAFF-GRANI-001', 'brand' => 'Torrefazione Demo', 'category' => 'Dispensa', 'price' => 7.90, 'image_url' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=400&q=60', 'description' => 'Miscela 100% arabica per moka e espresso.'],
        ['name' => 'Snack bio', 'ean' => '8000000000028', 'sku' => 'SNCK-BIO-002', 'brand' => 'Green Snacks', 'category' => 'Alimentari', 'price' => 3.20, 'image_url' => 'https://images.unsplash.com/photo-1585238341986-1e3b71ff0af8?auto=format&fit=crop&w=400&q=60', 'description' => 'Barretta biologica con frutta secca e cereali.'],
        ['name' => 'Detersivo eco', 'ean' => '8000000000035', 'sku' => 'DETR-ECO-003', 'brand' => 'Eco Home', 'category' => 'Cura casa', 'price' => 5.50, 'image_url' => 'https://images.unsplash.com/photo-1582719478248-54e9f2af4b03?auto=format&fit=crop&w=400&q=60', 'description' => 'Detersivo ecologico concentrato per bucato.'],
        ['name' => 'Prodotto personalizzato', 'ean' => '0000000000000', 'sku' => 'CUSTOM-000', 'brand' => 'Catalogo cliente', 'category' => 'Custom', 'price' => 0, 'image_url' => 'https://dummyimage.com/400x260/e0e0e0/555&text=Prodotto', 'description' => 'Segnaposto per prodotti non sincronizzati']
    ];
}
$productMap = [];
foreach ($productRows as $row) {
    $productMap[$row['ean']] = $row;
}

$currentOffersStmt = $mysqli->prepare('SELECT po.product_name, po.product_ean, po.created_at, p.image_url FROM personalized_offers po LEFT JOIN products p ON p.ean = po.product_ean WHERE po.user_id = ? ORDER BY po.created_at DESC');
$currentOffersStmt->bind_param('i', $user['id']);
$currentOffersStmt->execute();
$currentOffers = $currentOffersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$marketingConsent = (int) ($mysqli->query("SELECT marketing FROM user_consents WHERE user_id = {$user['id']}")->fetch_assoc()['marketing'] ?? 0);
$couponRow = $mysqli->query("SELECT code, shopify_price_rule_id FROM coupons WHERE user_id = {$user['id']} AND code LIKE 'OP-%' LIMIT 1")->fetch_assoc();
$couponCode = $couponRow['code'] ?? null;
$couponRuleId = isset($couponRow['shopify_price_rule_id']) ? (int)$couponRow['shopify_price_rule_id'] : null;
$missingOffers = 0;
foreach ($currentOffers as $offer) {
    if (!isset($productMap[$offer['product_ean']])) {
        $missingOffers++;
    }
}

$changesStmt = $mysqli->prepare('SELECT COUNT(*) AS changes_total FROM offer_change_log WHERE user_id = ? AND YEAR(changed_at) = YEAR(CURDATE())');
$changesStmt->bind_param('i', $user['id']);
$changesStmt->execute();
$changes = (int) ($changesStmt->get_result()->fetch_assoc()['changes_total'] ?? 0);
$remainingChanges = max(0, 2 - $changes);
$canOverrideMissing = $missingOffers > 0;

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) { die('Token CSRF non valido'); }
    $selected = [];
    foreach (['product1', 'product2'] as $field) {
        $ean = trim($_POST[$field] ?? '');
        if ($ean !== '') {
            if (!isset($productMap[$ean])) {
                $fetch = $mysqli->prepare('SELECT name, ean, sku, brand, category, price, image_url, description, shopify_product_id FROM products WHERE ean = ? LIMIT 1');
                $fetch->bind_param('s', $ean);
                $fetch->execute();
                $row = $fetch->get_result()->fetch_assoc();
                if (!$row) {
                    $error = 'Prodotto non valido';
                    break;
                }
                $productMap[$ean] = $row;
            }
            $selected[$ean] = $productMap[$ean];
        }
    }

    if (!$error && (count($selected) === 0 || count($selected) > 2)) {
        $error = 'Seleziona massimo 2 prodotti';
    } elseif (!$error && $changes >= 2 && !$canOverrideMissing) {
        $error = 'Limite di 2 cambi all\'anno raggiunto';
    }

    if (!$error) {
        try {
            $mysqli->begin_transaction();
            $del = $mysqli->prepare('DELETE FROM personalized_offers WHERE user_id = ?');
            $del->bind_param('i', $user['id']);
            $del->execute();

            $ins = $mysqli->prepare('INSERT INTO personalized_offers (user_id, product_name, product_ean, yearly_changes) VALUES (?, ?, ?, 1)');
            foreach ($selected as $product) {
                $ins->bind_param('iss', $user['id'], $product['name'], $product['ean']);
                $ins->execute();
            }

            if (!$canOverrideMissing) {
                $log = $mysqli->prepare('INSERT INTO offer_change_log (user_id) VALUES (?)');
                $log->bind_param('i', $user['id']);
                $log->execute();
            }

            $mysqli->commit();
            $shopifySyncError = null;
            if (shopify_enabled()) {
                require_once __DIR__ . '/../includes/shopify.php';
                $customerId = shopify_find_customer_id($user['email']);
                if (!$customerId) {
                    shopify_upsert_customer([
                        'email' => $user['email'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'phone' => $user['phone'] ?? null,
                        'tax_code' => $user['tax_code'] ?? null
                    ], ['marketing' => $marketingConsent]);
                    $customerId = shopify_find_customer_id($user['email']);
                }
                $shopifyProductIds = array_values(array_filter(array_map(fn($p) => $p['shopify_product_id'] ?? '', $selected)));
                if ($customerId && $shopifyProductIds) {
                    $sync = shopify_sync_offer_discount($customerId, $shopifyProductIds, $couponCode, $couponRuleId);
                    if (!empty($sync['ok'])) {
                        $codeToStore = $sync['code'] ?? $couponCode;
                        $ruleToStore = $sync['price_rule_id'] ?? null;
                        if ($codeToStore) {
                            $existingCoupon = $mysqli->prepare('SELECT id FROM coupons WHERE user_id = ? AND code = ?');
                            $existingCoupon->bind_param('is', $user['id'], $codeToStore);
                            $existingCoupon->execute();
                            $exists = $existingCoupon->get_result()->fetch_assoc();
                            if ($exists) {
                                $update = $mysqli->prepare('UPDATE coupons SET description = ?, discount_percent = ?, shopify_price_rule_id = ? WHERE id = ?');
                                $desc = 'Offerta personalizzata Shopify';
                                $discount = 10;
                                $update->bind_param('siii', $desc, $discount, $ruleToStore, $exists['id']);
                                $update->execute();
                            } else {
                                $insert = $mysqli->prepare('INSERT INTO coupons (user_id, code, description, discount_percent, expires_at, is_redeemed, shopify_price_rule_id) VALUES (?, ?, ?, ?, NULL, 0, ?)');
                                $desc = 'Offerta personalizzata Shopify';
                                $discount = 10;
                                $insert->bind_param('issii', $user['id'], $codeToStore, $desc, $discount, $ruleToStore);
                                $insert->execute();
                            }
                        }
                    } else {
                        $shopifySyncError = $sync['error'] ?? 'Sync Shopify fallita';
                    }
                } else {
                    $shopifySyncError = 'Cliente o prodotti non disponibili su Shopify';
                }
            }

            if (!$shopifySyncError) {
                header('Location: ' . base_url('public/offers.php'));
                exit;
            } else {
                $message = 'Preferenze salvate, ma lo sconto Shopify non è stato aggiornato: ' . $shopifySyncError;
            }
        } catch (mysqli_sql_exception $e) {
            $mysqli->rollback();
            $error = 'Errore nel salvataggio delle preferenze';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="card card-form">
    <div class="card-header">
        <div>
            <p class="eyebrow">Match 1:1 prodotto</p>
            <h2>Offerte personalizzate</h2>
            <p class="muted">Scegli massimo 2 prodotti per pricing dedicato. Cambi possibili: <?= e($remainingChanges) ?>/2 quest'anno.</p>
        </div>
        <div class="pill">Catalogo sincronizzabile</div>
    </div>
    <?php if ($message): ?><p class="success"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <h3>Selezioni attuali</h3>
    <?php if (!$currentOffers): ?>
        <p class="muted">Non hai offerte selezionate.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($currentOffers as $offer): ?>
                <li class="offer-row">
                    <?php if (!empty($offer['image_url'])): ?><span class="offer-thumb" style="background-image:url('<?= e($offer['image_url']) ?>');"></span><?php endif; ?>
                    <div>
                        <div><strong><?= e($offer['product_name']) ?></strong> <span class="pill subtle">EAN <?= e($offer['product_ean']) ?></span></div>
                        <span class="muted">Dal <?= e($offer['created_at']) ?></span>
                        <?php if (!isset($productMap[$offer['product_ean']])): ?>
                            <div class="pill subtle">Non più in catalogo</div>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($canOverrideMissing): ?>
            <p class="muted small">Alcune scelte non sono più disponibili: puoi sostituirle senza conteggiare il cambio.</p>
        <?php endif; ?>
    <?php endif; ?>
    <form method="post" class="stacked">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="grid">
            <div class="form-control">
                <label for="product1">Prodotto 1</label>
                <input id="product1" name="product1" list="products-list" placeholder="Cerca per nome, brand o EAN" aria-describedby="product1-help">
                <small id="product1-help" class="muted">Digita per filtrare rapidamente migliaia di prodotti.</small>
            </div>
            <div class="form-control">
                <label for="product2">Prodotto 2</label>
                <input id="product2" name="product2" list="products-list" placeholder="Cerca per nome, brand o EAN" aria-describedby="product2-help">
                <small id="product2-help" class="muted">Lascia vuoto se desideri selezionare solo un prodotto.</small>
            </div>
        </div>
        <datalist id="products-list">
            <?php foreach ($productRows as $product): ?>
                <option value="<?= e($product['ean']) ?>" data-ean="<?= e($product['ean']) ?>" label="<?= e($product['name'] . ' • ' . ($product['brand'] ?? '')) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <p class="muted">L'EAN funge da identificatore unico per Shopify e Odoo. SKU disponibile per cataloghi interni.</p>
        <button class="button" type="submit">Salva preferenze</button>
    </form>
    <div class="top-actions">
        <h3 style="margin: 0;">Catalogo sincronizzato</h3>
        <form method="get" class="form-control" style="margin:0;">
            <label class="small muted">Filtro rapido
                <input type="search" class="filter-input" name="q" data-product-filter="[data-product-card]" value="<?= e($search) ?>" placeholder="Filtra prodotti per nome, SKU, brand o EAN" aria-label="Filtro prodotti server-side">
            </label>
            <input type="hidden" name="page" value="1">
        </form>
    </div>
    <p class="muted small" data-product-count>Prodotti attivi: <?= e($productCount ?: count($productRows)) ?> · Ottimizzato per cataloghi da 1.000+ SKU.</p>
    <div class="product-gallery" data-product-gallery>
        <?php foreach ($productRows as $product): ?>
            <article class="product-card" data-product-card data-searchable="<?= e(strtolower($product['name'] . ' ' . ($product['brand'] ?? '') . ' ' . $product['sku'] . ' ' . $product['ean'])) ?>">
                <?php if (!empty($product['image_url'])): ?>
                    <div class="product-media" style="background-image:url('<?= e($product['image_url']) ?>');"></div>
                <?php endif; ?>
                <div class="product-body">
                    <div class="product-meta">
                        <span class="pill subtle"><?= e($product['category'] ?? 'Catalogo') ?></span>
                        <span class="pill subtle">SKU <?= e($product['sku']) ?></span>
                    </div>
                    <h4><?= e($product['name']) ?></h4>
                    <p class="muted">Brand: <?= e($product['brand'] ?? 'N/D') ?> · EAN <?= e($product['ean']) ?></p>
                    <?php if (!empty($product['description'])): ?><p class="muted small"><?= e($product['description']) ?></p><?php endif; ?>
                    <div class="product-footer">
                        <strong>&euro; <?= number_format((float)$product['price'], 2) ?></strong>
                        <span class="pill">Preferisci nel menu</span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="muted" data-empty-catalog style="display:none;">Nessun prodotto corrispondente. Allarga il filtro o sincronizza il catalogo.</p>
    <?php if ($productCount > $perPage): ?>
        <div class="top-actions">
            <div class="muted small">Pagina <?= e($page) ?> di <?= e((int) ceil($productCount / $perPage)) ?></div>
            <div class="hero-actions">
                <?php if ($page > 1): ?>
                    <a class="button ghost small" href="<?= e(base_url('public/offers.php') . '?page=' . ($page - 1) . ($search ? '&q=' . urlencode($search) : '')) ?>">&larr; Precedente</a>
                <?php endif; ?>
                <?php if ($page < ceil($productCount / $perPage)): ?>
                    <a class="button ghost small" href="<?= e(base_url('public/offers.php') . '?page=' . ($page + 1) . ($search ? '&q=' . urlencode($search) : '')) ?>">Successiva &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
