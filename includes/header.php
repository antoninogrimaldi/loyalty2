<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$config = app_config();
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Piattaforma loyalty pronta per integrazione con Shopify e Odoo.">
    <link rel="stylesheet" href="<?= e(base_url('assets/css/style.css')) ?>">
    <script defer src="<?= e(base_url('assets/js/app.js')) ?>"></script>
    <title><?= e($config['app']['name']) ?></title>
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(base_url('public/index.php')) ?>">Loyalty Hub</a>
    <nav>
        <a class="<?= $current === 'index.php' ? 'active' : '' ?>" href="<?= e(base_url('public/index.php')) ?>">Home</a>
        <a class="<?= $current === 'profile.php' ? 'active' : '' ?>" href="<?= e(base_url('public/profile.php')) ?>">Profilo</a>
        <a class="<?= $current === 'offers.php' ? 'active' : '' ?>" href="<?= e(base_url('public/offers.php')) ?>">Offerte</a>
        <?php if (current_user() && current_user()['role'] === 'admin'): ?>
            <a class="<?= $current === 'admin.php' ? 'active' : '' ?>" href="<?= e(base_url('public/admin.php')) ?>">Backoffice</a>
        <?php endif; ?>
    </nav>
    <div class="auth">
        <?php if (current_user()): ?>
            <span class="welcome">Ciao, <?= e(current_user()['first_name']); ?></span>
            <a class="button ghost" href="<?= e(base_url('public/logout.php')) ?>">Logout</a>
        <?php else: ?>
            <a class="button ghost" href="<?= e(base_url('public/login.php')) ?>">Login</a>
            <a class="button" href="<?= e(base_url('public/register.php')) ?>">Registrati</a>
        <?php endif; ?>
    </div>
</header>
<main class="container">
