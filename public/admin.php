<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (current_user()['role'] !== 'admin') { http_response_code(403); die('Solo admin'); }

$message = $error = null;

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) { die('Token CSRF non valido'); }

    if (isset($_POST['add_points'])) {
        $userId = (int) $_POST['user_id'];
        $points = (int) $_POST['points'];
        $stmt = $mysqli->prepare('UPDATE loyalty_balances SET points = points + ? WHERE user_id = ?');
        $stmt->bind_param('ii', $points, $userId);
        $stmt->execute();
        $message = 'Punti aggiornati';
    }

    if (isset($_POST['create_coupon'])) {
        $code = strtoupper(trim($_POST['code']));
        $desc = trim($_POST['description']);
        $discount = (int) $_POST['discount'];
        $userId = $_POST['coupon_user'] ? (int) $_POST['coupon_user'] : null;
        $stmt = $mysqli->prepare('INSERT INTO coupons (user_id, code, description, discount_percent, expires_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issis', $userId, $code, $desc, $discount, $_POST['expires_at']);
        $stmt->execute();
        $message = 'Coupon creato';
    }
}

$users = $mysqli->query('SELECT u.id, u.first_name, u.last_name, u.email, u.role, COALESCE(lb.points,0) as points, COALESCE(uc.data_processing,0) as data_processing, COALESCE(uc.profiling,0) as profiling, COALESCE(uc.marketing,0) as marketing, uc.recorded_at FROM users u LEFT JOIN loyalty_balances lb ON lb.user_id = u.id LEFT JOIN user_consents uc ON uc.user_id = u.id ORDER BY u.created_at DESC')->fetch_all(MYSQLI_ASSOC);
$coupons = $mysqli->query('SELECT code, description, discount_percent, expires_at FROM coupons ORDER BY expires_at DESC')->fetch_all(MYSQLI_ASSOC);
$offers = $mysqli->query('SELECT u.email, p.product_name, p.product_ean, p.created_at FROM personalized_offers p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC LIMIT 20')->fetch_all(MYSQLI_ASSOC);
$products = $mysqli->query('SELECT name, sku, ean, price, active, odoo_product_id, shopify_product_id FROM products ORDER BY name ASC')->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<section class="grid">
    <div class="card">
        <h3>Clienti</h3>
        <table class="table">
            <thead><tr><th>Nome</th><th>Email</th><th>Punti</th><th>Consensi</th><th>Ruolo</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['first_name'].' '.$u['last_name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['points']) ?></td>
                        <td>
                            <span class="pill subtle">Dati: <?= $u['data_processing'] ? '✅' : '❌' ?></span>
                            <span class="pill subtle">Prof.: <?= $u['profiling'] ? '✅' : '❌' ?></span>
                            <span class="pill subtle">Mkt: <?= $u['marketing'] ? '✅' : '❌' ?></span>
                        </td>
                        <td><?= e($u['role']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Aggiungi punti</h3>
        <?php if ($message): ?><p class="success"><?= e($message) ?></p><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="add_points" value="1">
            <div class="form-control">
                <label>Cliente
                    <select name="user_id" required>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= e($u['id']) ?>"><?= e($u['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-control"><label>Punti<input type="number" name="points" min="1" value="50" required></label></div>
            <button class="button" type="submit">Aggiungi</button>
        </form>
    </div>
    <div class="card">
        <h3>Crea coupon</h3>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="create_coupon" value="1">
            <div class="form-control"><label>Codice<input name="code" required></label></div>
            <div class="form-control"><label>Descrizione<input name="description" required></label></div>
            <div class="form-control"><label>Sconto %<input type="number" name="discount" min="0" max="100" value="10"></label></div>
            <div class="form-control"><label>Cliente (opzionale)
                <select name="coupon_user">
                    <option value="">Tutti</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= e($u['id']) ?>"><?= e($u['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label></div>
            <div class="form-control"><label>Scadenza<input type="date" name="expires_at"></label></div>
            <button class="button" type="submit">Crea</button>
        </form>
    </div>
    <div class="card">
        <h3>Catalogo (EAN master)</h3>
        <?php if (!$products): ?><p>Nessun prodotto. Aggiorna tramite Odoo/Shopify.</p><?php else: ?>
            <table class="table">
                <thead><tr><th>Nome</th><th>SKU</th><th>EAN</th><th>Prezzo</th><th>Odoo ID</th><th>Shopify ID</th><th>Stato</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= e($p['name']) ?></td>
                            <td><?= e($p['sku']) ?></td>
                            <td><?= e($p['ean']) ?></td>
                            <td>&euro; <?= e($p['price']) ?></td>
                            <td><?= e($p['odoo_product_id']) ?></td>
                            <td><?= e($p['shopify_product_id']) ?></td>
                            <td><?= $p['active'] ? 'Attivo' : 'Non attivo' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<section class="card">
    <h3>Offerte recenti</h3>
    <table class="table">
        <thead><tr><th>Cliente</th><th>Prodotto</th><th>EAN</th><th>Data</th></tr></thead>
        <tbody>
            <?php foreach ($offers as $o): ?>
                <tr><td><?= e($o['email']) ?></td><td><?= e($o['product_name']) ?></td><td><?= e($o['product_ean']) ?></td><td><?= e($o['created_at']) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
