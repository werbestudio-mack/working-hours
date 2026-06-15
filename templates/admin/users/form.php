<?php
$isEdit = $user !== null;
$action = $isEdit
    ? BASE_URL . '/admin/users/' . $user['id'] . '/update'
    : BASE_URL . '/admin/users/create';
$me     = Auth::currentUser();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><?= $isEdit ? 'Benutzer bearbeiten' : 'Benutzer anlegen' ?></h2>
    <a href="<?= BASE_URL ?>/admin/users" class="btn btn-sm btn-outline-secondary">Zurück</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header"><h5 class="mb-0">Stammdaten</h5></div>
    <div class="card-body p-4">
        <form method="post" action="<?= h($action) ?>">
            <?= Auth::csrfInput() ?>

            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= h($user['name'] ?? '') ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">E-Mail-Adresse <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= h($user['email'] ?? '') ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">
                        Passwort <?= $isEdit ? '<small class="text-muted">(leer = unverändert)</small>' : '<span class="text-danger">*</span>' ?>
                    </label>
                    <input type="password" name="password" class="form-control"
                           <?= !$isEdit ? 'required minlength="8"' : 'minlength="8"' ?>
                           autocomplete="new-password"
                           placeholder="<?= $isEdit ? 'Nur ausfüllen zum Ändern' : 'Mindestens 8 Zeichen' ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Wochenstunden <span class="text-danger">*</span></label>
                    <input type="number" name="weekly_hours" class="form-control" required
                           step="0.5" min="1" max="168"
                           value="<?= h((string)($user['weekly_hours'] ?? '40')) ?>">
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Urlaubstage pro Jahr <span class="text-danger">*</span></label>
                    <input type="number" name="vacation_days" class="form-control" required
                           step="0.5" min="0" max="365"
                           value="<?= h((string)($user['vacation_days'] ?? '30')) ?>">
                </div>
                <div class="col-sm-6 d-flex flex-column justify-content-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_admin" id="is_admin"
                               <?= (!empty($user['is_admin'])) ? 'checked' : '' ?>
                               <?= ($isEdit && (int)$user['id'] === $me['id']) ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="is_admin">Administrator-Rechte</label>
                        <?php if ($isEdit && (int)$user['id'] === $me['id']): ?>
                            <small class="text-muted d-block">Eigene Rechte nicht änderbar.</small>
                        <?php endif; ?>
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                               <?= (!isset($user['is_active']) || $user['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Benutzer aktiv</label>
                    </div>
                    <?php endif; ?>
                    <?php if ($isEdit && !empty($_POST)): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="must_change_password"
                               id="mcp">
                        <label class="form-check-label" for="mcp">Passwortänderung erzwingen</label>
                    </div>
                    <?php elseif (!$isEdit): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="must_change_password"
                               id="mcp" checked>
                        <label class="form-check-label" for="mcp">Passwortänderung beim ersten Login erzwingen</label>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'Speichern' : 'Benutzer anlegen' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($isEdit): ?>
<!-- ── Urlaubsanpassungen ──────────────────────────────────────────── -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Urlaubsanpassungen</h5>
        <small class="text-muted">Resturlaub aus Vorjahr und Sondertage</small>
    </div>
    <div class="card-body">
        <?php if (!empty($adjustments)): ?>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Jahr</th>
                        <th>Resturlaub</th>
                        <th>Sondertage</th>
                        <th>Notiz</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($adjustments as $adj): ?>
                    <tr>
                        <td><?= h((string)$adj['year']) ?></td>
                        <td><?= number_format((float)$adj['carry_over'], 1) ?></td>
                        <td><?= number_format((float)$adj['bonus_days'], 1) ?></td>
                        <td class="text-muted small"><?= h($adj['note'] ?? '') ?></td>
                        <td class="text-end text-nowrap">
                            <form method="post"
                                  action="<?= BASE_URL ?>/admin/users/<?= $user['id'] ?>/adjustments/<?= $adj['id'] ?>/delete"
                                  class="d-inline"
                                  onsubmit="return confirm('Urlaubsanpassung wirklich löschen?')">
                                <?= Auth::csrfInput() ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Neue Anpassung / Bearbeiten -->
        <h6 class="mb-3">Anpassung speichern</h6>
        <form method="post"
              action="<?= BASE_URL ?>/admin/users/<?= $user['id'] ?>/adjustments/save"
              class="row g-3">
            <?= Auth::csrfInput() ?>
            <div class="col-sm-2">
                <label class="form-label">Jahr</label>
                <input type="number" name="year" class="form-control" required
                       min="2000" max="2100" value="<?= date('Y') ?>">
            </div>
            <div class="col-sm-2">
                <label class="form-label">Resturlaub</label>
                <input type="number" name="carry_over" class="form-control"
                       step="0.5" min="0" value="0">
            </div>
            <div class="col-sm-2">
                <label class="form-label">Sondertage</label>
                <input type="number" name="bonus_days" class="form-control"
                       step="0.5" min="0" value="0">
            </div>
            <div class="col-sm-4">
                <label class="form-label">Notiz <span class="text-muted">(optional)</span></label>
                <input type="text" name="note" class="form-control" placeholder="z.B. Projekt X">
            </div>
            <div class="col-sm-2 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary w-100">Speichern</button>
            </div>
        </form>
        <small class="text-muted">Existiert bereits ein Eintrag für das Jahr, wird er überschrieben.</small>
    </div>
</div>
<?php endif; ?>
