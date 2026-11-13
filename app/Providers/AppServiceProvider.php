<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         View::composer('*', function ($view) {
            $analystWorksheetCount = 0;

            if (Auth::check()) {
                $user = Auth::user();
                $userRole = (int) $user->role;
                $unit = match ($userRole) {
                    2, 4 => 'FIS',
                    3 => 'MIC',
                    7 => 'CHEM',
                    0 => null,
                    default => '__NO_ACCESS__',
                };

                $statusRows = DB::table('laboratory_status')
                    ->where('status', 3)
                    ->get(['laboratory_code', 'rla_no']);

                if ($unit === null && $userRole === 0) {
                    $analystWorksheetCount = $statusRows->count();
                } elseif ($unit !== '__NO_ACCESS__') {
                    $pendingKeys = $statusRows->mapWithKeys(function ($status) {
                        $key = strtoupper(trim((string) $status->rla_no)) . '|' .
                            strtoupper(trim((string) $status->laboratory_code));
                        return [$key => true];
                    });

                    $rlaRows = DB::table('lf_06_02')
                        ->get(['RLA_no', 'laboratory_code', 'lab_unit']);

                    foreach ($rlaRows as $rla) {
                        $codes = json_decode($rla->laboratory_code ?? '[]', true);
                        $units = json_decode($rla->lab_unit ?? '[]', true);
                        $codes = is_array($codes) ? $codes : [];
                        $units = is_array($units) ? $units : [];

                        foreach ($codes as $index => $code) {
                            $normalizedUnit = strtoupper(trim((string) ($units[$index] ?? '')));
                            $matchesUnit = $unit === 'MIC'
                                ? in_array($normalizedUnit, ['MIC', 'MICRO', 'MICROBIOLOGY'], true)
                                : $normalizedUnit === $unit;

                            $key = strtoupper(trim((string) $rla->RLA_no)) . '|' .
                                strtoupper(trim((string) $code));

                            if ($matchesUnit && $pendingKeys->has($key)) {
                                $analystWorksheetCount++;
                            }
                        }
                    }
                }
            }

            $view->with('analystWorksheetCount', $analystWorksheetCount);
        });
    }
}
