<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Environmental Conditions Monitoring</title>
    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 11px;
            color: #111;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .header td {
            border: 1px solid #777;
            padding: 3px 7px;
            color: #777;
        }

        .header .agency {
            font-size: 13px;
            line-height: 1.1;
        }

        .meta {
            font-size: 9px;
        }

        .meta strong {
            display: block;
            font-size: 12px;
            margin-top: 5px;
        }

        .form {
            margin-top: 28px;
            table-layout: fixed;
        }

        .form th,
        .form td {
            border: 1px solid #111;
            padding: 3px;
        }

        .form th {
            text-align: center;
        }

        .form .lab-name {
            text-align: left;
            height: 42px;
        }

        .form .lab-name strong {
            text-decoration: underline;
        }

        .form .req {
            font-size: 10px;
            font-style: italic;
        }

        .form .limits {
            font-size: 9px;
            font-weight: normal;
            font-style: italic;
        }

        .form .reading td {
            height: 14px;
        }

        .checked {
            margin-top: 40px;
            color: #777;
        }

        .signature {
            margin-top: 30px;
            border-top: 1px solid #777;
            width: 240px;
            padding-top: 2px;
        }

        .sheet {
            page-break-after: always;
        }

        .sheet:last-child {
            page-break-after: auto;
        }
    </style>
</head>

<body>
    @foreach ($sheets as $sheet)
        <div class="sheet">
            <table class="header">
                <tr>
                    <td rowspan="3" style="width:14%;text-align:center"><img
                            src="{{ public_path('assets/images/bfarlogo.png') }}" width="82" alt="Logo"></td>
                    <td colspan="4" class="agency"><b>Republic of the Philippines<br>Department of
                            Agriculture<br>BUREAU OF FISHERIES AND AQUATIC RESOURCES<br>REGIONAL FISHERIES LABORATORY
                            XII</b><br>J. Catolico St., Lagao, General Santos City</td>
                </tr>
                <tr>
                    <td style="width:14%"><span class="meta">Document Type<strong>Laboratory Form</strong></span></td>
                    <td style="width:12%"><span class="meta">Revision No:<strong>0</strong></span></td>
                    <td style="width:30%"><span class="meta">Date Adopted:<strong>30 Jul 2026</strong></span></td>
                    <td><span class="meta">Page No:<strong>Page {{ $loop->iteration }} of
                                {{ $sheets->count() }}</strong></span></td>
                </tr>
                <tr>
                    <td><span class="meta">Document Code:<strong>LF 02-01</strong></span></td>
                    <td colspan="3"><span class="meta">Title:</span>
                        <div style="text-align:center;font-weight:bold;color:#777">ENVIRONMENTAL CONDITIONS MONITORING
                        </div>
                    </td>
                </tr>
            </table>
            <table class="form">
                <colgroup>
                    <col style="width:14%">
                    <col style="width:7.5%">
                    <col style="width:7.5%">
                    <col style="width:7.5%">
                    <col style="width:7.5%">
                    <col style="width:43%">
                    <col style="width:13%">
                </colgroup>
                <tr>
                    <th>Area</th>
                    <th colspan="6" class="lab-name"><strong>Name of
                            Laboratory</strong><br>{{ $sheet['laboratory_name'] }}</th>
                </tr>
                <tr>
                    <th class="req">Requirements</th>
                    <th colspan="2">Temperature<br><span class="limits">CHE/FIS: 23 &plusmn; 3 &deg;C<br>MIC:
                            21&ndash;23 &deg;C</span></th>
                    <th colspan="2">Humidity<br><span class="limits">CHE/FIS: 50 &plusmn; 15%<br>MIC:
                            45&ndash;50%</span></th>
                    <th>Remarks<br>(Analysis Conducted / Lab Code / RLA)</th>
                    <th>Analyst</th>
                </tr>
                <tr>
                    <th>Date</th>
                    <th>AM</th>
                    <th>PM</th>
                    <th>AM</th>
                    <th>PM</th>
                    <th></th>
                    <th></th>
                </tr>
                @php $rows = $sheet['records']; @endphp
                @for ($i = 0; $i < 18; $i++)
                    @php
                        $row = $rows[$i] ?? null;
                        $remarks = $row ? trim((string) ($row->analysis_description ?? '')) . (($row->analysis_description ?? '') && ($row->laboratory_code ?? '') ? ' / ' : '') . trim((string) ($row->laboratory_code ?? '')) . (($row->laboratory_code ?? '') && ($row->rla_no ?? '') ? ' / ' : '') . trim((string) ($row->rla_no ?? '')) : '';
                    @endphp
                    <tr class="reading">
                        <td>{{ $row->date ?? '' }}</td>
                        <td>{{ $row->temperature_am ?? '' }}</td>
                        <td>{{ $row->temperature_pm ?? '' }}</td>
                        <td>{{ $row->humidity_am ?? '' }}</td>
                        <td>{{ $row->humidity_pm ?? '' }}</td>
                        <td>{{ $remarks }}</td>
                        <td>{{ $row->analyst ?? '' }}</td>
                    </tr>
                @endfor
            </table>
            <div class="checked">Checked by:<div class="signature">{{ $sheet['checked_by'] }}</div>
            </div>
        </div>
    @endforeach
</body>

</html>
