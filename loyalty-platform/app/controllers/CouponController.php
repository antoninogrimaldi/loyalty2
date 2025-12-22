<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../db/db.php';

function coupon_list()
{
    require_login();
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT c.*, ca.status AS assignment_status FROM coupon_assignments ca JOIN coupons c ON ca.coupon_id=c.id WHERE ca.user_id=:u');
    $stmt->execute([':u' => current_user()['id']]);
    $coupons = $stmt->fetchAll();
    return ['view' => 'customer/coupons.php', 'data' => compact('coupons')];
}
