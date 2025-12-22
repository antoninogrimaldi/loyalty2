<?php
function app_log($message)
{
    $logFile = __DIR__ . '/../../storage.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}
