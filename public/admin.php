<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
if (current_user()['role'] !== 'admin') { http_response_code(403); die('Solo admin'); }

$message = $error = null;

$stats = [
    'customers' => (int) ($mysqli->query("SELECT COUNT(*) AS c FROM users WHERE role='customer'")->fetch_assoc()['c'] ?? 0),
    'activeProducts' => (int) ($mysqli->query("SELECT COUNT(*) AS c FROM products WHERE active=1")->fetch_assoc()['c'] ?? 0),
    'coupons' => (int) ($mysqli->query("SELECT COUNT(*) AS c FROM coupons WHERE expires_at IS NULL OR expires_at >= CURDATE()" )->fetch_assoc()['c'] ?? 0),
    'orders' => (int) ($mysqli->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'] ?? 0),
];

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) { die('Token CSRF non valido'); }

    if (isset($_POST['add_points']) && !$error) {
        $userId = (int) $_POST['user_id'];
        $points = (int) $_POST['points'];
        if ($points <= 0) {
            $error = 'Inserisci un valore di punti positivo';
        } else {
            $stmt = $mysqli->prepare('UPDATE loyalty_balances SET points = points + ? WHERE user_id = ?');
            $stmt->bind_param('ii', $points, $userId);
            $stmt->execute();
            $message = 'Punti aggiornati';
        }
    }

    if (isset($_POST['create_coupon']) && !$error) {
        $code = strtoupper(sanitize_field($_POST['code'] ?? '', 40));
        $desc = sanitize_field($_POST['description'] ?? '', 255);
        $discount = (int) $_POST['discount'];
        $userId = $_POST['coupon_user'] ? (int) $_POST['coupon_user'] : null;
        if ($code === '' || $desc === '') {
            $error = 'Codice e descrizione sono obbligatori';
        } elseif ($discount < 0 || $discount > 100) {
            $error = 'Sconto non valido (0-100)';
        } else {
            $stmt = $mysqli->prepare('INSERT INTO coupons (user_id, code, description, discount_percent, expires_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('issis', $userId, $code, $desc, $discount, $_POST['expires_at']);
            $stmt->execute();
            $message = 'Coupon creato';
        }
    }

    if (isset($_POST['create_product']) && !$error) {
        $name = sanitize_field($_POST['name'] ?? '', 120);
        $sku = sanitize_field($_POST['sku'] ?? '', 60);
        $ean = sanitize_field($_POST['ean'] ?? '', 32);
        $brand = sanitize_field($_POST['brand'] ?? '', 120);
        $category = sanitize_field($_POST['category'] ?? '', 120);
        $price = (float) $_POST['price'];
        $imageUrl = sanitize_field($_POST['image_url'] ?? '', 255);
        $description = sanitize_field($_POST['description'] ?? '', 255);
        $odooId = sanitize_field($_POST['odoo_product_id'] ?? '', 64);
        $shopifyId = sanitize_field($_POST['shopify_product_id'] ?? '', 64);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name === '' || $sku === '' || $ean === '') {
            $error = 'Nome, SKU ed EAN sono obbligatori';
        } elseif ($price < 0) {
            $error = 'Prezzo non valido';
        } else {
            $dupCheck = $mysqli->prepare('SELECT id FROM products WHERE sku = ? OR ean = ? LIMIT 1');
            $dupCheck->bind_param('ss', $sku, $ean);
            $dupCheck->execute();
            if ($dupCheck->get_result()->fetch_assoc()) {
                $error = 'Prodotto già presente (SKU o EAN)';
            }
        }

        if (!$error) {
            $stmt = $mysqli->prepare('INSERT INTO products (name, sku, ean, brand, category, description, image_url, price, active, odoo_product_id, shopify_product_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssssisss', $name, $sku, $ean, $brand, $category, $description, $imageUrl, $price, $active, $odooId, $shopifyId);
            $stmt->execute();
            $message = 'Prodotto aggiunto al catalogo master';
        }
    }

    if (isset($_POST['toggle_product']) && !$error) {
        $productId = (int) $_POST['product_id'];
        $newStatus = (int) $_POST['new_status'];
        $stmt = $mysqli->prepare('UPDATE products SET active = ? WHERE id = ?');
        $stmt->bind_param('ii', $newStatus, $productId);
        $stmt->execute();
        $message = 'Stato prodotto aggiornato';
    }

    if (isset($_POST['update_level']) && !$error) {
        $userId = (int) $_POST['user_id'];
        $level = trim($_POST['level']);
        $points = max(0, (int) $_POST['points']);
        $stmt = $mysqli->prepare('UPDATE loyalty_balances SET level = ?, points = ? WHERE user_id = ?');
        $stmt->bind_param('sii', $level, $points, $userId);
        $stmt->execute();
        $message = 'Livello e punti aggiornati';
    }

    if (isset($_POST['add_order']) && !$error) {
        $userId = (int) $_POST['user_id'];
        $orderNumber = sanitize_field($_POST['order_number'] ?? '', 40);
        $total = (float) $_POST['total'];
        if ($orderNumber === '') {
            $error = 'Inserisci un numero ordine';
        } elseif ($total < 0) {
            $error = 'Totale non valido';
        } else {
            $stmt = $mysqli->prepare('INSERT INTO orders (user_id, order_number, total) VALUES (?, ?, ?)');
            $stmt->bind_param('isd', $userId, $orderNumber, $total);
            $stmt->execute();
            $message = 'Ordine registrato';
        }
    }
}

