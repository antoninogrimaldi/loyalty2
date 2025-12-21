<?php $title='Login'; ob_start(); ?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h1 class="mb-3">Accedi</h1>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <div class="mb-3">
        <label class="form-label">Email o username</label>
        <input type="text" class="form-control" name="identifier" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" name="password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Login</button>
      <div class="mt-3 d-grid gap-2">
        <button class="btn btn-outline-secondary" disabled>Login con Google (Fase 2)</button>
        <button class="btn btn-outline-secondary" disabled>Login con Apple (Fase 2)</button>
      </div>
      <p class="mt-3">Non hai un account? <a href="<?php echo route_url('register'); ?>">Registrati</a></p>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
