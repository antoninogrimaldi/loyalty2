<?php $title='Impostazioni'; ob_start(); ?>
<h1>Impostazioni</h1>
<?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
<form method="post" class="row g-3">
  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
  <?php foreach ($settings as $s): ?>
    <div class="col-md-4">
      <label class="form-label"><?php echo $s['key']; ?></label>
      <input class="form-control" name="<?php echo $s['key']; ?>" value="<?php echo $s['value']; ?>">
    </div>
  <?php endforeach; ?>
  <div class="col-12"><button class="btn btn-primary">Salva</button></div>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/admin.php'; ?>
