<?php
// CLI/cron entrypoint to sync Shopify products into the loyalty catalog.
// Suggested crontab (ogni ora):
// 0 * * * * /usr/bin/php /path/to/htdocs/loyalty2/scripts/shopify_sync.php >> /var/log/loyalty_shopify_sync.log 2>&1

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/shopify.php';

if (!shopify_enabled()) {
    echo "[shopify-sync] Shopify non configurato: interrompo.\n";
    exit(0);
}

$storageDir = __DIR__ . '/../storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}
$lockPath = $storageDir . '/shopify_sync.lock';
$lock = fopen($lockPath, 'c+');
if (!$lock) {
    echo "[shopify-sync] Impossibile aprire il lock file.\n";
    exit(1);
}

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    echo "[shopify-sync] Sync già in esecuzione, salto.\n";
    exit(0);
}

// Controllo intervallo minimo di 1 ora
rewind($lock);
$existing = stream_get_contents($lock);
$meta = $existing ? json_decode($existing, true) : [];
$lastRun = $meta['last_run'] ?? 0;
if (time() - $lastRun < 3600) {
    echo "[shopify-sync] Ultima esecuzione recente, salto.\n";
    flock($lock, LOCK_UN);
    exit(0);
}

echo "[shopify-sync] Avvio sincronizzazione Shopify...\n";
$result = shopify_sync_products($mysqli);
$meta = [
    'last_run' => time(),
    'status' => $result
];
ftruncate($lock, 0);
rewind($lock);
fwrite($lock, json_encode($meta));
fflush($lock);
flock($lock, LOCK_UN);

if (!empty($result['ok'])) {
    $inserted = $result['inserted'] ?? 0;
    $updated = $result['updated'] ?? 0;
    echo "[shopify-sync] Completato. Inseriti: {$inserted}, Aggiornati: {$updated}\n";
    exit(0);
}

echo "[shopify-sync] Errore: " . ($result['error'] ?? 'sconosciuto') . "\n";
exit(1);
