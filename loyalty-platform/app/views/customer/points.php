<?php $title='Movimenti punti'; ob_start(); ?>
<h1>Saldo: <?php echo $balance; ?> punti</h1>
<table class="table table-striped">
  <thead><tr><th>Data</th><th>Tipo</th><th>Punti</th><th>Nota</th></tr></thead>
  <tbody>
    <?php foreach ($ledger as $row): ?>
      <tr>
        <td><?php echo $row['created_at']; ?></td>
        <td><?php echo $row['type']; ?></td>
        <td><?php echo $row['points']; ?></td>
        <td><?php echo $row['note']; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
