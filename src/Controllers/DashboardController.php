<?php
class DashboardController
{
    public function index(array $p = []): void
    {
        Auth::requireLogin();
        $me     = Auth::currentUser();
        $userId = (int) $me['id'];

        // Admin kann beliebigen Benutzer betrachten
        if ($me['is_admin'] && isset($_GET['view_user_id'])) {
            $vu = User::find((int) $_GET['view_user_id']);
            if ($vu) {
                $userId = (int) $vu['id'];
            }
        }
        $viewUserId = $userId;

        $year   = (int) ($_GET['year']   ?? date('Y'));
        $period = $_GET['period'] ?? 'month';
        $today  = date('Y-m-d');

        if ($period === 'year') {
            $from  = "$year-01-01";
            $to    = min("$year-12-31", $today); // nicht in die Zukunft rechnen
            $month = (int) date('m');
        } elseif ($period === 'custom') {
            $from  = $_GET['from'] ?? date('Y-m-01');
            $to    = $_GET['to']   ?? $today;
            $month = (int) date('m');
        } else {
            $period = 'month';
            $month  = (int) ($_GET['month'] ?? date('m'));
            $from   = sprintf('%04d-%02d-01', $year, $month);
            $to     = date('Y-m-t', strtotime($from));
        }

        $hoursData    = HoursCalculator::calculate($userId, $from, $to);
        $vacationData = VacationCalculator::forUser($userId, $year);
        $userRecord   = User::find($userId);
        $allUsers     = $me['is_admin'] ? User::all() : [];

        render('dashboard/index', [
            'hoursData'    => $hoursData,
            'vacationData' => $vacationData,
            'userRecord'   => $userRecord,
            'from'         => $from,
            'to'           => $to,
            'period'       => $period,
            'year'         => $year,
            'month'        => $month,
            'viewUserId'   => $viewUserId,
            'allUsers'     => $allUsers,
            'isAdmin'      => (bool) $me['is_admin'],
        ]);
    }
}
