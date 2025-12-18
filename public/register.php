<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$error = null;
$success = null;

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Token CSRF non valido');
    }
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'tax_code' => strtoupper(trim($_POST['tax_code'] ?? '')),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'password' => $_POST['password'] ?? ''
    ];
    $consents = [
        'data_processing' => isset($_POST['consent_data']) ? 1 : 0,
        'profiling' => isset($_POST['consent_profiling']) ? 1 : 0,
        'marketing' => isset($_POST['consent_marketing']) ? 1 : 0
    ];

    foreach ($data as $value) {
        if ($value === '') { $error = 'Compila tutti i campi'; break; }
    }

    if (!$error && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Email non valida';
    }

    if (!$error && strlen($data['password']) < 8) {
        $error = 'La password deve avere almeno 8 caratteri';
    }

    if (!$error && !is_valid_italian_tax_code($data['tax_code'])) {
        $error = 'Il codice fiscale non è valido';
    }

    if (!$error && !$consents['data_processing']) {
        $error = 'Devi acconsentire al trattamento dei dati per registrarti';
    }

    if (!$error) {
        $existing = $mysqli->prepare('SELECT email, phone, tax_code FROM users WHERE email = ? OR phone = ? OR tax_code = ? LIMIT 1');
        $existing->bind_param('sss', $data['email'], $data['phone'], $data['tax_code']);
        $existing->execute();
        $dup = $existing->get_result()->fetch_assoc();
        if ($dup) {
            if ($dup['email'] === $data['email']) {
                $error = 'Esiste già un account con questa email';
            } elseif ($dup['phone'] === $data['phone']) {
                $error = 'Esiste già un account con questo numero di telefono';
            } else {
                $error = 'Esiste già un account con questo codice fiscale';
            }
        }
    }

    if (!$error) {
        $mysqli->begin_transaction();
        $stmt = $mysqli->prepare('INSERT INTO users (first_name, last_name, tax_code, phone, email, postal_code, address, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->bind_param('ssssssss', $data['first_name'], $data['last_name'], $data['tax_code'], $data['phone'], $data['email'], $data['postal_code'], $data['address'], $hash);
        try {
            $stmt->execute();
            $userId = $stmt->insert_id;

            $balance = $mysqli->prepare('INSERT INTO loyalty_balances (user_id, points, level) VALUES (?, 0, "Bronze")');
            $balance->bind_param('i', $userId);
            $balance->execute();

            $consentStmt = $mysqli->prepare('INSERT INTO user_consents (user_id, data_processing, profiling, marketing) VALUES (?, ?, ?, ?)');
            $consentStmt->bind_param('iiii', $userId, $consents['data_processing'], $consents['profiling'], $consents['marketing']);
            $consentStmt->execute();

            $mysqli->commit();
            $success = 'Registrazione completata. Ora puoi accedere.';
        } catch (mysqli_sql_exception $e) {
            $mysqli->rollback();
            $error = 'Registrazione non riuscita. Riprovare più tardi';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <div class="card-header">
        <div>
            <p class="eyebrow">Onboarding sicuro</p>
            <h2>Crea un account</h2>
            <p class="muted">Password robuste, dati minimi e consensi tracciati per il go-live.</p>
        </div>
        <div class="pill">GDPR ready</div>
    </div>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= e($success) ?></p><?php endif; ?>
    <form method="post" class="stacked">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="grid">
            <div class="form-control"><label>Nome<input required name="first_name" autocomplete="given-name"></label></div>
            <div class="form-control"><label>Cognome<input required name="last_name" autocomplete="family-name"></label></div>
            <div class="form-control"><label>Codice fiscale<input required name="tax_code"></label></div>
            <div class="form-control"><label>Telefono<input required name="phone" type="tel" autocomplete="tel"></label></div>
            <div class="form-control"><label>Email<input required name="email" type="email" autocomplete="email"></label></div>
            <div class="form-control"><label>CAP<input required name="postal_code" autocomplete="postal-code"></label></div>
            <div class="form-control"><label>Indirizzo<input required name="address" autocomplete="street-address"></label></div>
            <div class="form-control"><label>Password<input required name="password" type="password" autocomplete="new-password" minlength="8"></label></div>
        </div>
        <fieldset class="consents">
            <legend>Consensi privacy</legend>
            <label class="checkbox">
                <input type="checkbox" name="consent_data" required>
                <span>Accetto il trattamento dei dati personali per l'erogazione del servizio.</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="consent_profiling">
                <span>Acconsento alla profilazione per suggerimenti personalizzati.</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="consent_marketing">
                <span>Desidero ricevere comunicazioni promozionali.</span>
            </label>
            <p class="muted">Ogni consenso viene salvato con timestamp per audit. Puoi revocare in qualsiasi momento contattando l'amministratore.</p>
        </fieldset>
        <button class="button" type="submit">Registrati</button>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
