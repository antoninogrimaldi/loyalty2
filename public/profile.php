<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();
$success = $error = null;

$stmt = $mysqli->prepare('SELECT first_name, last_name, tax_code, phone, email, postal_code, address FROM users WHERE id = ?');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

$consentStmt = $mysqli->prepare('SELECT data_processing, profiling, marketing, recorded_at FROM user_consents WHERE user_id = ?');
$consentStmt->bind_param('i', $user['id']);
$consentStmt->execute();
$consents = $consentStmt->get_result()->fetch_assoc();

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) { die('Token CSRF non valido'); }
    $fields = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'address' => trim($_POST['address'] ?? '')
    ];
    foreach ($fields as $value) {
        if ($value === '') { $error = 'Completa tutti i campi'; break; }
    }
    if (!$error) {
        $update = $mysqli->prepare('UPDATE users SET first_name=?, last_name=?, phone=?, postal_code=?, address=? WHERE id=?');
        $update->bind_param('sssssi', $fields['first_name'], $fields['last_name'], $fields['phone'], $fields['postal_code'], $fields['address'], $user['id']);
        $update->execute();
        $success = 'Profilo aggiornato';
        $profile = array_merge($profile, $fields);
        $_SESSION['user']['first_name'] = $fields['first_name'];
        $_SESSION['user']['last_name'] = $fields['last_name'];
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <div class="card-header">
        <div>
            <p class="eyebrow">Profilo</p>
            <h2>I tuoi dati</h2>
            <p class="muted">Gestisci recapiti e indirizzo. Per variazioni privacy contatta l'amministratore.</p>
        </div>
    </div>
    <?php if ($success): ?><p class="success"><?= e($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="stacked">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="grid">
            <div class="form-control"><label>Nome<input name="first_name" value="<?= e($profile['first_name']) ?>"></label></div>
            <div class="form-control"><label>Cognome<input name="last_name" value="<?= e($profile['last_name']) ?>"></label></div>
            <div class="form-control"><label>Telefono<input name="phone" value="<?= e($profile['phone']) ?>"></label></div>
            <div class="form-control"><label>CAP<input name="postal_code" value="<?= e($profile['postal_code']) ?>"></label></div>
            <div class="form-control"><label>Indirizzo<input name="address" value="<?= e($profile['address']) ?>"></label></div>
        </div>
        <p class="muted">Per GDPR, puoi chiedere esportazione o cancellazione scrivendo all'amministratore.</p>
        <button class="button" type="submit">Salva</button>
    </form>
    <?php if ($consents): ?>
        <div class="consent-summary">
            <h3>Consensi registrati</h3>
            <ul class="list">
                <li>Trattamento dati: <?= $consents['data_processing'] ? '✅ attivo' : '❌ non attivo' ?></li>
                <li>Profilazione: <?= $consents['profiling'] ? '✅' : '❌' ?></li>
                <li>Marketing: <?= $consents['marketing'] ? '✅' : '❌' ?></li>
            </ul>
            <p class="muted">Ultimo aggiornamento: <?= e($consents['recorded_at']) ?></p>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
