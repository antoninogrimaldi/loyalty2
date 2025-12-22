<?php
$envFile = __DIR__ . '/env.php';
if (!file_exists($envFile)) {
    die('Config non trovato. Copia app/config/env.php.example in app/config/env.php');
}
$config = require $envFile;

foreach ($config as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

date_default_timezone_set('Europe/Rome');

function app_base_path($path = '') {
    return realpath(__DIR__ . '/..' . ($path ? '/' . ltrim($path, '/') : ''));
}
