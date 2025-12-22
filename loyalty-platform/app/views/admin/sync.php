<?php $title='Sync'; ob_start(); ?>
<h1>Sincronizzazioni</h1>
<p>Avvia i job manualmente (in produzione usare cron).</p>
<a class="btn btn-primary" href="<?php echo route_url('admin_sync', ['run' => 1]); ?>">Esegui run_sync.php</a>
<?php if ($output): ?><pre class="mt-3 bg-dark text-white p-3" style="white-space: pre-wrap;"><?php echo $output; ?></pre><?php endif; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/admin.php'; ?>
