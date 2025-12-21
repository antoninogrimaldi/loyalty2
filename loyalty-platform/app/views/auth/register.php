<?php $title='Registrati'; ob_start(); ?>
<div class="row justify-content-center">
  <div class="col-md-8">
    <h1 class="mb-3">Crea account</h1>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success">Registrazione completata. Controlla la tua email per verificare l'account.</div>
    <?php endif; ?>
    <form method="post" class="row g-3">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <div class="col-md-6">
        <label class="form-label">Nome</label>
        <input class="form-control" name="first_name" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Cognome</label>
        <input class="form-control" name="last_name" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Username</label>
        <input class="form-control" name="username" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Telefono</label>
        <input class="form-control" name="phone" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Codice Fiscale</label>
        <input class="form-control" name="tax_code" required>
      </div>
      <div class="col-md-8">
        <label class="form-label">Indirizzo</label>
        <input class="form-control" name="address" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Città</label>
        <input class="form-control" name="city" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">CAP</label>
        <input class="form-control" name="zip" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Paese</label>
        <input class="form-control" name="country" value="Italia">
      </div>
      <div class="col-md-4">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" name="password" required>
      </div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="consent_data" required>
          <label class="form-check-label">Acconsento al trattamento dati (obbligatorio)</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="consent_marketing">
          <label class="form-check-label">Consenso marketing</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="consent_profiling">
          <label class="form-check-label">Consenso profilazione</label>
        </div>
      </div>
      <div class="col-12">
        <button class="btn btn-success" type="submit">Registrati</button>
      </div>
    </form>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layouts/main.php'; ?>
