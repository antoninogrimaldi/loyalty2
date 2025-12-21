<?php $title='I tuoi coupon'; ob_start(); ?>
<h1>Coupon assegnati</h1>
<div class="list-group">
  <?php foreach ($coupons as $coupon): ?>
    <div class="list-group-item d-flex justify-content-between align-items-center">
      <div>
        <strong><?php echo $coupon['code']; ?></strong> - <?php echo $coupon['description']; ?>
        <div class="small text-muted"><?php echo $coupon['assignment_status']; ?></div>
      </div>
      <a class="btn btn-outline-primary" target="_blank" href="https://<?php echo SHOPIFY_SHOP; ?>/discount/<?php echo $coupon['code']; ?>?redirect=/cart">Applica</a>
    </div>
  <?php endforeach; ?>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
