<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function payments(Request $request){
        $year = $request->year ?? date('Y');
        $month = $request->month;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $query = DB::table('lf_06_02')
            ->join('clients', 'lf_06_02.user_id', '=', 'clients.id')
            ->leftJoin('lf_06_04', 'lf_06_02.id', '=', 'lf_06_04.lf_06_02_id');
        if ($year) {
            $query->whereYear('lf_06_02.date_payment', $year);
        }
        if ($month) {
            $query->whereMonth('lf_06_02.date_payment', $month);
        }
        if ($dateFrom && $dateTo) {
            $query->whereBetween('lf_06_02.date_payment', [
                $dateFrom,
                $dateTo
            ]);
        }

        $records = $query->select(
            'lf_06_02.user_id',
            'lf_06_02.payment',
            'lf_06_02.date_payment'
        )->get();

        $totalCustomers = $records->pluck('user_id')->unique()->count();

        $totalPayments = DB::table('lf_06_02')
            ->when($year, function ($q) use ($year) {
                $q->whereYear('date_payment', $year);
            })
            ->when($month, function ($q) use ($month) {
                $q->whereMonth('date_payment', $month);
            })
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date_payment', [$dateFrom, $dateTo]);
            })
            ->sum(DB::raw("CAST(REPLACE(payment, ',', '') AS DECIMAL(15,2))"));
        $monthlyData = DB::table('lf_06_02')
            ->selectRaw("
                MONTH(date_payment) as month,
                COUNT(DISTINCT user_id) as total_customers,
                SUM(CAST(REPLACE(payment, ',', '') AS DECIMAL(15,2))) as total_payments
            ")
            ->when($year, function ($q) use ($year) {
                $q->whereYear('date_payment', $year);
            })
            ->when($month, function ($q) use ($month) {
                $q->whereMonth('date_payment', $month);
            })
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date_payment', [$dateFrom, $dateTo]);
            })
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

        return view('Reports.payments', compact(
            'report',
            'year',
            'month',
            'dateFrom',
            'dateTo',
            'totalCustomers',
            'totalPayments'
        ));
    }
    private function getAddressFromCoordinates($coordinates)
    {
        if (empty($coordinates)) {
            return 'N/A';
        }

        [$lat, $lng] = explode(',', $coordinates);

        $response = Http::get(
            'https://nominatim.openstreetmap.org/reverse',
            [
                'lat' => trim($lat),
                'lon' => trim($lng),
                'format' => 'jsonv2'
            ]
        );

        if ($response->successful()) {
            return $response->json()['display_name'] ?? 'Unknown Location';
        }

        return 'Unknown Location';
    }
   public function customers(Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        $query = DB::table('clients');

        if ($year) {
            $query->whereYear('created_at', $year);
        }

        if ($month) {
            $query->whereMonth('created_at', $month);
        }

        $customers = $query
            ->select(
                'id',
                'company_name',
                'address',
                'contact_no',
                'created_at'
            )
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($customers as $customer) {

            $customer->resolved_address = 'N/A';

            if (!empty($customer->address) && str_contains($customer->address, ',')) {

                try {

                [$lat, $lng] = explode(',', $customer->address);

                    $customer->resolved_address = Cache::remember(
                        'resolved_location_' . $customer->address,
                        now()->addDays(30),
                        function () use ($lat, $lng, $customer) {

                            $response = Http::timeout(3)
                                ->withHeaders([
                                    'User-Agent' => 'Laravel Application'
                                ])
                                ->get('https://nominatim.openstreetmap.org/reverse', [
                                    'lat' => trim($lat),
                                    'lon' => trim($lng),
                                    'format' => 'jsonv2'
                                ]);

                            if ($response->successful()) {

                                $data = $response->json();

                                return $data['display_name'] ?? $customer->address;
                            }

                            return $customer->address;
                        }
                    );

                } catch (\Exception $e) {

                    $customer->resolved_address = $customer->address;
                }
            }
        }

        $totalCustomers = $customers->count();

        return view(
            'Reports.customers',
            compact(
                'customers',
                'totalCustomers'
            )
        );
    }
  public function labtest()
{
    $rows = DB::table('lf_06_02')->get();

    // ===================== SAMPLE DESCRIPTION =====================

    $fis = [
        "Crab (Scylla serrata)" => 0,
        "Crablets (Scylla serrata)" => 0,
        "Shrimp fry (L. vannamei)" => 0,
        "Polychaete (Polychaeta)" => 0,
        "Live Tilapia Fingerlings (Oreochromis niloticus)" => 0,
    ];

    $chem = [
        "C.O Treated Frozen Yellowfin Tuna (Thunnus albacares)" => 0,
        "Panga (Thunnus albacares)" => 0,
        "Frozen Yellowfin Cubes Handcut (Thunnus albacares)" => 0,
        "Bonito Rawmat (Sarda sarda)" => 0,
        "Frozen Tuna Bloodmeat (Thunnus albacares)" => 0,
        "Mackerel (Scomber scombrus)" => 0,
        "Skipjack Tuna (Katsuwonus pelamis)" => 0,
        "Frozen Cooked Tuna Loins (Katsuwonus pelamis)" => 0,
        "Frozen Yellowfin Tuna (Thunnus albacares)" => 0,
        "Yellowfin Tuna Product Roe (Thunnus albacares)" => 0,
        "Snack Bar Seaweeds Powder (Eucheuma cottonii)" => 0,
        "Milkfish (Chanos chanos) Bone Dough" => 0,
        "Shrimp fry (L. vannamei) Kropek Crackers" => 0,
        "Dried Smoke Fish Skipjack (Katsuwonus pelamis)" => 0,
        "Pond Water" => 0,
        "Lake Water" => 0,
        "Water" => 0,
    ];

    $micro = [
        "Pangasius Fillet" => 0,
        "Bullet Tuna" => 0,
        "Frozen Yellowfin Tuna" => 0,
        "Fermented Duck Mussel" => 0,
        "Frozen Cooked Tuna Loins Skipjack" => 0,
        "Tuna Panga" => 0,
        "Tuna Tail" => 0,
        "Nile Tilapia" => 0,
        "Skipjack Tuna" => 0,
        "Frozen Cooked Octopus Sliced" => 0,
        "Tuna Entrails" => 0,
        "Mud Clam" => 0,
        "Carp" => 0,
        "Fresh Frozen Yellowfin Tuna Fillet" => 0,
        "Tuna Siomai" => 0,
        "Tuna Sisig" => 0,
        "Tuna Skin Pop (Regular)" => 0,
        "Tuna Skin Pop (Hot & Spicy)" => 0,
        "Tuna Skin Pop (Salted Egg)" => 0,
        "Tuna Belly" => 0,
        "Tuna Cubes" => 0,
        "Tuna Crazy Cut" => 0,
        "Tuna Steak" => 0,
        "Frozen White Shrimp" => 0,
        "Yellowfin Tuna Crazy Cut" => 0,
        "Milkfish Cut" => 0,
        "Pond Water" => 0,
        "Water" => 0,
        "ICE" => 0,
    ];

    // ===================== ANALYSIS REQUESTED =====================

    $fisAnalysis = [
        'EMS/AHPND' => 0,
        'WSSV' => 0,
        'EHP' => 0,
        'TILV' => 0,
        'Parasitological Examination' => 0,
        'Gross Examination' => 0,
    ];

    $chemAnalysis = [
        'HISTAMINE' => 0,
        'MOISTURE CONTENT' => 0,
        'pH' => 0,
        'Alkalinity' => 0,
        'Nitrite Nitrogen' => 0,
        'Calcium Hardness' => 0,
        'Dissolved Oxygen' => 0,
    ];

    $microAnalysis = [
        'APC' => 0,
        'E COLI' => 0,
        'S. AUREUS' => 0,
        'SALMONELLA' => 0,
        'SHIGELLA' => 0,
        'HPC' => 0,
        'TOTAL COLIFORM' => 0,
        'FECAL COLIFORM' => 0,
        'ENTEROCOCCI' => 0,
        'TOTAL LUMINOUS BACTERIAL COUNT' => 0,
        'TOTAL VIBRIO COUNT' => 0,
        '% YELLOW COLONIES' => 0,
        '% GREEN COLONIES' => 0,
    ];

    foreach ($rows as $row) {

        $labs = json_decode($row->lab_unit, true);
        $samples = json_decode($row->sample_description, true);
        $analysis = json_decode($row->analysis_requested, true);

        if (!is_array($labs) || !is_array($samples)) {
            continue;
        }

        if (!is_array($analysis)) {
            $analysis = [];
        }

        $processed = [];

        foreach ($labs as $i => $lab) {

            $lab = is_array($lab) ? ($lab[0] ?? '') : $lab;
            $sample = $samples[$i] ?? '';
            $sample = is_array($sample) ? ($sample[0] ?? '') : $sample;

            $request = $analysis[$i] ?? '';
            $request = is_array($request) ? ($request[0] ?? '') : $request;

            $lab = strtoupper(trim($lab));
            $sample = trim($sample);
            $request = trim($request);

            $key = $row->RLA_no . '|' . $lab . '|' . $sample;

            if (!isset($processed[$key])) {

                $processed[$key] = true;

                if ($lab == 'FIS' && isset($fis[$sample])) {
                    $fis[$sample]++;
                }

                if ($lab == 'CHEM' && isset($chem[$sample])) {
                    $chem[$sample]++;
                }

                if (($lab == 'MIC' || $lab == 'MICRO') && isset($micro[$sample])) {
                    $micro[$sample]++;
                }
            }

            // Analysis Requested Count
            if ($lab == 'FIS' && isset($fisAnalysis[$request])) {
                $fisAnalysis[$request]++;
            }

            if ($lab == 'CHEM' && isset($chemAnalysis[$request])) {
                $chemAnalysis[$request]++;
            }

            if (($lab == 'MIC' || $lab == 'MICRO') && isset($microAnalysis[$request])) {
                $microAnalysis[$request]++;
            }
        }
    }

    return view('Reports.labtest', compact(
        'fis',
        'chem',
        'micro',
        'fisAnalysis',
        'chemAnalysis',
        'microAnalysis'
    ));
}
}
