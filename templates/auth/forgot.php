<div class="row justify-content-center">
    <div class="col-sm-8 col-md-5 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Passwort vergessen</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= h($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <p class="text-muted small mb-3">
                    Geben Sie Ihre E-Mail-Adresse ein. Sie erhalten einen Link zum Zurücksetzen Ihres Passworts.
                </p>
                <form method="post" action="<?= BASE_URL ?>/password/forgot">
                    <?= Auth::csrfInput() ?>
                    <div class="mb-3">
                        <label class="form-label">E-Mail-Adresse</label>
                        <input type="email" name="email" class="form-control" required autofocus
                               autocomplete="email">
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Reset-Link anfordern</button>
                </form>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="<?= BASE_URL ?>/login" class="text-muted small">Zurück zur Anmeldung</a>
                </div>
            </div>
        </div>
    </div>
</div>
