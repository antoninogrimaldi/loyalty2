<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/logger.php';

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login($adminOnly = false)
{
    $user = current_user();
    if (!$user) {
        header('Location: /loyalty-platform/public/?route=login');
        exit;
    }
    if ($adminOnly && empty($user['is_admin'])) {
        http_response_code(403);
        echo 'Accesso negato';
        exit;
    }
}

function authenticate($identifier, $password)
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email OR username = :username');
    $stmt->execute([':email' => $identifier, ':username' => $identifier]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    $_SESSION['user'] = $user;
    return $user;
}

function logout()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function normalize_phone($phone)
{
    return preg_replace('/[^0-9]/', '', $phone);
}

function validate_tax_code($tax)
{
    return preg_match('/^[A-Z0-9]{11,16}$/', strtoupper($tax));
}
