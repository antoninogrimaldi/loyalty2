<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();
$message = $error = null;

$productRows = $mysqli->query('SELECT name, ean, sku FROM products WHERE active = 1 ORDER BY name ASC')->fetch_all(MYSQLI_ASSOC);
if (!$productRows) {
    $productRows = [
        ['name' => 'Caffè in grani', 'ean' => '8000000000011', 'sku' => 'CAFF-GRANI-001'],
        ['name' => 'Snack bio', 'ean' => '8000000000028', 'sku' => 'SNCK-BIO-002'],
        ['name' => 'Detersivo eco', 'ean' => '8000000000035', 'sku' => 'DETR-ECO-003'],
        ['name' => 'Prodotto personalizzato', 'ean' => '0000000000000', 'sku' => 'CUSTOM-000']
    ];
}
$productMap = [];
foreach ($productRows as $row) {
    $productMap[$row['ean']] = $row;
}

$currentOffersStmt = $mysqli->prepare('SELECT product_name, product_ean, created_at FROM personalized_offers WHERE user_id = ? ORDER BY created_at DESC');
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
<section class="card">
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
                <label>Prodotto 1
                    <select name="product1">
                        <option value="">-- scegli --</option>
                        <?php foreach ($productRows as $product): ?>
                            <option value="<?= e($product['ean']) ?>"><?= e($product['name']) ?> · EAN <?= e($product['ean']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-control">
                <label>Prodotto 2
                    <select name="product2">
                        <option value="">-- scegli --</option>
                        <?php foreach ($productRows as $product): ?>
                            <option value="<?= e($product['ean']) ?>"><?= e($product['name']) ?> · EAN <?= e($product['ean']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </div>
        <p class="muted">L'EAN funge da identificatore unico per Shopify e Odoo. SKU disponibile per cataloghi interni.</p>
        <button class="button" type="submit">Salva preferenze</button>
    </form>
    <h3>Selezioni attuali</h3>
    <?php if (!$currentOffers): ?>
        <p>Nessuna scelta salvata.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($currentOffers as $offer): ?>
                <li><?= e($offer['product_name']) ?> <span class="muted">EAN <?= e($offer['product_ean']) ?> · dal <?= e($offer['created_at']) ?></span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
