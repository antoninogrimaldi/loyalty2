<?php
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function sanitize_field(string $value, int $maxLength): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value));
    return mb_substr($value, 0, $maxLength);
}

function validate_password_strength(string $password): ?string
{
    if (strlen($password) < 10) {
        return 'La password deve avere almeno 10 caratteri.';
    }
    $hasUpper = preg_match('/[A-Z]/', $password);
    $hasLower = preg_match('/[a-z]/', $password);
    $hasDigit = preg_match('/[0-9]/', $password);
    $hasSymbol = preg_match('/[^A-Za-z0-9]/', $password);

    if (!$hasUpper || !$hasLower || !$hasDigit || !$hasSymbol) {
        return 'Usa maiuscole, minuscole, numeri e simboli per una password robusta.';
    }
    return null;
}

function app_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    return $config;
}

function base_url(string $path = ''): string
{
    $base = rtrim(app_config()['app']['base_url'] ?? '', '/');

    if ($path === '') {
        return $base === '' ? '' : $base;
    }

    $prefix = $base === '' ? '' : $base;
    return $prefix . '/' . ltrim($path, '/');
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function normalize_phone(string $phone): ?string
{
    $clean = preg_replace('/[^\d+]/', '', $phone);
    if ($clean === '' || strlen($clean) < 6 || strlen($clean) > 18) {
        return null;
    }
    return $clean;
}

function is_valid_postal_code(string $postal): bool
{
    return preg_match('/^[0-9]{5}$/', $postal) === 1;
}

function is_valid_italian_tax_code(string $taxCode): bool
{
    $taxCode = strtoupper(trim($taxCode));
    if (!preg_match('/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', $taxCode)) {
        return false;
    }

    $oddMap = [
        '0'=>1,'1'=>0,'2'=>5,'3'=>7,'4'=>9,'5'=>13,'6'=>15,'7'=>17,'8'=>19,'9'=>21,
        'A'=>1,'B'=>0,'C'=>5,'D'=>7,'E'=>9,'F'=>13,'G'=>15,'H'=>17,'I'=>19,'J'=>21,
        'K'=>2,'L'=>4,'M'=>18,'N'=>20,'O'=>11,'P'=>3,'Q'=>6,'R'=>8,'S'=>12,'T'=>14,
        'U'=>16,'V'=>10,'W'=>22,'X'=>25,'Y'=>24,'Z'=>23
    ];
    $evenMap = [
        '0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
        'A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6,'H'=>7,'I'=>8,'J'=>9,
        'K'=>10,'L'=>11,'M'=>12,'N'=>13,'O'=>14,'P'=>15,'Q'=>16,'R'=>17,'S'=>18,'T'=>19,
        'U'=>20,'V'=>21,'W'=>22,'X'=>23,'Y'=>24,'Z'=>25
    ];

    $sum = 0;
    for ($i = 0; $i < 15; $i++) {
        $char = $taxCode[$i];
        $sum += ($i % 2 === 0) ? $oddMap[$char] : $evenMap[$char];
    }
    $expected = chr(($sum % 26) + ord('A'));

    return $expected === $taxCode[15];
}
