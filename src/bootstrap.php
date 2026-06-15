<?php
require_once dirname(__DIR__) . '/src/config.php';

date_default_timezone_set(APP_TIMEZONE);

require_once BASE_PATH . '/src/Database.php';
require_once BASE_PATH . '/src/Auth.php';
require_once BASE_PATH . '/src/Models/User.php';
require_once BASE_PATH . '/src/Models/TimeEntry.php';
require_once BASE_PATH . '/src/Models/PublicHoliday.php';
require_once BASE_PATH . '/src/Models/VacationAdjustment.php';
require_once BASE_PATH . '/src/Services/HoursCalculator.php';
require_once BASE_PATH . '/src/Services/VacationCalculator.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function render(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/templates/' . $template . '.php';
    $content = ob_get_clean();
    require BASE_PATH . '/templates/layout.php';
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function getFlash(string $key): ?string
{
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function formatHours(float $hours): string
{
    $sign    = $hours < 0 ? '-' : '';
    $abs     = abs($hours);
    $h       = (int) $abs;
    $m       = (int) round(($abs - $h) * 60);
    return sprintf('%s%d:%02d h', $sign, $h, $m);
}
