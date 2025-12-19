<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$error = null;
$success = null;
$data = [];

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Token CSRF non valido');
    }
    $data = [
        'first_name' => sanitize_field($_POST['first_name'] ?? '', 80),
        'last_name' => sanitize_field($_POST['last_name'] ?? '', 80),
        'tax_code' => strtoupper(sanitize_field($_POST['tax_code'] ?? '', 32)),
        'phone' => sanitize_field($_POST['phone'] ?? '', 32),
        'email' => strtolower(sanitize_field($_POST['email'] ?? '', 120)),
        'postal_code' => sanitize_field($_POST['postal_code'] ?? '', 12),
        'address' => sanitize_field($_POST['address'] ?? '', 180),
        'password' => $_POST['password'] ?? '',
        'password_confirmation' => $_POST['password_confirmation'] ?? ''
    ];
    $consents = [
        'data_processing' => isset($_POST['consent_data']) ? 1 : 0,
        'profiling' => isset($_POST['consent_profiling']) ? 1 : 0,
        'marketing' => isset($_POST['consent_marketing']) ? 1 : 0
    ];

    foreach (['first_name','last_name','tax_code','phone','email','postal_code','address','password'] as $field) {
        if ($data[$field] === '') { $error = 'Compila tutti i campi obbligatori'; break; }
    }

    if (!$error && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Email non valida';
    }

    $phoneNormalized = normalize_phone($data['phone']);
    if (!$error && !$phoneNormalized) {
        $error = 'Telefono non valido. Usa prefisso e solo numeri';
    }

    if (!$error && !is_valid_postal_code($data['postal_code'])) {
        $error = 'Inserisci un CAP italiano a 5 cifre';
    }

    if (!$error) {
        $pwdError = validate_password_strength($data['password']);
        if ($pwdError) { $error = $pwdError; }
    }

    if (!$error && $data['password'] !== $data['password_confirmation']) {
        $error = 'Le password non coincidono';
    }

    if (!$error && !is_valid_italian_tax_code($data['tax_code'])) {
        $error = 'Il codice fiscale non è valido';
    }

    if (!$error && !$consents['data_processing']) {
        $error = 'Devi acconsentire al trattamento dei dati per registrarti';
    }

    if (!$error) {
        $existing = $mysqli->prepare('SELECT email, phone, tax_code FROM users WHERE email = ? OR phone = ? OR tax_code = ? LIMIT 1');
        $existing->bind_param('sss', $data['email'], $phoneNormalized, $data['tax_code']);
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
        $stmt->bind_param('ssssssss', $data['first_name'], $data['last_name'], $data['tax_code'], $phoneNormalized, $data['email'], $data['postal_code'], $data['address'], $hash);
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
<section class="card card-form">
    <div class="card-header">
        <div>
            <p class="eyebrow">Onboarding sicuro</p>
            <h2>Crea un account</h2>
            <p class="muted">Validazione CF, CAP italiano e password robuste in stile Laravel.</p>
        </div>
        <div class="pill">GDPR ready</div>
    </div>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= e($success) ?></p><?php endif; ?>
    <form method="post" class="stacked">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <div class="form-control"><label>Nome<input required name="first_name" autocomplete="given-name" value="<?= e($data['first_name'] ?? '') ?>"></label></div>
            <div class="form-control"><label>Cognome<input required name="last_name" autocomplete="family-name" value="<?= e($data['last_name'] ?? '') ?>"></label></div>
            <div class="form-control"><label>Codice fiscale<input required name="tax_code" value="<?= e($data['tax_code'] ?? '') ?>"></label></div>
            <div class="form-control"><label>Telefono<input required name="phone" type="tel" autocomplete="tel" value="<?= e($data['phone'] ?? '') ?>"></label></div>
            <div class="form-control"><label>Email<input required name="email" type="email" autocomplete="email" value="<?= e($data['email'] ?? '') ?>"></label></div>
            <div class="form-control"><label>CAP<input required name="postal_code" autocomplete="postal-code" value="<?= e($data['postal_code'] ?? '') ?>"></label></div>
            <div class="form-control"><label>Indirizzo<input required name="address" autocomplete="street-address" value="<?= e($data['address'] ?? '') ?>"></label></div>
            <div class="form-control"><label>Password<input required name="password" type="password" autocomplete="new-password" minlength="10" placeholder="Almeno 10 caratteri"></label></div>
            <div class="form-control"><label>Conferma password<input required name="password_confirmation" type="password" autocomplete="new-password" minlength="10" placeholder="Ripeti password"></label></div>
        </div>
        <fieldset class="consents">
            <legend>Consensi privacy</legend>
            <label class="checkbox">
                <input type="checkbox" name="consent_data" required <?= isset($_POST['consent_data']) ? 'checked' : '' ?>>
                <span>Accetto il trattamento dei dati personali per l'erogazione del servizio.</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="consent_profiling" <?= isset($_POST['consent_profiling']) ? 'checked' : '' ?>>
                <span>Acconsento alla profilazione per suggerimenti personalizzati.</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="consent_marketing" <?= isset($_POST['consent_marketing']) ? 'checked' : '' ?>>
                <span>Desidero ricevere comunicazioni promozionali.</span>
            </label>
            <p class="muted">Ogni consenso viene salvato con timestamp per audit. Puoi revocare in qualsiasi momento contattando l'amministratore.</p>
        </fieldset>
        <div class="form-footer">
            <div class="muted small">Duplicati su email/telefono/CF sono bloccati. Password sempre hashate; abilita la cifratura a riposo del database in produzione.</div>
            <button class="button" type="submit">Registrati</button>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
