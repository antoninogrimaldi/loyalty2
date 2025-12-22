<?php $title='Profilo'; ob_start(); ?>
<h1>Profilo</h1>
<p>Questa pagina può essere estesa per modificare dati personali e consensi GDPR.</p>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
