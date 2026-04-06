<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Suivi Saisie des Notes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header .school-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }

        .header h1 {
            color: #0066cc;
            margin: 5px 0 0;
            font-size: 16px;
        }

        .header h2 {
            color: #666;
            margin: 3px 0 0;
            font-size: 11px;
            font-weight: normal;
        }

        .stats-bar {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 6px 8px;
            border: 1px solid #ddd;
        }

        .stat-item .stat-value {
            font-size: 18px;
            font-weight: bold;
            display: block;
        }

        .stat-total .stat-value { color: #0066cc; }
        .stat-complete .stat-value { color: #28a745; }
        .stat-partial .stat-value { color: #ffc107; }
        .stat-notstarted .stat-value { color: #dc3545; }

        .teacher-section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .teacher-header {
            background: #f0f4ff;
            padding: 6px 10px;
            border: 1px solid #0066cc;
            border-bottom: none;
            font-weight: bold;
            font-size: 11px;
        }

        .teacher-header .phone {
            font-weight: normal;
            color: #666;
            font-size: 9px;
        }

        .teacher-header .teacher-status {
            float: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #0066cc;
            color: white;
            padding: 5px 6px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }

        td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            font-size: 9px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .progress-bar-container {
            background: #e9ecef;
            border-radius: 3px;
            height: 10px;
            width: 80px;
            display: inline-block;
            vertical-align: middle;
        }

        .progress-bar-fill {
            height: 10px;
            border-radius: 3px;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }

        @page {
            margin: 10mm 8mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $school_name }}</div>
        <h1>SUIVI DE LA SAISIE DES NOTES</h1>
        <h2>{{ $sequence_name }} - Généré le {{ $date_generation }}</h2>
    </div>

    <div class="stats-bar">
        <div class="stat-item stat-total">
            <span class="stat-value">{{ $stats['total'] }}</span>
            Enseignants
        </div>
        <div class="stat-item stat-complete">
            <span class="stat-value">{{ $stats['complete'] }}</span>
            Complet
        </div>
        <div class="stat-item stat-partial">
            <span class="stat-value">{{ $stats['partial'] }}</span>
            En cours
        </div>
        <div class="stat-item stat-notstarted">
            <span class="stat-value">{{ $stats['not_started'] }}</span>
            Non commencé
        </div>
    </div>

    @foreach($teachers as $teacher)
    <div class="teacher-section">
        <div class="teacher-header">
            {{ $teacher['full_name'] }}
            @if($teacher['phone_number'])
                <span class="phone">| Tél: {{ $teacher['phone_number'] }}</span>
            @endif
            <span class="teacher-status">
                @if($teacher['status'] === 'complete')
                    <span class="badge badge-success">COMPLET ({{ $teacher['summary']['complete'] }}/{{ $teacher['summary']['total'] }})</span>
                @elseif($teacher['status'] === 'partial')
                    <span class="badge badge-warning">EN COURS ({{ $teacher['summary']['complete'] }}/{{ $teacher['summary']['total'] }})</span>
                @else
                    <span class="badge badge-danger">NON COMMENCÉ (0/{{ $teacher['summary']['total'] }})</span>
                @endif
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Classe</th>
                    <th style="width: 30%;">Matière</th>
                    <th style="width: 12%;">Effectif</th>
                    <th style="width: 13%;">Notes saisies</th>
                    <th style="width: 10%;">Progression</th>
                    <th style="width: 10%;">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teacher['assignments'] as $assignment)
                <tr>
                    <td>{{ $assignment['class_name'] }}</td>
                    <td>{{ $assignment['subject_name'] }}</td>
                    <td style="text-align: center;">{{ $assignment['total_students'] }}</td>
                    <td style="text-align: center;">{{ $assignment['grades_entered'] }} / {{ $assignment['total_students'] }}</td>
                    <td style="text-align: center;">
                        @php
                            $pct = $assignment['total_students'] > 0 ? round(($assignment['grades_entered'] / $assignment['total_students']) * 100) : 0;
                            $color = $pct >= 100 ? '#28a745' : ($pct > 0 ? '#ffc107' : '#dc3545');
                        @endphp
                        {{ $pct }}%
                    </td>
                    <td style="text-align: center;">
                        @if($assignment['status'] === 'complete')
                            <span class="badge badge-success">OK</span>
                        @elseif($assignment['status'] === 'partial')
                            <span class="badge badge-warning">En cours</span>
                        @else
                            <span class="badge badge-danger">Aucune</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    @if(count($teachers) === 0)
    <div style="text-align: center; padding: 30px; color: #999;">
        Aucun enseignant trouvé pour les critères sélectionnés.
    </div>
    @endif

    <div class="footer">
        Document généré le {{ $date_generation }} - {{ $school_name }}
    </div>
</body>
</html>
