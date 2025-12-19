<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();

if (empty($user['tax_code'])) {
    $userStmt = $mysqli->prepare('SELECT tax_code FROM users WHERE id = ?');
    $userStmt->bind_param('i', $user['id']);
    $userStmt->execute();
    $fresh = $userStmt->get_result()->fetch_assoc();
    if ($fresh) {
        $user['tax_code'] = $fresh['tax_code'];
        $_SESSION['user']['tax_code'] = $fresh['tax_code'];
    }
}

$balanceStmt = $mysqli->prepare('SELECT points, level FROM loyalty_balances WHERE user_id = ?');
$balanceStmt->bind_param('i', $user['id']);
$balanceStmt->execute();
$balance = $balanceStmt->get_result()->fetch_assoc();

$couponStmt = $mysqli->prepare('SELECT code, description, discount_percent, expires_at, is_redeemed FROM coupons WHERE (user_id = ? OR user_id IS NULL) AND (expires_at IS NULL OR expires_at >= CURDATE())');
$couponStmt->bind_param('i', $user['id']);
$couponStmt->execute();
$coupons = $couponStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$shopifyLink = null;
$shopifyCfg = app_config()['shopify'] ?? [];
if (!empty($shopifyCfg['domain'])) {
    $shopBase = 'https://' . $shopifyCfg['domain'];
    $opCoupon = null;
    foreach ($coupons as $c) {
        if (strpos($c['code'], 'OP-') === 0) { $opCoupon = $c; break; }
    }
    if ($opCoupon) {
        $shopifyLink = $shopBase . '/discount/' . urlencode($opCoupon['code']);
    } else {
        $shopifyLink = $shopBase;
    }
}

$offersStmt = $mysqli->prepare('SELECT po.product_name, po.product_ean, po.note, po.created_at, p.image_url FROM personalized_offers po LEFT JOIN products p ON p.ean = po.product_ean WHERE po.user_id = ? ORDER BY po.created_at DESC');
$offersStmt->bind_param('i', $user['id']);
$offersStmt->execute();
$offers = $offersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($user['tax_code']);

$orderStmt = $mysqli->prepare('SELECT order_number, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$orderStmt->bind_param('i', $user['id']);
$orderStmt->execute();
$orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<section class="card highlight">
    <div class="top-hero">
        <div>
            <p class="eyebrow">Benvenuto</p>
            <h1 style="margin: 0 0 0.35rem;"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h1>
            <p class="tagline">Carta digitale pronta al check-out. Sincronizzata con Shopify e Odoo tramite EAN.</p>
            <div class="hero-actions">
                <a class="button small" href="<?= e(base_url('public/offers.php')) ?>">Gestisci offerte</a>
                <a class="button ghost small" href="<?= e(base_url('public/profile.php')) ?>">Aggiorna profilo</a>
            </div>
            <div class="stat-grid">
                <div class="stat"><h4>Punti</h4><strong><?= e($balance['points'] ?? 0) ?></strong></div>
                <div class="stat"><h4>Livello</h4><strong><?= e($balance['level'] ?? 'Bronze') ?></strong></div>
                <div class="stat"><h4>Coupon attivi</h4><strong><?= count($coupons) ?></strong></div>
                <div class="stat"><h4>Offerte salvate</h4><strong><?= count($offers) ?></strong></div>
            </div>
        </div>
        <div class="qr-box">
            <img src="<?= e($qrUrl) ?>" alt="QR code carta" loading="lazy">
            <div>
                <p class="muted" style="margin:0;">Mostra questo QR in cassa per identificarti rapidamente.</p>
                <p class="small" style="margin:0.35rem 0 0;">Codice fiscale: <strong><?= e($user['tax_code']) ?></strong></p>
            </div>
        </div>
    </div>
</section>

<section class="grid">
    <div class="card">
        <div class="card-header">
            <div>
                <p class="eyebrow">Vantaggi</p>
                <h3 style="margin:0;">Coupon</h3>
            </div>
            <div class="hero-actions">
                <span class="pill subtle">Aggiornati in tempo reale</span>
                <?php if ($shopifyLink): ?>
                    <a class="button ghost small" href="<?= e($shopifyLink) ?>" target="_blank" rel="noopener">Apri su Shopify</a>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!$coupons): ?>
            <p class="muted">Nessun coupon attivo.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($coupons as $coupon): ?>
                    <li>
                        <div class="top-actions">
                            <div>
                                <strong><?= e($coupon['code']) ?></strong> · <?= e($coupon['description']) ?> (<?= e($coupon['discount_percent']) ?>%)
                                <?php if ($coupon['expires_at']): ?>
                                    <div class="muted small">Scade: <?= e($coupon['expires_at']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if ($coupon['is_redeemed']): ?><span class="pill subtle">Usato</span><?php else: ?><span class="pill">Disponibile</span><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <p class="eyebrow">Scelte 1:1</p>
                <h3 style="margin:0;">Offerte personalizzate</h3>
            </div>
            <a class="button ghost" href="<?= e(base_url('public/offers.php')) ?>">Gestisci</a>
        </div>
        <?php if (!$offers): ?>
            <p class="muted">Non hai offerte selezionate.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($offers as $offer): ?>
                    <li class="offer-row">
                        <?php if (!empty($offer['image_url'])): ?><span class="offer-thumb" style="background-image:url('<?= e($offer['image_url']) ?>');"></span><?php endif; ?>
                        <div>
                            <strong><?= e($offer['product_name']) ?></strong> <span class="muted">EAN <?= e($offer['product_ean']) ?></span>
                            <?php if (!empty($offer['note'])): ?><div class="muted small"><?= e($offer['note']) ?></div><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <p class="eyebrow">Storico</p>
                <h3 style="margin:0;">Ultimi ordini</h3>
            </div>
            <span class="pill subtle">Sincronizzati</span>
        </div>
        <?php if (!$orders): ?>
            <p class="muted">Nessun ordine registrato.</p>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach ($orders as $order): ?>
                    <li>
                        <strong><?= e($order['order_number']) ?></strong> · &euro; <?= e($order['total']) ?>
                        <div class="muted small"><?= e($order['created_at']) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
