<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$error = null;
$cooldown = null;

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Token CSRF non valido');
    }
    $email = strtolower(sanitize_field($_POST['email'] ?? '', 120));
    $password = $_POST['password'] ?? '';

    if (login_throttled()) {
        $attempts = $_SESSION['login_attempts'] ?? [];
        $oldest = $attempts ? min($attempts) : time();
        $cooldown = ceil((($oldest + 900) - time()) / 60);
        $error = 'Troppi tentativi. Riprova tra circa ' . max(1, $cooldown) . ' minuti.';
    } elseif ($email === '' || $password === '') {
        $error = 'Inserisci email e password';
    } else {
        $stmt = $mysqli->prepare('SELECT * FROM users WHERE LOWER(email) = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $rehashStmt = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $rehashStmt->bind_param('si', $newHash, $user['id']);
                $rehashStmt->execute();
            }
            clear_login_attempts();
            login_user($user);
            header('Location: ' . base_url('public/index.php'));
            exit;
        }
        record_login_attempt();
        usleep(500000);
        $error = 'Credenziali non valide';
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="card card-form">
    <div class="card-header">
        <div>
            <p class="eyebrow">Accesso sicuro</p>
            <h2>Accedi</h2>
            <p class="muted">Protezione CSRF, throttling anti-brute force e hashing forte delle password.</p>
        </div>
        <span class="pill subtle">Stile Laravel</span>
    </div>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($cooldown): ?><p class="muted small">Consiglio: resetta la password se non ricordi le credenziali.</p><?php endif; ?>
    <form method="post" class="stacked">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <div class="form-control">
                <label for="email">Email</label>
                <input required type="email" id="email" name="email" autocomplete="email" placeholder="tuo@email.it">
            </div>
            <div class="form-control">
                <label for="password">Password</label>
                <input required type="password" id="password" name="password" autocomplete="current-password" minlength="10" placeholder="••••••••••">
            </div>
        </div>
        <div class="form-footer">
            <div class="muted small">Accesso consentito solo da modulo ufficiale; ogni tentativo fallito viene registrato.</div>
            <button class="button" type="submit">Login</button>
        </div>
    </form>
    <div class="social-logins soft-card">
        <p class="muted">Oppure accedi con</p>
        <div class="grid two">
            <a class="button ghost" href="#" aria-disabled="true">Google (stub)</a>
            <a class="button ghost" href="#" aria-disabled="true">Facebook (stub)</a>
        </div>
        <small>Per OAuth reale, collega le API di Google/Facebook e salva solo i dati minimi necessari.</small>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
