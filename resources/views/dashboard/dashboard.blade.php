@extends('layouts.app')
@section('content')
<script>
    window.chartData = {
        months: @json($months),
        totalRla: @json($totalRla),
        totalRelease: @json($totalRelease)
    };
</script>
@php
    $totalLabTest = $isDepartmentDashboard ? $departmentRlaCount : DB::table('lf_06_02')->count();
    $totalEquipment = DB::table('lf_03_05')->count();
    $orderForPayment = DB::table('lf_06_03')->count();
    $jobRouteForm = DB::table('lf_06_04')->count();
    $totalReleasing = $isDepartmentDashboard ? $departmentReleaseCount : DB::table('lf_06_02')->where('status', 8)->count();
  $totalClients = DB::table('clients')
    ->whereDate('created_at', today())
    ->count();
    $registeredEquipments = DB::table('lf_03_05')->count();
    $sampleStorageLogbooks = DB::table('sample_storage_logbooks')->count();
@endphp
<style>
    .d-slider1 .swiper-wrapper{
    gap: 12px !important;
}

.d-slider1 .swiper-slide{
    width: 250px !important;
    margin-right: 0 !important;
}

.card-slide{
    margin-bottom:0;
}
</style>
    <div class="container-fluid py-0" style="width: 95%;margin-top:-7%">
        <div class="row">
            <div class="col-md-12 col-lg-12">
                <div class="row row-cols-1">
                    <style>
                        /* Card background colors */
                        .card-slide:nth-child(1){
                            background: #0d6efd !important;
                            color:white;
                        }

                        .card-slide:nth-child(2){
                            background: #198754 !important;
                            color:white;
                        }

                        .card-slide:nth-child(3){
                            background: #0dcaf0 !important;
                            color:white;
                        }

                        .card-slide:nth-child(4){
                            background: #ffc107 !important;
                            color:#000;
                        }

                        .card-slide:nth-child(5){
                            background: #dc3545 !important;
                            color:white;
                        }

                        .card-slide:nth-child(6){
                            background: #212529 !important;
                            color:white;
                        }


                        /* Make icon white */
                        .card-slide .icon{
                            background:white !important;
                        }


                        /* Icon color */
                        .card-slide:nth-child(1) .icon svg{
                            fill:#0d6efd;
                        }

                        .card-slide:nth-child(2) .icon svg{
                            fill:#198754;
                        }

                        .card-slide:nth-child(3) .icon svg{
                            fill:#0dcaf0;
                        }

                        .card-slide:nth-child(4) .icon svg{
                            fill:#ffc107;
                        }

                        .card-slide:nth-child(5) .icon svg{
                            fill:#dc3545;
                        }

                        .card-slide:nth-child(6) .icon svg{
                            fill:#212529;
                        }


                        /* Text color */
                        .card-slide p,
                        .card-slide h4{
                            color:inherit !important;
                        }

                    </style>
                    <div class="overflow-hidden d-slider1">
                        <ul class="p-0 m-0 mb-2 swiper-wrapper list-inline">

                            <!-- Total Lab Test -->
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="700">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">

                                        <div class="icon bg-primary text-white rounded p-3">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
                                                <path d="M9 2V4H10V9.59L5.29 14.29C4.4 15.18 4 16.39 4 17.66A4.34 4.34 0 0 0 8.34 22H15.66A4.34 4.34 0 0 0 20 17.66C20 16.39 19.6 15.18 18.71 14.29L14 9.59V4H15V2H9Z"/>
                                            </svg>
                                        </div>

                                        <div class="text-end">
                                            <p class="mb-1">{{ $isDepartmentDashboard ? $dashboardUnit . ' RLA Requests' : 'Total Lab Test' }}</p>
                                            <h4 class="mb-0">{{ $totalLabTest ?: '0' }}</h4>
                                        </div>

                                    </div>
                                </div>
                            </li>

                            @unless($isDepartmentDashboard)
                            <!-- Order for Payment -->
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="800">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">

                                        <div class="icon bg-success text-white rounded p-3">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
                                            <path d="M2 6H22V18H2V6M4 8V16H20V8H4M12 9C10.34 9 9 10.34 9 12S10.34 15 12 15 15 13.66 15 12 13.66 9 12 9Z"/>
                                        </svg>
                                        </div>

                                        <div class="text-end">
                                            <p class="mb-1"> Equipment</p>
                                            <h4 class="mb-0">{{ $totalEquipment ?: '0' }}</h4>
                                        </div>

                                    </div>
                                </div>
                            </li>
                            @endunless

                            @unless($isDepartmentDashboard)
                            <!-- Job Route Form -->
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="900">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">

                                        <div class="icon bg-info text-white rounded p-3">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
                                            <path d="M19 3H14.82C14.4 1.84 13.3 1 12 1S9.6 1.84 9.18 3H5C3.89 3 3 3.89 3 5V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V5C21 3.89 20.11 3 19 3Z"/>
                                        </svg>
                                        </div>

                                        <div class="text-end">
                                            <p class="mb-1">Job Route Form</p>
                                            <h4 class="mb-0">{{ $jobRouteForm ?: '0' }}</h4>
                                        </div>

                                    </div>
                                </div>
                            </li>
                            @endunless

                            <!-- Releasing -->
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="1000">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">

                                        <div class="icon bg-warning text-success rounded p-3">
                                            <svg width="30" viewBox="0 0 24 24">
                                                <path fill="currentColor"
                                                    d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.59L19 8Z"/>
                                            </svg>
                                        </div>

                                        <div class="text-end">
                                            <p class="mb-1">{{ $isDepartmentDashboard ? 'Lab Codes Released' : 'Releasing' }}</p>
                                            <h4 class="mb-0">{{ $totalReleasing ?: '0' }}</h4>
                                        </div>

                                    </div>
                                </div>
                            </li>

                            @if($isDepartmentDashboard)
                            <!-- Unit laboratory code total -->
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="1100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="icon bg-info text-white rounded p-3">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h16v3H4zm0 6h16v10H4zm3 2v2h4v-2zm0 4v2h8v-2z"/></svg>
                                        </div>
                                        <div class="text-end">
                                            <p class="mb-1">Laboratory Codes</p>
                                            <h4 class="mb-0">{{ $unitLaboratoryCodeCount }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- Unit codes at analysis worksheet stage -->
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="1200">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="icon bg-danger text-white rounded p-3">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M9 2h6v2h3v18H6V4h3zm1 7v2h4V9zm0 4v2h4v-2z"/></svg>
                                        </div>
                                        <div class="text-end">
                                            <p class="mb-1">Still in Analysis Worksheet</p>
                                            <h4 class="mb-0">{{ $pendingAnalysisCount }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- Unit codes released today -->
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="1300">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="icon bg-success text-white rounded p-3">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20m-1 5h2v5l4 2-1 1.7-5-2.7z"/></svg>
                                        </div>
                                        <div class="text-end">
                                            <p class="mb-1">Released Today</p>
                                            <h4 class="mb-0">{{ $releasedTodayCount }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- Unit codes still in the workflow -->
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="1400">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="icon bg-dark text-white rounded p-3">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20m1 5v5.4l3.5 2.1-1 1.7-4.5-2.8V7z"/></svg>
                                        </div>
                                        <div class="text-end">
                                            <p class="mb-1">In Progress</p>
                                            <h4 class="mb-0">{{ $inProgressCount }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @endif

                            @unless($isDepartmentDashboard)
                            <!-- Clients -->
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="1100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">

                                        <div class="icon bg-danger text-white rounded p-3">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
                                                <path d="M16 11C17.66 11 19 9.66 19 8S17.66 5 16 5 13 6.34 13 8 14.34 11 16 11M8 11C9.66 11 11 9.66 11 8S9.66 5 8 5 5 6.34 5 8 6.34 11 8 11M8 13C5.33 13 0 14.34 0 17V19H16V17C16 14.34 10.67 13 8 13Z"/>
                                            </svg>
                                        </div>

                                        <div class="text-end">
                                            <p class="mb-1">Customers</p>
                                            <h4 class="mb-0">{{ $totalClients ?: '0' }}</h4>
                                        </div>

                                    </div>
                                </div>
                            </li>

                        
                            <li class="swiper-slide card card-slide" data-aos="fade-up" data-aos-delay="1300">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">

                                        <div class="icon bg-dark text-white rounded p-3">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="white">
                                                <path d="M12 3C7.58 3 4 4.79 4 7V17C4 19.21 7.58 21 12 21S20 19.21 20 17V7C20 4.79 16.42 3 12 3Z"/>
                                            </svg>
                                        </div>

                                        <div class="text-end">
                                            <p class="mb-1">Sample Storage Logbook</p>
                                            <h4 class="mb-0">{{ $sampleStorageLogbooks ?: '0' }}</h4>
                                        </div>

                                    </div>
                                </div>
                            </li>
                            @endunless

                        </ul>
                    </div>
                </div>
            </div>
            @unless($isDepartmentDashboard)
            <div class="col-md-12 col-lg-7" style="margin-top:13px">
                <div class="row" >
                    <div class="col-md-12">
                        <div class="card" data-aos="fade-up" data-aos-delay="800">
                        <div class="flex-wrap card-header d-flex justify-content-between align-items-center">
                            <div class="header-title">
                                <h4 class="card-title">CUSTOMER'S LOCATION</h4>
                                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.awesome-markers@2.0.4/dist/leaflet.awesome-markers.css">
                                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

                                <script src="https://cdn.jsdelivr.net/npm/leaflet.awesome-markers@2.0.4/dist/leaflet.awesome-markers.js"></script>     
                            </div>
                            @php

                                $clients = DB::table('clients')
                                    ->select('company_name','address')
                                    ->whereNotNull('address')
                                    ->where('address','!=','')
                                    ->get();
                                @endphp
                            {{-- <div class="d-flex align-items-center align-self-center">
                                <div class="d-flex align-items-center text-primary">
                                    <svg class="icon-12" xmlns="http://www.w3.org/2000/svg" width="12" viewBox="0 0 24 24" fill="currentColor">
                                    <g>
                                        <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                    </g>
                                    </svg>
                                    <div class="ms-2">
                                    <span class="text-gray">RLA</span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center ms-3 text-info">
                                    <svg class="icon-12" xmlns="http://www.w3.org/2000/svg" width="12" viewBox="0 0 24 24" fill="currentColor">
                                    <g>
                                        <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                    </g>
                                    </svg>
                                    <div class="ms-2">
                                    <span class="text-gray">Release</span>
                                    </div>
                                </div>
                            </div> --}}
                            
                        </div>
                            <div class="card-body">
                            <div id="clientMap" style="height:450px;border-radius:10px;"></div>

                                    <script>
                                        
                                        var mbAttr='Powered by: OBX SOLUTIONS TECHNOLOGY INC. © OpenStreetMap';

                                            var streets=L.tileLayer(
                                            'http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',
                                            {
                                                maxZoom:20,
                                                subdomains:['mt0','mt1','mt2','mt3'],
                                                attribution:mbAttr
                                            });

                                            var satellite=L.tileLayer(
                                            'http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',
                                            {
                                                maxZoom:20,
                                                subdomains:['mt0','mt1','mt2','mt3'],
                                                attribution:mbAttr
                                            });

                                            var map=L.map('clientMap',{
                                                center:[6.116564,125.176742],
                                                zoom:9,
                                                layers:[satellite]
                                            });

                                            L.control.layers({
                                                "Street":streets,
                                                "Satellite":satellite
                                            }).addTo(map);
                                        </script>
                                        <script>

                                        var userIcon = L.AwesomeMarkers.icon({
                                                icon: 'user',
                                                prefix: 'fa',
                                                markerColor: 'red',
                                                iconColor: 'white'
                                            });

                                         @foreach($clients as $client)

                                        @php
                                            $coord = explode(',', $client->address);
                                            $locationName = $client->address;

                                            if(count($coord) == 2){

                                                $lat = trim($coord[0]);
                                                $lng = trim($coord[1]);

                                                $locationName = \Illuminate\Support\Facades\Cache::remember(
                                                    'client_location_'.$client->address,
                                                    now()->addDays(30),
                                                    function() use ($lat, $lng, $client){

                                                        $response = Http::timeout(3)
                                                            ->withHeaders([
                                                                'User-Agent' => 'YourAppName'
                                                            ])
                                                            ->get("https://nominatim.openstreetmap.org/reverse", [
                                                                'format' => 'json',
                                                                'lat' => $lat,
                                                                'lon' => $lng
                                                            ]);

                                                        $location = $response->json();

                                                        return $location['display_name'] ?? $client->address;
                                                    }
                                                );
                                            }
                                            @endphp


                                            @if(count($coord)==2)

                                            L.marker([
                                                {{ trim($coord[0]) }},
                                                {{ trim($coord[1]) }}
                                            ],{
                                                icon:userIcon
                                            })
                                            .addTo(map)

                                            .bindPopup(`
                                                <b>{{ $client->company_name }}</b>
                                                <br>
                                                {{ $locationName }}
                                            `);

                                            @endif

                                            @endforeach

                                            </script>
                            </div>
                        </div>
                    </div>
                   
                </div>
            </div>
            <div class="col-md-12 col-lg-5" style="margin-top:13px">
                <div class="row">
                    <div class="col-md-12 col-lg-12">
                        <div class="card credit-card-widget" data-aos="fade-up" data-aos-delay="900">
                            <div class="pb-4 border-0 card-header">
                        </div>
                        <div class="card-body">
                            @php
                                $year = date('Y');

                                $yearlyGrandTotal = DB::table('lf_06_02')
                                    ->whereYear('date_payment', $year)
                                    ->sum(DB::raw("CAST(REPLACE(payment, ',', '') AS DECIMAL(15,2))"));
                            @endphp

                                <div class="mb-4">
                                    <div class="flex-wrap d-flex justify-content-between">

                                     <h2 class="mb-2">
                                        {{ $yearlyGrandTotal > 0 ? '₱ ' . number_format($yearlyGrandTotal, 2) : '₱ 0.00' }}
                                    </h2>

                                        <div>
                                            <span class="badge bg-success rounded-pill">
                                                {{ date('Y') }}
                                            </span>
                                        </div>

                                    </div>

                                    <p class="text-info">Customer Payments</p>
                                   <div style="height:344px;">
                                        <canvas id="paymentChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <script>
                const ctx = document.getElementById('paymentChart').getContext('2d');

                const gradient = ctx.createLinearGradient(0, 0, 0, 280);
                gradient.addColorStop(0, 'rgba(59,130,246,.35)');
                gradient.addColorStop(1, 'rgba(59,130,246,0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [
                            @foreach($report as $row)
                                "{{ $row['month'] }}",
                            @endforeach
                        ],

                        datasets: [{
                            label: 'Payments',
                            data: [
                                @foreach($report as $row)
                                    {{ $row['payments'] }},
                                @endforeach
                            ],

                            borderColor: '#3B82F6',
                            backgroundColor: gradient,
                            fill: true,
                            borderWidth: 3,
                            tension: .45,
                            cubicInterpolationMode: 'monotone',

                            pointRadius: 3,
                            pointHoverRadius: 8,
                            pointHitRadius: 25,

                            pointBackgroundColor: '#3B82F6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }]
                    },

                    options: {

                        responsive: true,
                        maintainAspectRatio: false,

                        interaction: {
                            mode: 'index',
                            intersect: false
                        },

                        hover: {
                            mode: 'index',
                            intersect: false
                        },

                        plugins: {

                            legend: {
                                display: false
                            },

                            tooltip: {
                                enabled: true,
                                mode: 'index',
                                intersect: false,

                                backgroundColor: '#ffffff',
                                titleColor: '#111827',
                                bodyColor: '#111827',

                                borderColor: '#d1d5db',
                                borderWidth: 1,
                                padding: 12,

                                displayColors: true,

                                callbacks: {
                                    label: function(context){
                                        return '₱ ' + Number(context.raw).toLocaleString();
                                    }
                                }
                            }

                        },

                        scales: {

                            x: {
                                grid: {
                                    display: false
                                },
                                border: {
                                    display: false
                                }
                            },

                            y: {
                                beginAtZero: true,

                                grid: {
                                    color: 'rgba(148,163,184,.15)'
                                },

                                border: {
                                    display: false
                                },

                                ticks: {
                                    callback: function(value){
                                        return '₱ ' + value.toLocaleString();
                                    }
                                }
                            }

                        }

                    }

                });
            </script>
            @endunless
            <style>
                .report-chart-grid {
                    display: grid;
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    gap: 0.85rem;
                    margin-top: -1rem;
                    margin-bottom: 2%;
                }
                .chart-panel {
                    min-height: 320px;
                    padding: 1rem;
                    border: 1px solid var(--report-border);
                    border-radius: 8px;
                    background: #fff;
                    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
                }
                .chart-title {
                    margin: 0 0 0.75rem;
                    color: var(--report-brand);
                    font-size: 0.92rem;
                    font-weight: 800;
                }
                .chart-box {
                    position: relative;
                    height: 248px;
                }
            </style>
            @if($isDepartmentDashboard)
                <div class="report-chart-grid mt-4" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                    <div class="chart-panel">
                        <h2 class="chart-title">{{ $dashboardUnit }} - ANALYSIS REQUESTED</h2>
                        <div style="height:400px"><canvas id="unitAnalysisChart"></canvas></div>
                    </div>
                    <div class="chart-panel">
                        <h2 class="chart-title">{{ $dashboardUnit }} - MONTHLY RLA / RELEASE</h2>
                        <div style="height:400px"><canvas id="unitMonthlyChart"></canvas></div>
                    </div>
                    <div class="chart-panel">
                        <h2 class="chart-title">{{ $dashboardUnit }} - RELEASES PER DAY (LAST 7 DAYS)</h2>
                        <div style="height:400px"><canvas id="unitDailyReleaseChart"></canvas></div>
                    </div>
                </div>
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="chart-title mb-0">Still in Analysis Worksheet by Laboratory Code</h2>
                        <span class="badge bg-danger">Total: {{ $pendingAnalysisCount }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Laboratory Code</th>
                                    <th>RLA No.</th>
                                    <th>Analysis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingAnalysisCodes as $pendingCode)
                                    <tr>
                                        <td>{{ $pendingCode['laboratory_code'] }}</td>
                                        <td>{{ $pendingCode['rla_no'] }}</td>
                                        <td>{{ $pendingCode['analysis_description'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No laboratory codes are currently in Analysis Worksheet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    new Chart(document.getElementById('unitAnalysisChart'), {
                        type: 'pie',
                        data: {
                            labels: @json(array_keys($unitAnalysis)),
                            datasets: [{
                                label: 'No. of RLA',
                                data: @json(array_values($unitAnalysis)),
                                backgroundColor: ['#198754', '#20c997', '#0d6efd', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#0dcaf0'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'right' } }
                        }
                    });
                    new Chart(document.getElementById('unitMonthlyChart'), {
                        type: 'bar',
                        data: {
                            labels: @json(array_values($months)),
                            datasets: [
                                { label: 'RLA', data: @json($totalRla), backgroundColor: '#0d6efd' },
                                { label: 'Released', data: @json($totalRelease), backgroundColor: '#198754' }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                        }
                    });
                    new Chart(document.getElementById('unitDailyReleaseChart'), {
                        type: 'bar',
                        data: {
                            labels: @json($dailyReleaseLabels),
                            datasets: [{
                                label: 'Released lab codes',
                                data: @json($dailyReleaseCounts),
                                backgroundColor: '#198754'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                        }
                    });
                </script>
            @else
            @php
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

                $rows = DB::table('lf_06_02')->get();

                /*
                |--------------------------------------------------------------------------
                | Count per RLA (unique analysis only)
                |--------------------------------------------------------------------------
                */

                foreach ($rows as $row) {

                    $analysis = json_decode($row->analysis_requested, true);

                    if (!$analysis) {
                        continue;
                    }

                    // mahimong nested array ang imong data
                    $flat = [];

                    array_walk_recursive($analysis, function ($item) use (&$flat) {
                        if (!empty($item)) {
                            $flat[] = strtoupper(trim($item));
                        }
                    });

                    // aron dili ma-count ug balik sulod sa same RLA
                    $flat = array_unique($flat);

                    foreach ($flat as $item) {

                        foreach ($fisAnalysis as $key => $value) {
                            if (strtoupper($key) == $item) {
                                $fisAnalysis[$key]++;
                            }
                        }

                        foreach ($chemAnalysis as $key => $value) {
                            if (strtoupper($key) == $item) {
                                $chemAnalysis[$key]++;
                            }
                        }

                        foreach ($microAnalysis as $key => $value) {
                            if (strtoupper($key) == $item) {
                                $microAnalysis[$key]++;
                            }
                        }

                    }

                }
                @endphp
                <div class="report-chart-grid">
                      <div class="chart-panel">
                            <h2 class="chart-title">FIS - ANALYSIS REQUESTED</h2>
                                <div style="height:400px">
                                 <canvas id="fisChart"></canvas>
                              </div>

                        </div>
                    <div class="chart-panel">
                        <h2 class="chart-title">CHEM - ANALYSIS REQUESTED </h2>
                      
                            <div style="height:400px">
                                <canvas id="chemChart"></canvas>
                            </div>
                    </div>
                    <div class="chart-panel">
                        <h2 class="chart-title">MICRO - ANALYSIS REQUESTED</h2>
                        <div style="height:400px">
                            <canvas id="microChart"></canvas>
                        </div>
                    </div>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                        <script>
                // ================= FIS =================
                    new Chart(document.getElementById('fisChart'), {
                        type: 'pie',
                        data: {
                            labels: {!! json_encode(array_keys($fisAnalysis)) !!},
                            datasets: [{
                                label: 'No. of RLA',
                                data: {!! json_encode(array_values($fisAnalysis)) !!},
                                backgroundColor: [
                                    '#198754',
                                    '#20c997',
                                    '#0d6efd',
                                    '#ffc107',
                                    '#dc3545',
                                    '#6f42c1'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    });


                    // ================= CHEM =================
                    new Chart(document.getElementById('chemChart'), {
                        type: 'pie',
                        data: {
                            labels: {!! json_encode(array_keys($chemAnalysis)) !!},
                            datasets: [{
                                label: 'No. of RLA',
                                data: {!! json_encode(array_values($chemAnalysis)) !!},
                                backgroundColor: [
                                    '#6c757d',
                                    '#0d6efd',
                                    '#198754',
                                    '#ffc107',
                                    '#dc3545',
                                    '#20c997'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    });


                    // ================= MICRO =================
                    new Chart(document.getElementById('microChart'), {
                        type: 'pie',
                        data: {
                            labels: {!! json_encode(array_keys($microAnalysis)) !!},
                            datasets: [{
                                label: 'No. of RLA',
                                data: {!! json_encode(array_values($microAnalysis)) !!},
                                backgroundColor: [
                                    '#dc3545',
                                    '#fd7e14',
                                    '#ffc107',
                                    '#198754',
                                    '#0dcaf0',
                                    '#6f42c1'
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    });
                        </script>
                    {{-- <div class="chart-panel">
                        <h2 class="chart-title">Location Distribution</h2>
                        <div class="chart-box"><canvas id="locationDistributionChart"></canvas></div>
                    </div>
                    <div class="chart-panel">
                        <h2 class="chart-title">Brand / Model Distribution</h2>
                        <div class="chart-box"><canvas id="brandDistributionChart"></canvas></div>
                    </div>
                    <div class="chart-panel">
                        <h2 class="chart-title">Quantity Position</h2>
                        <div class="chart-box"><canvas id="quantitySummaryChart"></canvas></div>
                    </div> --}}
            @endif
                </div>
        </div>
</div>
  
@endsection
