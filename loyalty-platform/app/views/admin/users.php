<?php $title='Utenti'; ob_start(); ?>
<h1>Utenti</h1>
<table class="table table-striped">
  <thead><tr><th>ID</th><th>Email</th><th>Nome</th><th>Stato</th></tr></thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?php echo $u['id']; ?></td>
        <td><?php echo $u['email']; ?></td>
        <td><?php echo $u['first_name'] . ' ' . $u['last_name']; ?></td>
        <td><?php echo $u['status']; ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/admin.php'; ?>
