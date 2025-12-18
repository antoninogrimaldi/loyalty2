<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$error = null;

if (is_post()) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Token CSRF non valido');
    }
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $mysqli->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password_hash'])) {
        login_user($user);
        header('Location: /public/index.php');
        exit;
    }
    $error = 'Credenziali non valide';
}

include __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Accedi</h2>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="form-control">
            <label for="email">Email</label>
            <input required type="email" id="email" name="email" autocomplete="email">
        </div>
        <div class="form-control">
            <label for="password">Password</label>
            <input required type="password" id="password" name="password" autocomplete="current-password">
        </div>
        <button class="button" type="submit">Login</button>
    </form>
    <div class="social-logins">
        <p class="muted">Oppure accedi con</p>
        <div class="grid">
            <a class="button ghost" href="#" aria-disabled="true">Google (stub)</a>
            <a class="button ghost" href="#" aria-disabled="true">Facebook (stub)</a>
        </div>
        <small>Per OAuth reale, collega le API di Google/Facebook e salva solo i dati minimi necessari.</small>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
