<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();
$message = $error = null;

// lista prodotti base (in produzione usare tabella products)
$products = [];
$prodResult = $mysqli->query('SELECT name FROM products WHERE active = 1 ORDER BY name ASC');
if ($prodResult) {
    while ($row = $prodResult->fetch_assoc()) { $products[] = $row['name']; }
}
if (!$products) {
    $products = ['Caffè in grani', 'Snack bio', 'Detersivo eco', 'Prodotto personalizzato'];
}

// offerte esistenti
$currentOffersStmt = $mysqli->prepare('SELECT id, product_name, yearly_changes, created_at FROM personalized_offers WHERE user_id = ? ORDER BY created_at DESC');
$currentOffersStmt->bind_param('i', $user['id']);
$currentOffersStmt->execute();
$currentOffers = $currentOffersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// conteggio modifiche nell'anno
$changesStmt = $mysqli->prepare('SELECT SUM(yearly_changes) AS changes_total FROM personalized_offers WHERE user_id = ? AND YEAR(created_at) = YEAR(CURDATE())');
$changesStmt->bind_param('i', $user['id']);
$changesStmt->execute();
$changes = (int) ($changesStmt->get_result()->fetch_assoc()['changes_total'] ?? 0);

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) { die('Token CSRF non valido'); }
    $selected = array_filter([trim($_POST['product1'] ?? ''), trim($_POST['product2'] ?? '')]);
    if (count($selected) === 0 || count($selected) > 2) {
        $error = 'Seleziona massimo 2 prodotti';
    } elseif ($changes >= 2) {
        $error = 'Limite di 2 cambi all\'anno raggiunto';
    } else {
        // reset offerte precedenti e salva nuove scelte
        $del = $mysqli->prepare('DELETE FROM personalized_offers WHERE user_id = ?');
        $del->bind_param('i', $user['id']);
        $del->execute();
        $ins = $mysqli->prepare('INSERT INTO personalized_offers (user_id, product_name, yearly_changes) VALUES (?, ?, 1)');
        foreach ($selected as $product) {
            $ins->bind_param('is', $user['id'], $product);
            $ins->execute();
        }
        $message = 'Preferenze aggiornate. Puoi cambiarle ancora ' . max(0, 1 - $changes) . ' volte quest\'anno.';
        header('Location: /public/offers.php');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Offerte personalizzate</h2>
    <p class="muted">Seleziona massimo 2 prodotti. Cambi possibili: 2/anno.</p>
    <?php if ($message): ?><p class="success"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="grid">
            <div class="form-control">
                <label>Prodotto 1
                    <select name="product1">
                        <option value="">-- scegli --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= e($product) ?>"><?= e($product) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-control">
                <label>Prodotto 2
                    <select name="product2">
                        <option value="">-- scegli --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= e($product) ?>"><?= e($product) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </div>
        <button class="button" type="submit">Salva preferenze</button>
    </form>
    <h3>Selezioni attuali</h3>
    <?php if (!$currentOffers): ?>
        <p>Nessuna scelta salvata.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($currentOffers as $offer): ?>
                <li><?= e($offer['product_name']) ?> <span class="muted">dal <?= e($offer['created_at']) ?></span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
