<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();

$balanceStmt = $mysqli->prepare('SELECT points, level FROM loyalty_balances WHERE user_id = ?');
$balanceStmt->bind_param('i', $user['id']);
$balanceStmt->execute();
$balance = $balanceStmt->get_result()->fetch_assoc();

$couponStmt = $mysqli->prepare('SELECT code, description, discount_percent, expires_at, is_redeemed FROM coupons WHERE (user_id = ? OR user_id IS NULL) AND (expires_at IS NULL OR expires_at >= CURDATE())');
$couponStmt->bind_param('i', $user['id']);
$couponStmt->execute();
$coupons = $couponStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$offersStmt = $mysqli->prepare('SELECT po.product_name, po.product_ean, po.note, po.created_at, p.image_url FROM personalized_offers po LEFT JOIN products p ON p.ean = po.product_ean WHERE po.user_id = ? ORDER BY po.created_at DESC');
$offersStmt->bind_param('i', $user['id']);
$offersStmt->execute();
$offers = $offersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$orderStmt = $mysqli->prepare('SELECT order_number, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$orderStmt->bind_param('i', $user['id']);
$orderStmt->execute();
$orders = $orderStmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>
<section class="grid">
    <div class="card highlight">
        <h3>Carta fedeltà</h3>
        <p class="muted">Saldo punti</p>
        <div class="badge large"><?= e($balance['points'] ?? 0) ?> pt</div>
        <p>Livello: <strong><?= e($balance['level'] ?? 'Bronze') ?></strong></p>
    </div>

    <div class="card">
        <h3>Coupon</h3>
        <?php if (!$coupons): ?>
            <p>Nessun coupon attivo.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($coupons as $coupon): ?>
                    <li><strong><?= e($coupon['code']) ?></strong> · <?= e($coupon['description']) ?> (<?= e($coupon['discount_percent']) ?>%)
                        <?php if ($coupon['expires_at']): ?>
                            <span class="muted">Scade: <?= e($coupon['expires_at']) ?></span>
                        <?php endif; ?>
                        <?php if ($coupon['is_redeemed']): ?><span class="pill subtle">Usato</span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Offerte personalizzate</h3>
        <?php if (!$offers): ?>
            <p>Non hai offerte selezionate.</p>
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
        <a class="button ghost" href="<?= e(base_url('public/offers.php')) ?>">Gestisci offerte</a>
    </div>

    <div class="card">
        <h3>Ultimi ordini</h3>
        <?php if (!$orders): ?>
            <p>Nessun ordine registrato.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>#</th><th>Totale</th><th>Data</th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= e($order['order_number']) ?></td>
                            <td>&euro; <?= e($order['total']) ?></td>
                            <td><?= e($order['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
