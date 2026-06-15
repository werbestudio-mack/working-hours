<?php
$dayNames = [1=>'Mo', 2=>'Di', 3=>'Mi', 4=>'Do', 5=>'Fr', 6=>'Sa', 7=>'So'];

$isOwnView = ((int)$viewUserId === (int)Auth::currentUser()['id']);

$viewUser = null;
if ($isAdmin && !empty($allUsers)) {
    foreach ($allUsers as $u) {
        if ((int)$u['id'] === (int)$viewUserId) {
            $viewUser = $u;
            break;
        }
    }
}

function fmtDay(float $days): string {
    if ($days == 0.5) return '½ Tag';
    if ($days == 1.0) return '1 Tag';
    // strip trailing zero for whole numbers
    if (floor($days) == $days) return number_format($days, 0) . ' Tage';
    return number_format($days, 1) . ' Tage';
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Urlaubsdetails</h2>
        <?php if (!$isOwnView && $viewUser): ?>
            <div class="text-muted small mt-1">Benutzer: <strong><?= h($viewUser['name']) ?></strong></div>
        <?php endif; ?>
    </div>
    <a href="<?= BASE_URL ?>/" class="btn btn-sm btn-outline-secondary">Dashboard</a>
</div>

<!-- ── Jahr- und Benutzerauswahl ──────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end" method="get" action="<?= BASE_URL ?>/vacation/detail">

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

<!-- ── Urlaubskonto Übersicht ──────────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Urlaubskonto <?= $year ?></h5>
        <small class="text-muted">Angaben in Arbeitstagen</small>
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
    </div>
</div>

<!-- ── Urlaubseinträge ────────────────────────────────────────────── -->
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Urlaubseinträge <?= $year ?></h5>
    </div>

    <?php if (empty($entryDetails)): ?>
    <div class="card-body text-muted">Keine Urlaubseinträge für <?= $year ?>.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Zeitraum</th>
                    <th class="text-end">Urlaubstage</th>
                    <th>Feiertage im Zeitraum</th>
                    <th class="d-none d-md-table-cell">Notiz</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $totalDays = 0.0;
            foreach ($entryDetails as $detail):
                $entry    = $detail['entry'];
                $vacDays  = $detail['vac_days'];
                $holidays = $detail['holidays'];
                $totalDays += $vacDays;

                $startTs  = strtotime($entry['date_start']);
                $startDay = $dayNames[(int)date('N', $startTs)] ?? '';
                $dateLabel = $startDay . ' ' . date('d.m.Y', $startTs);

                if ($entry['date_start'] !== $entry['date_end']) {
                    $endTs  = strtotime($entry['date_end']);
                    $endDay = $dayNames[(int)date('N', $endTs)] ?? '';
                    $dateLabel .= ' – ' . $endDay . ' ' . date('d.m.Y', $endTs);
                }
                if ($entry['half_day'] != 0) {
                    $dateLabel .= ' (' . ($entry['half_day'] == 1 ? 'Vormittag' : 'Nachmittag') . ')';
                }

                // Mark clipped entries (span year boundary)
                $clipped = ($entry['date_start'] < "$year-01-01" || $entry['date_end'] > "$year-12-31");
            ?>
                <tr>
                    <td class="text-nowrap">
                        <?= h($dateLabel) ?>
                        <?php if ($clipped): ?>
                        <span class="badge bg-light text-muted border ms-1" title="Eintrag reicht über Jahresgrenze — nur Anteil in <?= $year ?> gezählt">
                            geteilt
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-semibold"><?= fmtDay($vacDays) ?></td>
                    <td>
                        <?php if (empty($holidays)): ?>
                            <span class="text-muted">–</span>
                        <?php else: ?>
                            <?php foreach ($holidays as $hol): ?>
                            <span class="badge bg-light text-dark border me-1">
                                <?= h($hol['name']) ?>
                                <span class="text-muted">(<?= date('d.m.', strtotime($hol['date'])) ?>)</span>
                            </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small d-none d-md-table-cell"><?= h($entry['notes'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold border-top">
                <tr>
                    <td>Gesamt</td>
                    <td class="text-end"><?= fmtDay($totalDays) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>
</div>
