@extends('layouts.app')

@section('content')
<div class="container-fluid content-inner mt-n5 py-0">
    <div class="card">
        <div class="card-header"><h4 class="card-title"><span class="badge bg-success p-3">LF 02-01 : ENVIRONMENTAL CONDITIONS MONITORING</span></h4></div>
        <div class="card-body">
            <form action="{{ route('environmental_plan.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="laboratory_code" class="form-label">Laboratory Code / RLA</label>
                        <select id="laboratory_code" name="laboratory_code" class="form-select" required>
                            <option value="">Select a laboratory code</option>
                            @foreach($laboratoryEntries as $entry)
                                <option value="{{ $entry['laboratory_code'] }}" data-description="{{ $entry['analysis_description'] }}" data-rla="{{ $entry['rla_no'] }}" {{ old('laboratory_code') === $entry['laboratory_code'] ? 'selected' : '' }}>
                                    {{ $entry['laboratory_code'] }} (RLA {{ $entry['rla_no'] }})
                                </option>
                            @endforeach
                        </select>
                        @error('laboratory_code')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4"><label class="form-label">Name of Laboratory</label><input id="laboratory_name" name="laboratory_name" class="form-control" value="{{ old('laboratory_name', 'Regional Fisheries Laboratory XII') }}" readonly></div>
                    <div class="col-md-4"><label class="form-label">Area</label><input name="area" class="form-control" value="{{ old('area', 'Laboratory') }}" readonly></div>
                    <input type="hidden" name="analysis_description" id="analysis_description" value="{{ old('analysis_description') }}">
                    <input type="hidden" name="rla_no" id="rla_no" value="{{ old('rla_no') }}">
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center">
                        <thead class="table-light">
                            <tr><th rowspan="3">Date</th><th colspan="2">Temperature</th><th colspan="2">Humidity</th><th rowspan="3">Remarks<br><small>(Analysis Conducted / Lab Code / RLA)</small></th><th rowspan="3">Analyst</th></tr>
                            <tr><th colspan="2"><small><i>CHE/FIS: 23 ± 3 °C; MIC: 21–23 °C</i></small></th><th colspan="2"><small><i>CHE/FIS: 50 ± 15%; MIC: 45–50%</i></small></th></tr>
                            <tr><th>AM</th><th>PM</th><th>AM</th><th>PM</th></tr>
                        </thead>
                        <tbody><tr>
                            <td><input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required></td>
                            <td><input type="number" step="0.01" name="temperature_am" class="form-control" value="{{ old('temperature_am') }}"></td>
                            <td><input type="number" step="0.01" name="temperature_pm" class="form-control" value="{{ old('temperature_pm') }}"></td>
                            <td><input type="number" step="0.01" name="humidity_am" class="form-control" value="{{ old('humidity_am') }}"></td>
                            <td><input type="number" step="0.01" name="humidity_pm" class="form-control" value="{{ old('humidity_pm') }}"></td>
                            <td id="remark_preview" class="text-start">Select a laboratory code</td>
                            <td><input type="text" name="analyst" class="form-control" value="{{ old('analyst') }}"></td>
                        </tr></tbody>
                    </table>
                </div>
                <div class="d-flex gap-2"><button type="submit" class="btn btn-primary">Save Reading</button><a href="{{ route('environmental_plan/index') }}" class="btn btn-secondary">Back</a></div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const selector = document.getElementById('laboratory_code');
    const description = document.getElementById('analysis_description');
    const rla = document.getElementById('rla_no');
    const preview = document.getElementById('remark_preview');
    function updateRemark() {
        const option = selector.options[selector.selectedIndex];
        const analysis = option?.dataset.description || '';
        const rlaNo = option?.dataset.rla || '';
        description.value = analysis;
        rla.value = rlaNo;
        preview.textContent = option?.value ? [analysis, option.value, rlaNo].filter(Boolean).join(' / ') : 'Select a laboratory code';
    }
    selector.addEventListener('change', updateRemark);
    updateRemark();
})();
</script>
@endsection
