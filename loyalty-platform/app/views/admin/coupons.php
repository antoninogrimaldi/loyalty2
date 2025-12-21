<?php $title='Coupon'; ob_start(); ?>
<h1>Coupon</h1>
<?php if ($message): ?><div class="alert alert-info"><?php echo $message; ?></div><?php endif; ?>
<div class="row g-3">
  <div class="col-md-4">
    <h3>Crea</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="create" value="1">
      <div class="mb-2"><input class="form-control" name="code" placeholder="CODICE" required></div>
      <div class="mb-2"><input class="form-control" name="description" placeholder="Descrizione"></div>
      <div class="mb-2"><input class="form-control" type="date" name="valid_from"></div>
      <div class="mb-2"><input class="form-control" type="date" name="valid_to"></div>
      <button class="btn btn-primary">Salva</button>
    </form>
  </div>
  <div class="col-md-4">
    <h3>Assegna</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="assign" value="1">
      <div class="mb-2">
        <select class="form-select" name="coupon_id">
          <?php foreach ($coupons as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo $c['code']; ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-2">
        <select class="form-select" name="user_id">
          <?php foreach ($users as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo $u['email']; ?></option><?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-secondary">Assegna</button>
    </form>
  </div>
  <div class="col-md-4">
    <h3>Coupon esistenti</h3>
    <ul class="list-group">
      <?php foreach ($coupons as $c): ?>
        <li class="list-group-item"><?php echo $c['code']; ?> (<?php echo $c['valid_from']; ?> - <?php echo $c['valid_to']; ?>)</li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<h3 class="mt-4">Assegnazioni</h3>
<table class="table table-striped">
  <thead><tr><th>Codice</th><th>User</th><th>Status</th><th>Data</th></tr></thead>
  <tbody>
    <?php foreach ($assignments as $a): ?>
      <tr>
        <td><?php echo $a['code']; ?></td>
        <td><?php echo $a['email']; ?></td>
        <td><?php echo $a['status']; ?></td>
        <td><?php echo $a['created_at']; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/admin.php'; ?>
