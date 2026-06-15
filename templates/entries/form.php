<?php
$isEdit    = $entry !== null;
$action    = $isEdit
    ? BASE_URL . '/entries/' . $entry['id'] . '/update'
    : BASE_URL . '/entries/create';
$type      = $entry['type'] ?? 'work';
$isTimeBased = in_array($type, ['work', 'compensatory']);

// Werte für Felder vorbelegen
$startDate  = '';
$startTime  = '';
$endDate    = '';
$endTime    = '';
$dateStart  = $entry['date_start'] ?? '';
$dateEnd    = $entry['date_end']   ?? '';
$halfDay    = (int) ($entry['half_day'] ?? 0);
$notes      = $entry['notes'] ?? '';

if ($isEdit && $isTimeBased) {
    $startDate = $entry['started_at'] ? date('Y-m-d', strtotime($entry['started_at'])) : '';
    $startTime = $entry['started_at'] ? date('H:i',   strtotime($entry['started_at'])) : '';
    $endDate   = $entry['ended_at']   ? date('Y-m-d', strtotime($entry['ended_at']))   : '';
    $endTime   = $entry['ended_at']   ? date('H:i',   strtotime($entry['ended_at']))   : '';
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0"><?= $isEdit ? 'Eintrag bearbeiten' : 'Neuer Eintrag' ?></h2>
            <a href="<?= BASE_URL ?>/entries" class="btn btn-sm btn-outline-secondary">Zurück</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?= h($action) ?>" id="entryForm">
                    <?= Auth::csrfInput() ?>

                    <!-- Typ -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Art des Eintrags</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ([
                                'work'         => 'Arbeit',
                                'compensatory' => 'Freizeitausgleich',
                                'vacation'     => 'Urlaub',
                                'sick'         => 'Krankheit',
                            ] as $val => $label): ?>
                            <div class="form-check form-check-inline m-0">
                                <input class="form-check-input" type="radio"
                                       name="type" id="type_<?= $val ?>" value="<?= $val ?>"
                                       <?= $type === $val ? 'checked' : '' ?> required>
                                <label class="form-check-label border rounded px-3 py-1"
                                       for="type_<?= $val ?>"><?= $label ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Felder für Arbeit / Freizeitausgleich (Datum + Uhrzeit) -->
                    <div id="fields-time">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">Startdatum</label>
                                <input type="date" name="started_date" class="form-control"
                                       value="<?= h($startDate ?: date('Y-m-d')) ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Startzeit</label>
                                <input type="time" name="started_time" class="form-control"
                                       value="<?= h($startTime ?: '09:00') ?>">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">Enddatum</label>
                                <input type="date" name="ended_date" class="form-control"
                                       value="<?= h($endDate ?: date('Y-m-d')) ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Endzeit</label>
                                <input type="time" name="ended_time" class="form-control"
                                       value="<?= h($endTime ?: '17:00') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Felder für Urlaub / Krankheit (nur Datum) -->
                    <div id="fields-date">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">Startdatum</label>
                                <input type="date" name="date_start" id="date_start" class="form-control"
                                       value="<?= h($dateStart ?: date('Y-m-d')) ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Enddatum</label>
                                <input type="date" name="date_end" id="date_end" class="form-control"
                                       value="<?= h($dateEnd ?: date('Y-m-d')) ?>">
                            </div>
                        </div>

                        <!-- Halber Tag (nur Urlaub) -->
                        <div id="half-day-section" class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="halfDayCheck"
                                       <?= $halfDay != 0 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="halfDayCheck">
                                    Halber Tag
                                    <span id="halfDayNote" class="text-muted small ms-1">
                                        (nur bei Einzeltag)
                                    </span>
                                </label>
                            </div>
                            <div id="half-day-options" class="ms-4 mt-2 <?= $halfDay != 0 ? '' : 'd-none' ?>">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="half_day"
                                           id="hd_1" value="1" <?= $halfDay == 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="hd_1">Vormittag</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="half_day"
                                           id="hd_2" value="2" <?= $halfDay == 2 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="hd_2">Nachmittag</label>
                                </div>
                                <!-- hidden "no half day" fallback -->
                                <input type="hidden" name="half_day" value="0" id="hd_none"
                                       <?= $halfDay != 0 ? 'disabled' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Notiz -->
                    <div class="mb-4">
                        <label class="form-label">Notiz <span class="text-muted">(optional)</span></label>
                        <textarea name="notes" class="form-control" rows="2"><?= h($notes) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?= $isEdit ? 'Speichern' : 'Eintrag anlegen' ?>
                        </button>
                        <a href="<?= BASE_URL ?>/entries" class="btn btn-outline-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Initialen Typ aus PHP übernehmen
document.addEventListener('DOMContentLoaded', function () {
    updateFields('<?= h($type) ?>');

    document.querySelectorAll('input[name="type"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            updateFields(this.value);
        });
    });
});

function updateFields(type) {
    var isTime = type === 'work' || type === 'compensatory';
    document.getElementById('fields-time').style.display = isTime ? '' : 'none';
    document.getElementById('fields-date').style.display = isTime ? 'none' : '';

    var halfDaySection = document.getElementById('half-day-section');
    if (halfDaySection) {
        halfDaySection.style.display = type === 'vacation' ? '' : 'none';
    }
}
</script>
