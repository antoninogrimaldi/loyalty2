<?php
$config = require __DIR__ . '/../config/config.php';

$mysqli = new mysqli(
    $config['db']['host'],
    $config['db']['user'],
    $config['db']['password'],
    $config['db']['name']
);

if ($mysqli->connect_errno) {
    http_response_code(500);
    die('Database connection error. Please retry later.');
}

$mysqli->set_charset('utf8mb4');
