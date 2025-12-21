<?php $title='La tua card'; ob_start(); ?>
<div class="row g-4">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <h5>Il tuo QR</h5>
        <img src="<?php echo $qr; ?>" alt="QR" class="img-fluid" />
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h5>Punti disponibili</h5>
          <div class="display-6"><?php echo $balance; ?></div>
        </div>
        <a class="btn btn-outline-primary" href="/?route=customer_points">Dettaglio movimenti</a>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-header">Ultimi ordini</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($orders as $order): ?>
          <li class="list-group-item d-flex justify-content-between"><span>#<?php echo $order['source']; ?>-<?php echo $order['source_order_id']; ?></span><span><?php echo $order['total_amount']; ?> <?php echo $order['currency']; ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="card mb-3">
      <div class="card-header">Offerta personalizzata</div>
      <div class="card-body">
        <?php if ($offer): ?>
          <p>Prodotti EAN: <?php echo $offer['product1_ean']; ?>, <?php echo $offer['product2_ean']; ?> | Sconto <?php echo $offer['discount_pct']; ?>%</p>
          <?php if (!empty($offer['shopify_discount_code'])): ?>
            <a class="btn btn-success" href="https://<?php echo SHOPIFY_SHOP; ?>/discount/<?php echo $offer['shopify_discount_code']; ?>?redirect=/cart" target="_blank">Applica su Shopify</a>
          <?php else: ?>
            <p class="text-muted">Il codice sconto sarà generato alla prossima sincronizzazione.</p>
          <?php endif; ?>
        <?php else: ?>
          <p>Nessuna offerta ancora. <a href="/?route=customer_offers">Seleziona i tuoi prodotti</a>.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="card">
      <div class="card-header">Coupon disponibili</div>
      <div class="card-body">
        <?php foreach ($coupons as $coupon): ?>
          <div class="d-flex justify-content-between border-bottom py-2">
            <div>
              <strong><?php echo $coupon['code']; ?></strong> - <?php echo $coupon['description']; ?>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="https://<?php echo SHOPIFY_SHOP; ?>/discount/<?php echo $coupon['code']; ?>?redirect=/cart" target="_blank">Applica</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
