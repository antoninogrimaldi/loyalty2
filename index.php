<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$target = current_user() ? base_url('public/index.php') : base_url('public/login.php');
header('Location: ' . $target);
exit;
