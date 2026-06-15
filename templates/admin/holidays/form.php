<?php
$isEdit = $holiday !== null;
$action = $isEdit
    ? BASE_URL . '/admin/holidays/' . $holiday['id'] . '/update'
    : BASE_URL . '/admin/holidays/create';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><?= $isEdit ? 'Feiertag bearbeiten' : 'Feiertag anlegen' ?></h2>
    <a href="<?= BASE_URL ?>/admin/holidays" class="btn btn-sm btn-outline-secondary">Zurück</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?= h($action) ?>">
                    <?= Auth::csrfInput() ?>

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= h($holiday['name'] ?? '') ?>"
                               placeholder="z.B. Weihnachten">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Datum <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" required
                               value="<?= h($holiday['date'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_half_day"
                                   id="is_half_day"
                                   <?= !empty($holiday['is_half_day']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_half_day">
                                Halber Feiertag
                                <small class="text-muted d-block">
                                    z.B. Heiligabend (24.12.) oder Silvester (31.12.)
                                </small>
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?= $isEdit ? 'Speichern' : 'Feiertag anlegen' ?>
                        </button>
                        <a href="<?= BASE_URL ?>/admin/holidays" class="btn btn-outline-secondary">
                            Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
