<div class="row justify-content-center">
    <div class="col-sm-8 col-md-5 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Neues Passwort vergeben</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= BASE_URL ?>/password/reset">
                    <?= Auth::csrfInput() ?>
                    <input type="hidden" name="token" value="<?= h($token) ?>">

                    <div class="mb-3">
                        <label class="form-label">Neues Passwort <small class="text-muted">(min. 8 Zeichen)</small></label>
                        <input type="password" name="new_password" class="form-control"
                               required minlength="8" autofocus autocomplete="new-password">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Passwort bestätigen</label>
                        <input type="password" name="confirm_password" class="form-control"
                               required minlength="8" autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Passwort speichern</button>
                </form>
            </div>
        </div>
    </div>
</div>
