<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
$config = app_config();
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
    <div class="brand">Loyalty Hub</div>
    <nav>
        <a href="<?= e(base_url('public/index.php')) ?>">Home</a>
        <a href="<?= e(base_url('public/profile.php')) ?>">Profilo</a>
        <a href="<?= e(base_url('public/offers.php')) ?>">Offerte</a>
        <?php if (current_user() && current_user()['role'] === 'admin'): ?>
            <a href="<?= e(base_url('public/admin.php')) ?>">Backoffice</a>
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
