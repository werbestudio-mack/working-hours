<?php
$monthNames = [
    1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',
    7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember',
];
$balance      = $hoursData['balance'];
$balanceClass = $balance >= 0 ? 'text-success' : 'text-danger';

$periodLabel = match($period) {
    'year'   => "Gesamtes Jahr $year (bis heute)",
    'custom' => date('d.m.Y', strtotime($from)) . ' – ' . date('d.m.Y', strtotime($to)),
    default  => ($monthNames[$month] ?? '') . " $year",
};

$isOwnView = ((int)$viewUserId === (int)Auth::currentUser()['id']);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Dashboard</h2>
        <?php if (!$isOwnView && $userRecord): ?>
            <div class="text-muted small mt-1">Benutzer: <strong><?= h($userRecord['name']) ?></strong></div>
        <?php endif; ?>
    </div>
    <span class="text-muted">Willkommen, <?= h(Auth::currentUser()['name']) ?></span>
</div>

<!-- ── Zeitraum-Auswahl ─────────────────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end" method="get" action="<?= BASE_URL ?>/" id="periodForm">

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

            <div class="col-auto">
                <label class="form-label mb-1 small">Zeitraum</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="month"  <?= $period === 'month'  ? 'selected' : '' ?>>Monat</option>
                    <option value="year"   <?= $period === 'year'   ? 'selected' : '' ?>>Jahr (bis heute)</option>
                    <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Benutzerdefiniert</option>
                </select>
            </div>

            <?php if ($period === 'month'): ?>
            <div class="col-auto">
                <label class="form-label mb-1 small">Monat</label>
                <select name="month" class="form-select form-select-sm">
                    <?php foreach ($monthNames as $n => $label): ?>
                    <option value="<?= $n ?>" <?= $month == $n ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-auto">
                <label class="form-label mb-1 small">Jahr</label>
                <select name="year" class="form-select form-select-sm">
                    <?php for ($y = (int)date('Y') - 3; $y <= (int)date('Y') + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <?php if ($period === 'custom'): ?>
            <div class="col-auto">
                <label class="form-label mb-1 small">Von</label>
                <input type="date" name="from" class="form-control form-control-sm"
                       value="<?= h($from) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label mb-1 small">Bis</label>
                <input type="date" name="to" class="form-control form-control-sm"
                       value="<?= h($to) ?>">
            </div>
            <?php endif; ?>

            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Anzeigen</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Stunden ─────────────────────────────────────────────────────── -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Arbeitsstunden</h5>
                <span class="text-muted small"><?= h($periodLabel) ?></span>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-4 fw-bold"><?= formatHours($hoursData['soll']) ?></div>
                            <div class="text-muted small">Soll</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-4 fw-bold"><?= formatHours($hoursData['ist']) ?></div>
                            <div class="text-muted small">Ist</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-4 fw-bold <?= $balanceClass ?>">
                                <?= ($balance >= 0 ? '+' : '') . formatHours($balance) ?>
                            </div>
                            <div class="text-muted small">Saldo</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-6 fw-semibold">
                                <?= formatHours($hoursData['work']) ?> Arbeit<br>
                                <?= formatHours($hoursData['compensatory']) ?> FZA<br>
                                <?= formatHours($hoursData['sick_ist']) ?> Krank<br>
                                <?= formatHours($hoursData['vacation_ist']) ?> Urlaub
                            </div>
                            <div class="text-muted small">Aufschlüsselung</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Urlaubskonto ───────────────────────────────────────────────── -->
<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Urlaubskonto</h5>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small"><?= $vacationData['year'] ?></span>
                    <a href="<?= BASE_URL ?>/vacation/detail?year=<?= $year ?><?= $isAdmin && !$isOwnView ? '&view_user_id='.$viewUserId : '' ?>"
                       class="btn btn-sm btn-outline-secondary">Details</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-6 col-md">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-4 fw-bold"><?= number_format($vacationData['carry_over'], 1) ?></div>
                            <div class="text-muted small">Resturlaub Vorjahr</div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-4 fw-bold"><?= number_format($vacationData['annual_allowance'], 1) ?></div>
                            <div class="text-muted small">Jahresanspruch</div>
                        </div>
                    </div>
                    <?php if ($vacationData['bonus_days'] > 0): ?>
                    <div class="col-6 col-md">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-4 fw-bold"><?= number_format($vacationData['bonus_days'], 1) ?></div>
                            <div class="text-muted small">Sondertage</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-6 col-md">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-4 fw-bold"><?= number_format($vacationData['total_entitlement'], 1) ?></div>
                            <div class="text-muted small">Gesamt</div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-4 fw-bold text-warning"><?= number_format($vacationData['taken'], 1) ?></div>
                            <div class="text-muted small">Genommen</div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="p-3 bg-light rounded">
                            <div class="fs-4 fw-bold <?= $vacationData['remaining'] < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= number_format($vacationData['remaining'], 1) ?>
                            </div>
                            <div class="text-muted small">Verbleibend</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <small class="text-muted">Angaben in Arbeitstagen</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isOwnView): ?>
<div class="mt-4 text-end">
    <a href="<?= BASE_URL ?>/entries/create" class="btn btn-primary">
        + Neuer Zeiteintrag
    </a>
</div>
<?php endif; ?>
