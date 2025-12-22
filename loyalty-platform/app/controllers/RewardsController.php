<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/points.php';
require_once __DIR__ . '/../db/db.php';

function reward_catalog()
{
    require_login();
    $pdo = get_db();
    $rewards = $pdo->query('SELECT * FROM rewards WHERE active=1')->fetchAll();
    return ['view' => 'customer/rewards.php', 'data' => ['rewards' => $rewards, 'balance' => points_balance(current_user()['id'])]];
}
