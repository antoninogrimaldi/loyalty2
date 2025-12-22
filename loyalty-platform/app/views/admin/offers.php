<?php $title='Offerte'; ob_start(); ?>
<h1>Offerte personalizzate</h1>
<table class="table table-striped">
  <thead><tr><th>User</th><th>EAN1</th><th>EAN2</th><th>Sconto</th><th>Cambi anno</th><th>Azione</th></tr></thead>
  <tbody>
    <?php foreach ($offers as $o): ?>
      <tr>
        <td><?php echo $o['email']; ?></td>
        <td><?php echo $o['product1_ean']; ?></td>
        <td><?php echo $o['product2_ean']; ?></td>
        <td><?php echo $o['discount_pct']; ?>%</td>
        <td><?php echo $o['changes_count_year']; ?></td>
        <td><a class="btn btn-sm btn-outline-primary" href="<?php echo route_url('admin_offer_reset', ['id' => $o['id']]); ?>">Reset contatore</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/admin.php'; ?>
