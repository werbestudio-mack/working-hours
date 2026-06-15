<div class="row justify-content-center">
    <div class="col-sm-8 col-md-5 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><?= h(APP_NAME) ?> – Anmelden</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= BASE_URL ?>/login">
                    <?= Auth::csrfInput() ?>
                    <div class="mb-3">
                        <label class="form-label">E-Mail-Adresse</label>
                        <input type="email" name="email" class="form-control" required autofocus
                               autocomplete="email">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Passwort</label>
                        <input type="password" name="password" class="form-control" required
                               autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Anmelden</button>
                </form>
            </div>
        </div>
    </div>
</div>
