<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/points.php';
require_once __DIR__ . '/../db/db.php';

function admin_dashboard()
{
    require_login(true);
    $pdo = get_db();
    $users = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $orders = $pdo->query('SELECT COUNT(*) FROM orders_unified')->fetchColumn();
    $points = $pdo->query('SELECT SUM(points) FROM points_ledger WHERE type="earn"')->fetchColumn();
    $errors = $pdo->query('SELECT * FROM sync_errors ORDER BY created_at DESC LIMIT 5')->fetchAll();
    return ['view' => 'admin/dashboard.php', 'data' => compact('users','orders','points','errors')];
}

function admin_users()
{
    require_login(true);
    $pdo = get_db();
    $list = $pdo->query('SELECT u.*, cp.first_name, cp.last_name FROM users u JOIN customer_profiles cp ON u.id=cp.user_id ORDER BY u.id DESC')->fetchAll();
    return ['view' => 'admin/users.php', 'data' => ['users' => $list]];
}

function admin_points_adjust()
{
    require_login(true);
    $pdo = get_db();
    $message = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
        $userId = (int)$_POST['user_id'];
        $points = (int)$_POST['points'];
        $note = sanitize($_POST['note'] ?? '');
        adjust_points($userId, abs($points), $points >=0 ? 'earn':'refund', 'manual', null, $note);
        $message = 'Punti aggiornati';
    }
    $users = $pdo->query('SELECT id, email FROM users')->fetchAll();
    return ['view' => 'admin/points_adjust.php', 'data' => compact('users','message')];
}

function admin_rewards()
{
    require_login(true);
    $pdo = get_db();
    $message = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
        if (isset($_POST['create'])) {
            $pdo->prepare('INSERT INTO rewards (name, description, points_cost, stock) VALUES (:n,:d,:p,:s)')->execute([
                ':n'=>sanitize($_POST['name']),':d'=>sanitize($_POST['description']),':p'=>$_POST['points_cost'],':s'=>$_POST['stock']
            ]);
        }
        if (isset($_POST['fulfill'])) {
            $pdo->prepare('UPDATE redemptions SET status="fulfilled", fulfilled_at=NOW() WHERE id=:id')->execute([':id'=>(int)$_POST['redemption_id']]);
        }
        $message = 'Aggiornato.';
    }
    $rewards = $pdo->query('SELECT * FROM rewards')->fetchAll();
    $redemptions = $pdo->query('SELECT r.*, u.email FROM redemptions r JOIN users u ON r.user_id=u.id ORDER BY r.created_at DESC LIMIT 20')->fetchAll();
    return ['view' => 'admin/rewards.php', 'data' => compact('rewards','redemptions','message')];
}

function admin_coupons()
{
    require_login(true);
    $pdo = get_db();
    $message = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
        if (isset($_POST['create'])) {
            $pdo->prepare('INSERT INTO coupons (code, description, valid_from, valid_to) VALUES (:c,:d,:vf,:vt)')->execute([
                ':c'=>sanitize($_POST['code']), ':d'=>sanitize($_POST['description']), ':vf'=>$_POST['valid_from'] ?: null, ':vt'=>$_POST['valid_to'] ?: null
            ]);
        }
        if (isset($_POST['assign'])) {
            $pdo->prepare('INSERT INTO coupon_assignments (coupon_id, user_id) VALUES (:c,:u)')->execute([
                ':c'=>(int)$_POST['coupon_id'], ':u'=>(int)$_POST['user_id']
            ]);
        }
        $message = 'Aggiornato';
    }
    $coupons = $pdo->query('SELECT * FROM coupons')->fetchAll();
    $users = $pdo->query('SELECT id, email FROM users')->fetchAll();
    $assignments = $pdo->query('SELECT ca.*, c.code, u.email FROM coupon_assignments ca JOIN coupons c ON ca.coupon_id=c.id JOIN users u ON ca.user_id=u.id ORDER BY ca.created_at DESC')->fetchAll();
    return ['view' => 'admin/coupons.php', 'data' => compact('coupons','users','assignments','message')];
}

function admin_offers()
{
    require_login(true);
    $pdo = get_db();
    $offers = $pdo->query('SELECT po.*, u.email FROM personalized_offers po JOIN users u ON po.user_id=u.id ORDER BY po.created_at DESC')->fetchAll();
    return ['view' => 'admin/offers.php', 'data' => compact('offers')];
}

function admin_settings()
{
    require_login(true);
    $pdo = get_db();
    $message = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
        foreach ($_POST as $k=>$v) {
            if ($k === 'csrf_token') continue;
            $stmt = $pdo->prepare('REPLACE INTO settings (`key`,`value`) VALUES (:k,:v)');
            $stmt->execute([':k'=>$k, ':v'=>sanitize($v)]);
        }
        $message = 'Impostazioni salvate';
    }
    $settings = $pdo->query('SELECT * FROM settings')->fetchAll();
    return ['view' => 'admin/settings.php', 'data' => compact('settings','message')];
}

function admin_sync()
{
    require_login(true);
    $output = null;
    if (isset($_GET['run'])) {
        $output = shell_exec('php ' . __DIR__ . '/../../scripts/run_sync.php');
    }
    return ['view' => 'admin/sync.php', 'data' => compact('output')];
}
