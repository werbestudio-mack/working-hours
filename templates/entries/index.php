<?php
$monthNames = [
    1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',
    7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember',
];
$typeLabels = [
    'work'         => 'Arbeit',
    'vacation'     => 'Urlaub',
    'sick'         => 'Krankheit',
    'compensatory' => 'Freizeitausgleich',
];
$typeBadge = [
    'work'         => 'bg-primary',
    'vacation'     => 'bg-success',
    'sick'         => 'bg-warning text-dark',
    'compensatory' => 'bg-info text-dark',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Zeiteinträge</h2>
    <a href="<?= BASE_URL ?>/entries/create" class="btn btn-primary btn-sm">+ Neuer Eintrag</a>
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

<!-- Monatsfilter -->
<form class="row g-2 mb-3 align-items-end" method="get" action="<?= BASE_URL ?>/entries">
    <div class="col-auto">
        <label class="form-label mb-1 small">Monat</label>
        <select name="month" class="form-select form-select-sm">
            <?php foreach ($monthNames as $n => $label): ?>
            <option value="<?= $n ?>" <?= $month == $n ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label mb-1 small">Jahr</label>
        <select name="year" class="form-select form-select-sm">
            <?php for ($y = (int)date('Y') - 3; $y <= (int)date('Y') + 1; $y++): ?>
            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-secondary">Anzeigen</button>
    </div>
</form>

<?php if (empty($entries)): ?>
    <div class="alert alert-light border">
        Keine Einträge für <?= h($monthNames[$month] ?? '') ?> <?= $year ?>.
    </div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Typ</th>
                    <th>Zeitraum</th>
                    <th>Dauer / Tage</th>
                    <th>Notiz</th>
                    <th class="text-end">Aktionen</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $e):
                $isDateBased = in_array($e['type'], ['vacation', 'sick']);
                if (!$isDateBased) {
                    $startTs  = strtotime($e['started_at']);
                    $endTs    = strtotime($e['ended_at']);
                    $durH     = ($endTs - $startTs) / 3600;
                    $durLabel = number_format($durH, 2) . ' h';
                    $rangeLabel = date('d.m.Y H:i', $startTs) . ' – ' . date('d.m.Y H:i', $endTs);
                } else {
                    $rangeLabel = date('d.m.Y', strtotime($e['date_start']));
                    if ($e['date_start'] !== $e['date_end']) {
                        $rangeLabel .= ' – ' . date('d.m.Y', strtotime($e['date_end']));
                    }
                    if ($e['type'] === 'vacation' && $e['half_day'] != 0) {
                        $rangeLabel .= ' (' . ($e['half_day'] == 1 ? 'Vormittag' : 'Nachmittag') . ')';
                    }
                    $durLabel = '—';
                }
            ?>
                <tr>
                    <td>
                        <span class="badge <?= $typeBadge[$e['type']] ?? 'bg-secondary' ?>">
                            <?= h($typeLabels[$e['type']] ?? $e['type']) ?>
                        </span>
                    </td>
                    <td><?= h($rangeLabel) ?></td>
                    <td><?= h($durLabel) ?></td>
                    <td class="text-muted small"><?= h($e['notes'] ?? '') ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= BASE_URL ?>/entries/<?= $e['id'] ?>/edit"
                           class="btn btn-outline-secondary btn-sm">Bearbeiten</a>
                        <form method="post"
                              action="<?= BASE_URL ?>/entries/<?= $e['id'] ?>/delete"
                              class="d-inline"
                              onsubmit="return confirm('Eintrag wirklich löschen?')">
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
<?php endif; ?>
