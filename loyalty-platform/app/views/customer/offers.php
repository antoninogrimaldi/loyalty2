<?php $title='Offerte personalizzate'; ob_start(); ?>
<h1>Seleziona i tuoi prodotti</h1>
<?php if ($message): ?><div class="alert alert-info"><?php echo $message; ?></div><?php endif; ?>
<form method="post" class="row g-3">
  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
  <div class="col-md-6">
    <label class="form-label">Prodotto 1</label>
    <select class="form-select" name="product1" required>
      <?php foreach ($products as $p): ?>
        <option value="<?php echo $p['ean']; ?>" <?php echo ($offer['product1_ean'] ?? '') === $p['ean'] ? 'selected' : ''; ?>><?php echo $p['title']; ?> (<?php echo $p['ean']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Prodotto 2</label>
    <select class="form-select" name="product2" required>
      <?php foreach ($products as $p): ?>
        <option value="<?php echo $p['ean']; ?>" <?php echo ($offer['product2_ean'] ?? '') === $p['ean'] ? 'selected' : ''; ?>><?php echo $p['title']; ?> (<?php echo $p['ean']; ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12">
    <button class="btn btn-primary">Salva</button>
  </div>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
