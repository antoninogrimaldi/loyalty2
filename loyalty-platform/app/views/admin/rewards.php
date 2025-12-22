<?php $title='Premi'; ob_start(); ?>
<h1>Premi</h1>
<?php if ($message): ?><div class="alert alert-info"><?php echo $message; ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-md-6">
    <h3>Crea premio</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="create" value="1">
      <div class="mb-2"><input class="form-control" name="name" placeholder="Nome" required></div>
      <div class="mb-2"><textarea class="form-control" name="description" placeholder="Descrizione"></textarea></div>
      <div class="mb-2"><input class="form-control" type="number" name="points_cost" placeholder="Punti" required></div>
      <div class="mb-2"><input class="form-control" type="number" name="stock" placeholder="Stock" required></div>
      <button class="btn btn-primary">Salva</button>
    </form>
  </div>
  <div class="col-md-6">
    <h3>Premi esistenti</h3>
    <ul class="list-group">
      <?php foreach ($rewards as $r): ?>
        <li class="list-group-item d-flex justify-content-between"><span><?php echo $r['name']; ?> (<?php echo $r['points_cost']; ?>)</span><span>Stock: <?php echo $r['stock']; ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<h3 class="mt-4">Redemptions</h3>
<table class="table table-striped">
  <thead><tr><th>ID</th><th>User</th><th>Reward</th><th>Status</th><th>Azione</th></tr></thead>
  <tbody>
    <?php foreach ($redemptions as $red): ?>
      <tr>
        <td><?php echo $red['id']; ?></td>
        <td><?php echo $red['email']; ?></td>
        <td><?php echo $red['reward_id']; ?></td>
        <td><?php echo $red['status']; ?></td>
        <td>
          <?php if ($red['status'] !== 'fulfilled'): ?>
            <form method="post" class="d-inline">
              <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
              <input type="hidden" name="fulfill" value="1">
              <input type="hidden" name="redemption_id" value="<?php echo $red['id']; ?>">
              <button class="btn btn-sm btn-success">Segna come evaso</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/admin.php'; ?>
