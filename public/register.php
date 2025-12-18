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
        'tax_code' => trim($_POST['tax_code'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'password' => $_POST['password'] ?? ''
    ];

    foreach ($data as $value) {
        if ($value === '') { $error = 'Compila tutti i campi'; break; }
    }

    if (!$error) {
        $stmt = $mysqli->prepare('INSERT INTO users (first_name, last_name, tax_code, phone, email, postal_code, address, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->bind_param('ssssssss', $data['first_name'], $data['last_name'], $data['tax_code'], $data['phone'], $data['email'], $data['postal_code'], $data['address'], $hash);
        try {
            $stmt->execute();
            $userId = $stmt->insert_id;
            $balance = $mysqli->prepare('INSERT INTO loyalty_balances (user_id, points, level) VALUES (?, 0, "Bronze")');
            $balance->bind_param('i', $userId);
            $balance->execute();
            $success = 'Registrazione completata. Ora puoi accedere.';
        } catch (mysqli_sql_exception $e) {
            $error = 'Email già registrata o dati non validi';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="card">
    <h2>Crea un account</h2>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="success"><?= e($success) ?></p><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="grid">
            <div class="form-control"><label>Nome<input required name="first_name"></label></div>
            <div class="form-control"><label>Cognome<input required name="last_name"></label></div>
            <div class="form-control"><label>Codice fiscale<input required name="tax_code"></label></div>
            <div class="form-control"><label>Telefono<input required name="phone" type="tel"></label></div>
            <div class="form-control"><label>Email<input required name="email" type="email"></label></div>
            <div class="form-control"><label>CAP<input required name="postal_code"></label></div>
            <div class="form-control"><label>Indirizzo<input required name="address"></label></div>
            <div class="form-control"><label>Password<input required name="password" type="password"></label></div>
        </div>
        <button class="button" type="submit">Registrati</button>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
