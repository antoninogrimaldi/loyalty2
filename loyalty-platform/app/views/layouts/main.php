<?php
if (!isset($title)) { $title = 'Loyalty'; }
?><!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo route_url('home'); ?>">Loyalty</a>
    <div class="d-flex">
      <?php if (!empty($_SESSION['user'])): ?>
        <a class="btn btn-outline-secondary" href="<?php echo route_url('logout'); ?>">Logout</a>
      <?php else: ?>
        <a class="btn btn-primary" href="<?php echo route_url('login'); ?>">Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container">
    <?php echo $content; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
