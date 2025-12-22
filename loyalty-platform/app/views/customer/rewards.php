<?php $title='Premi'; ob_start(); ?>
<h1>Premi</h1>
<p>Saldo punti: <?php echo $balance; ?></p>
<?php if (!empty($message)): ?><div class="alert alert-info"><?php echo $message; ?></div><?php endif; ?>
<div class="row g-3">
  <?php foreach ($rewards as $reward): ?>
    <div class="col-md-4">
      <div class="card h-100">
        <?php if ($reward['image_url']): ?><img src="<?php echo $reward['image_url']; ?>" class="card-img-top" alt="">
        <?php endif; ?>
        <div class="card-body d-flex flex-column">
          <h5><?php echo $reward['name']; ?></h5>
          <p class="text-muted flex-grow-1"><?php echo $reward['description']; ?></p>
          <p><strong><?php echo $reward['points_cost']; ?> punti</strong></p>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
            <button class="btn btn-primary" <?php echo $reward['points_cost'] > $balance ? 'disabled' : ''; ?>>Riscatta</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
