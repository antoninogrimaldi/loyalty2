<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/points.php';
require_once __DIR__ . '/../helpers/qr.php';
require_once __DIR__ . '/../db/db.php';

function customer_home()
{
    require_login();
    $user = current_user();
    $pdo = get_db();
    $balance = points_balance($user['id']);
    $orders = $pdo->prepare('SELECT * FROM orders_unified WHERE user_id=:u ORDER BY created_at DESC LIMIT 10');
    $orders->execute([':u' => $user['id']]);
    $offers = $pdo->prepare('SELECT * FROM personalized_offers WHERE user_id=:u LIMIT 1');
    $offers->execute([':u' => $user['id']]);
    $offer = $offers->fetch();
    $coupons = $pdo->prepare('SELECT c.*, ca.status AS assignment_status FROM coupon_assignments ca JOIN coupons c ON ca.coupon_id=c.id WHERE ca.user_id=:u');
    $coupons->execute([':u' => $user['id']]);
    $qr = qr_image_url($user['phone'] ?? $user['id']);
    return ['view' => 'customer/home.php', 'data' => compact('balance','orders','offer','coupons','qr')];
}

function customer_offers()
{
    require_login();
    $pdo = get_db();
    $user = current_user();
    $products = $pdo->query("SELECT * FROM product_cache WHERE status='active' ORDER BY title LIMIT 50")->fetchAll();
    $stmt = $pdo->prepare('SELECT * FROM personalized_offers WHERE user_id=:u');
    $stmt->execute([':u' => $user['id']]);
    $offer = $stmt->fetch();
    $message = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
        $p1 = sanitize($_POST['product1']);
        $p2 = sanitize($_POST['product2']);
        $discount = get_setting('offer_discount_pct', 10);
        if ($offer && $offer['changes_count_year'] >= get_setting('offer_max_changes_year', 2)) {
            $message = 'Hai raggiunto il limite di cambi annuali.';
        } else {
            if ($offer) {
                $pdo->prepare('UPDATE personalized_offers SET product1_ean=:p1, product2_ean=:p2, discount_pct=:d, changes_count_year=changes_count_year+1, last_change_at=NOW() WHERE id=:id')->execute([
                    ':p1'=>$p1, ':p2'=>$p2, ':d'=>$discount, ':id'=>$offer['id']
                ]);
            } else {
                $pdo->prepare('INSERT INTO personalized_offers (user_id, product1_ean, product2_ean, discount_pct, changes_count_year, last_change_at) VALUES (:u,:p1,:p2,:d,1,NOW())')->execute([
                    ':u'=>$user['id'], ':p1'=>$p1, ':p2'=>$p2, ':d'=>$discount
                ]);
            }
            $message = 'Offerta aggiornata. Il codice sconto verrà creato durante la prossima sync.';
        }
    }
    return ['view' => 'customer/offers.php', 'data' => compact('products','offer','message')];
}

function customer_coupons()
{
    require_login();
    $pdo = get_db();
    $user = current_user();
    $stmt = $pdo->prepare('SELECT c.*, ca.status AS assignment_status FROM coupon_assignments ca JOIN coupons c ON ca.coupon_id=c.id WHERE ca.user_id=:u');
    $stmt->execute([':u' => $user['id']]);
    $coupons = $stmt->fetchAll();
    return ['view' => 'customer/coupons.php', 'data' => compact('coupons')];
}

function customer_points()
{
    require_login();
    $user = current_user();
    $pdo = get_db();
    $ledger = $pdo->prepare('SELECT * FROM points_ledger WHERE user_id=:u ORDER BY created_at DESC LIMIT 50');
    $ledger->execute([':u' => $user['id']]);
    $balance = points_balance($user['id']);
    return ['view' => 'customer/points.php', 'data' => compact('ledger','balance')];
}

function customer_rewards()
{
    require_login();
    $pdo = get_db();
    $user = current_user();
    $rewards = $pdo->query('SELECT * FROM rewards WHERE active=1')->fetchAll();
    $balance = points_balance($user['id']);
    $message = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
        $rewardId = (int)$_POST['reward_id'];
        $reward = $pdo->prepare('SELECT * FROM rewards WHERE id=:id');
        $reward->execute([':id'=>$rewardId]);
        $reward = $reward->fetch();
        if ($reward && $reward['points_cost'] <= $balance) {
            $pdo->prepare('INSERT INTO redemptions (user_id, reward_id, points_spent) VALUES (:u,:r,:p)')->execute([
                ':u'=>$user['id'], ':r'=>$rewardId, ':p'=>$reward['points_cost']
            ]);
            adjust_points($user['id'], $reward['points_cost'], 'spend', 'reward', $rewardId, 'Redemption');
            $message = 'Richiesta inviata, un admin la evaderà.';
        } else {
            $message = 'Punti insufficienti.';
        }
    }
    return ['view' => 'customer/rewards.php', 'data' => compact('rewards','balance','message')];
}
