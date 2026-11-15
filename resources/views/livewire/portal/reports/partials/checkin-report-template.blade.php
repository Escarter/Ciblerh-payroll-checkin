<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ __('reports.checkin_report').now()->IsoFormat('LL')}}</title>

    <style type="text/css">
        @page {
            margin: 0cm 0cm;
        }

        /** Define now the real margins of every page in the PDF **/
        body {
            margin-top: 0.2cm;
            margin-left: 0.3cm;
            margin-right: 0.3cm;
            margin-bottom: 0cm;
        }

        /** Define the header rules **/
        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 0.1cm;

            /** Extra personal styles **/
            background-color: #17469E;
        }

        /** Define the footer rules **/
        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2cm;

            /** Extra personal styles **/
            text-align: center;
            font-size: medium;
            line-height: 1.5cm;
            border-bottom: 2px solid #17469E;
        }

        .iso-logo {
            position: fixed;
            bottom: 2cm;
            right: .8cm;
            height: 3.1cm;
        }

        .heading {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 22px;
            font-weight: 800;
            padding-bottom: 0;
        }

        .sub-heading {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            font-weight: 500;
            padding-bottom: 2px;
        }

        .badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: .75em;
            font-weight: 400;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }

        .bg-danger {
            background-color: #d32f2f !important;
        }

        .bg-success {
            background-color: #2e7d32 !important;
        }

        .bg-warning {
            background-color: #ffb300 !important;
        }

        .bg-primary {
            background-color: #17469E !important;
        }

        .text-white {
            color: white;
        }

        .table {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        td,
        th {
            border: 1px solid #ddd;
            padding: 3px 4px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .table th {
            padding: 4px;
            text-align: center;
            background-color: #17469E;
            color: white;
            font-size: 9px;
            font-weight: bold;
        }

        .table .employee-col {
            width: 18%;
            text-align: left;
            padding: 4px 6px;
            font-size: 9px;
        }

        .table .qualification-col {
            width: 15%;
            text-align: left;
            padding: 4px 6px;
            font-size: 9px;
        }

        .table .date-col {
            width: auto;
            text-align: center;
            padding: 3px 2px;
            font-size: 8px;
            white-space: nowrap;
        }

        .summary-table {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            border-collapse: collapse;
            width: 80%;
        }

        .summary-table td,
        .summary-table th {
            border: 1px solid #ddd;
            text-align: left;
        }

        .fs-bold {
            font-weight: 600;
            font-size: 18px;
        }

        .w-25 {
            width: 15%;
        }

        .h-25 {
            height: 15%;
        }
    </style>
</head>

<body>
    <header>
    </header>

    <footer>
        <p class=''>{{__('reports.generated_by')}} - {{ auth()->user()->name }} {{__('reports.on_the')}} - {{__('reports.copyright')}} &copy; {{config('app.name')}} {{now()->year}}</p>
    </footer>


    <div class=''>
        <div class='' style="padding-bottom: 40px;">
            <div style="float: left; width: 40%; ">
                <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/logo.jpg')))}}" alt='' style="width:auto;height:80px;">
                <p style="margin-top: -18px; padding-left:15px; font-size: 12px;">{{__('B.P. 3462')}} {{__('Douala')}} - {{__('Cameroon')}} {{__('Tel:')}} +237 233 433 88 88 /
                    <br> 42 71 09 / 699 90 65 68 / 699 92 63 62 <br> {{__("FAX")}} +237 233 42 71 62 {{__('site web')}} : www.groupe-cible.com
                </p>
            </div>

            <p style="margin-left: 63%; width: 35%; font-size: 13px;">
                <span style="font-size: 18px; font-weight:bolder; ">{{__('reports.attachment')}} <small style="font-size: 11px; color:red;">{{\Str::random(10)}}</small></span>
                <br />{{__('reports.period_from')}} {{ now()->month((int) $month)->startOfMonth()->ISOFormat('LL')}} {{__('common.to')}} {{ now()->month((int) $month)->endOfMonth()->ISOFormat('LL') }}
                <br />{{__('reports.client')}} : {{!is_null($company) ? $company->name : ''}}
                <br>{{__('departments.department')}} : {{!is_null($department) ? $department->name : ''}}
            </p>
        </div>
        <table class="table">
            <thead class="bg-primary text-white">
                <tr>
                    <th class="employee-col">{{ __('employees.employee_name') }}</th>
                    <th class="qualification-col">{{ __('employees.qualification') }}</th>
                    @foreach ($dates as $date)
                    <th class="date-col">{{ $date->day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td class="employee-col">{{$user->name}}</td>
                    <td class="qualification-col">{{$user->position}}</td>

                    @foreach ($dates as $day)
                    <td class="date-col">
                        {{$user->getHoursWorked($day,$month)}}
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class='' style="margin-left: 80%; padding-left:3px;">
            <div>
                @if(!empty($supervisor))
                <h4>{{ucwords($supervisor->name)}}</h4>
                @if(!is_null($supervisor->signature_path))
                <div class="w-25 h-25">
                    <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(asset('storage/attachments/'.$supervisor->signature_path)))}}" alt=''>
                </div>
                <p>{{__('common.date')}} {{now()->format('Y-m-d')}}</p>
                @endif
                @else
                <p style="padding-top:5px">
                    {{ __('departments.no_assigned_supervisor')}}
                </p>
                @endif
            </div>
        </div>
    </div>
    <div class="iso-logo">
        <img src="{{'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/iso-logo.jpeg')))}}" alt='' style="width:auto;height:300px;">
    </div>

</body>

</html>