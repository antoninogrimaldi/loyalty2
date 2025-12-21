<?php
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../db/db.php';

function admin_offer_reset($id)
{
    require_login(true);
    $pdo = get_db();
    $pdo->prepare('UPDATE personalized_offers SET changes_count_year=0 WHERE id=:id')->execute([':id'=>$id]);
    header('Location: ' . route_url('admin_offers'));
    exit;
}
