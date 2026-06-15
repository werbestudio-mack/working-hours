<?php
class DashboardController
{
    public function index(array $p = []): void
    {
        Auth::requireLogin();

        $userId = Auth::currentUser()['id'];
        $year   = (int) ($_GET['year']   ?? date('Y'));
        $period = $_GET['period'] ?? 'month';

        if ($period === 'year') {
            $from  = "$year-01-01";
            $to    = "$year-12-31";
            $month = (int) date('m');
        } elseif ($period === 'custom') {
            $from  = $_GET['from'] ?? date('Y-m-01');
            $to    = $_GET['to']   ?? date('Y-m-d');
            $month = (int) date('m');
        } else {
            $period = 'month';
            $month  = (int) ($_GET['month'] ?? date('m'));
            $from   = sprintf('%04d-%02d-01', $year, $month);
            $to     = date('Y-m-t', strtotime($from));
        }

        $hoursData   = HoursCalculator::calculate($userId, $from, $to);
        $vacationData = VacationCalculator::forUser($userId, $year);
        $userRecord  = User::find($userId);

        render('dashboard/index', [
            'hoursData'    => $hoursData,
            'vacationData' => $vacationData,
            'userRecord'   => $userRecord,
            'from'         => $from,
            'to'           => $to,
            'period'       => $period,
            'year'         => $year,
            'month'        => $month,
        ]);
    }
}
