<?php
require_once __DIR__ . '/../db/db.php';

function get_setting($key, $default = null)
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = :k');
    $stmt->execute([':k' => $key]);
    $value = $stmt->fetchColumn();
    return $value !== false ? $value : $default;
}

function adjust_points($userId, $points, $type, $referenceType = null, $referenceId = null, $note = null)
{
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO points_ledger (user_id, type, points, reference_type, reference_id, note) VALUES (:u, :t, :p, :rt, :ri, :n)
        ON DUPLICATE KEY UPDATE points = VALUES(points), note = VALUES(note)');
    $stmt->execute([
        ':u' => $userId,
        ':t' => $type,
        ':p' => $points,
        ':rt' => $referenceType,
        ':ri' => $referenceId,
        ':n' => $note
    ]);
}

function points_balance($userId)
{
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT SUM(CASE WHEN type='earn' THEN points WHEN type='refund' THEN points WHEN type='spend' THEN -points ELSE 0 END) FROM points_ledger WHERE user_id=:u");
    $stmt->execute([':u' => $userId]);
    return (int)($stmt->fetchColumn() ?? 0);
}
