<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/../helpers/mailer.php';
require_once __DIR__ . '/../helpers/gdpr.php';
require_once __DIR__ . '/../helpers/logger.php';
require_once __DIR__ . '/../db/db.php';

function handle_login()
{
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            $errors[] = 'CSRF token non valido';
        } else {
            $identifier = sanitize($_POST['identifier'] ?? '');
            $password = $_POST['password'] ?? '';
            $user = authenticate($identifier, $password);
            if ($user) {
                header('Location: ' . route_url('home'));
                exit;
            }
            $errors[] = 'Credenziali errate';
        }
    }
    return ['view' => 'auth/login.php', 'data' => ['errors' => $errors]];
}

function handle_register()
{
    $errors = [];
    $success = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            $errors[] = 'CSRF token non valido';
        } else {
            $pdo = get_db();
            $email = strtolower(sanitize($_POST['email'] ?? ''));
            $username = strtolower(sanitize($_POST['username'] ?? ''));
            $phone = normalize_phone($_POST['phone'] ?? '');
            $tax = strtoupper(sanitize($_POST['tax_code'] ?? ''));
            if (!validate_tax_code($tax)) {
                $errors[] = 'Codice Fiscale non valido';
            }
            $required = ['first_name','last_name','address','city','zip','password'];
            if (!require_fields($_POST, $required)) {
                $errors[] = 'Compila tutti i campi obbligatori';
            }
            if (!$errors) {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('INSERT INTO users (email, username, phone, password_hash, status, created_at) VALUES (:e, :u, :p, :ph, "pending", NOW())');
                    $stmt->execute([
                        ':e' => $email,
                        ':u' => $username,
                        ':p' => $phone,
                        ':ph' => password_hash($_POST['password'], PASSWORD_DEFAULT)
                    ]);
                    $userId = $pdo->lastInsertId();
                    $stmt2 = $pdo->prepare('INSERT INTO customer_profiles (user_id, first_name, last_name, tax_code, address, city, zip, country, consent_marketing, consent_profiling, consent_data, consent_ts, privacy_version) VALUES (:id,:fn,:ln,:tax,:addr,:city,:zip,:country,:m,:p,:d,NOW(),:ver)');
                    $stmt2->execute([
                        ':id' => $userId,
                        ':fn' => sanitize($_POST['first_name']),
                        ':ln' => sanitize($_POST['last_name']),
                        ':tax' => $tax,
                        ':addr' => sanitize($_POST['address']),
                        ':city' => sanitize($_POST['city']),
                        ':zip' => sanitize($_POST['zip']),
                        ':country' => sanitize($_POST['country'] ?? 'Italia'),
                        ':m' => isset($_POST['consent_marketing']) ? 1 : 0,
                        ':p' => isset($_POST['consent_profiling']) ? 1 : 0,
                        ':d' => isset($_POST['consent_data']) ? 1 : 0,
                        ':ver' => 'v1'
                    ]);
                    $token = bin2hex(random_bytes(16));
                    $stmt3 = $pdo->prepare('INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (:u, :t, DATE_ADD(NOW(), INTERVAL 1 DAY))');
                    $stmt3->execute([':u' => $userId, ':t' => password_hash($token, PASSWORD_DEFAULT)]);
                    $link = route_url('verify', ['token' => $token, 'uid' => $userId]);
                    send_mail($email, 'Verifica la tua email', '<p>Clicca per verificare: <a href="'.$link.'">'.$link.'</a></p>');
                    $pdo->commit();
                    $success = true;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = 'Errore registrazione: ' . $e->getMessage();
                }
            }
        }
    }
    return ['view' => 'auth/register.php', 'data' => ['errors' => $errors, 'success' => $success]];
}

function handle_verify()
{
    $pdo = get_db();
    $token = $_GET['token'] ?? '';
    $uid = $_GET['uid'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM email_verification_tokens WHERE user_id=:u ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([':u' => $uid]);
    $row = $stmt->fetch();
    if ($row && password_verify($token, $row['token_hash']) && strtotime($row['expires_at']) > time()) {
        $pdo->prepare('UPDATE users SET status="active", email_verified_at=NOW() WHERE id=:u')->execute([':u' => $uid]);
        $message = 'Email verificata! Ora puoi accedere.';
    } else {
        $message = 'Token non valido o scaduto.';
    }
    return ['view' => 'auth/verify.php', 'data' => ['message' => $message]];
}

function handle_logout()
{
    logout();
    header('Location: ' . route_url('login'));
    exit;
}
