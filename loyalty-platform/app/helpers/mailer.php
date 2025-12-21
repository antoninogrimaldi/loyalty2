<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/logger.php';

function send_mail($to, $subject, $html)
{
    // Implementazione semplice via mail() per XAMPP; per produzione usare PHPMailer
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= 'From: ' . SMTP_FROM . "\r\n";
    $success = mail($to, $subject, $html, $headers);
    if (!$success) {
        app_log('Invio email fallito verso ' . $to);
    }
    return $success;
}
