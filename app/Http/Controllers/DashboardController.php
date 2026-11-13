<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSetting;
use DB;
class DashboardController extends Controller
{
    private function equipmentReportChartData()
    {
        $monthlyEquipment = DB::table('equipment')
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->groupByRaw('MONTH(created_at)')
            ->orderByRaw('MONTH(created_at)')
            ->get();

        $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        $added = [];
        foreach (range(1,12) as $month) {
            $record = $monthlyEquipment->firstWhere('month', $month);
            $added[] = $record ? (int) $record->total : 0;
        }

        $available = DB::table('equipment')->sum('balance_quantity');
        $used      = DB::table('equipment')->sum('used_quantity');

        $good = DB::table('equipment')
            ->where(function ($q) {
                $q->whereNull('status_remarks')
                ->orWhere('status_remarks', '')
                ->orWhere('status_remarks', 'Good');
            })
            ->count();

        $repair = DB::table('equipment')
            ->where('status_remarks', 'For Repair')
            ->count();

        $condemned = DB::table('equipment')
            ->where('status_remarks', 'Condemned')
            ->count();
        $locations = DB::table('equipment')
        ->selectRaw('IFNULL(location, "Unknown") as location, COUNT(*) as total')
        ->groupBy('location')
        ->orderByDesc('total')
        ->get();
        return [
            'statusDistribution' => [
                'labels' => ['Good', 'For Repair', 'Condemned'],
                'data' => [$good, $repair, $condemned],
                'colors' => ['#16a34a', '#f59e0b', '#dc2626'],
            ],
        'locationDistribution' => [
            'labels' => $locations->pluck('location')->toArray(),
            'data' => $locations->pluck('total')->map(fn($v) => (int)$v)->toArray(),
        ],
            'availability' => [
                'labels' => ['Available', 'Used'],
                'data' => [$available, $used],
                'colors' => ['#2563eb', '#f97316'],
            ],

            'addedPerMonth' => [
                'labels' => $monthLabels,
                'added' => $added,
                'maintenance' => array_fill(0, 12, 0),
            ],
        ];
    }
    public function Dashboard(Request $request){
        $settings = null;
        if(Auth::check()){
            $userRole = (int) Auth::user()->role;
            $dashboardUnit = match ($userRole) {
                2, 4 => 'FIS',
                3 => 'MIC',
                7 => 'CHEM',
                default => null,
            };
            $isDepartmentDashboard = $dashboardUnit !== null;

            $monthlyRla = DB::table('lf_06_02')
            ->selectRaw('MONTH(created_at) as month')
            ->selectRaw('COUNT(*) as total_rla')
            ->selectRaw('SUM(CASE WHEN status = 8 THEN 1 ELSE 0 END) as total_release')
            ->groupByRaw('MONTH(created_at)')
            ->orderByRaw('MONTH(created_at)')
            ->get();

            $months = [];
            $totalRla = [];
            $totalRelease = [];

            foreach (range(1, 12) as $month) {
                $record = $monthlyRla->firstWhere('month', $month);

                $months[] = date('M', mktime(0, 0, 0, $month, 1));
                $totalRla[] = $record ? (int) $record->total_rla : 0;
                $totalRelease[] = $record ? (int) $record->total_release : 0;
            }
           $monthlyData = DB::table('lf_06_02')
            ->selectRaw("
                MONTH(date_payment) as month,
                COUNT(DISTINCT user_id) as total_customers,
                SUM(CAST(REPLACE(payment, ',', '') AS DECIMAL(15,2))) as total_payments
            ")
            // ->when($year, function ($q) use ($year) {
            //     $q->whereYear('date_payment', $year);
            // })
            // ->when($month, function ($q) use ($month) {
            //     $q->whereMonth('date_payment', $month);
            // })
            // ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
            //     $q->whereBetween('date_payment', [$dateFrom, $dateTo]);
            // })
            ->groupByRaw('MONTH(date_payment)')
            ->orderByRaw('MONTH(date_payment)')
            ->get();

            $months = [
                1 => 'Jan',
                2 => 'Feb',
                3 => 'Mar',
                4 => 'Apr',
                5 => 'May',
                6 => 'Jun',
                7 => 'Jul',
                8 => 'Aug',
                9 => 'Sep',
                10 => 'Oct',
                11 => 'Nov',
                12 => 'Dec'
            ];

         $report = [];

        foreach ($months as $number => $name) {

            $row = $monthlyData->firstWhere('month', $number);

            $report[] = [
                'month' => $name,
                'customers' => $row->total_customers ?? 0,
                'payments' => $row->total_payments ?? 0
            ];
        }

            $unitAnalysis = [];
            $departmentRlaCount = 0;
            $departmentReleaseCount = 0;
            $pendingAnalysisCodes = [];
            $pendingAnalysisCount = 0;
            $unitLaboratoryCodeCount = 0;
            $releasedTodayCount = 0;
            $inProgressCount = 0;
            $dailyReleaseLabels = [];
            $dailyReleaseCounts = [];
            if ($isDepartmentDashboard) {
                $departmentRows = DB::table('lf_06_02')
                    ->select('laboratory_code', 'lab_unit', 'analysis_description', 'created_at', 'status', 'RLA_no')
                    ->get();
                $unitMonthlyCounts = array_fill(1, 12, 0);
                $unitMonthlyReleaseCounts = array_fill(1, 12, 0);
                $unitLaboratoryCodes = [];

                foreach ($departmentRows as $departmentRow) {
                    $units = json_decode($departmentRow->lab_unit ?? '[]', true);
                    $descriptions = json_decode($departmentRow->analysis_description ?? '[]', true);
                    $laboratoryCodes = json_decode($departmentRow->laboratory_code ?? '[]', true);
                    $units = is_array($units) ? $units : [];
                    $descriptions = is_array($descriptions) ? $descriptions : [];
                    $laboratoryCodes = is_array($laboratoryCodes) ? $laboratoryCodes : [];
                    $matchesUnit = false;
                    $rowDescriptions = [];

                    foreach ($units as $index => $unit) {
                        $normalizedUnit = strtoupper(trim((string) $unit));
                        $matches = $dashboardUnit === 'MIC'
                            ? in_array($normalizedUnit, ['MIC', 'MICRO', 'MICROBIOLOGY'], true)
                            : $normalizedUnit === $dashboardUnit;

                        if (!$matches) {
                            continue;
                        }

                        $matchesUnit = true;
                        $description = trim((string) ($descriptions[$index] ?? ''));
                        $laboratoryCode = trim((string) ($laboratoryCodes[$index] ?? ''));
                        if ($description !== '') {
                            $rowDescriptions[$description] = true;
                        }
                        if ($laboratoryCode !== '') {
                            $unitLaboratoryCodes[strtoupper($laboratoryCode)] = [
                                'laboratory_code' => $laboratoryCode,
                                'rla_no' => $departmentRow->RLA_no,
                                'analysis_description' => $description,
                            ];
                        }
                    }

                    if (!$matchesUnit) {
                        continue;
                    }

                    foreach (array_keys($rowDescriptions) as $description) {
                        $unitAnalysis[$description] = ($unitAnalysis[$description] ?? 0) + 1;
                    }

                    $departmentRlaCount++;
                    if ((int) $departmentRow->status === 8) {
                        $departmentReleaseCount++;
                    }

                    if ($departmentRow->created_at) {
                        $monthNumber = (int) date('n', strtotime($departmentRow->created_at));
                        $unitMonthlyCounts[$monthNumber]++;
                        if ((int) $departmentRow->status === 8) {
                            $unitMonthlyReleaseCounts[$monthNumber]++;
                        }
                    }
                }

                ksort($unitAnalysis);
                $totalRla = [];
                $totalRelease = [];
                foreach (range(1, 12) as $monthNumber) {
                    $totalRla[] = $unitMonthlyCounts[$monthNumber];
                    $totalRelease[] = $unitMonthlyReleaseCounts[$monthNumber];
                }

                $laboratoryCodeValues = array_values(array_unique(array_column($unitLaboratoryCodes, 'laboratory_code')));
                if ($laboratoryCodeValues !== []) {
                    $unitStatuses = DB::table('laboratory_status')
                        ->whereIn('laboratory_code', $laboratoryCodeValues)
                        ->orderBy('laboratory_code')
                        ->get(['laboratory_code', 'rla_no', 'status', 'updated_at']);
                    $unitLaboratoryCodeCount = count($unitLaboratoryCodes);
                    $departmentReleaseCount = $unitStatuses->where('status', 8)->count();
                    $releasedTodayCount = $unitStatuses
                        ->filter(fn ($status) => (int) $status->status === 8 && $status->updated_at &&
                            substr((string) $status->updated_at, 0, 10) === now()->toDateString())
                        ->count();
                    $inProgressCount = $unitStatuses
                        ->whereIn('status', [0, 1, 2, 4, 5, 6, 7])
                        ->count();
                    $pendingAnalysisCodes = $unitStatuses
                        ->where('status', 3)
                        ->map(function ($status) use ($unitLaboratoryCodes) {
                            $details = $unitLaboratoryCodes[strtoupper(trim($status->laboratory_code))] ?? [];
                            return [
                                'laboratory_code' => $status->laboratory_code,
                                'rla_no' => $status->rla_no,
                                'analysis_description' => $details['analysis_description'] ?? '',
                            ];
                        })
                        ->values()
                        ->all();
                    $pendingAnalysisCount = count($pendingAnalysisCodes);
                }

                $releaseStart = now()->startOfDay()->subDays(6);
                $releaseEnd = now()->endOfDay();
                $dailyReleaseMap = [];
                foreach (range(6, 0) as $daysAgo) {
                    $date = now()->startOfDay()->subDays($daysAgo);
                    $dateKey = $date->toDateString();
                    $dailyReleaseLabels[] = $date->format('M j');
                    $dailyReleaseMap[$dateKey] = 0;
                }

                if ($laboratoryCodeValues !== []) {
                    $releasedStatuses = DB::table('laboratory_status')
                        ->where('status', 8)
                        ->whereIn('laboratory_code', $laboratoryCodeValues)
                        ->whereBetween('updated_at', [$releaseStart, $releaseEnd])
                        ->get(['updated_at']);
                    foreach ($releasedStatuses as $releasedStatus) {
                        $releaseDate = date('Y-m-d', strtotime($releasedStatus->updated_at));
                        if (array_key_exists($releaseDate, $dailyReleaseMap)) {
                            $dailyReleaseMap[$releaseDate]++;
                        }
                    }
                }
                $dailyReleaseCounts = array_values($dailyReleaseMap);
            }

        $chartData = $this->equipmentReportChartData();
        $settings = UserSetting::where('user_id', Auth::id())->first();
        return view('dashboard.dashboard', compact(
                'months',
                'report',
                'chartData',
                'totalRla',
                'totalRelease',
                'dashboardUnit',
                'isDepartmentDashboard',
                'departmentRlaCount',
                'departmentReleaseCount',
                'unitLaboratoryCodeCount',
                'releasedTodayCount',
                'inProgressCount',
                'unitAnalysis',
                'pendingAnalysisCodes',
                'pendingAnalysisCount',
                'dailyReleaseLabels',
                'dailyReleaseCounts'
            ));
    }else{
        Auth::logout();
            return redirect('/login')->with('error', 'End Session, Automatic Logout');
        }
    }
}
