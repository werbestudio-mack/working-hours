<?php
$monthNames = [
    1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',
    7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember',
];
$dayNames = [1=>'Mo', 2=>'Di', 3=>'Mi', 4=>'Do', 5=>'Fr', 6=>'Sa', 7=>'So'];
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

// Benutzeranzeige-Name für Header
$viewUser = null;
if ($isAdmin && !empty($allUsers)) {
    foreach ($allUsers as $u) {
        if ((int)$u['id'] === (int)$viewUserId) {
            $viewUser = $u;
            break;
        }
    }
}
$isOwnView = ((int)$viewUserId === (int)Auth::currentUser()['id']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Zeiteinträge</h2>
        <?php if (!$isOwnView && $viewUser): ?>
            <div class="text-muted small mt-1">Benutzer: <strong><?= h($viewUser['name']) ?></strong></div>
        <?php endif; ?>
    </div>
    <?php if ($isOwnView): ?>
    <a href="<?= BASE_URL ?>/entries/create" class="btn btn-primary btn-sm">+ Neuer Eintrag</a>
    <?php endif; ?>
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

<!-- ── Filter & Ansicht ───────────────────────────────────────────── -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end" method="get" action="<?= BASE_URL ?>/entries">

            <?php if ($isAdmin && !empty($allUsers)): ?>
            <div class="col-auto">
                <label class="form-label mb-1 small">Benutzer</label>
                <select name="view_user_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($allUsers as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= (int)$u['id'] === (int)$viewUserId ? 'selected' : '' ?>>
                        <?= h($u['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Ansichts-Umschalter -->
            <div class="col-auto">
                <label class="form-label mb-1 small">Ansicht</label>
                <div class="btn-group btn-group-sm d-flex">
                    <a href="<?= BASE_URL ?>/entries?view=list&year=<?= $year ?>&month=<?= $month ?><?= $isAdmin && !$isOwnView ? '&view_user_id='.$viewUserId : '' ?>"
                       class="btn <?= $view === 'list' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                        Liste
                    </a>
                    <a href="<?= BASE_URL ?>/entries?view=kw&year=<?= $year ?><?= $isAdmin && !$isOwnView ? '&view_user_id='.$viewUserId : '' ?>"
                       class="btn <?= $view === 'kw' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                        Kalenderwochen
                    </a>
                </div>
            </div>

            <?php if ($view === 'list'): ?>
            <div class="col-auto">
                <label class="form-label mb-1 small">Monat</label>
                <select name="month" class="form-select form-select-sm">
                    <?php foreach ($monthNames as $n => $label): ?>
                    <option value="<?= $n ?>" <?= $month == $n ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="view" value="kw">
            <?php endif; ?>

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
    </div>
</div>

<?php if ($view === 'kw'): ?>
<!-- ── Kalenderwochenansicht ──────────────────────────────────────── -->
<?php if (empty($weekData)): ?>
    <div class="alert alert-light border">Keine Daten für <?= $year ?>.</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>KW</th>
                    <th>Zeitraum</th>
                    <th class="text-end">Soll</th>
                    <th class="text-end">Ist</th>
                    <th class="text-end">Saldo</th>
                    <th class="d-none d-md-table-cell">Aufschlüsselung</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $totalSoll = $totalIst = $totalBal = 0.0;
            foreach ($weekData as $w):
                $totalSoll += $w['soll'];
                $totalIst  += $w['ist'];
                $totalBal  += $w['balance'];
                $balCls = $w['balance'] >= 0 ? 'text-success' : 'text-danger';
                $fromLabel = date('d.m.', strtotime($w['from']));
                $toLabel   = date('d.m.Y', strtotime($w['to']));
                $fromDay   = $dayNames[(int)date('N', strtotime($w['from']))] ?? '';
                $toDay     = $dayNames[(int)date('N', strtotime($w['to']))]   ?? '';
            ?>
                <tr>
                    <td class="fw-semibold">KW&nbsp;<?= $w['kw'] ?></td>
                    <td class="text-muted small text-nowrap">
                        <?= $fromDay ?> <?= $fromLabel ?> – <?= $toDay ?> <?= $toLabel ?>
                    </td>
                    <td class="text-end"><?= formatHours($w['soll']) ?></td>
                    <td class="text-end"><?= formatHours($w['ist']) ?></td>
                    <td class="text-end fw-semibold <?= $balCls ?>">
                        <?= ($w['balance'] >= 0 ? '+' : '') . formatHours($w['balance']) ?>
                    </td>
                    <td class="text-muted small d-none d-md-table-cell">
                        <?php
                        $parts = [];
                        if ($w['work']         > 0) $parts[] = 'Arbeit: '  . formatHours($w['work']);
                        if ($w['compensatory'] > 0) $parts[] = 'FZA: '     . formatHours($w['compensatory']);
                        if ($w['vacation_ist'] > 0) $parts[] = 'Urlaub: '  . formatHours($w['vacation_ist']);
                        if ($w['sick_ist']     > 0) $parts[] = 'Krank: '   . formatHours($w['sick_ist']);
                        echo implode(' · ', $parts) ?: '–';
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold border-top">
                <tr>
                    <td colspan="2">Gesamt <?= $year ?></td>
                    <td class="text-end"><?= formatHours($totalSoll) ?></td>
                    <td class="text-end"><?= formatHours($totalIst) ?></td>
                    <td class="text-end <?= $totalBal >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= ($totalBal >= 0 ? '+' : '') . formatHours($totalBal) ?>
                    </td>
                    <td class="d-none d-md-table-cell"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ── Listenansicht ──────────────────────────────────────────────── -->
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
                    <th>Dauer</th>
                    <th class="d-none d-md-table-cell">Notiz</th>
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

                    $startDay   = $dayNames[(int)date('N', $startTs)] ?? '';
                    $startStr   = $startDay . ' ' . date('d.m.Y H:i', $startTs);
                    $sameDay    = date('Y-m-d', $startTs) === date('Y-m-d', $endTs);
                    if ($sameDay) {
                        $rangeLabel = $startStr . ' – ' . date('H:i', $endTs);
                    } else {
                        $endDay     = $dayNames[(int)date('N', $endTs)] ?? '';
                        $rangeLabel = $startStr . ' – ' . $endDay . ' ' . date('d.m.Y H:i', $endTs);
                    }
                } else {
                    $startTs  = strtotime($e['date_start']);
                    $startDay = $dayNames[(int)date('N', $startTs)] ?? '';
                    $rangeLabel = $startDay . ' ' . date('d.m.Y', $startTs);

                    if ($e['date_start'] !== $e['date_end']) {
                        $endTs  = strtotime($e['date_end']);
                        $endDay = $dayNames[(int)date('N', $endTs)] ?? '';
                        $rangeLabel .= ' – ' . $endDay . ' ' . date('d.m.Y', $endTs);
                    }
                    if ($e['type'] === 'vacation' && $e['half_day'] != 0) {
                        $rangeLabel .= ' (' . ($e['half_day'] == 1 ? 'Vm.' : 'Nm.') . ')';
                    }
                    $durLabel = '—';
                }

                // Bearbeiten-Link: admin → view_user_id mitgeben
                $editUrl = BASE_URL . '/entries/' . $e['id'] . '/edit';
                if ($isAdmin && (int)$e['user_id'] !== (int)Auth::currentUser()['id']) {
                    $editUrl .= '?view_user_id=' . (int)$e['user_id'];
                }
            ?>
                <tr>
                    <td>
                        <span class="badge <?= $typeBadge[$e['type']] ?? 'bg-secondary' ?>">
                            <?= h($typeLabels[$e['type']] ?? $e['type']) ?>
                        </span>
                    </td>
                    <td class="text-nowrap"><?= h($rangeLabel) ?></td>
                    <td class="text-nowrap"><?= h($durLabel) ?></td>
                    <td class="text-muted small d-none d-md-table-cell"><?= h($e['notes'] ?? '') ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?= h($editUrl) ?>"
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
<?php endif; ?>
