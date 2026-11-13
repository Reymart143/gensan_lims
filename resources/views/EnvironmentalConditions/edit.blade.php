@extends('layouts.app')

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <div class="card">
        <div class="card-header"><h4 class="card-title"><span class="badge bg-success p-3">LF 02-01 : ENVIRONMENTAL CONDITIONS MONITORING — EDIT</span></h4></div>
        <div class="card-body">
            @php
                $remark = implode(' / ', array_filter([
                    $environmental->analysis_description ?? '',
                    $environmental->laboratory_code ?? '',
                    $environmental->rla_no ?? '',
                ]));
            @endphp
            <form action="{{ route('environmental.update') }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" value="{{ $environmental->id }}">
                <input type="hidden" name="laboratory_name" value="{{ $environmental->laboratory_name }}">
                <input type="hidden" name="laboratory_code" value="{{ $environmental->laboratory_code }}">
                <input type="hidden" name="analysis_description" value="{{ $environmental->analysis_description }}">
                <input type="hidden" name="rla_no" value="{{ $environmental->rla_no }}">
                <input type="hidden" name="area" value="{{ $environmental->area }}">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <colgroup><col style="width:14%"><col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:42%"><col style="width:12%"></colgroup>
                        <thead>
                            <tr><th>Area<br><span class="fw-normal">{{ $environmental->area }}</span></th><th colspan="6" class="text-start"><u>Name of Laboratory</u><br>{{ $environmental->laboratory_name }}</th></tr>
                            <tr><th><i>Requirements</i></th><th colspan="2">Temperature<br><small><i>CHE/FIS: 23 &plusmn; 3 &deg;C<br>MIC: 21&ndash;23 &deg;C</i></small></th><th colspan="2">Humidity<br><small><i>CHE/FIS: 50 &plusmn; 15%<br>MIC: 45&ndash;50%</i></small></th><th>Remarks<br>(Analysis Conducted / Lab Code / RLA)</th><th>Analyst</th></tr>
                            <tr><th>Date</th><th>AM</th><th>PM</th><th>AM</th><th>PM</th><th></th><th></th></tr>
                        </thead>
                        <tbody><tr>
                            <td><input type="date" name="date" class="form-control" value="{{ old('date', $environmental->date) }}" required></td>
                            <td><input type="number" step="0.01" name="temperature_am" class="form-control" value="{{ old('temperature_am', $environmental->temperature_am) }}"></td>
                            <td><input type="number" step="0.01" name="temperature_pm" class="form-control" value="{{ old('temperature_pm', $environmental->temperature_pm) }}"></td>
                            <td><input type="number" step="0.01" name="humidity_am" class="form-control" value="{{ old('humidity_am', $environmental->humidity_am) }}"></td>
                            <td><input type="number" step="0.01" name="humidity_pm" class="form-control" value="{{ old('humidity_pm', $environmental->humidity_pm) }}"></td>
                            <td class="text-start">{{ $remark }}</td>
                            <td><input type="text" name="analyst" class="form-control" value="{{ old('analyst', $environmental->analyst) }}"></td>
                        </tr></tbody>
                    </table>
                </div>
                <div class="row mb-3"><div class="col-md-4"><label class="form-label">Checked by</label><input type="text" name="checked_by" class="form-control" value="{{ old('checked_by', $environmental->checked_by) }}"></div></div>
                <button type="submit" class="btn btn-primary">Update Reading</button>
                <a href="{{ route('environmental_plan/index') }}" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
@endsection
