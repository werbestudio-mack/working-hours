<div class="row justify-content-center">
    <div class="col-sm-8 col-md-6 col-lg-5">
        <h2 class="mb-4">Passwort ändern</h2>

        <?php if (!empty($_SESSION['must_change_password'])): ?>
        <div class="alert alert-warning">
            Bitte vergeben Sie ein neues Passwort, bevor Sie fortfahren.
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?= BASE_URL ?>/password/change">
                    <?= Auth::csrfInput() ?>
                    <div class="mb-3">
                        <label class="form-label">Aktuelles Passwort</label>
                        <input type="password" name="current_password" class="form-control" required
                               autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Neues Passwort <small class="text-muted">(min. 8 Zeichen)</small></label>
                        <input type="password" name="new_password" class="form-control" required
                               minlength="8" autocomplete="new-password">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Neues Passwort bestätigen</label>
                        <input type="password" name="confirm_password" class="form-control" required
                               autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary">Passwort ändern</button>
                    <?php if (empty($_SESSION['must_change_password'])): ?>
                    <a href="<?= BASE_URL ?>/" class="btn btn-link">Abbrechen</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
