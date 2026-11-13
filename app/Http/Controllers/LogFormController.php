<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
 use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class LogFormController extends Controller
{
     private function getAddressFromCoordinates($value)
        {
            $value = trim($value ?? '');

            if (!preg_match('/^-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?$/', $value)) {
                return $value;
            }

            [$lat, $lng] = array_map('trim', explode(',', $value));

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'LIMS-RFL12/1.0',
                ])->timeout(10)->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lng,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

                if (!$response->successful()) {
                    return $value;
                }

                $data = $response->json();

                if (!empty($data['display_name'])) {
                    return $data['display_name'];
                }

                return $value;

            } catch (\Exception $e) {
                return $value;
            }
        }
    public function index(){
        return view('LogForm.index');
    }
    public function lf_03_05(Request $request){
        $selectedMonth = (int) $request->query('month', 0);
        $selectedYear = (int) $request->query('year', 0);

        $equipmentQuery = DB::table('lf_03_05');

        if ($selectedMonth >= 1 && $selectedMonth <= 12) {
            $equipmentQuery->where('month', str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT));
        }

        if ($selectedYear >= 1000 && $selectedYear <= 9999) {
            $equipmentQuery->where('year', (string) $selectedYear);
        }

        $equipments = $equipmentQuery->orderByDesc('id')->paginate(10)->withQueryString();
        $years = DB::table('lf_03_05')
            ->whereNotNull('year')
            ->where('year', '<>', '')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('LogForm.lf_03_05', compact('equipments', 'selectedMonth', 'selectedYear', 'years'));
    }
    public function store(Request $request){

        $equipment = DB::table('lf_03_05')->insert([
            'equipment' => $request->equipment,
            'model'  => $request->model,
            'equipment_no'  => $request->equipment_no,
            'location'  => $request->location,
            'month'  => $request->month,
            'year'  => $request->year,
        ]);

        return redirect()->back()->with('success', 'Equipment saved successfully!');
    }
    public function equipments(Request $request, $id)
    {
        $equipment = DB::table('lf_03_05')->where('id', $id)->first();
        $selectedMonth = (int) $request->query('month', 0);
        $selectedYear = (int) $request->query('year', 0);
        $hasSelectedPeriod = $selectedMonth >= 1 && $selectedMonth <= 12
            && $selectedYear >= 1000 && $selectedYear <= 9999;

        $logs = collect();

        if ($hasSelectedPeriod) {
            $logs = DB::table('lf_03_05_logs')
                ->where('equipment_id', $id)
                ->whereMonth('date', $selectedMonth)
                ->whereYear('date', $selectedYear)
                ->orderBy('date')
                ->orderBy('id')
                ->get();
        }

        $years = DB::table('lf_03_05_logs')
            ->where('equipment_id', $id)
            ->whereNotNull('date')
            ->selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if (!$years->contains(now()->year)) {
            $years->prepend(now()->year);
        }

       $laboratoryCodeOptions = DB::table('lf_06_02')
            ->whereNotNull('RLA_no')
            ->whereNotNull('laboratory_code')
            ->orderByDesc('id')
            ->get(['RLA_no', 'laboratory_code'])
            ->flatMap(function ($record) {
                $codes = json_decode($record->laboratory_code, true);
                $codes = is_array($codes) ? $codes : [$record->laboratory_code];

                return collect($codes)
                    ->flatten()
                    ->filter(fn ($code) => trim((string) $code) !== '')
                    ->map(function ($code) use ($record) {
                        $code = trim((string) $code);

                        // Get status based on laboratory_code
                        $laboratoryStatus = DB::table('laboratory_status')
                            ->where('laboratory_code', $code)
                            ->value('status');

                        // Only include laboratory codes with status = 4
                        if ((int) $laboratoryStatus !== 4) {
                            return null;
                        }

                        return [
                            'code' => $code,
                            'rla_no' => (string) $record->RLA_no,
                            'status' => $laboratoryStatus,
                        ];
                    })
                    ->filter();
            })
            ->unique(fn ($option) => $option['code'] . '|' . $option['rla_no'])
            ->values();

        return view('LogForm.equipments_logbook', compact('logs', 'id', 'equipment', 'selectedMonth', 'selectedYear', 'hasSelectedPeriod', 'years', 'laboratoryCodeOptions'));
    }
    public function equipment_store(Request $request)
    {
        $equipmentId = $request->equipment_id;
        $selectedMonth = (int) $request->input('filter_month', 0);
        $selectedYear = (int) $request->input('filter_year', 0);
        $hasSelectedPeriod = $selectedMonth >= 1 && $selectedMonth <= 12
            && $selectedYear >= 1000 && $selectedYear <= 9999;

        if (!$hasSelectedPeriod) {
            return back()->withErrors(['period' => 'Select a month and year before saving log entries.']);
        }

        $selectedPeriod = sprintf('%04d-%02d', $selectedYear, $selectedMonth);
        $submittedIds = [];

        $totalRows = count($request->date ?? []);

        for ($i = 0; $i < $totalRows; $i++) {
            $logId                  = $request->log_id[$i] ?? null;
            $day                    = trim($request->date[$i] ?? '');
            $clean_equipment        = trim($request->clean_equipment[$i] ?? '');
            $check_powersupply      = trim($request->check_powersupply[$i] ?? '');
            $switchon_equipment     = trim($request->switchon_equipment[$i] ?? '');
            $shutdown_equipment     = trim($request->shutdown_equipment[$i] ?? '');
            $preventive_maintenance = trim($request->preventive_maintenance[$i] ?? '');
            $name_analyst           = trim($request->name_analyst[$i] ?? '');
            $analysis               = trim($request->analysis[$i] ?? '');
            $RLA_no                 = trim($request->RLA_no[$i] ?? '');
            $laboratory_code        = trim($request->laboratory_code[$i] ?? '');
            $remarks                = trim($request->remarks[$i] ?? '');

            $isEmpty =
                $day === '' &&
                $clean_equipment === '' &&
                $check_powersupply === '' &&
                $switchon_equipment === '' &&
                $shutdown_equipment === '' &&
                $preventive_maintenance === '' &&
                $name_analyst === '' &&
                $analysis === '' &&
                $RLA_no === '' &&
                $laboratory_code === '' &&
                $remarks === '';

            if ($isEmpty) {
                continue;
            }

            if (!ctype_digit($day) || !checkdate($selectedMonth, (int) $day, $selectedYear)) {
                return back()->withErrors(['date' => 'Enter a valid day for the selected month and year.'])->withInput();
            }

            $date = sprintf('%s-%02d', $selectedPeriod, (int) $day);

            $data = [
                'equipment_id'           => $equipmentId,
                'date'                   => $date,
                'clean_equipment'        => $clean_equipment,
                'check_powersupply'      => $check_powersupply,
                'switchon_equipment'     => $switchon_equipment,
                'shutdown_equipment'     => $shutdown_equipment,
                'preventive_maintenance' => $preventive_maintenance,
                'name_analyst'           => $name_analyst,
                'analysis'               => $analysis,
                'RLA_no'                 => $RLA_no,
                'laboratory_code'        => $laboratory_code,
                'remarks'                => $remarks,
                'updated_at'             => now(),
            ];

            if (!empty($logId)) {
                DB::table('lf_03_05_logs')
                    ->where('id', $logId)
                    ->where('equipment_id', $equipmentId)
                    ->update($data);

                $submittedIds[] = $logId;
            } else {
                $newId = DB::table('lf_03_05_logs')->insertGetId(array_merge($data, [
                    'created_at' => now(),
                ]));

                $submittedIds[] = $newId;
            }
        }

        DB::table('lf_03_05_logs')
            ->where('equipment_id', $equipmentId)
            ->whereMonth('date', $selectedMonth)
            ->whereYear('date', $selectedYear)
            ->whereNotIn('id', $submittedIds)
            ->delete();

        return redirect()->route('equipments_book.index', [
            'id' => $equipmentId,
            'month' => $selectedMonth,
            'year' => $selectedYear,
        ])->with('success', 'Saved successfully!');
    }
    public function scan(Request $request)
    {
     
        $equipment = DB::table('lf_03_05')
                        ->where('equipment_no',$request->qr)
                        ->first();
     
        if(!$equipment){

            return response()->json([
                'status'=>false,
                'message'=>'Equipment not found.'
            ]);

        }

        DB::table('lf_03_05_logs')->insert([

            'equipment_id' => $equipment->id,

            'date' => now()->toDateString(),

            'clean_equipment' => 'Done',

            'check_powersupply' => 'Done',

            'switchon_equipment' => 'Done',

            'shutdown_equipment' => 'Done',

            'preventive_maintenance' => 'Checked',

            'name_analyst' => auth()->user()->f_name,

            'analysis' => json_encode($request->analysis),

            'RLA_no' => $request->rla,

            'laboratory_code' => json_encode($request->laboratory_code),

            'remarks' => 'Done',

            'created_at' => now(),

            'updated_at' => now(),

        ]);
        return response()->json([

            'status'=>true,

            'message'=>'Equipment logged successfully.'

        ]);

    }
    //RLA
  public function generateRlaNo()
    {
        $year = date('Y');

        $latest = DB::table('lf_06_02')
            ->whereYear('created_at', $year)
            ->whereNotNull('RLA_no')
            ->orderByDesc('id')
            ->value('RLA_no');
     
        if ($latest) {
            $lastNumber = (int) $latest;
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $newRlaNo = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        while (
            DB::table('lf_06_02')
                ->whereYear('created_at', $year)
                ->where('RLA_no', $newRlaNo)
                ->exists()
        ) {
            $nextNumber++;
            $newRlaNo = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

        return response()->json([
            'success' => true,
            'RLA_no' => $newRlaNo
        ]);
    }

//  public function lf_06_02()
//     {
//         $clients = DB::table('clients')->get();

//         $rla = DB::table('lf_06_02')
//             ->join('clients', 'lf_06_02.user_id', '=', 'clients.id')
//             ->select(
//                 'lf_06_02.id',
//                 'lf_06_02.user_id',
//                 'lf_06_02.RLA_no',
//                 'lf_06_02.payment',
//                 'lf_06_02.date_collected',
//                 'lf_06_02.sample',
//                 'clients.address',
//                 'clients.contact_no',
//                 'lf_06_02.source_sample',
//                 'lf_06_02.sample_received_by',
//                 'lf_06_02.service_officer',
//                 'lf_06_02.date_received',
//                 'lf_06_02.date_payment',
//                 'lf_06_02.or_no',
//                 'lf_06_02.remarks',
//                 'lf_06_02.status',
//                 'clients.company_name'
//             )
//             ->orderBy('lf_06_02.id', 'desc')
//             ->get();

//         $rla = $rla->map(function ($item) {
//             $item->display_address = $this->getAddressFromCoordinates($item->address ?? '');
//             return $item;
//         });

//         return view('RLA.lf_06_02', compact('clients', 'rla'));
//     }
  public function lf_06_02(Request $request)
    {
        $clients = DB::table('clients')->get();

        $rlaQuery = DB::table('lf_06_02')
            ->join('clients', 'lf_06_02.user_id', '=', 'clients.id')
            ->select(
                'lf_06_02.id',
                'lf_06_02.user_id',
                'lf_06_02.RLA_no',
                'lf_06_02.laboratory_code',
                'lf_06_02.payment',
                'lf_06_02.date_collected',
                'lf_06_02.sample',
                'clients.address',
                'clients.contact_no',
                'lf_06_02.source_sample',
                'lf_06_02.sample_description',
                'lf_06_02.sample_received_by',
                'lf_06_02.service_officer',
                'lf_06_02.date_received',
                'lf_06_02.date_payment',
                'lf_06_02.or_no',
                'lf_06_02.remarks',
                'lf_06_02.status',
                'clients.company_name'
            )
            ->orderBy('lf_06_02.id', 'desc');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $matchingRlaExists = DB::table('lf_06_02')
                ->where('RLA_no', 'like', '%' . $search . '%')
                ->exists();

            $rlaQuery->where(function ($query) use ($search, $matchingRlaExists) {
                if ($matchingRlaExists) {
                    $query->where('lf_06_02.RLA_no', 'like', '%' . $search . '%');
                } else {
                    $query->where('lf_06_02.laboratory_code', 'like', '%' . $search . '%')
                        ->orWhere('clients.company_name', 'like', '%' . $search . '%');
                }
            });
        }

        $rla = $rlaQuery->paginate(10)->withQueryString();
         $laboratoryStatuses = DB::table('laboratory_status')
            ->get()
            ->groupBy('rla_no');
        /*
        |--------------------------------------------------------------------------
        | DISPLAY ONLY ONE SAMPLE DESCRIPTION
        |--------------------------------------------------------------------------
        */
        $rla->getCollection()->transform(function ($item) {

            $sampleDescriptions = json_decode($item->sample_description, true);

            if (is_array($sampleDescriptions) && count($sampleDescriptions) > 0) {
                $item->sample_description = $sampleDescriptions[0] ?? '';
            }

            return $item;
        });

        $users = DB::table('users')->get();

        return view('RLA.lf_06_02', compact('clients', 'rla', 'users','laboratoryStatuses'));
    }
    public function getLatestLaboratoryCode()
        {
            $year = date('y');
            $prefix = 'RFL12-' . $year . '-';

            $records = DB::table('lf_06_02')
                ->whereNotNull('laboratory_code')
                ->pluck('laboratory_code');

            $highestNumber = 0;
            $latestCode = null;

            foreach ($records as $record) {
            $codes = json_decode($record, true);

            if (!is_array($codes)) {
                continue;
            }

            foreach ($codes as $code) {
                if (!is_string($code)) {
                    continue;
                }

                $code = trim($code);

                if (preg_match('/^' . preg_quote($prefix, '/') . '(\d{3})$/', $code, $match)) {
                    $number = (int) $match[1];

                    if ($number > $highestNumber) {
                        $highestNumber = $number;
                        $latestCode = $code;
                    }
                }
            }
        }

        $nextNumber = $highestNumber + 1;

        return response()->json([
            'success' => true,
            'latest_code' => $latestCode,
            'highest_number' => $highestNumber,
            'next_code' => $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT),
            'next_number' => $nextNumber,
            'prefix' => $prefix,
        ]);
    }   
    public function getUserInfo($id){
        $Client = DB::table('clients')->find($id);

        if (!$Client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        return response()->json([
            'company_name' => $Client->company_name ?? '',
            'contact_no' => $Client->contact_no ?? '',
            'address' => $Client->address ?? '',
            'source_sample' => $Client->source_sample ?? '',
        ]);
    }
     public function lf_06_02_store(Request $request)
        {
            // dd($request->all());

            $labCodes = $request->laboratory_code ?? [];
            $descriptions = $request->sample_description ?? [];
            $sampleCodes = $request->sample_code ?? [];
            $labUnits = $request->lab_unit ?? [];
            $analysisDescriptions = $request->analysis_description ?? [];
            $analyses = $request->analysis_requested ?? [];
            $methods = $request->test_method ?? [];

            $finalLabCodes = [];
            $finalDescriptions = [];
            $finalSampleCodes = [];
            $finalLabUnits = [];
            $finalAnalysisDescriptions = [];
            $finalAnalyses = [];
            $finalMethods = [];

            $rowCount = max(
                count($labCodes),
                count($descriptions),
                count($sampleCodes),
                count($labUnits),
                count($analysisDescriptions),
                count($analyses),
                count($methods)
            );

            for ($i = 0; $i < $rowCount; $i++) {
                $lab = $labCodes[$i] ?? null;
                $desc = $descriptions[$i] ?? null;
                $sample = $sampleCodes[$i] ?? null;
                $labUnit = $labUnits[$i] ?? null;
                $analysisDescription = $analysisDescriptions[$i] ?? null;
                $analysis = $analyses[$i] ?? [];
                $method = $methods[$i] ?? null;

                if (!is_array($analysis)) {
                    $analysis = $analysis ? [$analysis] : [];
                }

                $analysis = array_values(array_filter($analysis, function ($value) {
                    return $value !== null && $value !== '';
                }));

                if (
                    empty($lab) &&
                    empty($desc) &&
                    empty($sample) &&
                    empty($labUnit) &&
                    empty($analysisDescription) &&
                    empty($analysis) &&
                    empty($method)
                ) {
                    continue;
                }

                $finalLabCodes[] = $lab;
                $finalDescriptions[] = $desc;
                $finalSampleCodes[] = $sample;
                $finalLabUnits[] = $labUnit;
                $finalAnalysisDescriptions[] = $analysisDescription;
                $finalAnalyses[] = $analysis;
                $finalMethods[] = $method;
            }

            DB::table('lf_06_02')->insert([
                'user_id' => $request->user_id,
                'RLA_no' => $request->RLA_no,
                'terms_accepted' => $request->terms_accepted,
                'source_sample' => $request->source_sample,
                'sample' => $request->sample,
                'date_collected' => $request->date_collected,
                 'retrieve_sample' => $request->retrieve_sample,
                'release_date' => $request->release_date,
                'laboratory_code' => json_encode($finalLabCodes),
                'sample_description' => json_encode($finalDescriptions),
                'sample_code' => json_encode($finalSampleCodes),
                'lab_unit' => json_encode($finalLabUnits),
                'analysis_description' => json_encode($finalAnalysisDescriptions),
                'analysis_requested' => json_encode($finalAnalyses),
                'test_method' => json_encode($finalMethods),

                'sample_received_by' => $request->sample_received_by,
                'service_officer' => $request->service_officer,
                'date_received' => $request->date_received,
                'payment' => $request->payment,
                'date_payment' => $request->date_payment,
                'or_no' => $request->or_no,
                'remarks' => $request->remarks,
                'created_at' => now(),
                'updated_at' => now(),
                
            ]);
           foreach ($finalLabCodes as $laboratoryCode) {

                if (empty($laboratoryCode)) {
                    continue;
                }

                DB::table('laboratory_status')->insert([
                    'laboratory_code' => $laboratoryCode,
                    'rla_no' => $request->RLA_no,
                    'status' => 0,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return redirect()->back()->with('success', 'Saved successfully.');
        }
  public function edit($id)
    {
        $rla = DB::table('lf_06_02')->where('id', $id)->first();

        if (!$rla) {
            return response()->json([
                'status' => false,
                'message' => 'Record not found.'
            ], 404);
        }

        $client = DB::table('clients')->where('id', $rla->user_id)->first();

        $laboratoryCodes = !empty($rla->laboratory_code) 
            ? json_decode($rla->laboratory_code, true) 
            : [];

        $sampleDescriptions = !empty($rla->sample_description) 
            ? json_decode($rla->sample_description, true) 
            : [];

        $sampleCodes = !empty($rla->sample_code) 
            ? json_decode($rla->sample_code, true) 
            : [];

        $labUnits = !empty($rla->lab_unit) 
            ? json_decode($rla->lab_unit, true) 
            : [];

        $analysisDescriptions = !empty($rla->analysis_description) 
            ? json_decode($rla->analysis_description, true) 
            : [];

        $analysisRequested = !empty($rla->analysis_requested) 
            ? json_decode($rla->analysis_requested, true) 
            : [];

        $testMethods = !empty($rla->test_method) 
            ? json_decode($rla->test_method, true) 
            : [];

        $rows = [];

        $maxRows = max(
            count($laboratoryCodes),
            count($sampleDescriptions),
            count($sampleCodes),
            count($labUnits),
            count($analysisDescriptions),
            count($analysisRequested),
            count($testMethods)
        );

        for ($i = 0; $i < $maxRows; $i++) {
            $analysis = $analysisRequested[$i] ?? [];

            if (!is_array($analysis)) {
                $analysis = !empty($analysis) ? [$analysis] : [];
            }

            $rows[] = [
                'laboratory_code' => $laboratoryCodes[$i] ?? '',
                'sample_description' => $sampleDescriptions[$i] ?? '',
                'sample_code' => $sampleCodes[$i] ?? '',
                'lab_unit' => $labUnits[$i] ?? '',
                'analysis_description' => $analysisDescriptions[$i] ?? '',
                'analysis_requested' => $analysis,
                'test_method' => $testMethods[$i] ?? '',
            ];
        }

        $payment = DB::table('lf_06_03')
            ->where('lf_06_02_id', $id)
            ->first();

        return response()->json([
            'status' => true,
            'id' => $rla->id,
            'edit_user_id' => $rla->user_id ?? '',
            'edit_company_name' => $client->company_name ?? '',
            'edit_RLA_no' => $rla->RLA_no ?? '',
            'edit_location' => $client->address ?? '',
            'edit_contact_no' => $client->contact_no ?? '',
            'edit_source_sample' => $rla->source_sample ?? '',
            'edit_sample' => $rla->sample ?? '',
            'edit_date_collected' => $rla->date_collected ?? '',
            'edit_sample_received_by' => $rla->sample_received_by ?? '',
            'edit_service_officer' => $rla->service_officer ?? '',
            'edit_date_received' => $rla->date_received ?? '',
            'edit_payment' => isset($payment->grand_total)
                ? number_format($payment->grand_total, 2)
                : '0.00',
            'edit_date_payment' => $rla->date_payment ?? '',
            'edit_or_no' => $rla->or_no ?? '',
            'edit_remarks' => $rla->remarks ?? '',
            'rows' => $rows,
        ]);
    }
   public function update(Request $request, $id)
    {
        $rla = DB::table('lf_06_02')->where('id', $id)->first();

        if (!$rla) {
            return redirect()->back()->with('error', 'Record not found.');
        }

        $labCodes = $request->edit_laboratory_code ?? [];
        $descriptions = $request->edit_sample_description ?? [];
        $sampleCodes = $request->edit_sample_code ?? [];
        $labUnits = $request->edit_lab_unit ?? [];
        $analysisDescriptions = $request->edit_analysis_description ?? [];
        $analyses = $request->edit_analysis_requested ?? [];
        $methods = $request->edit_test_method ?? [];

        $finalLabCodes = [];
        $finalDescriptions = [];
        $finalSampleCodes = [];
        $finalLabUnits = [];
        $finalAnalysisDescriptions = [];
        $finalAnalyses = [];
        $finalMethods = [];

        $rowCount = max(
            count($labCodes),
            count($descriptions),
            count($sampleCodes),
            count($labUnits),
            count($analysisDescriptions),
            count($analyses),
            count($methods)
        );

        for ($i = 0; $i < $rowCount; $i++) {
            $lab = $labCodes[$i] ?? null;
            $desc = $descriptions[$i] ?? null;
            $sample = $sampleCodes[$i] ?? null;
            $labUnit = $labUnits[$i] ?? null;
            $analysisDescription = $analysisDescriptions[$i] ?? null;
            $analysis = $analyses[$i] ?? [];
            $method = $methods[$i] ?? null;

            if (!is_array($analysis)) {
                $analysis = !empty($analysis) ? [$analysis] : [];
            }

            $analysis = array_values(array_filter($analysis, function ($value) {
                return $value !== null && $value !== '';
            }));

            if (
                empty($lab) &&
                empty($desc) &&
                empty($sample) &&
                empty($labUnit) &&
                empty($analysisDescription) &&
                empty($analysis) &&
                empty($method)
            ) {
                continue;
            }

            $finalLabCodes[] = $lab;
            $finalDescriptions[] = $desc;
            $finalSampleCodes[] = $sample;
            $finalLabUnits[] = $labUnit;
            $finalAnalysisDescriptions[] = $analysisDescription;
            $finalAnalyses[] = $analysis;
            $finalMethods[] = $method;
        }

        DB::table('lf_06_02')->where('id', $id)->update([
            'user_id' => $request->edit_user_id,
            'RLA_no' => $request->edit_RLA_no,
            'source_sample' => $request->edit_source_sample,
            'sample' => $request->edit_sample,
            'date_collected' => $request->edit_date_collected,

            'laboratory_code' => json_encode($finalLabCodes),
            'sample_description' => json_encode($finalDescriptions),
            'sample_code' => json_encode($finalSampleCodes),
            'lab_unit' => json_encode($finalLabUnits),
            'analysis_description' => json_encode($finalAnalysisDescriptions),
            'analysis_requested' => json_encode($finalAnalyses),
            'test_method' => json_encode($finalMethods),

            'sample_received_by' => $request->edit_sample_received_by,
            'service_officer' => $request->edit_service_officer,
            'date_received' => $request->edit_date_received,
            'payment' => $request->edit_payment,
            'date_payment' => $request->edit_date_payment,
            'or_no' => $request->edit_or_no,
            'remarks' => $request->edit_remarks,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'RLA updated successfully.');
    }
       
      public function downloadPdf($id)
        {
            $rla = DB::table('lf_06_02')->where('id', $id)->first();

            if (!$rla) {
                return redirect()->back()->with('error', 'Record not found.');
            }

            $client = DB::table('clients')->where('id', $rla->user_id)->first();

            $laboratoryCodes = json_decode($rla->laboratory_code ?? '[]', true) ?? [];
            $sampleDescriptions = json_decode($rla->sample_description ?? '[]', true) ?? [];
            $sampleCodes = json_decode($rla->sample_code ?? '[]', true) ?? [];
            $analysisRequested = json_decode($rla->analysis_requested ?? '[]', true) ?? [];
            $testMethods = json_decode($rla->test_method ?? '[]', true) ?? [];

            $maxRows = max(
                count($laboratoryCodes),
                count($sampleDescriptions),
                count($sampleCodes),
                count($analysisRequested),
                count($testMethods)
            );

            $rows = [];

            for ($i = 0; $i < $maxRows; $i++) {
                $analysis = $analysisRequested[$i] ?? '';

                if (is_array($analysis)) {
                    $analysis = implode(', ', array_filter($analysis));
                }

                $rows[] = [
                    'laboratory_code' => $laboratoryCodes[$i] ?? '',
                    'sample_description' => $sampleDescriptions[$i] ?? '',
                    'sample_code' => $sampleCodes[$i] ?? '',
                    'analysis_requested' => $analysis,
                    'test_method' => $testMethods[$i] ?? '',
                ];
            }

           $displayAddress = $this->getAddressFromCoordinates($client->address ?? '');
            // dd( $displayAddress );
            $data = [
                'rla' => $rla,
                'client' => $client,
                'rows' => $rows,
                'displayAddress' => $displayAddress 
            ];

            $pdf = Pdf::loadView('RLA.lf_06_02_download', $data)
                ->setPaper('a4', 'portrait');

            return $pdf->download('RLA-' . $rla->RLA_no . '.pdf');
        }
        public function delete($id){
            return view('confirm-delete', ['id'=> $id]);
        }

        public function rladelete($id){
            try{
                $rla = DB::table('lf_06_02')->where('id', $id)->delete();
                // $rla->delete();
                // ActivityLog::create([
                //     'user_id' => Auth::user()->id,
                //     'activity' => 'Deleted the User Name'. $user->f_name . '' .  $user->l_name,
                //     'time' => now('Asia/Manila'),
                //     'date' => now()->toDateString(), 
                // ]);
              return redirect()->back()->with('success', 'RLA Deleted successfully.');
            }
            catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e){
                return response()->json(['error' => 'RLA not found'], 404);
            }
            catch(\Exception $e){
                return response()->json(['error'=> 'An error occured while deleting the RLA'], 500);
            }
        } 
        //status
       public function updateStatusRLA($id)
        {
            DB::transaction(function () use ($id) {

                // Get the RLA
                $rla = DB::table('lf_06_02')
                    ->where('id', $id)
                    ->first();

                if (!$rla) {
                    abort(404, 'RLA not found.');
                }

                /*
                |--------------------------------------------------------------------------
                | Update ALL laboratory codes with the same RLA No.
                |--------------------------------------------------------------------------
                */

                DB::table('laboratory_status')
                    ->where('rla_no', $rla->RLA_no)
                    ->update([
                        'status' => 1,
                        'updated_at' => now(),
                    ]);

            });

            return redirect()
                ->back()
                ->with('success', 'Successfully updated all laboratory statuses.');
        }
        public function updateStatusOP($id)
        {
             DB::transaction(function () use ($id) {

                // Get the RLA
                $rla = DB::table('lf_06_02')
                    ->where('id', $id)
                    ->first();

                if (!$rla) {
                    abort(404, 'RLA not found.');
                }

                /*
                |--------------------------------------------------------------------------
                | Update ALL laboratory codes with the same RLA No.
                |--------------------------------------------------------------------------
                */

                DB::table('laboratory_status')
                    ->where('rla_no', $rla->RLA_no)
                    ->update([
                        'status' => 2,
                        'updated_at' => now(),
                    ]);

            });

            return redirect()
                ->back()
                ->with('success', 'Successfully updated all laboratory statuses.');
        }
        public function updateStatusJRF(Request $request)
        {
            $validated = $request->validate([
                'laboratory_code' => 'required|string|max:255',
                'rla_no' => 'required|string|max:255',
            ]);

            DB::table('laboratory_status')
                ->where('laboratory_code', $validated['laboratory_code'])
                ->where('rla_no', $validated['rla_no'])
                ->update([
                    'status' => 3,
                    'updated_at' => now(),
                ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Laboratory status successfully updated.'
                );
        }
        public function updateStatusAW(Request $request)
        {
            $validated = $request->validate([
                'laboratory_code' => 'required|string|max:255',
                'rla_no' => 'required|string|max:255',
            ]);

            DB::table('laboratory_status')
                ->where('laboratory_code', $validated['laboratory_code'])
                ->where('rla_no', $validated['rla_no'])
                ->update([
                    'status' => 4,
                    'updated_at' => now(),
                ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Laboratory status successfully updated.'
                );
        }
        
        //Order Payment
        public function lf_06_03(Request $request){
          $clients = DB::table('clients')->get();

            $rlaQuery = DB::table('lf_06_02')
                ->join('clients', 'lf_06_02.user_id', '=', 'clients.id')
                ->select(
                    'lf_06_02.id',
                    'lf_06_02.user_id',
                    'lf_06_02.RLA_no',
                    'lf_06_02.laboratory_code',
                    'lf_06_02.payment',
                    'lf_06_02.date_collected',
                    'lf_06_02.sample',
                    'clients.address',
                    'clients.contact_no',
                    'lf_06_02.source_sample',
                    'lf_06_02.sample_received_by',
                    'lf_06_02.sample_description',
                    'lf_06_02.service_officer',
                    'lf_06_02.date_received',
                    'lf_06_02.date_payment',
                    'lf_06_02.or_no',
                    'lf_06_02.remarks',
                    'lf_06_02.status',
                    'clients.company_name'
                )->orderBy('lf_06_02.id', 'desc');

                $search = trim((string) $request->input('search', ''));
                if ($search !== '') {
                    $matchingRlaExists = DB::table('lf_06_02')
                        ->where('RLA_no', 'like', '%' . $search . '%')
                        ->exists();
                    $rlaQuery->where(function ($query) use ($search, $matchingRlaExists) {
                        if ($matchingRlaExists) {
                            $query->where('lf_06_02.RLA_no', 'like', '%' . $search . '%');
                        } else {
                            $query->where('lf_06_02.laboratory_code', 'like', '%' . $search . '%')
                                ->orWhere('clients.company_name', 'like', '%' . $search . '%');
                        }
                    });
                }

                $rla = $rlaQuery
                // ->where('lf_06_02.status', 1)
                ->paginate(10)
                ->withQueryString();
                $laboratoryStatuses = DB::table('laboratory_status')
                    ->get()
                    ->groupBy('rla_no');
                // dd($laboratoryStatuses);
              $rla->getCollection()->transform(function ($item) {

                $sampleDescriptions = json_decode($item->sample_description, true);

                if (is_array($sampleDescriptions) && count($sampleDescriptions) > 0) {
                    $item->sample_description = $sampleDescriptions[0] ?? '';
                }

                return $item;
            });
            return view('OrderPayment.lf_06_03',compact('clients','rla','laboratoryStatuses'));
        }
        public function payment($id)
            {
                $rla = DB::table('lf_06_02')->where('id', $id)->first();

                if (!$rla) {
                    return response()->json([
                        'status' => false,
                        'message' => 'RLA not found.'
                    ], 404);
                }

                $client = DB::table('clients')->where('id', $rla->user_id)->first();

                $labCodes = json_decode($rla->laboratory_code ?? '[]', true) ?? [];
                $sampleDesc = json_decode($rla->sample_description ?? '[]', true) ?? [];
                $analysisRequested = json_decode($rla->analysis_requested ?? '[]', true) ?? [];

                $payment = DB::table('lf_06_03')
                    ->where('lf_06_02_id', $id)
                    ->first();

                $savedItems = [];

                if ($payment && !empty($payment->items)) {
                    $savedItems = json_decode($payment->items, true) ?? [];
                }

                $allLabCodes = '';

                if ($payment && !empty($payment->laboratory_code)) {
                    $paymentLabCodes = json_decode($payment->laboratory_code, true);

                    if (is_array($paymentLabCodes)) {
                        $allLabCodes = implode(', ', array_filter($paymentLabCodes));
                    } else {
                        $allLabCodes = $payment->laboratory_code;
                    }
                } else {
                    $allLabCodes = implode(', ', array_filter($labCodes));
                }

                
                $firstSampleDescription = '';

                if ($payment && !empty($payment->sample_description)) {
                    $paymentSampleDesc = json_decode($payment->sample_description, true);

                    if (is_array($paymentSampleDesc)) {
                        $firstSampleDescription = $paymentSampleDesc[0] ?? '';
                    } else {
                        $firstSampleDescription = $payment->sample_description;
                    }
                } else {
                    $firstSampleDescription = $sampleDesc[0] ?? '';
                }

                return response()->json([
                    'status' => true,
                    'id' => $rla->id,
                    'user_id' => $rla->user_id ?? '',
                    'company_name' => $payment->company_name ?? ($client->company_name ?? ''),
                    'location' => $payment->address ?? ($client->address ?? ''),
                    'RLA_no' => $payment->RLA_no ?? ($rla->RLA_no ?? ''),
                    'sample' => $payment->sample ?? ($rla->sample ?? ''),

                    // Laboratory code = tanan
                    'first_lab_code' => $allLabCodes,

                    // Sample description = first/isa ra
                    'first_sample_desc' => $firstSampleDescription,
                    'analysis_requested' => $analysisRequested,

                    'issued_by' => $payment->issued_by ?? '',
                    'date_issued' => $payment->date_issued ?? '',
                    'grand_total' => $payment->grand_total ?? '',
                    'signature' => $payment->signature ?? '',

                    'items' => $savedItems,
                    'has_existing_payment' => $payment ? true : false,
                    'payment_id' => $payment->id ?? null,
                ]);
            }
      public function lf_06_03_store(Request $request, $id)
        {
            $rla = DB::table('lf_06_02')->where('id', $id)->first();

            if (!$rla) {
                return redirect()->back()->with('error', 'RLA record not found.');
            }

            $items = [];

            foreach (($request->items ?? []) as $item) {
                $items[] = [
                    'section' => $item['section'] ?? '',
                    'name' => $item['name'] ?? '',
                    'checked' => !empty($item['checked']),
                    'qty' => (float)($item['qty'] ?? 0),
                    'unit' => (float)($item['unit'] ?? 0),
                    'total' => (float)($item['total'] ?? 0),
                ];
            }

            $existing = DB::table('lf_06_03')
                ->where('lf_06_02_id', $id)
                ->first();

            $data = [
                'lf_06_02_id' => $id,
                'user_id' => $request->user_id ?: ($rla->user_id ?? null),
                'RLA_no' => $request->RLA_no,
                'company_name' => $request->company_name,
                'laboratory_code' => $request->laboratory_code,
                'items' => json_encode($items),
                'grand_total' => (float)($request->grand_total ?? 0),
                'issued_by' => $request->issued_by,
                'date_issued' => $request->date_issued,
                'signature' => $request->signature,
                'updated_at' => now(),
            ];
            
            if ($existing) {
                DB::table('lf_06_03')
                    ->where('lf_06_02_id', $id)
                    ->update($data);

                return redirect()->back()->with('success', 'Order of Payment updated successfully.');
            } else {
                $data['created_at'] = now();

                DB::table('lf_06_03')->insert($data);

                return redirect()->back()->with('success', 'Order of Payment saved successfully.');
            }
        }
  public function downloadPdfOP($id)
    {
        $payment = DB::table('lf_06_03')
            ->where('lf_06_02_id', $id)
            ->first();

        if (!$payment) {
            return back()->with('error', 'No payment record found.');
        }

        $rla = DB::table('lf_06_02')
            ->where('id', $id)
            ->first();

        $clientId = $payment->user_id ?? ($rla->user_id ?? null);

        $client = null;
        if ($clientId) {
            $client = DB::table('clients')
                ->where('id', $clientId)
                ->first();
        }
        $sampleDesc = json_decode($rla->sample_description, true);
        $items = json_decode($payment->items ?? '[]', true);
        $displayAddress = $this->getAddressFromCoordinates($client->address ?? '');
        // dd( $displayAddress );
        $data = [
            'payment' => $payment,
            'client' => $client,
            'displayAddress' => $displayAddress ,
            'rla' => $rla,
            'items' => $items,
            'sample' => $sampleDesc[0] ?? '',
        ];
    // dd($data);
        $pdf = Pdf::loadView('OrderPayment.lf_06_03_download', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->download('Order of Payment-' . ($payment->RLA_no ?? 'record') . '.pdf');
    }

    // public function lf_06_04(Request $request) { $clients = DB::table('clients')->get(); $userRole = Auth::user()->role; $records = DB::table('lf_06_02') ->join('clients', 'lf_06_02.user_id', '=', 'clients.id') // ->whereIn('lf_06_02.status', [2, 7]) ->select( 'lf_06_02.id', 'lf_06_02.user_id', 'lf_06_02.RLA_no', 'lf_06_02.payment', 'lf_06_02.date_collected', 'lf_06_02.sample', 'clients.address', 'clients.contact_no', 'lf_06_02.source_sample', 'lf_06_02.sample_received_by', 'lf_06_02.service_officer', 'lf_06_02.date_received', 'lf_06_02.date_payment', 'lf_06_02.or_no', 'lf_06_02.remarks', 'lf_06_02.status', 'clients.company_name', 'lf_06_02.laboratory_code', 'lf_06_02.sample_description', 'lf_06_02.lab_unit' ) ->orderBy('lf_06_02.id', 'desc') ->get(); $rows = collect(); foreach ($records as $record) { $labCodes = json_decode($record->laboratory_code, true) ?? []; $sampleDescs = json_decode($record->sample_description, true) ?? []; $labUnits = json_decode($record->lab_unit, true) ?? []; foreach ($labCodes as $index => $labCode) { $unit = $labUnits[$index] ?? ''; if (($userRole == 2 || $userRole == 4) && $unit != 'FIS') { continue; } if ($userRole == 3 && $unit != 'MIC') { continue; } if ($userRole == 7 && $unit != 'CHEM') { continue; } if (!in_array($userRole, [0, 2, 3, 4, 7])) { continue; } $row = clone $record; $row->lab_code = $labCode; $row->sample_desc = $sampleDescs[$index] ?? ''; $row->lab_unit = $unit; $rows->push($row); } } $perPage = 10; $currentPage = LengthAwarePaginator::resolveCurrentPage(); $rla = new LengthAwarePaginator( $rows->slice(($currentPage - 1) * $perPage, $perPage)->values(), $rows->count(), $perPage, $currentPage, [ 'path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query(), ] ); return view('JobRoutingForm.lf_06_04', compact('clients', 'rla')); }

    //    public function lf_06_04(Request $request)
    //     {
    //         $clients = DB::table('clients')->get();

    //         $userRole = Auth::user()->role;

    //         $records = DB::table('lf_06_02')
    //             ->join('clients', 'lf_06_02.user_id', '=', 'clients.id')
    //             ->select(
    //                 'lf_06_02.id',
    //                 'lf_06_02.user_id',
    //                 'lf_06_02.RLA_no',
    //                 'lf_06_02.payment',
    //                 'lf_06_02.date_collected',
    //                 'lf_06_02.sample',
    //                 'clients.address',
    //                 'clients.contact_no',
    //                 'lf_06_02.source_sample',
    //                 'lf_06_02.sample_received_by',
    //                 'lf_06_02.service_officer',
    //                 'lf_06_02.date_received',
    //                 'lf_06_02.date_payment',
    //                 'lf_06_02.or_no',
    //                 'lf_06_02.remarks',
    //                 'lf_06_02.status',
    //                 'clients.company_name',
    //                 'lf_06_02.laboratory_code',
    //                 'lf_06_02.sample_description',
    //                 'lf_06_02.lab_unit'
    //             )
    //             ->orderBy('lf_06_02.id', 'desc')
    //             ->get();


    //         $rows = collect();


    //         foreach ($records as $record) {

    //             $labCodes = json_decode($record->laboratory_code, true) ?? [];
    //             $sampleDescs = json_decode($record->sample_description, true) ?? [];
    //             $labUnits = json_decode($record->lab_unit, true) ?? [];


    //             foreach ($labCodes as $index => $labCode) {


    //                 $unit = strtoupper(trim($labUnits[$index] ?? ''));

    //                 if (($userRole == 2 || $userRole == 4) && $unit !== 'FIS') {
    //                     continue;
    //                 }


    //                 if ($userRole == 3 && $unit !== 'MIC') {
    //                     continue;
    //                 }


    //                 if ($userRole == 7 && $unit !== 'CHEM') {
    //                     continue;
    //                 }
    //                 if (!in_array($userRole, [0,2,3,4,7])) {
    //                     continue;
    //                 }


    //                 $row = clone $record;

    //                 $row->lab_code = $labCode;
    //                 $row->sample_desc = $sampleDescs[$index] ?? '';
    //                 $row->lab_unit = $unit;


    //                 $rows->push($row);
    //             }
    //         }

    //         $perPage = 10;
    //         $currentPage = LengthAwarePaginator::resolveCurrentPage();

    //         $rla = new LengthAwarePaginator(
    //             $rows->forPage($currentPage, $perPage),
    //             $rows->count(),
    //             $perPage,
    //             $currentPage,
    //             [
    //                 'path' => LengthAwarePaginator::resolveCurrentPath(),
    //                 'query' => request()->query(),
    //             ]
    //         );


    //         return view('JobRoutingForm.lf_06_04', compact('clients', 'rla'));
    //     }
      public function lf_06_04(Request $request)
{
    $clients = DB::table('clients')->get();

    $userRole = Auth::user()->role;

    /*
    |--------------------------------------------------------------------------
    | Get all laboratory statuses
    |--------------------------------------------------------------------------
    */
    $laboratoryStatuses = DB::table('laboratory_status')
        ->get()
        ->keyBy(function ($status) {
            return $status->rla_no . '|' . $status->laboratory_code;
        });


    $records = DB::table('lf_06_02')
        ->join('clients', 'lf_06_02.user_id', '=', 'clients.id')
        ->select(
            'lf_06_02.id',
            'lf_06_02.user_id',
            'lf_06_02.RLA_no',
            'lf_06_02.payment',
            'lf_06_02.date_collected',
            'lf_06_02.sample',
            'clients.address',
            'clients.contact_no',
            'lf_06_02.source_sample',
            'lf_06_02.sample_received_by',
            'lf_06_02.service_officer',
            'lf_06_02.date_received',
            'lf_06_02.date_payment',
            'lf_06_02.or_no',
            'lf_06_02.remarks',
            'clients.company_name',
            'lf_06_02.laboratory_code',
            'lf_06_02.sample_description',
            'lf_06_02.lab_unit'
        )
        ->orderBy('lf_06_02.id', 'desc')
        ->get();


    $rows = collect();


    foreach ($records as $record) {

        $labCodes = json_decode($record->laboratory_code, true) ?? [];
        $sampleDescs = json_decode($record->sample_description, true) ?? [];
        $labUnits = json_decode($record->lab_unit, true) ?? [];


        foreach ($labCodes as $index => $labCode) {

            $unit = strtoupper(trim($labUnits[$index] ?? ''));


            /*
            |--------------------------------------------------------------------------
            | Role filtering
            |--------------------------------------------------------------------------
            */

            if (($userRole == 2 || $userRole == 4) && $unit !== 'FIS') {
                continue;
            }

            if ($userRole == 3 && $unit !== 'MIC') {
                continue;
            }

            if ($userRole == 7 && $unit !== 'CHEM') {
                continue;
            }

            if (!in_array($userRole, [0, 2, 3, 4, 7])) {
                continue;
            }


            $row = clone $record;

            $row->lab_code = $labCode;
            $row->sample_desc = $sampleDescs[$index] ?? '';
            $row->lab_unit = $unit;


            /*
            |--------------------------------------------------------------------------
            | Get status from laboratory_status
            |--------------------------------------------------------------------------
            */

            $labStatus = $laboratoryStatuses->get(
                $record->RLA_no . '|' . $labCode
            );

            $row->status = $labStatus
                ? $labStatus->status
                : 0;


            $rows->push($row);
        }
    }


    $search = trim((string) $request->input('search', ''));
    if ($search !== '') {
        $matchingRlaRows = $rows->filter(function ($row) use ($search) {
            return stripos((string) ($row->RLA_no ?? ''), $search) !== false;
        });
        $rows = $matchingRlaRows->isNotEmpty() ? $matchingRlaRows : $rows->filter(function ($row) use ($search) {
            foreach ([$row->RLA_no ?? '', $row->lab_code ?? '', $row->company_name ?? ''] as $value) {
                if (stripos((string) $value, $search) !== false) return true;
            }
            return false;
        })->values();
    }

    $perPage = 10;

    $currentPage = LengthAwarePaginator::resolveCurrentPage();


    $rla = new LengthAwarePaginator(
        $rows->forPage($currentPage, $perPage),
        $rows->count(),
        $perPage,
        $currentPage,
        [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]
    );


    return view(
        'JobRoutingForm.lf_06_04',
        compact('clients', 'rla')
    );
}  
 public function lf_06_04_store(Request $request, $id)
{
    /*
    |--------------------------------------------------------------------------
    | Get RLA
    |--------------------------------------------------------------------------
    */
    $rla = DB::table('lf_06_02')
        ->where('id', $id)
        ->first();

    if (!$rla) {
        return response()->json([
            'status' => false,
            'message' => 'RLA not found.'
        ], 404);
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Request
    |--------------------------------------------------------------------------
    */
    $validated = $request->validate([

        'user_id' => 'nullable',

        'RLA_no' => 'nullable|string|max:255',

        'laboratory_code' => 'nullable|string|max:255',

        'sample' => 'nullable|string|max:255',

        'receiving_in_date' => 'nullable|date',
        'receiving_in_time' => 'nullable',
        'receiving_out_date' => 'nullable|date',
        'receiving_out_time' => 'nullable',
        'receiving_remarks' => 'nullable|string|max:255',
        'receiving_initials' => 'nullable|string|max:255',

        'prep_in_date' => 'nullable|date',
        'prep_in_time' => 'nullable',
        'prep_out_date' => 'nullable|date',
        'prep_out_time' => 'nullable',
        'prep_results' => 'nullable|string|max:255',
        'prep_recovery' => 'nullable|string|max:255',
        'prep_initials' => 'nullable|string|max:255',

        // Analysis 1
        'analysis_1' => 'nullable|string|max:255',
        'analysis_2' => 'nullable|string|max:255',
        'analysis_3' => 'nullable|string|max:255',
        'analysis_4' => 'nullable|string|max:255',
        'analysis_results' => 'nullable|string|max:255',
        'analysis_recovery' => 'nullable|string|max:255',
        'analysis_initials' => 'nullable|string|max:255',

        // Analysis 2
        'name_analysis_2' => 'nullable|string|max:255',
        'analysis_2_2' => 'nullable|string|max:255',
        'analysis_2_3' => 'nullable|string|max:255',
        'analysis_2_4' => 'nullable|string|max:255',
        'analysis_2_5' => 'nullable|string|max:255',
        'analysis_results_2' => 'nullable|string|max:255',
        'analysis_recovery_2' => 'nullable|string|max:255',
        'analysis_initials_2' => 'nullable|string|max:255',

        // Analysis 3
        'name_analysis_3' => 'nullable|string|max:255',
        'analysis_3_2' => 'nullable|string|max:255',
        'analysis_3_3' => 'nullable|string|max:255',
        'analysis_3_4' => 'nullable|string|max:255',
        'analysis_3_5' => 'nullable|string|max:255',
        'analysis_results_3' => 'nullable|string|max:255',
        'analysis_recovery_3' => 'nullable|string|max:255',
        'analysis_initials_3' => 'nullable|string|max:255',

        // Analysis 4
        'name_analysis_4' => 'nullable|string|max:255',
        'analysis_4_2' => 'nullable|string|max:255',
        'analysis_4_3' => 'nullable|string|max:255',
        'analysis_4_4' => 'nullable|string|max:255',
        'analysis_4_5' => 'nullable|string|max:255',
        'analysis_results_4' => 'nullable|string|max:255',
        'analysis_recovery_4' => 'nullable|string|max:255',
        'analysis_initials_4' => 'nullable|string|max:255',

        // Analysis 5
        'name_analysis_5' => 'nullable|string|max:255',
        'analysis_5_2' => 'nullable|string|max:255',
        'analysis_5_3' => 'nullable|string|max:255',
        'analysis_5_4' => 'nullable|string|max:255',
        'analysis_5_5' => 'nullable|string|max:255',
        'analysis_results_5' => 'nullable|string|max:255',
        'analysis_recovery_5' => 'nullable|string|max:255',
        'analysis_initials_5' => 'nullable|string|max:255',

        // Analysis 6
        'name_analysis_6' => 'nullable|string|max:255',
        'analysis_6_2' => 'nullable|string|max:255',
        'analysis_6_3' => 'nullable|string|max:255',
        'analysis_6_4' => 'nullable|string|max:255',
        'analysis_6_5' => 'nullable|string|max:255',
        'analysis_results_6' => 'nullable|string|max:255',
        'analysis_recovery_6' => 'nullable|string|max:255',
        'analysis_initials_6' => 'nullable|string|max:255',

        'remarks' => 'nullable|string',

        'checked_by' => 'nullable|string|max:255',
        'checked_date' => 'nullable|date',

        'report_in_date' => 'nullable|date',
        'report_in_time' => 'nullable',
        'report_out_date' => 'nullable|date',
        'report_out_time' => 'nullable',
        'report_remarks' => 'nullable|string|max:255',
        'report_initials' => 'nullable|string|max:255',

        'date_approved_release' => 'nullable|date',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Decode laboratory codes from lf_06_02
    |--------------------------------------------------------------------------
    */
    $labCodes = json_decode(
        $rla->laboratory_code ?? '[]',
        true
    ) ?? [];


    /*
    |--------------------------------------------------------------------------
    | Prepare Routing Form Data
    |--------------------------------------------------------------------------
    */
    $data = array_merge($validated, [

        'lf_06_02_id' => $rla->id,

        'user_id' =>
            $request->user_id
            ?? $rla->user_id
            ?? null,

        'RLA_no' =>
            $request->RLA_no
            ?? $rla->RLA_no
            ?? null,

        'sample' =>
            $request->sample
            ?? $rla->sample
            ?? null,

        'laboratory_code' =>
            $request->laboratory_code
            ?? ($labCodes[0] ?? null),
    ]);


    /*
    |--------------------------------------------------------------------------
    | Check Existing Routing Form
    |--------------------------------------------------------------------------
    */
    $existing = DB::table('lf_06_04')
        ->where('lf_06_02_id', $id)
        ->where(
            'laboratory_code',
            $data['laboratory_code']
        )
        ->first();


    /*
    |--------------------------------------------------------------------------
    | Update / Insert Routing Form
    |--------------------------------------------------------------------------
    */
    if ($existing) {

        DB::table('lf_06_04')
            ->where('id', $existing->id)
            ->update(
                array_merge(
                    $data,
                    [
                        'updated_at' => now()
                    ]
                )
            );

        $message = 'Routing form updated successfully.';

    } else {

        DB::table('lf_06_04')
            ->insert(
                array_merge(
                    $data,
                    [
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                )
            );

        $message = 'Routing form saved successfully.';
    }

/*
|--------------------------------------------------------------------------
| Selected Laboratory Code
|--------------------------------------------------------------------------
*/
$requestedLabCode = trim(
    (string) $data['laboratory_code']
);


/*
|--------------------------------------------------------------------------
| Check current laboratory status
|--------------------------------------------------------------------------
*/
$labStatus = null;

if (!empty($requestedLabCode)) {

    $labStatus = DB::table('laboratory_status')
        ->where(
            'laboratory_code',
            $requestedLabCode
        )
        ->first();
}


/*
|--------------------------------------------------------------------------
| Update status 7 -> 8 ONLY
|--------------------------------------------------------------------------
*/
if ($labStatus && (int) $labStatus->status === 7) {

    DB::table('laboratory_status')
        ->where(
            'laboratory_code',
            $requestedLabCode
        )
        ->where('status', 7)
        ->update([
            'status' => 8,
            'updated_at' => now(),
        ]);
}


/*
|--------------------------------------------------------------------------
| Check if this laboratory code is now status 8
|--------------------------------------------------------------------------
*/
$released = DB::table('laboratory_status')
    ->where(
        'laboratory_code',
        $requestedLabCode
    )
    ->where('status', 8)
    ->first();

    /*
    |--------------------------------------------------------------------------
    | Send Release SMS ONLY if laboratory code is already status = 8
    |--------------------------------------------------------------------------
    */
    if ($released) {

        $rlaInfo = DB::table('lf_06_02')
            ->where('id', $id)
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Check SMS Setting
        |--------------------------------------------------------------------------
        */
        $smsNotificationsActive = (bool) DB::table('sms_settings')
            ->where(
                'key',
                'release_notifications'
            )
            ->value('is_active');


        if (
            $smsNotificationsActive
            && $rlaInfo
            && !empty($rlaInfo->user_id)
        ) {

            /*
            |--------------------------------------------------------------------------
            | Get Client
            |--------------------------------------------------------------------------
            */
            $clientInfo = DB::table('clients')
                ->where(
                    'id',
                    $rlaInfo->user_id
                )
                ->first();


            if (
                $clientInfo
                && !empty($clientInfo->contact_no)
            ) {

                $mobile = trim(
                    $clientInfo->contact_no
                );


                /*
                |--------------------------------------------------------------------------
                | Decode Laboratory Codes / Sample Descriptions
                |--------------------------------------------------------------------------
                */
                $labCodes = json_decode(
                    $rlaInfo->laboratory_code ?? '[]',
                    true
                ) ?? [];

                $sampleDescriptions = json_decode(
                    $rlaInfo->sample_description ?? '[]',
                    true
                ) ?? [];


                /*
                |--------------------------------------------------------------------------
                | Find Sample Description of THIS laboratory code
                |--------------------------------------------------------------------------
                */
                $sampleDescription = '';

                $index = array_search(
                    $requestedLabCode,
                    $labCodes,
                    true
                );

                if ($index !== false) {

                    $sampleDescription =
                        $sampleDescriptions[$index]
                        ?? '';
                }


                /*
                |--------------------------------------------------------------------------
                | Release Date
                |--------------------------------------------------------------------------
                */
                $releaseDate = !empty(
                    $request->date_approved_release
                )
                    ? date(
                        'F d, Y',
                        strtotime(
                            $request->date_approved_release
                        )
                    )
                    : date('F d, Y');


                /*
                |--------------------------------------------------------------------------
                | SMS Message
                |--------------------------------------------------------------------------
                */
                $messages =
                    "Good day Mr./Ms. {$clientInfo->company_name}, "
                    . "Your Sample Lab Test {$sampleDescription} "
                    . "is ready for release on {$releaseDate}. "
                    . "You may claim it at BFAR General Santos City "
                    . "during office hours (8:00 AM - 5:00 PM). "
                    . "Thank you.";


                /*
                |--------------------------------------------------------------------------
                | Send SMS
                |--------------------------------------------------------------------------
                */
                try {

                    $smsClient =
                        new \GuzzleHttp\Client();


                    $response =
                        $smsClient->request(
                            'POST',
                            'https://api.m360.com.ph/v3/api/broadcast',
                            [
                                'verify' => false,

                                'http_errors' => false,

                                'form_params' => [

                                    'app_key' =>
                                        'KDcofKlw6ePUl48b',

                                    'app_secret' =>
                                        'GJHPtrwjDHtsMls24Bgt73lCHqJPoY7J',

                                    'msisdn' =>
                                        $mobile,

                                    'content' =>
                                        $messages,

                                    'shortcode_mask' =>
                                        'BFAR12_LAB',
                                ]
                            ]
                        );

                } catch (\Exception $e) {

                    Log::error(
                        'SMS Sending Failed: '
                        . $e->getMessage()
                    );
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */
    return response()->json([
        'status' => true,
        'message' => $message
    ]);
}
        public function jobrouting(Request $request,$id)
        {
            $laboratoryCode = $request->laboratory_code;
            // dd('dsad');
            $rla = DB::table('lf_06_02')->where('id', $id)->first();

            if (!$rla) {
                return response()->json([
                    'status' => false,
                    'message' => 'RLA not found.'
                ], 404);
            }

            $client = DB::table('clients')->where('id', $rla->user_id)->first();
            $labCodes = json_decode($rla->laboratory_code ?? '[]', true) ?? [];
        
            $payment = DB::table('lf_06_03')
                ->where('lf_06_02_id', $id)
                ->first();

            $savedItems = [];
            if ($payment && !empty($payment->items)) {
                $savedItems = json_decode($payment->items, true) ?? [];
            }
        
            $routingForm = DB::table('lf_06_04')
                ->where('lf_06_02_id', $id)
                ->where('laboratory_code', $laboratoryCode)
                ->first();
            
            $rlaSample = json_decode($rla->sample_description ?? '[]', true) ?? [];
                     $index = array_search($laboratoryCode, $labCodes);
            $analysisRequested = json_decode($rla->analysis_requested ?? '[]', true) ?? [];

            $index = array_search($laboratoryCode, $labCodes);

            $currentAnalysis = [];

            if ($index !== false) {
                $currentAnalysis = $analysisRequested[$index] ?? [];

                if (!is_array($currentAnalysis)) {
                    $currentAnalysis = [$currentAnalysis];
                }
            }
            // dd($currentAnalysis);
            return response()->json([
                'status' => true,
                'job_status' => $rla->status,
                'id' => $rla->id,
                'user_id' => $rla->user_id ?? '',
                'company_name' => $payment->company_name ?? ($client->company_name ?? ''),
                'location' => $payment->address ?? ($client->address ?? ''),
                'RLA_no' => $routingForm->RLA_no ?? ($payment->RLA_no ?? ($rla->RLA_no ?? '')),              
                'analysis_requested' => $currentAnalysis,

                'sample_desc' => $index !== false ? ($rlaSample[$index] ?? '') : '',
                'first_lab_code' => $laboratoryCode,
                'issued_by' => $payment->issued_by ?? '',
                'date_issued' => $payment->date_issued ?? '',
                'grand_total' => $payment->grand_total ?? '',
                'signature' => $payment->signature ?? '',
                'items' => $savedItems,
                'has_existing_payment' => $payment ? true : false,
                'payment_id' => $payment->id ?? null,            
                'routing_form' => $routingForm,
                'has_existing_routing' => $routingForm ? true : false,
            ]);
          
        }
    // public function downloadPdfRoutingForm($id)
    // {
    //     $rla = DB::table('lf_06_02')->where('id', $id)->first();

    //     if (!$rla) {
    //         abort(404, 'RLA not found.');
    //     }

    //     $client = DB::table('clients')->where('id', $rla->user_id)->first();
    //     $labCodes = json_decode($rla->laboratory_code ?? '[]', true) ?? [];

    //     $payment = DB::table('lf_06_03')
    //         ->where('lf_06_02_id', $id)
    //         ->first();

    //     $routingForm = DB::table('lf_06_04')
    //         ->where('lf_06_02_id', $id)
    //         ->first();
    //      $sampleDesc = json_decode($rla->sample_description?? '[]', true) ?? [];
    //     //   $rlaSample = json_decode($rla->sample_description ?? '[]', true) ?? [];
    //     $data = [
    //         'rla' => $rla,
    //         'client' => $client,
    //         'payment' => $payment,
    //         'routingForm' => $routingForm,
    //         'RLA_no' => $routingForm->RLA_no ?? ($payment->RLA_no ?? ($rla->RLA_no ?? '')),
    //         'sample' => $sampleDesc[0] ?? '',
    //         'laboratory_code' => $routingForm->laboratory_code ?? ($payment->laboratory_code ?? ($labCodes[0] ?? '')),
    //         'user_id' => $rla->user_id ?? '',
    //     ];

    //     $pdf = Pdf::loadView('JobRoutingForm.lf_06_04_download', $data)
    //         ->setPaper('a4', 'landscape');

    //     return $pdf->download('Job Routing Form-' . ($data['RLA_no'] ?: $id) . '.pdf');
    // }
    public function downloadPdfRoutingForm(Request $request, $id)
{
        $laboratoryCode = $request->laboratory_code;

        $rla = DB::table('lf_06_02')->where('id', $id)->first();

        if (!$rla) {
            abort(404, 'RLA not found.');
        }

        $client = DB::table('clients')->where('id', $rla->user_id)->first();

        $labCodes = json_decode($rla->laboratory_code ?? '[]', true) ?? [];
        $sampleDesc = json_decode($rla->sample_description ?? '[]', true) ?? [];

        $index = array_search($laboratoryCode, $labCodes);

        $payment = DB::table('lf_06_03')
            ->where('lf_06_02_id', $id)
            ->first();

        $routingForm = DB::table('lf_06_04')
            ->where('lf_06_02_id', $id)
            ->where('laboratory_code', $laboratoryCode)
            ->first();

        $data = [
            'rla' => $rla,
            'client' => $client,
            'payment' => $payment,
            'routingForm' => $routingForm,
            'sample' => $index !== false ? ($sampleDesc[$index] ?? '') : '',
            'laboratory_code' => $laboratoryCode,
            'RLA_no' => $routingForm->RLA_no ?? ($payment->RLA_no ?? $rla->RLA_no),
            'routing_form' => $routingForm, 'has_existing_routing' => $routingForm ? true : false,
        ];

        $pdf = Pdf::loadView('JobRoutingForm.lf_06_04_download', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('Job Routing Form-'.$laboratoryCode.'.pdf');
    }
    //Sample disposal logbook
  public function sample_disposal_logbook_index()
    {
        $userRole = Auth::user()->role;

        $samples = DB::table('sample_storage_logbooks')
            ->orderBy('id', 'desc')
            ->get();

        $filteredSamples = collect();

        foreach ($samples as $sample) {
            $rlaList = DB::table('lf_06_02')
                ->get();


            foreach ($rlaList as $rla) {

                $laboratoryCodes = json_decode($rla->laboratory_code, true) ?? [];
                $labUnits = json_decode($rla->lab_unit, true) ?? [];


                foreach ($laboratoryCodes as $index => $code) {

                    if ($code == $sample->lab_code) {


                        $unit = $labUnits[$index] ?? null;
                        if ($userRole == 2 || $userRole == 4) {

                            // FIS ACCOUNT
                            if ($unit !== 'FIS') {
                                continue 2;
                            }

                        } elseif ($userRole == 3) {

                            // MICRO ACCOUNT
                            if ($unit !== 'MIC') {
                                continue 2;
                            }

                        } elseif ($userRole == 7) {

                            // CHEM ACCOUNT
                            if ($unit !== 'CHEM') {
                                continue 2;
                            }

                        }

                        $sample->lab_unit = $unit;

                        $filteredSamples->push($sample);
                        break 2;
                    }
                }
            }
        }

        $page = request()->get('page', 1);
        $perPage = 10;

        $search = trim((string) request()->input('search', ''));
        if ($search !== '') {
            $filteredSamples = $filteredSamples->filter(function ($sample) use ($search) {
                return stripos((string) ($sample->lab_code ?? ''), $search) !== false
                    || stripos((string) ($sample->sample_desc ?? ''), $search) !== false;
            })->values();
        }

        $samples = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredSamples->forPage($page, $perPage),
            $filteredSamples->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );


        return view('SampleDisposalLogbook.index', compact('samples'));
    }   
    public function getRLAInfo($id)
    {
        $data = DB::table('lf_06_02')->where('id', $id)->first();

        return response()->json([
            'sample_description' => json_decode($data->sample_description, true)[0] ?? ''
        ]);
    }

public function sample_disposal_logbook_create()
{
    $userRole = (int) auth()->user()->role;

    /*
    |--------------------------------------------------------------------------
    | Get laboratory codes with status = 4
    |--------------------------------------------------------------------------
    */
    $activeLaboratoryCodes = DB::table('laboratory_status')
        ->where('status', 4)
        ->whereNotNull('laboratory_code')
        ->pluck('laboratory_code')
        ->map(function ($code) {
            return strtoupper(trim((string) $code));
        })
        ->filter()
        ->unique()
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Get RLA
    |--------------------------------------------------------------------------
    */
    $RLA = DB::table('lf_06_02')
        ->whereNotNull('laboratory_code')
        ->get()
        ->flatMap(function ($item) use ($userRole, $activeLaboratoryCodes) {

            /*
            |--------------------------------------------------------------------------
            | Decode JSON fields
            |--------------------------------------------------------------------------
            */
            $laboratoryCodes = json_decode($item->laboratory_code, true) ?? [];
            $labUnits = json_decode($item->lab_unit, true) ?? [];
            $sampleDescriptions = json_decode($item->sample_description, true) ?? [];

            $result = [];

            /*
            |--------------------------------------------------------------------------
            | Loop laboratory codes
            |--------------------------------------------------------------------------
            */
            foreach ($laboratoryCodes as $index => $code) {

                $code = trim((string) $code);

                /*
                |--------------------------------------------------------------------------
                | Check if laboratory code has status = 4
                |--------------------------------------------------------------------------
                */
                if (!$activeLaboratoryCodes->contains(strtoupper($code))) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Get corresponding lab unit using SAME INDEX
                |--------------------------------------------------------------------------
                */
                $unit = $labUnits[$index] ?? null;

                $unit = strtoupper(trim((string) $unit));

                if (!$unit) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Filter based on logged-in user's role
                |--------------------------------------------------------------------------
                */
                if ($userRole == 2 || $userRole == 4) {

                    // FIS ACCOUNT
                    if ($unit !== 'FIS') {
                        continue;
                    }

                } elseif ($userRole == 3) {

                    // MICRO ACCOUNT
                    if ($unit !== 'MIC') {
                        continue;
                    }

                } elseif ($userRole == 7) {

                    // CHEM ACCOUNT
                    if ($unit !== 'CHEM') {
                        continue;
                    }

                } elseif ($userRole != 0) {

                    // Other accounts
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Create individual RLA item
                |--------------------------------------------------------------------------
                */
                $newItem = clone $item;

                // Individual laboratory code
                $newItem->laboratory_code = $code;

                // Corresponding lab unit
                $newItem->lab_unit = $unit;

                // Keep sample descriptions decoded
                $newItem->sample_description = $sampleDescriptions;

                $result[] = $newItem;
            }

            return $result;
        })
        ->values();

    return view(
        'SampleDisposalLogbook.create',
        compact('RLA')
    );
}
    public function sample_disposal_logbook_store(Request $request)
{
    DB::table('sample_storage_logbooks')->insert([
        'lab_code'      => $request->lab_code,
        'sample_desc'   => $request->sample_desc,
        'date_received' => $request->date_received,
        'date_stored'   => $request->date_stored,
        'date_analyzed' => $request->date_analyzed,
        'date_disposal' => $request->date_disposal,
        'disposed_by'   => $request->disposed_by,
        'checked_by'    => $request->checked_by,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Update laboratory status
    |--------------------------------------------------------------------------
    | Find the exact laboratory_code and change its status from 4 to 7.
    */
    DB::table('laboratory_status')
        ->where('laboratory_code', $request->lab_code)
        ->update([
            'status' => 7,
        ]);

    return redirect()
        ->route('sample_logbook')
        ->with('success', 'Logbook Save Successfully');
}
  public function edit_sample($id)
    {
        $sample = DB::table('sample_storage_logbooks')->where('id', $id)->first();

        if (!$sample) {
            return redirect()->back()->with('error', 'Record not found.');
        }

        $RLA = DB::table('lf_06_02')->get()->map(function ($item) {
            $decoded = json_decode($item->laboratory_code, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $item->lab_code_display = $decoded[0] ?? '';
            } else {
                $item->lab_code_display = $item->laboratory_code ?? '';
            }

            return $item;
        });

        return view('SampleDisposalLogbook.edit', compact('sample', 'RLA'));
    }
    public function update_sample(Request $request)
    {
        // dd($request->all());
        DB::table('sample_storage_logbooks')
            ->where('id', $request->id)
            ->update([
                'lab_code' => $request->lab_code,
                'sample_desc' => $request->sample_desc,
                'date_received' => $request->date_received,
                'date_stored' => $request->date_stored,
                'date_analyzed' => $request->date_analyzed,
                'date_disposal' => $request->date_disposal,
                'disposed_by' => $request->disposed_by,
                'checked_by' => $request->checked_by,
                'updated_at' => now(),
            ]);

        return redirect()->route('sample_logbook')->with('success', 'Record updated successfully.');
    }
    public function deleteSample($id){
        return view('confirm-delete', ['id'=> $id]);
    }

    public function hardDelete_sample($id){
        try{
            $sample = DB::table('sample_storage_logbooks')->where('id',$id)->delete();

            // ActivityLog::create([
            //     'user_id' => Auth::user()->id,
            //     'activity' => 'Deleted the User Name'. $user->f_name . '' .  $user->l_name,
            //     'time' => now('Asia/Manila'),
            //     'date' => now()->toDateString(), 
            // ]);
            return redirect()->route('sample_logbook')->with('success', 'Sample Storage & Disposal Logbook Deleted Successfully!');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json(['error' => 'client not found'], 404);
        }
        catch(\Exception $e){
            return response()->json(['error'=> 'An error occured while deleting the client'], 500);
        }
    } 
    public function downloadPdfSample()
        {
            $records = DB::Table('sample_storage_logbooks')->orderBy('date_received', 'asc')->get();
             if ($records->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No data available to download.'
                ], 404);
            }
            $logoPath = public_path('assets/images/bfarlogo.png');
            $logoSrc = null;
            

            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoSrc = 'data:image/png;base64,' . $logoData;
            }

            $chunkedRecords = $records->chunk(18);

            $data = [
                'records' => $records,
                'chunkedRecords' => $chunkedRecords,
                'logoSrc' => $logoSrc,
                'date_adopted' => '13 Aug 2019',
                'document_type' => 'Laboratory Record',
                'revision_no' => '0',
                'document_code' => 'LF 07-FIS-01',
                'title' => 'SAMPLE STORAGE AND DISPOSAL LOGBOOK',
            ];

            $pdf = Pdf::loadView('SampleDisposalLogbook.download', $data)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'Times New Roman',
                ]);

            return $pdf->download('Sample Storage and Disposal Logbook.pdf');
    }
    //equipment usage 
    public function equipments_usage(Request $request)
    {
        $userRole = Auth::user()->role;
        $selectedMonth = (int) $request->query('month', 0);
        $selectedYear = (int) $request->query('year', 0);

        $equipments = DB::table('lf_03_05');


        if ($userRole == 2 || $userRole == 4) {
            
            $equipments->where('equipment_no', 'like', 'FIS-%');

        } elseif ($userRole == 3) {
           
            $equipments->where('equipment_no', 'like', 'MIC-%');

        } elseif ($userRole == 7) {
            
            $equipments->where('equipment_no', 'like', 'CHE-%');

        } elseif ($userRole == 0) {
           

        } else {
            $equipments->whereRaw('1 = 0');
        }

        $years = (clone $equipments)
            ->whereNotNull('year')
            ->where('year', '<>', '')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if ($selectedMonth >= 1 && $selectedMonth <= 12) {
            $equipments->where('month', str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT));
        }

        if ($selectedYear >= 1000 && $selectedYear <= 9999) {
            $equipments->where('year', (string) $selectedYear);
        }

        $equipments = $equipments
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();


        return view('LogForm.lf_03_05', compact('equipments', 'selectedMonth', 'selectedYear', 'years'));
    }
    // hardDelete_lf_03_05
    public function hardDelete_lf_03_05($id)
    {
        try {
            $equipment = DB::table('lf_03_05')->where('id', $id)->delete();

            return redirect()->route('equipments_usage')->with('success', 'Equipment Usage Record Deleted Successfully!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Record not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while deleting the record'], 500);
        }
    }
    //equipments plan
    // public function equipment_plan_index(){
    //     return view('EquipmentPlan.index');
    // }
    // public function equipment_plan_create(){
    //     return view('EquipmentPlan.create');
    // }
    // public function equipment_plan_store(Request $request)
    // {
    //     $data = [
    //         'unit' => $request->unit ?: null,
    //         'equipment_code' => $request->equipment_code ?: null,
    //         'equipment_name' => $request->equipment_name ?: null,
    //         'manufacturer' => $request->manufacturer ?: null,
    //         'brand_model_no' => $request->brand_model_no ?: null,

    //         'date_of_maintenance' => $request->date_of_maintenance ?: null,
    //         'service_report_no' => $request->service_report_no ?: null,

    //         'maintenance_type' => $request->maintenance_type ?: null,
    //         'maintenance_provider' => $request->maintenance_provider ?: null,
    //         'maintenance_technician' => $request->maintenance_technician ?: null,
    //         'maintenance_hours' => $request->maintenance_hours ?: null,

    //         'issues_reported' => !empty($request->issues_reported)
    //             ? json_encode($request->issues_reported)
    //             : json_encode([]),

    //         'issue_other' => $request->issue_other ?: null,

    //         'actions_taken' => !empty($request->actions_taken)
    //             ? json_encode($request->actions_taken)
    //             : json_encode([]),

    //         'action_other' => $request->action_other ?: null,

    //         'tools_used' => $request->tools_used ?: null,

    //         'operational_status' => !empty($request->operational_status)
    //             ? json_encode($request->operational_status)
    //             : json_encode([]),

    //         'equipment_status' => !empty($request->equipment_status)
    //             ? json_encode($request->equipment_status)
    //             : json_encode([]),

    //         'next_maintenance_due' => $request->next_maintenance_due ?: null,

    //         'frequency' => !empty($request->frequency)
    //             ? json_encode($request->frequency)
    //             : json_encode([]),

    //         'technician_notes' => $request->technician_notes ?: null,
    //         'manager_feedback' => $request->manager_feedback ?: null,

    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ];

    //     DB::table('equipment_maintenance_plans')->insert($data);

    //     return redirect()
    //         ->back()
    //         ->with('success', 'Equipment maintenance plan saved successfully.');
    // }
    //ENVIRONMENTAL CONDITION FORM
    private function environmentalLaboratoryEntries(bool $excludeReleased = false): array
    {
        $role = (int) Auth::user()->role;
        $unit = match ($role) {
            2, 4 => 'FIS',
            3 => 'MIC',
            7 => 'CHEM',
            0 => null,
            default => '__NO_ACCESS__',
        };
        $entries = [];
        $releasedCodes = $excludeReleased
            ? DB::table('laboratory_status')
                ->where('status', 8)
                ->whereNotNull('laboratory_code')
                ->pluck('laboratory_code')
                ->map(fn ($code) => strtoupper(trim((string) $code)))
                ->filter()
                ->unique()
                ->flip()
            : collect();

        foreach (DB::table('lf_06_02')->orderByDesc('id')->get(['RLA_no', 'laboratory_code', 'analysis_description', 'lab_unit']) as $rla) {
            $codes = json_decode($rla->laboratory_code ?? '', true);
            $descriptions = json_decode($rla->analysis_description ?? '', true);
            $units = json_decode($rla->lab_unit ?? '', true);
            $codes = is_array($codes) ? $codes : [$rla->laboratory_code];
            $descriptions = is_array($descriptions) ? $descriptions : [$rla->analysis_description];
            $units = is_array($units) ? $units : [$rla->lab_unit];

            foreach ($codes as $i => $code) {
                $code = trim((string) $code);
                $codeUnit = strtoupper(trim((string) ($units[$i] ?? '')));
                if (
                    $code === '' ||
                    ($excludeReleased && isset($releasedCodes[strtoupper($code)])) ||
                    ($unit !== null && $codeUnit !== $unit) ||
                    isset($entries[$code])
                ) continue;
                $entries[$code] = [
                    'laboratory_code' => $code,
                    'analysis_description' => (string) ($descriptions[$i] ?? ''),
                    'rla_no' => (string) ($rla->RLA_no ?? ''),
                    'laboratory_name' => 'Regional Fisheries Laboratory XII',
                    'lab_unit' => $codeUnit,
                ];
            }
        }

        return $entries;
    }

    public function environmental_plan_index(Request $request)
    {
        $entries = $this->environmentalLaboratoryEntries();
        $query = DB::table('environmental_plans')->latest();
        if ((int) Auth::user()->role !== 0) {
            $query->whereIn('laboratory_code', array_keys($entries));
        }
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('area', 'like', '%' . $search . '%')
                    ->orWhere('laboratory_name', 'like', '%' . $search . '%');
            });
        }
        $environmentals = $query->paginate(10)->withQueryString();
        $laboratories = collect($entries)->map(fn ($entry) => (object) $entry)->values();
        return view('EnvironmentalConditions.index', compact('environmentals','laboratories'));
    }
    public function environmental_plan_create()
    {
        $laboratoryEntries = $this->environmentalLaboratoryEntries(true);
        return view('EnvironmentalConditions.create', compact('laboratoryEntries'));
    }

    public function environmental_plan_store(Request $request)
    {
        $validated = $request->validate([
            'area' => 'nullable|string|max:255',
            'laboratory_name' => 'nullable|string|max:255',
            'laboratory_code' => 'required|string|max:255',
            'analysis_description' => 'nullable|string',
            'rla_no' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'temperature_am' => 'nullable|numeric',
            'temperature_pm' => 'nullable|numeric',
            'humidity_am' => 'nullable|numeric',
            'humidity_pm' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'analyst' => 'nullable|string|max:255',
            'checked_by' => 'nullable|string|max:255',
        ]);

        $laboratoryEntries = $this->environmentalLaboratoryEntries(true);
        abort_unless(isset($laboratoryEntries[$validated['laboratory_code']]), 403, 'You cannot access this laboratory code.');
        $entry = $laboratoryEntries[$validated['laboratory_code']];
        $validated['laboratory_name'] = $entry['laboratory_name'];
        $validated['analysis_description'] = $entry['analysis_description'];
        $validated['rla_no'] = $entry['rla_no'];

        $validated['remarks'] = implode(' / ', array_filter([
            trim((string) ($validated['analysis_description'] ?? '')),
            trim((string) $validated['laboratory_code']),
            trim((string) ($validated['rla_no'] ?? '')),
        ], fn ($value) => $value !== ''));
        DB::Table('environmental_plans')->insert($validated + ['created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('environmental_plan/index')
            ->with('success', 'Environmental record created successfully.');
    }
    public function edit_environmental($id)
    {
        $environmental = DB::table('environmental_plans')->where('id', $id)->first();

        if (!$environmental) {
            return redirect()->back()->with('error', 'Record not found.');
        }
        abort_unless((int) Auth::user()->role === 0 || isset($this->environmentalLaboratoryEntries()[$environmental->laboratory_code]), 403);

        return view('EnvironmentalConditions.edit', compact('environmental'));
    }
    public function update_environmental(Request $request)
    {
        $request->validate([
            'area' => 'nullable|string|max:255',
            'laboratory_name' => 'nullable|string|max:255',
            'laboratory_code' => 'required|string|max:255',
            'analysis_description' => 'nullable|string',
            'rla_no' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'temperature_am' => 'nullable|numeric',
            'temperature_pm' => 'nullable|numeric',
            'humidity_am' => 'nullable|numeric',
            'humidity_pm' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'analyst' => 'nullable|string|max:255',
            'checked_by' => 'nullable|string|max:255',
        ]);

        $environmental = DB::table('environmental_plans')->where('id', $request->id)->first();
        abort_unless($environmental, 404);
        $entries = $this->environmentalLaboratoryEntries();
        abort_unless((int) Auth::user()->role === 0 || isset($entries[$environmental->laboratory_code]), 403);
        $metadata = $entries[$environmental->laboratory_code] ?? [
            'laboratory_code' => $environmental->laboratory_code,
            'analysis_description' => $environmental->analysis_description,
            'rla_no' => $environmental->rla_no,
            'laboratory_name' => $environmental->laboratory_name,
        ];

        $remarks = implode(' / ', array_filter([
            trim((string) $metadata['analysis_description']),
            trim((string) $metadata['laboratory_code']),
            trim((string) $metadata['rla_no']),
        ], fn ($value) => $value !== ''));

        $updated = DB::table('environmental_plans')
            ->where('id', $request->id)
            ->update([
                'area' => $request->area,
                'laboratory_name' => $metadata['laboratory_name'],
                'laboratory_code' => $metadata['laboratory_code'],
                'analysis_description' => $metadata['analysis_description'],
                'rla_no' => $metadata['rla_no'],
                'date' => $request->date,
                'temperature_am' => $request->temperature_am,
                'temperature_pm' => $request->temperature_pm,
                'humidity_am' => $request->humidity_am,
                'humidity_pm' => $request->humidity_pm,
                'remarks' => $remarks,
                'analyst' => $request->analyst,
                'checked_by' => $request->checked_by,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return redirect()->back()->with('warning', 'No changes were made.');
        }

        return redirect()->route('environmental_plan/index')->with('success', 'Environmental Condition Form updated successfully.');
    }
    public function deleteEnvi($id){
        return view('confirm-delete', ['id'=> $id]);
    }

    public function hardDelete_environmental($id){
        try{
            $record = DB::table('environmental_plans')->where('id', $id)->first();
            abort_unless($record, 404);
            abort_unless((int) Auth::user()->role === 0 || isset($this->environmentalLaboratoryEntries()[$record->laboratory_code]), 403);
            $envi = DB::table('environmental_plans')->where('id',$id)->delete();
            // ActivityLog::create([
            //     'user_id' => Auth::user()->id,
            //     'activity' => 'Deleted the User Name'. $user->f_name . '' .  $user->l_name,
            //     'time' => now('Asia/Manila'),
            //     'date' => now()->toDateString(), 
            // ]);
            return redirect()->route('environmental_plan/index')->with('success', 'Environmental Condition Form Deleted Successfully!');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json(['error' => 'client not found'], 404);
        }
        catch(\Exception $e){
            return response()->json(['error'=> 'An error occured while deleting the Environmental Condition Form'], 500);
        }
    } 
    public function downloadPdfEnvironmental(Request $request)
    {
        $entries = $this->environmentalLaboratoryEntries();
        $recordsQuery = DB::table('environmental_plans')->orderBy('laboratory_code')->orderBy('date');
        if ((int) Auth::user()->role !== 0) {
            $recordsQuery->whereIn('laboratory_code', array_keys($entries));
        }
        $records = $recordsQuery->get()->groupBy('laboratory_code');
        $sheets = collect();
        foreach ($records as $code => $codeRecords) {
            $metadata = $entries[$code] ?? null;
            if (!$metadata) continue;
            foreach ($codeRecords->chunk(18) as $page => $pageRecords) {
                $sheets->push([
                    'laboratory_code' => $code,
                    'laboratory_name' => $metadata['laboratory_name'],
                    'records' => $pageRecords->values(),
                    'checked_by' => $pageRecords->last()->checked_by ?? '',
                    'page' => $page + 1,
                ]);
            }
        }

        $pdf = Pdf::loadView('EnvironmentalConditions.download', [
            'sheets' => $sheets,
        ])->setPaper('a4', 'landscape');

        $role = (int) Auth::user()->role;
        $unit = match ($role) { 2, 4 => 'fis', 3 => 'micro', 7 => 'chem', 0 => 'all-units', default => 'unit' };
        return $pdf->download('environmental-monitoring-' . $unit . '.pdf');
    }
    public function ReportTest(Request $request)
    {
    $clients = DB::table('clients')->get();

    /*
    |--------------------------------------------------------------------------
    | Get laboratory codes with status = 8
    |--------------------------------------------------------------------------
    */
    $status8Codes = DB::table('laboratory_status')
        ->where('status', 8)
        ->whereNotNull('laboratory_code')
        ->pluck('laboratory_code')
        ->map(function ($code) {
            return strtoupper(
                trim((string) $code)
            );
        })
        ->filter()
        ->unique()
        ->values();

    /*
    |--------------------------------------------------------------------------
    | Get RLA records
    |--------------------------------------------------------------------------
    */
    $rla = DB::table('lf_06_02')
        ->join(
            'clients',
            'lf_06_02.user_id',
            '=',
            'clients.id'
        )

        /*
        |--------------------------------------------------------------------------
        | Only show RLA containing at least one laboratory code with status = 8
        |--------------------------------------------------------------------------
        */
        ->where(function ($query) use ($status8Codes) {

            if ($status8Codes->isEmpty()) {

                $query->whereRaw('1 = 0');

                return;
            }

            foreach ($status8Codes as $code) {

                $query->orWhereJsonContains(
                    'lf_06_02.laboratory_code',
                    $code
                );
            }
        })

        ->select(
            'lf_06_02.id',
            'lf_06_02.user_id',
            'lf_06_02.RLA_no',
            'lf_06_02.payment',
            'lf_06_02.date_collected',
            'lf_06_02.sample',
            'lf_06_02.laboratory_code',
            'lf_06_02.lab_unit',
            'lf_06_02.sample_description',
            'clients.address',
            'clients.contact_no',
            'lf_06_02.source_sample',
            'lf_06_02.sample_received_by',
            'lf_06_02.service_officer',
            'lf_06_02.date_received',
            'lf_06_02.date_payment',
            'lf_06_02.or_no',
            'lf_06_02.remarks',
            'clients.company_name'
        )

        ->orderBy(
            'lf_06_02.id',
            'desc'
        )

        ->when(trim((string) $request->input('search', '')) !== '', function ($query) use ($request, $status8Codes) {
            $search = trim((string) $request->input('search'));
            $matchingRlaExists = DB::table('lf_06_02')
                ->where('RLA_no', 'like', '%' . $search . '%')
                ->exists();
            $matchingLabCodeExists = $status8Codes->contains(function ($code) use ($search) {
                return stripos((string) $code, $search) !== false;
            });
            $query->where(function ($builder) use ($search, $matchingRlaExists, $matchingLabCodeExists) {
                if ($matchingRlaExists) {
                    $builder->where('lf_06_02.RLA_no', 'like', '%' . $search . '%');
                } elseif ($matchingLabCodeExists) {
                    $builder->where('lf_06_02.laboratory_code', 'like', '%' . $search . '%');
                } else {
                    $builder->where('lf_06_02.RLA_no', 'like', '%' . $search . '%')
                        ->orWhere('clients.company_name', 'like', '%' . $search . '%');
                }
            });
        })
        ->paginate(10)
        ->withQueryString();

    $search = trim((string) $request->input('search', ''));
    $matchingStatusLabCodeExists = $search !== '' && $status8Codes->contains(function ($code) use ($search) {
        return stripos((string) $code, $search) !== false;
    });
    $matchingStatusRlaExists = $search !== '' && DB::table('lf_06_02')
        ->where('RLA_no', 'like', '%' . $search . '%')
        ->exists();

    /*
    |--------------------------------------------------------------------------
    | Get all status-8 laboratory codes for every RLA
    |--------------------------------------------------------------------------
    */
    $rla->getCollection()->transform(
        function ($item) use ($status8Codes, $search, $matchingStatusLabCodeExists, $matchingStatusRlaExists) {

            /*
            |--------------------------------------------------------------------------
            | Decode laboratory codes
            |--------------------------------------------------------------------------
            */
            $laboratoryCodes = json_decode(
                $item->laboratory_code ?? '[]',
                true
            );

            if (!is_array($laboratoryCodes)) {
                $laboratoryCodes = [];
            }

            /*
            |--------------------------------------------------------------------------
            | Decode laboratory units
            |--------------------------------------------------------------------------
            */
            $labUnits = json_decode(
                $item->lab_unit ?? '[]',
                true
            );

            if (!is_array($labUnits)) {
                $labUnits = [];
            }

            /*
            |--------------------------------------------------------------------------
            | Defaults
            |--------------------------------------------------------------------------
            */
            $item->status = null;

            // All status-8 codes
            $item->status8_laboratory_codes = [];

            // Corresponding units
            $item->status8_lab_units = [];

            // Keep these because your modal still uses them
            $item->current_laboratory_code = null;
            $item->current_lab_unit = null;

            /*
            |--------------------------------------------------------------------------
            | Check every laboratory code
            |--------------------------------------------------------------------------
            */
            foreach ($laboratoryCodes as $index => $code) {

                $originalCode = trim(
                    (string) $code
                );

                $normalizedCode = strtoupper(
                    $originalCode
                );

                /*
                |--------------------------------------------------------------------------
                | Check if this exact laboratory code has status = 8
                |--------------------------------------------------------------------------
                */
                if (
                    !$status8Codes->contains(
                        $normalizedCode
                    )
                ) {
                    continue;
                }

                if (!$matchingStatusRlaExists && $matchingStatusLabCodeExists && stripos($originalCode, $search) === false) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */
                $item->status = 8;

                /*
                |--------------------------------------------------------------------------
                | Add ALL status-8 laboratory codes
                |--------------------------------------------------------------------------
                */
                $item->status8_laboratory_codes[] =
                    $originalCode;

                /*
                |--------------------------------------------------------------------------
                | Matching lab unit by same index
                |--------------------------------------------------------------------------
                */
                $unit = $labUnits[$index] ?? null;

                $item->status8_lab_units[] =
                    $unit;

                /*
                |--------------------------------------------------------------------------
                | Set first matching code for existing modal
                |--------------------------------------------------------------------------
                */
                if (
                    $item->current_laboratory_code === null
                ) {

                    $item->current_laboratory_code =
                        $originalCode;

                    $item->current_lab_unit =
                        $unit;
                }
            }

            return $item;
        }
    );

    return view(
        'ReportTest.index',
        compact(
            'clients',
            'rla'
        )
    );
}
    //analyst worksheet
  public function analyst_worksheet()
{
    $clients = DB::table('clients')->get();

    $userRole = Auth::user()->role;


    /*
    |--------------------------------------------------------------------------
    | Get laboratory statuses
    |--------------------------------------------------------------------------
    |
    | Key format:
    | RLA_NO|LABORATORY_CODE
    |
    */

    $laboratoryStatuses = DB::table('laboratory_status')
        // ->where('status', 3)
        ->get()
        ->keyBy(function ($item) {
            return $item->rla_no . '|' . $item->laboratory_code;
        });


    /*
    |--------------------------------------------------------------------------
    | Get all RLA records
    |--------------------------------------------------------------------------
    */

    $records = DB::table('lf_06_02')
        ->join('clients', 'lf_06_02.user_id', '=', 'clients.id')
        ->select(
            'lf_06_02.*',
            'clients.company_name',
            'clients.address',
            'clients.contact_no'
        )
        ->orderBy('lf_06_02.id', 'desc')
        ->get();


    $rows = collect();


    foreach ($records as $record) {

        $labCodes = json_decode(
            $record->laboratory_code,
            true
        ) ?: [];

        $labUnits = json_decode(
            $record->lab_unit,
            true
        ) ?: [];

        $analysis = json_decode(
            $record->analysis_description,
            true
        ) ?: [];

        $sampleDesc = json_decode(
            $record->sample_description,
            true
        ) ?: [];


        foreach ($labCodes as $i => $code) {

            /*
            |--------------------------------------------------------------------------
            | Check laboratory_status
            |--------------------------------------------------------------------------
            */

            $statusKey = $record->RLA_no . '|' . $code;

            $laboratoryStatus = $laboratoryStatuses->get($statusKey);


            /*
            |--------------------------------------------------------------------------
            | Only show laboratory codes with status = 3
            |--------------------------------------------------------------------------
            */

            if (!$laboratoryStatus) {
                continue;
            }


            $unit = strtoupper(
                $labUnits[$i] ?? ''
            );


            /*
            |--------------------------------------------------------------------------
            | Filter by role
            |--------------------------------------------------------------------------
            */

            if ($userRole == 2 || $userRole == 4) {

                if ($unit != 'FIS') {
                    continue;
                }

            }
            elseif ($userRole == 3) {

                if ($unit != 'MIC') {
                    continue;
                }

            }
            elseif ($userRole == 7) {

                if ($unit != 'CHEM') {
                    continue;
                }

            }
            elseif ($userRole != 0) {

                continue;

            }


            /*
            |--------------------------------------------------------------------------
            | Create individual laboratory code row
            |--------------------------------------------------------------------------
            */

            $clone = clone $record;

            $clone->laboratory_code = $code;
            $clone->lab_unit = $unit;
            $clone->analysis_description = $analysis[$i] ?? '';
            $clone->sample_desc = $sampleDesc[$i] ?? '';

            // Status gikan sa laboratory_status
            $clone->status = $laboratoryStatus->status;


            $rows->push($clone);
        }
    }

  
    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    $search = trim((string) request()->input('search', ''));
    if ($search !== '') {
        $matchingRlaRows = $rows->filter(function ($row) use ($search) {
            return stripos((string) ($row->RLA_no ?? ''), $search) !== false;
        });
        $rows = $matchingRlaRows->isNotEmpty() ? $matchingRlaRows : $rows->filter(function ($row) use ($search) {
            foreach ([$row->RLA_no ?? '', $row->laboratory_code ?? '', $row->company_name ?? ''] as $value) {
                if (stripos((string) $value, $search) !== false) return true;
            }
            return false;
        })->values();
    }

    $page = request()->get('page', 1);

    $perPage = 10;


    $rla = new \Illuminate\Pagination\LengthAwarePaginator(

        $rows->forPage(
            $page,
            $perPage
        )->values(),

        $rows->count(),

        $perPage,

        $page,

        [
            'path' => request()->url(),
            'query' => request()->query(),
        ]
    );


    return view(
        'analyst_worksheet.LF-W01-MIC-03',
        compact(
            'clients',
            'rla'
        )
    );
}
    public function create(Request $request,$id)
    {
        $selectedLabCode = $request->laboratory_code;
        // dd($selectedLabCode);
        $rla = DB::table('lf_06_02')
            ->join('clients', 'lf_06_02.user_id', '=', 'clients.id')
            ->select(
                'lf_06_02.*',
                'clients.company_name',
                'clients.address',
                'clients.contact_no'
            )
            ->where('lf_06_02.id', $id)
            ->first();

        if (!$rla) {
            return back()->with('error', 'RLA not found.');
        }

       $labCodes = json_decode($rla->laboratory_code ?? '[]', true);
        $labUnits = json_decode($rla->lab_unit ?? '[]', true);
        $analysisDescriptions = json_decode($rla->analysis_description ?? '[]', true);

        $index = array_search($selectedLabCode, $labCodes);

        if ($index === false) {
            abort(404, 'Laboratory Code not found.');
        }

        $labUnit = strtoupper(trim($labUnits[$index] ?? ''));
        $analysisDescription = strtoupper(trim($analysisDescriptions[$index] ?? ''));

        $view = null;
        $worksheet = null;
        /*
        |--------------------------------------------------------------------------
        | FIS WORKSHEETS
        |--------------------------------------------------------------------------
        */
        if ($labUnit === 'FIS') {
            if ($analysisDescription === 'PCR') {
                $view = 'Fis_worksheet.PCR';
                $worksheet = DB::table('pcr_worksheets')
                        ->where('lf_06_02_id', $id)
                        // ->where('lab_code', $selectedLabCode)
                        ->first();
        

            } elseif ($analysisDescription === 'PARASITOLOGICAL EXAMINATION') {
                $view = 'Fis_worksheet.paragross';
                $worksheet = DB::table('paragross_worksheets')
                        ->where('lf_06_02_id', $id)
                        // ->where('laboratory_code', $selectedLabCode)
                        ->first();
            } elseif ($analysisDescription === 'GROSS EXAMINATION') {
                 $view = 'Fis_worksheet.paragross';
                 $worksheet = DB::table('paragross_worksheets')
                        ->where('lf_06_02_id', $id)
                        // ->where('lab_code', $selectedLabCode)
                        ->first();
            }
        }
        /*
        |--------------------------------------------------------------------------
        | CHEM WORKSHEETS
        |--------------------------------------------------------------------------
        */
        elseif ($labUnit === 'CHEM') {
            if ($analysisDescription === 'HISTAMINE') {
                $view = 'Chem_worksheet.histamine';
                $worksheet = DB::table('histamine_worksheets')
                
                    ->where('lf_06_02_id', $id)
                    // ->where('lab_code', $selectedLabCode)
                    ->first();
        

            } elseif ($analysisDescription === 'MOISTURE CONTENT') {
                $view = 'Chem_worksheet.moisture';
                 $worksheet = DB::table('moisture_worksheets')
                    ->where('lf_06_02_id', $id)
                    // ->where('lab_code', $selectedLabCode)
                    ->first();
            } elseif ($analysisDescription === 'WATER QUALITY') {
                $view = 'Chem_worksheet.waterquality';
                $worksheet = DB::table('water_quality_worksheets')
                    ->where('lf_06_02_id', $id)
                    // ->where('lab_code', $selectedLabCode)
                    ->first();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MICRO WORKSHEETS
        |--------------------------------------------------------------------------
        */
        elseif ($labUnit === 'MICRO' || $labUnit === 'MIC') {
            if ($analysisDescription === 'FISH AND FISHERY PRODUCTS') {
                $view = 'Micro_worksheet.fish_product';

                $worksheet = DB::table('fish_fishery_worksheets')
                        ->where('lf_06_02_id', $id)
                        // ->where('lab_code', $selectedLabCode)
                        ->first();
                    
            } elseif ($analysisDescription === 'WATER POTABILITY') {
                $view = 'Micro_worksheet.waterpotability';

              
                $worksheet = DB::table('water_potability_worksheets')
                        ->where('lf_06_02_id', $id)
                        // ->where('lab_code', $selectedLabCode)
                        ->first();
            } elseif ($analysisDescription === 'WATER BACTERIOLOGY') {
                $view = 'Micro_worksheet.bacteriology';


              $worksheet = DB::table('water_bacteriology_worksheets')
                ->where('lf_06_02_id', $id)
                // ->where('lab_code', $selectedLabCode)
                ->first();
            }
        }

        if (!$view) {
            return back()->with(
                'error',
                'No worksheet found for Lab Unit: ' . $labUnit . ' and Analysis Description: ' . $analysisDescription
            );
        }

        if (!view()->exists($view)) {
            return back()->with('error', 'Worksheet blade file does not exist: ' . $view);
        }

        /*
            Users for signature dropdown.
            Ayaw na pag-query sa users sulod sa Blade.
        */
        $users = DB::table('users')
            ->orderBy('f_name', 'asc')
            ->get();
        
        return view($view, compact(
            'rla',
            'labUnit',
            'analysisDescription',
            'selectedLabCode',
            'worksheet',
            'users'
        ));
    }   
    public function downloadPdfAnalysis(Request $request, $id)
        {
            
            $selectedLabCode = $request->laboratory_code;
            $rla = DB::table('lf_06_02')
                ->join('clients', 'lf_06_02.user_id', '=', 'clients.id')
                ->select(
                    'lf_06_02.*',
                    'clients.company_name',
                    'clients.address',
                    'clients.contact_no'
                )
                ->where('lf_06_02.id', $id)
                ->first();

            if (!$rla) {
                abort(404);
            }

           $labCodes = json_decode($rla->laboratory_code ?? '[]', true);
            $labUnits = json_decode($rla->lab_unit ?? '[]', true);
            $analysisDescriptions = json_decode($rla->analysis_description ?? '[]', true);

            $index = array_search($selectedLabCode, $labCodes);

            if ($index === false) {
                abort(404, 'Laboratory Code not found.');
            }

            $labUnit = strtoupper(trim($labUnits[$index] ?? ''));
            $analysisDescription = strtoupper(trim($analysisDescriptions[$index] ?? ''));

            $downloadView = null;
            $worksheet = null;
            

            /*
            |--------------------------------------------------------------------------
            | FIS DOWNLOAD VIEWS
            |--------------------------------------------------------------------------
            */
            if ($labUnit === 'FIS') {
                if ($analysisDescription === 'PCR') {
                    
                   $downloadView = 'Fis_worksheet.pcrdownload';
                    $worksheet = DB::table('pcr_worksheets')
                        ->where('lf_06_02_id', $id)
                        ->first();
                } elseif ($analysisDescription === 'PARASITOLOGICAL EXAMINATION') {
                    
                    $downloadView = 'Fis_worksheet.paragrossdownload';
                    $worksheet = DB::table('paragross_worksheets')
                        ->where('lf_06_02_id', $id)
                        ->where('lab_code', $selectedLabCode)
                        ->first();
                } elseif ($analysisDescription === 'GROSS EXAMINATION') {
                    
                    $downloadView = 'Fis_worksheet.paragrossdownload';
                     $worksheet = DB::table('paragross_worksheets')
                        ->where('lf_06_02_id', $id)
                        ->where('lab_code', $selectedLabCode)
                        ->first();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | CHEM DOWNLOAD VIEWS
            |--------------------------------------------------------------------------
            */
            elseif ($labUnit === 'CHEM') {
                if ($analysisDescription === 'HISTAMINE') {
                  
                     $downloadView = 'Chem_worksheet.histaminedownload';

                    $worksheet = DB::table('histamine_worksheets')
                        ->where('lf_06_02_id', $id)
                        ->first();
                } elseif ($analysisDescription === 'MOISTURE CONTENT') {
                    
                     $downloadView = 'Chem_worksheet.moisturedownload';

                        $worksheet = DB::table('moisture_worksheets')
                            ->where('lf_06_02_id', $id)
                            ->first();
                } elseif ($analysisDescription === 'WATER QUALITY') {
                   
                    $downloadView = 'Chem_worksheet.waterqualitydownload';

                    $worksheet = DB::table('water_quality_worksheets')
                        ->where('lf_06_02_id', $id)
                        ->first();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | MICRO DOWNLOAD VIEWS
            |--------------------------------------------------------------------------
            */
            elseif ($labUnit === 'MICRO' || $labUnit === 'MIC') {
                if ($analysisDescription === 'FISH AND FISHERY PRODUCTS') {
                        $downloadView = 'Micro_worksheet.fishfisherydownload';

                        $worksheet = DB::table('fish_fishery_worksheets')
                            ->where('lf_06_02_id', $id)
                            ->where('lab_code', $selectedLabCode)
                            ->first();
                } elseif ($analysisDescription === 'WATER POTABILITY') {
                       $downloadView = 'Micro_worksheet.waterpotabilitydownload';

                        $worksheet = DB::table('water_potability_worksheets')
                            ->where('lf_06_02_id', $id)
                            ->where('lab_code', $selectedLabCode)
                            ->first();
                } elseif ($analysisDescription === 'WATER BACTERIOLOGY') {
                    $downloadView = 'Micro_worksheet.waterbacteriologydownload';

                    $worksheet = DB::table('water_bacteriology_worksheets')
                        ->where('lf_06_02_id', $id)
                        ->where('lab_code', $selectedLabCode)
                        ->first();
                            }
            }

            if (!$downloadView) {
                return back()->with(
                    'error',
                    'No download worksheet found for Lab Unit: ' . $labUnit . ' and Analysis Description: ' . $analysisDescription
                );
            }

            if (!view()->exists($downloadView)) {
                return back()->with('error', 'Download blade file does not exist: ' . $downloadView);
            }

            $pdf = Pdf::loadView($downloadView, compact(
                'rla',
                'id',
                'worksheet',
                'selectedLabCode',
                'labUnit',
                'analysisDescription'
            ))->setPaper('a4', 'portrait');

            $displayAnalysisName = ucwords(strtolower($analysisDescription));
            $rlaNumber = $rla->RLA_no ?? $worksheet->rla_no ?? $id;

               $fileName = 'Analysis Worksheet' .' - '. $displayAnalysisName . '('. $rlaNumber . ')'. '.pdf';
            $fileName = preg_replace('/[\/\\\\:*?"<>|]/', '-', $fileName);



            return $pdf->download($fileName);
        }
      
   
    public function ReporttestdownloadPdf($id)
    {
        /*
        |--------------------------------------------------------------------------
        | GET RLA
        |--------------------------------------------------------------------------
        */
        $rla = DB::table('lf_06_02')
            ->join(
                'clients',
                'lf_06_02.user_id',
                '=',
                'clients.id'
            )
            ->select(
                'lf_06_02.*',
                'clients.company_name',
                'clients.address',
                'clients.contact_no'
            )
            ->where('lf_06_02.id', $id)
            ->first();


        if (!$rla) {
            abort(404);
        }


        /*
        |--------------------------------------------------------------------------
        | JSON DECODER
        |--------------------------------------------------------------------------
        */
        $decodeJsonArray = function ($value) {

            if (empty($value)) {
                return [];
            }

            if (is_array($value)) {
                return $value;
            }

            $decoded = json_decode($value, true);

            if (
                json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
            ) {
                return $decoded;
            }

            return [$value];
        };


        /*
        |--------------------------------------------------------------------------
        | GET LABORATORY CODES WITH STATUS = 8
        |--------------------------------------------------------------------------
        */
        $status8Codes = DB::table('laboratory_status')
            ->where('status', 8)
            ->whereNotNull('laboratory_code')
            ->pluck('laboratory_code')
            ->map(function ($code) {

                return strtoupper(
                    trim((string) $code)
                );
            })
            ->filter()
            ->unique()
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | DECODE RLA SAMPLE DATA
        |--------------------------------------------------------------------------
        */
        $laboratoryCodes = $decodeJsonArray(
            $rla->laboratory_code ?? null
        );

        $sampleCodes = $decodeJsonArray(
            $rla->sample_code ?? null
        );

        $sampleDescriptions = $decodeJsonArray(
            $rla->sample_description ?? null
        );

        $labUnits = $decodeJsonArray(
            $rla->lab_unit ?? null
        );

        $analysisDescriptions = $decodeJsonArray(
            $rla->analysis_description ?? null
        );


        /*
        |--------------------------------------------------------------------------
        | BUILD SAMPLE ROWS
        |--------------------------------------------------------------------------
        | Only laboratory codes with status = 8 will be included.
        |--------------------------------------------------------------------------
        */
        $samples = [];

        foreach (
            $laboratoryCodes as $index => $laboratoryCode
        ) {

            $normalizedLabCode = strtoupper(
                trim((string) $laboratoryCode)
            );


            /*
            |--------------------------------------------------------------------------
            | Skip laboratory code if status is not 8
            |--------------------------------------------------------------------------
            */
            if (
                !in_array(
                    $normalizedLabCode,
                    $status8Codes,
                    true
                )
            ) {
                continue;
            }


            $samples[] = [

                'lab_code' =>
                    trim((string) $laboratoryCode),

                'sample_code' =>
                    $sampleCodes[$index] ?? '',

                'sample_type' =>
                    $sampleDescriptions[$index] ?? '',

                'lab_unit' =>
                    $labUnits[$index] ?? '',

                'analysis_description' =>
                    $analysisDescriptions[$index] ?? '',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        */
        if (empty($samples)) {

            $samples[] = [

                'lab_code' => '',

                'sample_code' => '',

                'sample_type' => '',

                'lab_unit' => '',

                'analysis_description' => '',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | FIRST LABORATORY CODE
        |--------------------------------------------------------------------------
        */
        $firstLabCode =
            $samples[0]['lab_code']
            ?? '';


        /*
        |--------------------------------------------------------------------------
        | GET LAB UNIT AND ANALYSIS DESCRIPTION
        |--------------------------------------------------------------------------
        */
        $labUnit = strtoupper(
            trim(
                (string) (
                    $samples[0]['lab_unit']
                    ?? ''
                )
            )
        );


        $analysisDescription = strtoupper(
            trim(
                (string) (
                    $samples[0]['analysis_description']
                    ?? ''
                )
            )
        );


        /*
        |--------------------------------------------------------------------------
        | ANALYSIS REQUESTED
        |--------------------------------------------------------------------------
        */
        $analysisRequestedRaw =
            $decodeJsonArray(
                $rla->analysis_requested ?? null
            );


        /*
        |--------------------------------------------------------------------------
        | FLATTEN NESTED ARRAY
        |--------------------------------------------------------------------------
        */
        $flattenArray = function ($items) use (&$flattenArray) {

            $result = [];

            foreach ($items as $item) {

                if (is_array($item)) {

                    $result = array_merge(
                        $result,
                        $flattenArray($item)
                    );

                } else {

                    $result[] = $item;
                }
            }

            return $result;
        };


        $analysisRequestedFlat =
            $flattenArray(
                $analysisRequestedRaw
            );


        /*
        |--------------------------------------------------------------------------
        | CLEAN ANALYSIS REQUESTED
        |--------------------------------------------------------------------------
        */
        $analysisRequested = [];

        foreach ($analysisRequestedFlat as $item) {

            if (empty($item)) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Support comma separated values
            |--------------------------------------------------------------------------
            */
            $parts = explode(
                ',',
                (string) $item
            );


            foreach ($parts as $part) {

                $value = trim(
                    (string) $part
                );


                if ($value !== '') {

                    $analysisRequested[] =
                        $value;
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | REMOVE DUPLICATES
        |--------------------------------------------------------------------------
        */
        $analysisRequested =
            array_values(
                array_unique(
                    $analysisRequested
                )
            );


        /*
        |--------------------------------------------------------------------------
        | GET LF 06-04 ROWS
        |--------------------------------------------------------------------------
        */
        $lf0604Rows = DB::table('lf_06_04')
            ->where(
                'lf_06_02_id',
                $id
            )
            ->orderBy(
                'id',
                'asc'
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | ANALYSIS RESULTS PER LABORATORY CODE
        |--------------------------------------------------------------------------
        */
        $analysisResultsByLabCode = [];


        foreach ($lf0604Rows as $lf0604Row) {

            /*
            |--------------------------------------------------------------------------
            | Normalize laboratory code
            |--------------------------------------------------------------------------
            */
            $lf0604LabCode = strtoupper(
                trim(
                    (string) (
                        $lf0604Row->laboratory_code
                        ?? ''
                    )
                )
            );


            if ($lf0604LabCode === '') {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Only process laboratory codes with status = 8
            |--------------------------------------------------------------------------
            */
            if (
                !in_array(
                    $lf0604LabCode,
                    $status8Codes,
                    true
                )
            ) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Create array for this laboratory code
            |--------------------------------------------------------------------------
            */
            if (
                !isset(
                    $analysisResultsByLabCode[
                        $lf0604LabCode
                    ]
                )
            ) {

                $analysisResultsByLabCode[
                    $lf0604LabCode
                ] = [];
            }


            /*
            |--------------------------------------------------------------------------
            | ANALYSIS 2 TO 6
            |--------------------------------------------------------------------------
            */
            for (
                $analysisNo = 2;
                $analysisNo <= 6;
                $analysisNo++
            ) {

                $nameField =
                    'name_analysis_' . $analysisNo;

                $resultField =
                    'analysis_results_' . $analysisNo;


                /*
                |--------------------------------------------------------------------------
                | Normalize analysis name
                |--------------------------------------------------------------------------
                */
                $analysisName = strtoupper(
                    trim(
                        (string) (
                            $lf0604Row->{$nameField}
                            ?? ''
                        )
                    )
                );


                /*
                |--------------------------------------------------------------------------
                | Get result
                |--------------------------------------------------------------------------
                */
                $analysisResult = trim(
                    (string) (
                        $lf0604Row->{$resultField}
                        ?? ''
                    )
                );


                if ($analysisName === '') {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Do not overwrite existing result with blank value
                |--------------------------------------------------------------------------
                */
                if ($analysisResult !== '') {

                    $analysisResultsByLabCode[
                        $lf0604LabCode
                    ][
                        $analysisName
                    ] = $analysisResult;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | OPTIONAL: ANALYSIS 1
            |--------------------------------------------------------------------------
            | In case analysis_results is also being used.
            |--------------------------------------------------------------------------
            */
            $analysis1Name = strtoupper(
                trim(
                    (string) (
                        $lf0604Row->analysis_1
                        ?? ''
                    )
                )
            );


            $analysis1Result = trim(
                (string) (
                    $lf0604Row->analysis_results
                    ?? ''
                )
            );


            if (
                $analysis1Name !== ''
                && $analysis1Result !== ''
            ) {

                $analysisResultsByLabCode[
                    $lf0604LabCode
                ][
                    $analysis1Name
                ] = $analysis1Result;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SELECT DOWNLOAD VIEW
        |--------------------------------------------------------------------------
        */
        $downloadView = null;

        $worksheet = null;


        /*
        |--------------------------------------------------------------------------
        | FIS
        |--------------------------------------------------------------------------
        */
        if ($labUnit === 'FIS') {

            if (
                $analysisDescription === 'PCR'
            ) {

                $downloadView =
                    'ReportTestFis.molecular';


                $worksheet =
                    DB::table('pcr_worksheets')
                        ->where(
                            'lf_06_02_id',
                            $id
                        )
                        ->first();

            } elseif (
                $analysisDescription ===
                'PARASITOLOGICAL EXAMINATION'
            ) {

                $downloadView =
                    'ReportTestFis.parasitological';


                $worksheet =
                    DB::table(
                        'paragross_worksheets'
                    )
                        ->where(
                            'lf_06_02_id',
                            $id
                        )
                        ->first();

            } elseif (
                $analysisDescription ===
                'GROSS EXAMINATION'
            ) {

                $downloadView =
                    'ReportTestFis.gross';


                $worksheet =
                    DB::table(
                        'paragross_worksheets'
                    )
                        ->where(
                            'lf_06_02_id',
                            $id
                        )
                        ->first();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CHEM
        |--------------------------------------------------------------------------
        */
        elseif ($labUnit === 'CHEM') {

            if (
                $analysisDescription ===
                'HISTAMINE'
            ) {

                $downloadView =
                    'ReportTestChem.histamine';


                $worksheet =
                    DB::table(
                        'histamine_worksheets'
                    )
                        ->where(
                            'lf_06_02_id',
                            $id
                        )
                        ->first();

            } elseif (
                $analysisDescription ===
                'MOISTURE CONTENT'
            ) {

                $downloadView =
                    'ReportTestChem.moisture';


                $worksheet =
                    DB::table(
                        'moisture_worksheets'
                    )
                        ->where(
                            'lf_06_02_id',
                            $id
                        )
                        ->first();

            } elseif (
                $analysisDescription ===
                'WATER QUALITY'
            ) {

                $downloadView =
                    'ReportTestChem.waterquality';


                $worksheet =
                    DB::table(
                        'water_quality_worksheets'
                    )
                        ->where(
                            'lf_06_02_id',
                            $id
                        )
                        ->first();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | MICRO
        |--------------------------------------------------------------------------
        */
        elseif (
            $labUnit === 'MICRO'
            || $labUnit === 'MIC'
        ) {

            if (
                $analysisDescription ===
                'FISH AND FISHERY PRODUCTS'
            ) {

                $downloadView =
                    'ReportTestMic.fishfishery';


                $worksheet =
                    DB::table(
                        'fish_fishery_worksheets'
                    )
                        ->where(
                            'lf_06_02_id',
                            $id
                        )
                        ->first();

            } elseif (
                $analysisDescription ===
                'WATER POTABILITY'
            ) {

                $downloadView =
                    'ReportTestMic.waterpota';


                $worksheet =
                    DB::table(
                        'water_potability_worksheets'
                    )
                        ->where(
                            'lf_06_02_id',
                            $id
                        )
                        ->first();

            } elseif (
                $analysisDescription ===
                'WATER BACTERIOLOGY'
            ) {

                $downloadView =
                    'ReportTestMic.waterbacteriology';


                $worksheet =
                    DB::table(
                        'water_bacteriology_worksheets'
                    )
                        ->where(
                            'lf_06_02_id',
                            $id
                        )
                        ->first();
            }
        }
        /*
        |--------------------------------------------------------------------------
        | CHECK VIEW
        |--------------------------------------------------------------------------
        */
        if (!$downloadView) {

            return back()->with(
                'error',
                'No download worksheet found for Lab Unit: '
                . $labUnit
                . ' and Analysis Description: '
                . $analysisDescription
            );
        }


        if (
            !view()->exists(
                $downloadView
            )
        ) {

            return back()->with(
                'error',
                'Download blade file does not exist: '
                . $downloadView
            );
        }


        /*
        |--------------------------------------------------------------------------
        | REPORT TEST NUMBER
        |--------------------------------------------------------------------------
        */
        $year = date('y');


        $prefix = match ($labUnit) {

            'CHEM' =>
                'CHE',

            'MICRO', 'MIC' =>
                'MIC',

            'FIS' =>
                'FIS',

            default =>
                abort(
                    400,
                    'Invalid Lab Unit.'
                ),
        };


        $reportNo =
            'BFAR12-'
            . $prefix
            . '-'
            . $year
            . '-'
            . $rla->RLA_no;


        /*
        |--------------------------------------------------------------------------
        | EXISTING REPORT NUMBERS
        |--------------------------------------------------------------------------
        */
        $existingReportNos =
            json_decode(
                $rla->report_test_no ?? '[]',
                true
            );


        if (
            !is_array(
                $existingReportNos
            )
        ) {

            $existingReportNos = [];
        }


        /*
        |--------------------------------------------------------------------------
        | SAVE REPORT NUMBER IF NOT EXISTING
        |--------------------------------------------------------------------------
        */
        if (
            !in_array(
                $reportNo,
                $existingReportNos,
                true
            )
        ) {

            $existingReportNos[] =
                $reportNo;


            DB::table('lf_06_02')
                ->where(
                    'id',
                    $id
                )
                ->update([

                    'report_test_no' =>
                        json_encode(
                            $existingReportNos
                        ),

                    'updated_at' =>
                        now(),
                ]);
        }
        /*
        |--------------------------------------------------------------------------
        | GENERATE PDF
        |--------------------------------------------------------------------------
        */
        $pdf = Pdf::loadView(
            $downloadView,
            compact(
                'rla',
                'firstLabCode',
                'samples',
                'laboratoryCodes',
                'sampleCodes',
                'sampleDescriptions',
                'analysisRequested',
                'analysisResultsByLabCode',
                'lf0604Rows',
                'id',
                'worksheet',
                'reportNo',
                'labUnit',
                'analysisDescription'
            )
        )
        ->setPaper(
            'a4',
            'portrait'
        );
        /*
        |--------------------------------------------------------------------------
        | PDF FILE NAME
        |--------------------------------------------------------------------------
        */
        $displayAnalysisName =
            ucwords(
                strtolower(
                    $analysisDescription
                )
            );

        $rlaNumber =
            $rla->RLA_no
            ?? $id;


        $fileName =
            'Report of Test'
            . ' - '
            . $displayAnalysisName
            . '('
            . $rlaNumber
            . ')'
            . '.pdf';


        /*
        |--------------------------------------------------------------------------
        | REMOVE INVALID FILE NAME CHARACTERS
        |--------------------------------------------------------------------------
        */
        $fileName = preg_replace(
            '/[\/\\\\:*?"<>|]/',
            '-',
            $fileName
        );


        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD
        |--------------------------------------------------------------------------
        */
        return $pdf->download(
            $fileName
        );
    }
    // Releasing receiving logbook
    public function releasing(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Clients
        |--------------------------------------------------------------------------
        */
        $clients = DB::table('clients')->get();

        /*
        |--------------------------------------------------------------------------
        | Get laboratory codes with status = 8
        |--------------------------------------------------------------------------
        */
        $releasedLabCodes = DB::table('laboratory_status')
            ->where('status', 8)
            ->whereNotNull('laboratory_code')
            ->pluck('laboratory_code')
            ->map(function ($code) {
                return strtoupper(trim((string) $code));
            })
            ->filter()
            ->unique()
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Get RLA records containing at least one released laboratory code
        |--------------------------------------------------------------------------
        */
        $rla = DB::table('lf_06_02')
            ->join(
                'clients',
                'lf_06_02.user_id',
                '=',
                'clients.id'
            )
            ->where(function ($query) use ($releasedLabCodes) {

                if ($releasedLabCodes->isEmpty()) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                foreach ($releasedLabCodes as $labCode) {
                    $query->orWhereJsonContains(
                        'lf_06_02.laboratory_code',
                        $labCode
                    );
                }
            })
            ->when(trim((string) $request->input('search', '')) !== '', function ($query) use ($request, $releasedLabCodes) {
                $search = trim((string) $request->input('search'));
                $matchingLabCodeExists = $releasedLabCodes->contains(function ($code) use ($search) {
                    return stripos((string) $code, $search) !== false;
                });
                $query->where(function ($builder) use ($search, $matchingLabCodeExists) {
                    if ($matchingLabCodeExists) {
                        $builder->where('lf_06_02.laboratory_code', 'like', '%' . $search . '%');
                    } else {
                        $builder->where('lf_06_02.RLA_no', 'like', '%' . $search . '%')
                            ->orWhere('clients.company_name', 'like', '%' . $search . '%');
                    }
                });
            })
            ->select(
                'lf_06_02.id',
                'lf_06_02.user_id',
                'lf_06_02.RLA_no',
                'lf_06_02.payment',
                'lf_06_02.date_collected',
                'lf_06_02.sample',
                'lf_06_02.source_sample',
                'lf_06_02.sample_received_by',
                'lf_06_02.service_officer',
                'lf_06_02.date_received',
                'lf_06_02.date_payment',
                'lf_06_02.or_no',
                'lf_06_02.remarks',
                'lf_06_02.release_date',

                'clients.company_name',
                'clients.address',
                'clients.contact_no',

                'lf_06_02.laboratory_code',
                'lf_06_02.sample_description',
                'lf_06_02.sample_code',
                'lf_06_02.report_test_no'
            )
            ->orderBy(
                'lf_06_02.RLA_no',
                'ASC'
            )
            ->paginate(20)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Decode JSON values
        |--------------------------------------------------------------------------
        */
        $rla->getCollection()->transform(function ($item) {

            $decodeJson = function ($value) {

                if (empty($value)) {
                    return [];
                }

                if (is_array($value)) {
                    return $value;
                }

                $decoded = json_decode($value, true);

                if (
                    json_last_error() === JSON_ERROR_NONE
                    && is_array($decoded)
                ) {
                    return $decoded;
                }

                return [$value];
            };


            $item->laboratory_code =
                $decodeJson($item->laboratory_code);

            $item->sample_description =
                $decodeJson($item->sample_description);

            $item->sample_code =
                $decodeJson($item->sample_code);

            $item->report_test_no =
                $decodeJson($item->report_test_no);


            return $item;
        });


        /*
        |--------------------------------------------------------------------------
        | Expand each RLA into individual laboratory-code rows
        |--------------------------------------------------------------------------
        */
        $expandedRows = $rla
            ->getCollection()
            ->flatMap(function ($item) use ($releasedLabCodes) {

                $rows = [];


                foreach (
                    $item->laboratory_code as $index => $labCode
                ) {

                    $labCode = trim((string) $labCode);

                    $normalizedLabCode =
                        strtoupper($labCode);


                    /*
                    |--------------------------------------------------------------------------
                    | Only show status = 8 laboratory codes
                    |--------------------------------------------------------------------------
                    */
                    if (
                        !$releasedLabCodes->contains(
                            $normalizedLabCode
                        )
                    ) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Get status/signature for exact laboratory code
                    |--------------------------------------------------------------------------
                    */
                    $labStatus = DB::table('laboratory_status')
                        ->where(
                            'laboratory_code',
                            $labCode
                        )
                        ->where('status', 8)
                        ->first();


                    if (!$labStatus) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Clone original row
                    |--------------------------------------------------------------------------
                    */
                    $newItem = clone $item;


                    /*
                    |--------------------------------------------------------------------------
                    | Individual laboratory code
                    |--------------------------------------------------------------------------
                    */
                    $newItem->laboratory_code =
                        $labCode;


                    /*
                    |--------------------------------------------------------------------------
                    | Matching sample description
                    |--------------------------------------------------------------------------
                    */
                    $newItem->sample_description = [
                        $item->sample_description[$index]
                        ?? ''
                    ];


                    /*
                    |--------------------------------------------------------------------------
                    | Matching sample code
                    |--------------------------------------------------------------------------
                    */
                    $newItem->sample_code = [
                        $item->sample_code[$index]
                        ?? ''
                    ];


                    /*
                    |--------------------------------------------------------------------------
                    | Status from laboratory_status
                    |--------------------------------------------------------------------------
                    */
                    $newItem->status =
                        (int) $labStatus->status;


                    /*
                    |--------------------------------------------------------------------------
                    | Customer signature per laboratory code
                    |--------------------------------------------------------------------------
                    */
                    $newItem->customer_signature =
                        $labStatus->customer_signature
                        ?? null;


                    /*
                    |--------------------------------------------------------------------------
                    | Same report test number for same RLA
                    |--------------------------------------------------------------------------
                    */
                    $newItem->report_test_no =
                        $item->report_test_no;


                    /*
                    |--------------------------------------------------------------------------
                    | Get exact release date from lf_06_04
                    |--------------------------------------------------------------------------
                    */
                    $release = DB::table('lf_06_04')
                        ->where(
                            'lf_06_02_id',
                            $item->id
                        )
                        ->where(
                            'laboratory_code',
                            $labCode
                        )
                        ->first();


                    $newItem->date_approved_release =
                        $release->date_approved_release
                        ?? null;


                    $rows[] = $newItem;
                }


                return $rows;
            })
            ->values();

        $search = trim((string) $request->input('search', ''));
        $matchingReleasedLabCodeExists = $search !== '' && $releasedLabCodes->contains(function ($code) use ($search) {
            return stripos((string) $code, $search) !== false;
        });
        if ($matchingReleasedLabCodeExists) {
            $expandedRows = $expandedRows->filter(function ($row) use ($search) {
                return stripos((string) ($row->laboratory_code ?? ''), $search) !== false;
            })->values();
        }

        $rla->setCollection(
            $expandedRows
        );

        return view(
            'ReceivingReleasingLogbook.index',
            compact(
                'rla',
                'clients'
            )
        );
    }

/*
|--------------------------------------------------------------------------
| Download Receiving/Releasing PDF
|--------------------------------------------------------------------------
*/
public function downloadPdfReleasing(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Released laboratory codes
    |--------------------------------------------------------------------------
    */
    $releasedLabCodes = DB::table('laboratory_status')
        ->where('status', 8)
        ->whereNotNull('laboratory_code')
        ->pluck('laboratory_code')
        ->map(function ($code) {
            return strtoupper(trim((string) $code));
        })
        ->filter()
        ->unique()
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Get RLA records
    |--------------------------------------------------------------------------
    */
    $rla = DB::table('lf_06_02')
        ->join(
            'clients',
            'lf_06_02.user_id',
            '=',
            'clients.id'
        )
        ->where(function ($query) use ($releasedLabCodes) {

            if ($releasedLabCodes->isEmpty()) {
                $query->whereRaw('1 = 0');
                return;
            }


            foreach ($releasedLabCodes as $labCode) {

                $query->orWhereJsonContains(
                    'lf_06_02.laboratory_code',
                    $labCode
                );
            }
        })
        ->select(
            'lf_06_02.id',
            'lf_06_02.RLA_no',
            'lf_06_02.sample_received_by',
            'lf_06_02.date_received',
            'lf_06_02.or_no',

            'lf_06_02.laboratory_code',
            'lf_06_02.sample_description',
            'lf_06_02.sample_code',
            'lf_06_02.report_test_no',

            'clients.company_name'
        )
        ->orderBy(
            'lf_06_02.RLA_no',
            'ASC'
        )
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Decode JSON values
    |--------------------------------------------------------------------------
    */
    $rla = $rla->map(function ($item) {

        $decodeJson = function ($value) {

            if (empty($value)) {
                return [];
            }

            if (is_array($value)) {
                return $value;
            }

            $decoded = json_decode(
                $value,
                true
            );


            if (
                json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
            ) {
                return $decoded;
            }


            return [$value];
        };


        $item->laboratory_code =
            $decodeJson(
                $item->laboratory_code
            );


        $item->sample_description =
            $decodeJson(
                $item->sample_description
            );


        $item->sample_code =
            $decodeJson(
                $item->sample_code
            );


        $item->report_test_no =
            $decodeJson(
                $item->report_test_no
            );


        return $item;
    });


    /*
    |--------------------------------------------------------------------------
    | Create individual PDF rows per laboratory code
    |--------------------------------------------------------------------------
    */
    $rla = $rla
        ->flatMap(function ($item) use ($releasedLabCodes) {

            $rows = [];


            foreach (
                $item->laboratory_code as $index => $labCode
            ) {

                $labCode = trim(
                    (string) $labCode
                );


                $normalizedLabCode =
                    strtoupper(
                        $labCode
                    );


                /*
                |--------------------------------------------------------------------------
                | Only released laboratory codes
                |--------------------------------------------------------------------------
                */
                if (
                    !$releasedLabCodes->contains(
                        $normalizedLabCode
                    )
                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Get status and customer signature
                |--------------------------------------------------------------------------
                */
                $labStatus = DB::table('laboratory_status')
                    ->where(
                        'laboratory_code',
                        $labCode
                    )
                    ->where(
                        'status',
                        8
                    )
                    ->first();


                if (!$labStatus) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Clone row
                |--------------------------------------------------------------------------
                */
                $newItem = clone $item;


                /*
                |--------------------------------------------------------------------------
                | Individual laboratory code
                |--------------------------------------------------------------------------
                */
                $newItem->laboratory_code =
                    $labCode;


                /*
                |--------------------------------------------------------------------------
                | Matching sample description
                |--------------------------------------------------------------------------
                */
                $newItem->sample_description = [
                    $item->sample_description[$index]
                    ?? ''
                ];


                /*
                |--------------------------------------------------------------------------
                | Matching sample code
                |--------------------------------------------------------------------------
                */
                $newItem->sample_code = [
                    $item->sample_code[$index]
                    ?? ''
                ];


                /*
                |--------------------------------------------------------------------------
                | Same report test number for same RLA
                |--------------------------------------------------------------------------
                */
                $newItem->report_test_no =
                    $item->report_test_no;


                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */
                $newItem->status =
                    (int) $labStatus->status;


                /*
                |--------------------------------------------------------------------------
                | Customer signature for exact laboratory code
                |--------------------------------------------------------------------------
                */
                $newItem->customer_signature =
                    $labStatus->customer_signature
                    ?? null;


                /*
                |--------------------------------------------------------------------------
                | Exact release date
                |--------------------------------------------------------------------------
                */
                $release = DB::table('lf_06_04')
                    ->where(
                        'lf_06_02_id',
                        $item->id
                    )
                    ->where(
                        'laboratory_code',
                        $labCode
                    )
                    ->first();


                $newItem->date_approved_release =
                    $release->date_approved_release
                    ?? null;


                $rows[] = $newItem;
            }


            return $rows;
        })
        ->values();


    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */
    $logoPath = public_path(
        'assets/images/bfarlogo.png'
    );


    $logoSrc = null;


    if (
        file_exists(
            $logoPath
        )
    ) {

        $logoData = base64_encode(
            file_get_contents(
                $logoPath
            )
        );


        $logoSrc =
            'data:image/png;base64,'
            . $logoData;
    }


    /*
    |--------------------------------------------------------------------------
    | Generate PDF
    |--------------------------------------------------------------------------
    */
    $pdf = Pdf::loadView(
        'ReceivingReleasingLogbook.download',
        [
            'rla' =>
                $rla,

            'logoSrc' =>
                $logoSrc,
        ]
    )
        ->setPaper(
            'a4',
            'landscape'
        )
        ->setOptions([
            'isHtml5ParserEnabled' =>
                true,

            'isPhpEnabled' =>
                true,

            'isRemoteEnabled' =>
                true,

            'defaultFont' =>
                'DejaVu Sans',
        ]);


    /*
    |--------------------------------------------------------------------------
    | Download
    |--------------------------------------------------------------------------
    */
    return $pdf->download(
        'SAMPLE RECEIVING AND RELEASING LOGBOOK.pdf'
    );
}
public function saveReleasingSignature(Request $request)
{
    $request->validate([
        'rla_id' => 'required|integer',
        'laboratory_code' => 'required|string|max:255',
        'signature' => 'required|string',
    ]);

    $labCode = trim((string) $request->laboratory_code);

    $rla = DB::table('lf_06_02')
        ->where('id', $request->rla_id)
        ->first();

    if (!$rla) {
        return response()->json([
            'status' => false,
            'message' => 'RLA record not found.'
        ], 404);
    }

    $laboratoryCodes = json_decode(
        $rla->laboratory_code ?? '[]',
        true
    );

    if (!is_array($laboratoryCodes)) {
        $laboratoryCodes = [];
    }

    $belongsToRla = collect($laboratoryCodes)
        ->contains(function ($code) use ($labCode) {

            return strtoupper(trim((string) $code))
                === strtoupper(trim($labCode));
        });

    if (!$belongsToRla) {
        return response()->json([
            'status' => false,
            'message' => 'Laboratory code does not belong to this RLA.'
        ], 422);
    }

    $labStatus = DB::table('laboratory_status')
        ->where('laboratory_code', $labCode)
        ->where('status', 8)
        ->first();

    if (!$labStatus) {
        return response()->json([
            'status' => false,
            'message' => 'Laboratory code is not ready for release.'
        ], 422);
    }

    DB::table('laboratory_status')
        ->where('id', $labStatus->id)
        ->update([
            'customer_signature' => $request->signature,
        ]);

    return response()->json([
        'status' => true,
        'message' => 'Customer signature saved successfully.'
    ]);
}
}