$users = $mysqli->query('SELECT u.id, u.first_name, u.last_name, u.email, u.role, COALESCE(lb.points,0) as points, COALESCE(uc.data_processing,0) as data_processing, COALESCE(uc.profiling,0) as profiling, COALESCE(uc.marketing,0) as marketing, uc.recorded_at FROM users u LEFT JOIN loyalty_balances lb ON lb.user_id = u.id LEFT JOIN user_consents uc ON uc.user_id = u.id ORDER BY u.created_at DESC')->fetch_all(MYSQLI_ASSOC);
$coupons = $mysqli->query('SELECT code, description, discount_percent, expires_at, is_redeemed FROM coupons ORDER BY expires_at DESC')->fetch_all(MYSQLI_ASSOC);
$offers = $mysqli->query('SELECT u.email, p.product_name, p.product_ean, p.created_at FROM personalized_offers p JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC LIMIT 20')->fetch_all(MYSQLI_ASSOC);
$products = $mysqli->query('SELECT id, name, sku, ean, brand, category, description, image_url, price, active, odoo_product_id, shopify_product_id, created_at FROM products ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
$recentOrders = $mysqli->query('SELECT o.order_number, o.total, o.created_at, u.email FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 20')->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<section class="card highlight">
    <div class="card-header">
        <div>
            <p class="eyebrow">Backoffice</p>
            <h2>Panoramica</h2>
        </div>
        <?php if ($message): ?><p class="success" style="margin:0;"><?= e($message) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error" style="margin:0;"><?= e($error) ?></p><?php endif; ?>
    </div>
    <div class="stat-grid">
        <div class="stat"><h4>Clienti</h4><strong><?= e($stats['customers']) ?></strong></div>
        <div class="stat"><h4>Prodotti attivi</h4><strong><?= e($stats['activeProducts']) ?></strong></div>
        <div class="stat"><h4>Coupon</h4><strong><?= e($stats['coupons']) ?></strong></div>
        <div class="stat"><h4>Ordini</h4><strong><?= e($stats['orders']) ?></strong></div>
    </div>
</section>

<section class="grid">
    <div class="card">
        <div class="card-header">
            <h3>Clienti</h3>
            <input type="search" class="filter-input" placeholder="Filtra clienti" data-filter-target="#users-table tr" data-filter-field="[data-search]"></input>
        </div>
        <table class="table" id="users-table">
            <thead><tr><th>Nome</th><th>Email</th><th>Punti</th><th>Consensi</th><th>Ruolo</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr data-search="<?= e(strtolower($u['first_name'].' '.$u['last_name'].' '.$u['email'])) ?>">
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
        <div class="card-header"><h3>Punti & livelli</h3><span class="pill subtle">Controllo rapido</span></div>
        <form method="post" class="stacked">
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
            <div class="form-control"><label>Punti da aggiungere<input type="number" name="points" min="1" value="50" required></label></div>
            <button class="button" type="submit">Aggiungi punti</button>
        </form>
        <form method="post" class="stacked" style="margin-top:0.5rem;">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="update_level" value="1">
            <div class="form-control">
                <label>Cliente
                    <select name="user_id" required>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= e($u['id']) ?>"><?= e($u['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="form-control"><label>Nuovo livello<input name="level" placeholder="Bronze / Silver / Gold" required></label></div>
            <div class="form-control"><label>Punti totali<input type="number" name="points" min="0" value="0" required></label></div>
            <button class="button ghost" type="submit">Imposta livello</button>
        </form>
    </div>
    <div class="card">
        <div class="card-header"><h3>Crea coupon</h3><span class="pill subtle">Valido per sync</span></div>
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
        <div class="card-header"><h3>Catalogo (EAN master)</h3><input type="search" class="filter-input" placeholder="Cerca prodotto" data-filter-target="#products-table tr" data-filter-field="[data-search]"></div>
        <?php if (!$products): ?><p>Nessun prodotto. Aggiorna tramite Odoo/Shopify.</p><?php else: ?>
            <table class="table" id="products-table">
                <thead><tr><th>Immagine</th><th>Nome</th><th>Brand</th><th>SKU</th><th>EAN</th><th>Categoria</th><th>Prezzo</th><th>Odoo ID</th><th>Shopify ID</th><th>Stato</th><th>Azione</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr data-search="<?= e(strtolower($p['name'].' '.$p['brand'].' '.$p['sku'].' '.$p['ean'])) ?>">
                            <td><?php if ($p['image_url']): ?><img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" class="thumb"><?php endif; ?></td>
                            <td><?= e($p['name']) ?></td>
                            <td><?= e($p['brand'] ?? '') ?></td>
                            <td><?= e($p['sku']) ?></td>
                            <td><?= e($p['ean']) ?></td>
                            <td><?= e($p['category'] ?? '') ?></td>
                            <td>&euro; <?= e($p['price']) ?></td>
                            <td><?= e($p['odoo_product_id']) ?></td>
                            <td><?= e($p['shopify_product_id']) ?></td>
                            <td><?= $p['active'] ? 'Attivo' : 'Non attivo' ?></td>
                            <td>
                                <form method="post" class="table-actions">
                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="toggle_product" value="1">
                                    <input type="hidden" name="product_id" value="<?= e($p['id']) ?>">
                                    <input type="hidden" name="new_status" value="<?= $p['active'] ? 0 : 1 ?>">
                                    <button class="button subtle" type="submit"><?= $p['active'] ? 'Disattiva' : 'Attiva' ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php if (!empty($p['description'])): ?>
                            <tr data-search="<?= e(strtolower($p['description'])) ?>"><td></td><td colspan="10" class="muted small"><?= e($p['description']) ?></td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<section class="grid">
    <div class="card">
        <div class="card-header"><h3>Aggiungi prodotto</h3><span class="pill subtle">EAN unico</span></div>
        <form method="post" class="stacked">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="create_product" value="1">
            <div class="grid">
                <div class="form-control"><label>Nome<input name="name" required></label></div>
                <div class="form-control"><label>Brand<input name="brand"></label></div>
            </div>
            <div class="grid">
                <div class="form-control"><label>SKU<input name="sku" required></label></div>
                <div class="form-control"><label>EAN<input name="ean" required></label></div>
            </div>
            <div class="grid">
                <div class="form-control"><label>Categoria<input name="category"></label></div>
                <div class="form-control"><label>Prezzo<input type="number" step="0.01" name="price" value="0" min="0" required></label></div>
            </div>
            <div class="grid">
                <div class="form-control"><label>Odoo ID<input name="odoo_product_id"></label></div>
                <div class="form-control"><label>Shopify ID<input name="shopify_product_id"></label></div>
            </div>
            <div class="form-control"><label>URL immagine<input name="image_url" placeholder="https://"></label></div>
            <div class="form-control"><label>Descrizione<textarea name="description" rows="3"></textarea></label></div>
            <label class="checkbox"><input type="checkbox" name="active" checked> Prodotto attivo</label>
            <button class="button" type="submit">Salva prodotto</button>
        </form>
    </div>
    <div class="card">
        <div class="card-header"><h3>Ordini recenti</h3><span class="pill subtle">Fino a 20</span></div>
        <?php if (!$recentOrders): ?>
            <p>Nessun ordine.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Ordine</th><th>Cliente</th><th>Totale</th><th>Data</th></tr></thead>
                <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><?= e($o['order_number']) ?></td>
                            <td><?= e($o['email']) ?></td>
                            <td>&euro; <?= e($o['total']) ?></td>
                            <td><?= e($o['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <form method="post" class="stacked" style="margin-top:1rem;">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="add_order" value="1">
            <div class="form-control"><label>Cliente
                <select name="user_id" required>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= e($u['id']) ?>"><?= e($u['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label></div>
            <div class="grid">
                <div class="form-control"><label>Numero ordine<input name="order_number" required></label></div>
                <div class="form-control"><label>Totale<input type="number" step="0.01" name="total" min="0" value="0" required></label></div>
            </div>
            <button class="button ghost" type="submit">Registra ordine</button>
        </form>
    </div>
    <div class="card">
        <div class="card-header"><h3>Offerte recenti</h3><span class="pill subtle">Ultime 20</span></div>
        <table class="table">
            <thead><tr><th>Cliente</th><th>Prodotto</th><th>EAN</th><th>Data</th></tr></thead>
            <tbody>
                <?php foreach ($offers as $o): ?>
                    <tr><td><?= e($o['email']) ?></td><td><?= e($o['product_name']) ?></td><td><?= e($o['product_ean']) ?></td><td><?= e($o['created_at']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
