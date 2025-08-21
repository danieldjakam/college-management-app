<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Présences Élèves</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 15px;
            line-height: 1.2;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #007bff;
            padding-bottom: 10px;
        }
        
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            font-size: 12px;
        }
        
        .info-table td {
            padding: 8px 15px;
            border: none;
        }
        
        .info-table .info-label {
            color: #666;
            font-size: 10px;
        }
        
        .info-table .info-value {
            color: #007bff;
            font-weight: bold;
        }
        
        .stats-table {
            width: 100%;
            margin-bottom: 15px;
            background-color: #e9ecef;
            font-size: 12px;
        }
        
        .stats-table td {
            padding: 8px;
            text-align: center;
            border: none;
        }
        
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            display: block;
        }
        
        .stat-value.total { color: #007bff; }
        .stat-value.present { color: #28a745; }
        .stat-value.absent { color: #dc3545; }
        .stat-value.rate { color: #17a2b8; }
        
        .stat-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        th {
            background-color: #007bff;
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }
        
        td {
            padding: 4px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .status-present {
            background-color: #d4edda;
            color: #155724;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>COLLÈGE POLYVALENT BILINGUE DE DOUALA - Rapport de Présences des Élèves</h1>
    </div>
    
    <table class="info-table">
        <tr>
            <td style="width: 33.33%;">
                <span class="info-label">Date:</span>
                <span class="info-value">{{ $date }}</span>
            </td>
            <td style="width: 33.33%; text-align: center;">
                <span class="info-label">{{ $filterType }}:</span>
                <span class="info-value">{{ $filterTitle }}</span>
            </td>
            <td style="width: 33.33%; text-align: right;">
                <span class="info-label">Année scolaire:</span>
                <span class="info-value">{{ $schoolYear }}</span>
            </td>
        </tr>
    </table>
    
    <table class="stats-table">
        <tr>
            <td style="width: 25%;">
                <span class="stat-value total">{{ $stats['total'] }}</span>
                <span class="stat-label">Total Élèves</span>
            </td>
            <td style="width: 25%;">
                <span class="stat-value present">{{ $stats['present'] }}</span>
                <span class="stat-label">Présents</span>
            </td>
            <td style="width: 25%;">
                <span class="stat-value absent">{{ $stats['absent'] }}</span>
                <span class="stat-label">Absents</span>
            </td>
            <td style="width: 25%;">
                <span class="stat-value rate">{{ $stats['attendance_rate'] }}%</span>
                <span class="stat-label">Taux de présence</span>
            </td>
        </tr>
    </table>
    
    @if($attendanceData->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 12%">Matricule</th>
                    <th style="width: 20%">Nom</th>
                    <th style="width: 20%">Prénom</th>
                    <th style="width: 10%">Classe</th>
                    <th style="width: 13%">Série</th>
                    <th style="width: 10%">Statut</th>
                    <th style="width: 7.5%">Arrivée</th>
                    <th style="width: 7.5%">Sortie</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceData as $student)
                    <tr>
                        <td>{{ $student['matricule'] ?: '-' }}</td>
                        <td>{{ $student['nom'] ?: '-' }}</td>
                        <td>{{ $student['prenom'] ?: '-' }}</td>
                        <td>{{ $student['class_name'] ?: '-' }}</td>
                        <td>{{ $student['series_name'] ?: '-' }}</td>
                        <td>
                            @if($student['is_present'])
                                <span class="status-present">PRÉSENT</span>
                            @else
                                <span class="status-absent">ABSENT</span>
                            @endif
                        </td>
                        <td>
                            @if($student['is_present'] && $student['arrival_time'])
                                {{ \Carbon\Carbon::parse($student['arrival_time'])->format('H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($student['exit_time'])
                                {{ \Carbon\Carbon::parse($student['exit_time'])->format('H:i') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #666;">
            <p>Aucune donnée de présence trouvée pour les critères sélectionnés.</p>
        </div>
    @endif
    
    <div class="footer">
        <p>Document généré le {{ $generatedAt }}</p>
        <p>Système de Gestion Scolaire - CPBD</p>
    </div>
</body>
</html>