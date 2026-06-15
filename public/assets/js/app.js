/**
 * Arbeitszeiterfassung – Frontend-Logik
 */

document.addEventListener('DOMContentLoaded', function () {
    // ── Halber Urlaubstag: Checkbox + Sichtbarkeit ────────────────────
    var halfDayCheck   = document.getElementById('halfDayCheck');
    var halfDayOptions = document.getElementById('half-day-options');
    var hd1            = document.getElementById('hd_1');
    var hd2            = document.getElementById('hd_2');
    var hdNone         = document.getElementById('hd_none');
    var dateStart      = document.getElementById('date_start');
    var dateEnd        = document.getElementById('date_end');

    function updateHalfDayAvailability() {
        if (!halfDayCheck || !dateStart || !dateEnd) return;

        var isSingleDay = dateStart.value === dateEnd.value && dateStart.value !== '';
        halfDayCheck.disabled = !isSingleDay;

        if (!isSingleDay && halfDayCheck.checked) {
            halfDayCheck.checked = false;
            toggleHalfDayOptions(false);
        }

        var note = document.getElementById('halfDayNote');
        if (note) {
            note.textContent = isSingleDay ? '' : '(nur bei Einzeltag möglich)';
        }
    }

    function toggleHalfDayOptions(show) {
        if (!halfDayOptions) return;
        halfDayOptions.classList.toggle('d-none', !show);

        if (show) {
            // Standardmäßig Vormittag selektieren
            if (hd1 && hd2 && !hd1.checked && !hd2.checked) {
                hd1.checked = true;
            }
            if (hdNone) hdNone.disabled = true;
        } else {
            if (hdNone) hdNone.disabled = false;
            if (hd1) hd1.checked = false;
            if (hd2) hd2.checked = false;
        }
    }

    if (halfDayCheck) {
        halfDayCheck.addEventListener('change', function () {
            toggleHalfDayOptions(this.checked);
        });
        // Initialer Zustand
        toggleHalfDayOptions(halfDayCheck.checked);
    }

    if (dateStart) {
        dateStart.addEventListener('change', updateHalfDayAvailability);
    }
    if (dateEnd) {
        dateEnd.addEventListener('change', updateHalfDayAvailability);
    }
    updateHalfDayAvailability();

    // ── Auto-Submit für Perioden-Selector im Dashboard ───────────────
    var periodSelect = document.querySelector('#periodForm select[name="period"]');
    if (periodSelect) {
        periodSelect.addEventListener('change', function () {
            this.form.submit();
        });
    }

    // ── Alle Dismiss-Alerts nach 5s ausblenden ────────────────────────
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });
});
