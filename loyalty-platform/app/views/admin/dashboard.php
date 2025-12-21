<?php $title='Dashboard'; ob_start(); ?>
<h1>Dashboard</h1>
<div class="row g-3">
  <div class="col-md-3">
    <div class="card"><div class="card-body"><h6>Utenti</h6><div class="display-6"><?php echo $users; ?></div></div></div>
  </div>
  <div class="col-md-3">
    <div class="card"><div class="card-body"><h6>Ordini</h6><div class="display-6"><?php echo $orders; ?></div></div></div>
  </div>
  <div class="col-md-3">
    <div class="card"><div class="card-body"><h6>Punti generati</h6><div class="display-6"><?php echo $points ?: 0; ?></div></div></div>
  </div>
</div>
<h3 class="mt-4">Errori sync recenti</h3>
<ul class="list-group">
  <?php foreach ($errors as $err): ?>
    <li class="list-group-item"><strong><?php echo $err['type']; ?></strong> - <?php echo $err['error_message']; ?> (<?php echo $err['created_at']; ?>)</li>
  <?php endforeach; ?>
</ul>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/admin.php'; ?>
