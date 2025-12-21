<?php $title='Verifica'; ob_start(); ?>
<div class="alert alert-info mt-5"><?php echo htmlspecialchars($message); ?></div>
<a class="btn btn-primary" href="<?php echo route_url('login'); ?>">Vai al login</a>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
