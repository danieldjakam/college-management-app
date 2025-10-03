<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Mensuel - Présences Vacataires</title>
    <style>
        @page {
            size: A3 landscape;
            margin: 5mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 5px;
            line-height: 1.1;
            color: #333;
            font-size: 5px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 8px;
        }

        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 10px;
            font-weight: bold;
        }

        .header .subtitle {
            color: #666;
            font-size: 7px;
            margin-top: 2px;
        }

        .info-bar {
            background-color: #f8f9fa;
            padding: 3px 5px;
            margin-bottom: 5px;
            border-radius: 2px;
            font-size: 5px;
        }

        .info-bar span {
            margin-right: 15px;
        }

        .info-label {
            color: #666;
            font-weight: normal;
        }

        .info-value {
            color: #007bff;
            font-weight: bold;
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 4px;
            margin-bottom: 5px;
        }

        .calendar-table th {
            background-color: #007bff;
            color: white;
            padding: 2px 1px;
            text-align: center;
            font-weight: bold;
            border: 0.5px solid #0056b3;
            font-size: 4px;
            vertical-align: middle;
        }

        .calendar-table th.employee-header {
            background-color: #0056b3;
            text-align: left;
            width: 50px;
            font-size: 4px;
        }

        .calendar-table th.day-header {
            min-width: 18px;
            max-width: 18px;
            width: 18px;
        }

        .calendar-table th.stats-header {
            background-color: #28a745;
            min-width: 20px;
            width: 20px;
        }

        .calendar-table td {
            padding: 1px;
            border: 0.5px solid #ddd;
            text-align: center;
            vertical-align: middle;
            font-size: 3.5px;
        }

        .calendar-table td.employee-name {
            background-color: #f8f9fa;
            text-align: left;
            font-weight: bold;
            border-right: 1px solid #007bff;
            font-size: 4px;
            padding: 1px 2px;
        }

        .calendar-table td.day-cell {
            min-width: 18px;
            max-width: 18px;
            background-color: white;
        }

        .calendar-table td.weekend {
            background-color: #ffe5e5;
        }

        .calendar-table td.stats-cell {
            font-weight: bold;
            background-color: #f0f8ff;
        }

        .time-in {
            color: #28a745;
            font-weight: bold;
            display: block;
            line-height: 1.2;
            font-size: 3px;
        }

        .time-out {
            color: #dc3545;
            font-weight: bold;
            display: block;
            line-height: 1.2;
            font-size: 3px;
        }

        .late-badge {
            background-color: #ffc107;
            color: #000;
            padding: 0.5px 1px;
            border-radius: 1px;
            font-size: 3px;
            display: inline-block;
            margin-top: 0.5px;
        }

        .empty-day {
            color: #ccc;
            font-size: 4px;
        }

        .total-hours {
            color: #007bff;
            font-weight: bold;
            font-size: 4px;
        }

        .rate-good {
            color: #28a745;
            font-weight: bold;
        }

        .rate-warning {
            color: #ffc107;
            font-weight: bold;
        }

        .rate-bad {
            color: #dc3545;
            font-weight: bold;
        }

        .footer {
            margin-top: 5px;
            text-align: center;
            font-size: 4px;
            color: #666;
            border-top: 0.5px solid #ddd;
            padding-top: 3px;
        }

        .legend {
            margin-top: 3px;
            padding: 2px;
            background-color: #f8f9fa;
            border-radius: 1px;
            font-size: 4px;
        }

        .legend-item {
            display: inline-block;
            margin-right: 5px;
        }

        .day-name {
            font-size: 3px;
            color: #666;
            display: block;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>COLLÈGE POLYVALENT BILINGUE DE DOUALA</h1>
        <div class="subtitle">Rapport Mensuel de Présences - Vacataires et Semi-Permanents</div>
    </div>

    <div class="info-bar">
        <span>
            <span class="info-label">Mois:</span>
            <span class="info-value">{{ $monthName }} {{ $year }}</span>
        </span>
        <span>
            <span class="info-label">Total Vacataires:</span>
            <span class="info-value">{{ count($vacataires) }}</span>
        </span>
        <span>
            <span class="info-label">Jours ouvrables:</span>
            <span class="info-value">{{ $workingDaysInMonth ?? 0 }}</span>
        </span>
        <span>
            <span class="info-label">Année scolaire:</span>
            <span class="info-value">{{ $schoolYear }}</span>
        </span>
    </div>

    <table class="calendar-table">
        <thead>
            <tr>
                <th class="employee-header">Employé</th>

                @foreach($daysHeader as $dayInfo)
                    <th class="day-header">
                        <span class="day-name">{{ $dayInfo['day_name'] }}</span>
                        {{ str_pad($dayInfo['day'], 2, '0', STR_PAD_LEFT) }}
                    </th>
                @endforeach

                <th class="stats-header">(%)</th>
                <th class="stats-header">Total<br>Heures</th>
                <th class="stats-header">W</th>
                <th class="stats-header">P</th>
                <th class="stats-header">A</th>
                <th class="stats-header">L</th>
                <th class="stats-header">HD</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vacataires as $vacataire)
                <tr>
                    <td class="employee-name">
                        {{ $vacataire['name'] }}
                    </td>

                    @foreach($vacataire['days'] as $dayData)
                        @php
                            $isWeekend = isset($dayData['is_weekend']) && $dayData['is_weekend'];
                        @endphp

                        <td class="day-cell {{ $isWeekend ? 'weekend' : '' }}">
                            @if($dayData && isset($dayData['pairs']) && count($dayData['pairs']) > 0)
                                @foreach($dayData['pairs'] as $pair)
                                    <div style="margin-bottom: 2px; padding: 1px; border: 0.3px solid #ccc; background: #fafafa;">
                                        <span class="time-in">In: {{ $pair['in'] }}</span>
                                        @if($pair['out'])
                                            <span class="time-out">Out: {{ $pair['out'] }}</span>
                                        @else
                                            <span class="time-out">Out: --:--</span>
                                        @endif

                                        @if(isset($pair['late_minutes']) && $pair['late_minutes'] > 0)
                                            <span class="late-badge">{{ $pair['late_minutes'] }}min</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <span class="empty-day">-</span>
                            @endif
                        </td>
                    @endforeach

                    <td class="stats-cell">
                        @php
                            $rate = $vacataire['attendance_rate'];
                            $rateClass = $rate >= 80 ? 'rate-good' : ($rate >= 50 ? 'rate-warning' : 'rate-bad');
                        @endphp
                        <span class="{{ $rateClass }}">{{ $rate }}%</span>
                    </td>
                    <td class="stats-cell">
                        <span class="total-hours">{{ $vacataire['total_hours'] }}</span>
                    </td>
                    <td class="stats-cell">{{ $vacataire['working_days'] }}</td>
                    <td class="stats-cell">{{ $vacataire['present_days'] }}</td>
                    <td class="stats-cell">{{ $vacataire['absent_days'] }}</td>
                    <td class="stats-cell">{{ $vacataire['late_days'] }}</td>
                    <td class="stats-cell">{{ $vacataire['half_days'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="legend">
        <span class="legend-item"><strong>(%)</strong> : Taux de présence</span>
        <span class="legend-item"><strong>W</strong> : Working days (Jours ouvrables)</span>
        <span class="legend-item"><strong>P</strong> : Present (Jours présents)</span>
        <span class="legend-item"><strong>A</strong> : Absent (Jours absents)</span>
        <span class="legend-item"><strong>L</strong> : Late (Jours en retard)</span>
        <span class="legend-item"><strong>HD</strong> : Half Day (Demi-journées)</span>
    </div>

    <div class="footer">
        <p>Document généré le {{ $generatedAt }}</p>
        <p>Système de Gestion Scolaire - CPBD</p>
    </div>
</body>
</html>
