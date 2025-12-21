<?php
require_once __DIR__ . '/../app/helpers/logger.php';

$steps = ['sync_products.php','sync_orders.php','sync_returns.php','recalc_points.php'];
foreach ($steps as $step) {
    echo "Eseguo $step...\n";
    app_log("Run sync: $step");
    include __DIR__ . '/' . $step;
}
