<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();
$message = $error = null;

$productRows = $mysqli->query('SELECT name, ean, sku, brand, category, price, image_url, description FROM products WHERE active = 1 ORDER BY name ASC')->fetch_all(MYSQLI_ASSOC);
$productCount = count($productRows);
if (!$productRows) {
    $productRows = [
        ['name' => 'Caffè in grani', 'ean' => '8000000000011', 'sku' => 'CAFF-GRANI-001', 'brand' => 'Torrefazione Demo', 'category' => 'Dispensa', 'price' => 7.90, 'image_url' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=400&q=60', 'description' => 'Miscela 100% arabica per moka e espresso.'],
        ['name' => 'Snack bio', 'ean' => '8000000000028', 'sku' => 'SNCK-BIO-002', 'brand' => 'Green Snacks', 'category' => 'Alimentari', 'price' => 3.20, 'image_url' => 'https://images.unsplash.com/photo-1585238341986-1e3b71ff0af8?auto=format&fit=crop&w=400&q=60', 'description' => 'Barretta biologica con frutta secca e cereali.'],
        ['name' => 'Detersivo eco', 'ean' => '8000000000035', 'sku' => 'DETR-ECO-003', 'brand' => 'Eco Home', 'category' => 'Cura casa', 'price' => 5.50, 'image_url' => 'https://images.unsplash.com/photo-1582719478248-54e9f2af4b03?auto=format&fit=crop&w=400&q=60', 'description' => 'Detersivo ecologico concentrato per bucato.'],
        ['name' => 'Prodotto personalizzato', 'ean' => '0000000000000', 'sku' => 'CUSTOM-000', 'brand' => 'Catalogo cliente', 'category' => 'Custom', 'price' => 0, 'image_url' => 'https://dummyimage.com/400x260/e0e0e0/555&text=Prodotto', 'description' => 'Segnaposto per prodotti non sincronizzati']
    ];
    $productCount = count($productRows);
}
$productMap = [];
foreach ($productRows as $row) {
    $productMap[$row['ean']] = $row;
}

$currentOffersStmt = $mysqli->prepare('SELECT po.product_name, po.product_ean, po.created_at, p.image_url FROM personalized_offers po LEFT JOIN products p ON p.ean = po.product_ean WHERE po.user_id = ? ORDER BY po.created_at DESC');
$currentOffersStmt->bind_param('i', $user['id']);
$currentOffersStmt->execute();
$currentOffers = $currentOffersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$changesStmt = $mysqli->prepare('SELECT COUNT(*) AS changes_total FROM offer_change_log WHERE user_id = ? AND YEAR(changed_at) = YEAR(CURDATE())');
$changesStmt->bind_param('i', $user['id']);
$changesStmt->execute();
$changes = (int) ($changesStmt->get_result()->fetch_assoc()['changes_total'] ?? 0);
$remainingChanges = max(0, 2 - $changes);

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) { die('Token CSRF non valido'); }
    $selected = [];
    foreach (['product1', 'product2'] as $field) {
        $ean = trim($_POST[$field] ?? '');
        if ($ean !== '') {
            if (!isset($productMap[$ean])) {
                $error = 'Prodotto non valido';
                break;
            }
            $selected[$ean] = $productMap[$ean];
        }
    }

    if (!$error && (count($selected) === 0 || count($selected) > 2)) {
        $error = 'Seleziona massimo 2 prodotti';
    } elseif (!$error && $changes >= 2) {
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

            $log = $mysqli->prepare('INSERT INTO offer_change_log (user_id) VALUES (?)');
            $log->bind_param('i', $user['id']);
            $log->execute();

            $mysqli->commit();
            header('Location: ' . base_url('public/offers.php'));
            exit;
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
        <div class="form-control" style="margin:0;">
            <label class="small muted">Filtro rapido
                <input type="search" class="filter-input" placeholder="Filtra prodotti per nome, SKU, brand o EAN" data-product-filter aria-label="Filtro prodotti">
            </label>
        </div>
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
    <h3>Selezioni attuali</h3>
    <?php if (!$currentOffers): ?>
        <p>Nessuna scelta salvata.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($currentOffers as $offer): ?>
                <li class="offer-row">
                    <?php if (!empty($offer['image_url'])): ?><span class="offer-thumb" style="background-image:url('<?= e($offer['image_url']) ?>');"></span><?php endif; ?>
                    <div>
                        <div><strong><?= e($offer['product_name']) ?></strong> <span class="pill subtle">EAN <?= e($offer['product_ean']) ?></span></div>
                        <span class="muted">Dal <?= e($offer['created_at']) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
