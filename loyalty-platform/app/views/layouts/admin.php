<?php if (!isset($title)) { $title = 'Admin'; } ?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($title); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo route_url('admin_dashboard'); ?>">Admin</a>
    <div class="text-white">
      <a class="text-white me-3" href="<?php echo route_url('home'); ?>">Area cliente</a>
      <a class="btn btn-sm btn-outline-light" href="<?php echo route_url('logout'); ?>">Logout</a>
    </div>
  </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <div class="col-3 col-lg-2 bg-light vh-100 p-3">
      <div class="list-group">
        <a class="list-group-item" href="<?php echo route_url('admin_dashboard'); ?>">Dashboard</a>
        <a class="list-group-item" href="<?php echo route_url('admin_users'); ?>">Utenti</a>
        <a class="list-group-item" href="<?php echo route_url('admin_points'); ?>">Punti</a>
        <a class="list-group-item" href="<?php echo route_url('admin_rewards'); ?>">Premi</a>
        <a class="list-group-item" href="<?php echo route_url('admin_coupons'); ?>">Coupon</a>
        <a class="list-group-item" href="<?php echo route_url('admin_offers'); ?>">Offerte</a>
        <a class="list-group-item" href="<?php echo route_url('admin_settings'); ?>">Impostazioni</a>
        <a class="list-group-item" href="<?php echo route_url('admin_sync'); ?>">Sync</a>
      </div>
    </div>
    <div class="col-9 col-lg-10 p-4">
      <?php echo $content; ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
