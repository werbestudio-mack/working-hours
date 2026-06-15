<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Feiertage</h2>
    <a href="<?= BASE_URL ?>/admin/holidays/create" class="btn btn-primary btn-sm">+ Feiertag anlegen</a>
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

<?php if (empty($holidays)): ?>
    <div class="alert alert-light border">Noch keine Feiertage eingetragen.</div>
<?php else: ?>

<?php
// Nach Jahr gruppieren
$grouped = [];
foreach ($holidays as $h) {
    $grouped[date('Y', strtotime($h['date']))][] = $h;
}
krsort($grouped);
?>

<?php foreach ($grouped as $year => $rows): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold"><?= $year ?></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Datum</th>
                    <th>Name</th>
                    <th>Art</th>
                    <th class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= date('d.m.Y', strtotime($row['date'])) ?></td>
                    <td><?= h($row['name']) ?></td>
                    <td>
                        <?php if ($row['is_half_day']): ?>
                            <span class="badge bg-warning text-dark">Halber Tag</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Ganzer Tag</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="<?= BASE_URL ?>/admin/holidays/<?= $row['id'] ?>/edit"
                           class="btn btn-outline-secondary btn-sm">Bearbeiten</a>
                        <form method="post"
                              action="<?= BASE_URL ?>/admin/holidays/<?= $row['id'] ?>/delete"
                              class="d-inline"
                              onsubmit="return confirm('Feiertag wirklich löschen?')">
                            <?= Auth::csrfInput() ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm">Löschen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
