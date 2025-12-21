<?php
require_once __DIR__ . '/../app/helpers/points.php';
require_once __DIR__ . '/../app/db/db.php';

$pdo = get_db();
$orders = $pdo->query('SELECT * FROM orders_unified')->fetchAll();
foreach ($orders as $o) {
    adjust_points($o['user_id'], (int)$o['total_amount'] * get_setting('points_per_eur',1), 'earn', 'order', $o['source'] . '-' . $o['source_order_id'], 'Ricalcolo');
}

echo "Ricalcolo punti completato\n";
