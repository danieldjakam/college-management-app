<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Présences Personnel</title>
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
        
        .role-badge {
            background-color: #6c757d;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
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
        <h1>COLLÈGE POLYVALENT BILINGUE DE DOUALA - Rapport de Présences du Personnel</h1>
    </div>
    
    <table class="info-table">
        <tr>
            <td style="width: 33.33%;">
                <span class="info-label">Date:</span>
                <span class="info-value">{{ $date }}</span>
            </td>
            <td style="width: 33.33%; text-align: center;">
                <span class="info-label">Filtre:</span>
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
                <span class="stat-label">Total Personnel</span>
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
                    <th style="width: 18%">Nom</th>
                    <th style="width: 15%">Type</th>
                    <th style="width: 15%">Rôle</th>
                    <th style="width: 10%">Statut</th>
                    <th style="width: 30%">Entrées/Sorties</th>
                    <th style="width: 12%">Temps Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendanceData as $staff)
                    <tr>
                        <td>
                            <strong>{{ $staff['last_name'] ?: ($staff['name'] ?: '-') }}</strong>
                            @if($staff['first_name'])
                                <br><small>{{ $staff['first_name'] }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="role-badge">
                                {{ $staff['employment_type'] === 'P' ? 'Permanent (P)' : 
                                   ($staff['employment_type'] === 'SP' ? 'Semi-permanent (SP)' :
                                   ($staff['employment_type'] === 'V' ? 'Vacataire (V)' : 'Non défini')) }}
                            </span>
                        </td>
                        <td>
                            <span class="role-badge">
                                @if($staff['role'] === 'teacher')
                                    Enseignant
                                @elseif($staff['role'] === 'accountant')
                                    Comptable
                                @elseif($staff['role'] === 'admin')
                                    Administrateur
                                @elseif($staff['role'] === 'secretaire')
                                    Secrétaire
                                @elseif($staff['role'] === 'surveillant_general')
                                    Surveillant
                                @elseif($staff['role'] === 'comptable_superieur')
                                    Comptable Sup.
                                @else
                                    {{ $staff['role'] ?: 'Personnel' }}
                                @endif
                            </span>
                        </td>
                        <td>
                            @if($staff['is_present'])
                                <span class="status-present">PRÉSENT</span>
                            @else
                                <span class="status-absent">ABSENT</span>
                            @endif
                        </td>
                        <td>
                            @if(count($staff['entry_exit_pairs']) > 0)
                                @foreach($staff['entry_exit_pairs'] as $pair)
                                    <div style="margin-bottom: 2px; font-size: 9px;">
                                        <span style="color: #28a745;">
                                            {{ \Carbon\Carbon::parse($pair['entry_time'])->format('H:i') }}
                                        </span>
                                        @if($pair['exit_time'])
                                            <span style="margin: 0 2px;">→</span>
                                            <span style="color: #dc3545;">
                                                {{ \Carbon\Carbon::parse($pair['exit_time'])->format('H:i') }}
                                            </span>
                                            <span style="color: #666; margin-left: 2px;">
                                                ({{ $pair['working_hours'] }})
                                            </span>
                                        @else
                                            <span style="color: #ffc107; margin-left: 2px;">(En cours)</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <span style="color: #666;">Aucune donnée</span>
                            @endif
                        </td>
                        <td>
                            <strong style="color: #007bff;">
                                {{ $staff['total_working_hours'] ?: '0h 0min' }}
                            </strong>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #666;">
            <p>Aucune donnée de présence trouvée pour la date sélectionnée.</p>
        </div>
    @endif
    
    <div class="footer">
        <p>Document généré le {{ $generatedAt }}</p>
        <p>Système de Gestion Scolaire - CPBD</p>
    </div>
</body>
</html>