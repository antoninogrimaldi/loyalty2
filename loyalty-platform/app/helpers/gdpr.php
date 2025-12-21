<?php
require_once __DIR__ . '/../db/db.php';

function store_gdpr_consents($userId, $marketing, $profiling, $data, $privacyVersion)
{
    $pdo = get_db();
    $stmt = $pdo->prepare('UPDATE customer_profiles SET consent_marketing=:m, consent_profiling=:p, consent_data=:d, consent_ts=NOW(), privacy_version=:v WHERE user_id=:u');
    $stmt->execute([
        ':m' => $marketing ? 1 : 0,
        ':p' => $profiling ? 1 : 0,
        ':d' => $data ? 1 : 0,
        ':v' => $privacyVersion,
        ':u' => $userId
    ]);
}
