@extends('layouts.app')

@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<div class="container-fluid mt-4">
    <div>
      <div class="row">
      
        {{-- ================== FIS ================== --}}
        <div class="col-lg-4 col-md-12 mb-4" >
            <div class="card shadow h-100">

                <div class="card-header bg-success text-white">
                    <h5 class="mb-2 text-center text-white">FIS SAMPLE DESCRIPTION</h5>
                </div>

                <div class="card-body">

                    <div style="height:400px">
                        <canvas id="fisChart"></canvas>
                    </div>

                    <hr>

                <table class="table table-bordered table-sm">
                        <thead class="table-success">
                            <tr>
                                <th>Analysis Requested</th>
                                <th width="70">total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fisAnalysis as $analysis => $count)
                            <tr>
                                <td>{{ $analysis }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>

            </div>
        </div>

        {{-- ================== CHEM ================== --}}
        <div class="col-lg-4 col-md-12 mb-4">

            <div class="card shadow h-100">

                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-2 text-center text-white">CHEM SAMPLE DESCRIPTION</h5>
                </div>

                <div class="card-body">

                    <div style="height:400px">
                        <canvas id="chemChart"></canvas>
                    </div>

                    <hr>

                    <table class="table table-bordered table-sm">

                            <thead class="table-secondary">
                                <tr>
                                    <th>Analysis Requested</th>
                                    <th width="70">total</th>
                                </tr>
                            </thead>

                            <tbody>

                                @foreach($chemAnalysis as $analysis => $count)
                                <tr>
                                    <td>{{ $analysis }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                                @endforeach

                            </tbody>

                        </table>

                </div>

            </div>

        </div>

        {{-- ================== MICRO ================== --}}
        <div class="col-lg-4 col-md-12 mb-4">

            <div class="card shadow h-100">

                <div class="card-header bg-danger text-white">
                    <h5 class="mb-2 text-center text-white">MICRO SAMPLE DESCRIPTION</h5>
                </div>

                <div class="card-body">

                    <div style="height:400px">
                        <canvas id="microChart"></canvas>
                    </div>

                    <hr>

                    <table class="table table-bordered table-sm">

                        <thead class="table-danger">
                            <tr>
                                <th>Analysis Requested</th>
                                <th width="70">total</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($microAnalysis as $analysis => $count)
                            <tr>
                                <td>{{ $analysis }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>
    </div>
 

</div>

<script>
Chart.register(ChartDataLabels);

function createChart(canvasId, labels, values, color){

    new Chart(document.getElementById(canvasId),{

        type:'bar',

        data:{
            labels: labels,
            datasets:[{
                label:'No. of Samples',
                data: values,
                backgroundColor: color,
                borderColor: color,
                borderWidth:1,
                borderRadius:4,
                barThickness:18
            }]
        },

        options:{

            indexAxis:'y',

            responsive:true,
            maintainAspectRatio:false,

            plugins:{
                legend:{
                    display:false
                },

                datalabels:{
                    color:'#000',
                    anchor:'end',
                    align:'right',
                    font:{
                        size:11,
                        weight:'bold'
                    }
                }
            },

            scales:{

                x:{
                    beginAtZero:true,
                    ticks:{
                        precision:0,
                        stepSize:1
                    },
                    title:{
                        display:true,
                        text:'total'
                    }
                },

                y:{
                    ticks:{
                        autoSkip:false,
                        font:{
                            size:10
                        }
                    },
                    grid:{
                        display:false
                    }
                }

            }

        }

    });

}

createChart(
    'fisChart',
    {!! json_encode(array_keys($fis)) !!},
    {!! json_encode(array_values($fis)) !!},
    '#28a745'
);

createChart(
    'chemChart',
    {!! json_encode(array_keys($chem)) !!},
    {!! json_encode(array_values($chem)) !!},
    '#0d6efd'
);

createChart(
    'microChart',
    {!! json_encode(array_keys($micro)) !!},
    {!! json_encode(array_values($micro)) !!},
    '#dc3545'
);

</script>

@endsection