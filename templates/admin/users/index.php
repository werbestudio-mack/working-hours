<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Benutzerverwaltung</h2>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/dump" class="btn btn-outline-secondary btn-sm">
            DB-Backup herunterladen
        </a>
        <a href="<?= BASE_URL ?>/admin/users/create" class="btn btn-primary btn-sm">+ Benutzer anlegen</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= h($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= h($error) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>Wochenstd.</th>
                    <th>Urlaubstage</th>
                    <th>Rolle</th>
                    <th>Status</th>
                    <th class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">Keine Benutzer vorhanden.</td></tr>
            <?php else: ?>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= h($u['name']) ?></td>
                    <td><?= h($u['email']) ?></td>
                    <td><?= number_format((float)$u['weekly_hours'], 2) ?> h</td>
                    <td><?= number_format((float)$u['vacation_days'], 1) ?></td>
                    <td>
                        <?php if ($u['is_admin']): ?>
                            <span class="badge bg-danger">Admin</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Benutzer</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge bg-success">Aktiv</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inaktiv</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= BASE_URL ?>/admin/users/<?= $u['id'] ?>/edit"
                           class="btn btn-outline-secondary btn-sm">Bearbeiten</a>
                        <?php if ((int)$u['id'] !== Auth::currentUser()['id']): ?>
                        <form method="post"
                              action="<?= BASE_URL ?>/admin/users/<?= $u['id'] ?>/delete"
                              class="d-inline"
                              onsubmit="return confirm('Benutzer wirklich löschen? Alle Zeiteinträge werden ebenfalls gelöscht.')">
                            <?= Auth::csrfInput() ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm">Löschen</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
