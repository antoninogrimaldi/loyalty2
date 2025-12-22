<?php $title='Aggiusta punti'; ob_start(); ?>
<h1>Aggiusta punti</h1>
<?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
<form method="post" class="row g-3">
  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
  <div class="col-md-4">
    <label class="form-label">Utente</label>
    <select class="form-select" name="user_id">
      <?php foreach ($users as $u): ?><option value="<?php echo $u['id']; ?>"><?php echo $u['email']; ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Punti (+/-)</label>
    <input class="form-control" name="points" type="number" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Note</label>
    <input class="form-control" name="note">
  </div>
  <div class="col-12">
    <button class="btn btn-primary">Salva</button>
  </div>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/admin.php'; ?>
