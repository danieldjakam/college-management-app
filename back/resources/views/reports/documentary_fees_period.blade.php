<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Frais de Dossiers - {{ $start_date }} au {{ $end_date }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
            color: #333;
            line-height: 1.3;
        }
        
        .report-container {
            max-width: 100%;
            margin: 0;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .school-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .school-logo {
            width: 50px;
            height: 50px;
            margin-right: 15px;
            border-radius: 5px;
            object-fit: cover;
        }
        
        .school-photo-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            opacity: 0.08;
            z-index: 0;
            border-radius: 10px;
            object-fit: cover;
        }
        
        .school-details h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }
        
        .school-details p {
            margin: 1px 0;
            font-size: 9px;
            color: #666;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 8px 0;
            color: #d32f2f;
            z-index: 1;
            position: relative;
        }
        
        .period-info {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            z-index: 1;
            position: relative;
        }
        
        .stats-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 15px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }
        
        .stat-item {
            text-align: center;
            padding: 5px;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            background: white;
        }
        
        .stat-value {
            font-size: 12px;
            font-weight: bold;
            color: #d32f2f;
        }
        
        .stat-label {
            font-size: 8px;
            color: #666;
            margin-top: 2px;
        }
        
        .fees-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9px;
        }
        
        .fees-table th,
        .fees-table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }
        
        .fees-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
            font-size: 8px;
        }
        
        .fees-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .amount.penalty {
            color: #dc3545;
        }
        
        .amount.total {
            color: #28a745;
        }
        
        .footer-summary {
            background-color: #e9ecef;
            border: 2px solid #6c757d;
            border-radius: 5px;
            padding: 10px;
            margin-top: 15px;
            text-align: center;
        }
        
        .footer-summary h3 {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #495057;
        }
        
        .footer-summary .total-amount {
            font-size: 16px;
            font-weight: bold;
            color: #28a745;
        }
        
        .generation-info {
            text-align: center;
            font-size: 8px;
            color: #999;
            margin-top: 15px;
        }
        
        @media print {
            body { 
                margin: 0; 
                padding: 5px;
                font-size: 9px;
            }
            .fees-table {
                font-size: 8px;
            }
            .fees-table th,
            .fees-table td {
                padding: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- En-tête -->
        <div class="header">
            <!-- Photo de l'école en arrière-plan -->
            @if(isset($school_settings['school_photo']) && $school_settings['school_photo'])
                <img src="{{ public_path('storage/' . $school_settings['school_photo']) }}" alt="Photo École" class="school-photo-bg">
            @elseif(isset($school_settings['logo_path']) && $school_settings['logo_path'])
                <img src="{{ public_path('storage/' . $school_settings['logo_path']) }}" alt="Logo École" class="school-photo-bg">
            @endif
            
            <div class="school-info">
                @if(isset($school_settings['logo_path']) && $school_settings['logo_path'])
                    <img src="{{ public_path('storage/' . $school_settings['logo_path']) }}" alt="Logo" class="school-logo">
                @endif
                <div class="school-details">
                    <h1>{{ $school_settings['school_name'] ?? 'COLLÈGE POLYVALENT BILINGUE DE DOUALA' }}</h1>
                    <p>{{ $school_settings['address'] ?? 'Douala, Cameroun' }}</p>
                    <p>Tél: {{ $school_settings['phone'] ?? 'N/A' }} | Email: {{ $school_settings['email'] ?? 'N/A' }}</p>
                </div>
            </div>
            
            <div class="report-title">Rapport des Frais de Dossiers</div>
            <div class="period-info">
                Période: du {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} 
                au {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}
            </div>
            <div class="period-info">Année scolaire: {{ $working_year->name }}</div>
        </div>

        <!-- Statistiques -->
        <div class="stats-section">
            <div class="stat-item">
                <div class="stat-value">{{ $statistics['total_count'] }}</div>
                <div class="stat-label">Total versements</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($statistics['total_amount'], 0, ',', ' ') }} FCFA</div>
                <div class="stat-label">Montant total</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $statistics['fees_with_penalty'] }}</div>
                <div class="stat-label">Avec pénalité</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($statistics['total_penalty_amount'], 0, ',', ' ') }} FCFA</div>
                <div class="stat-label">Total pénalités</div>
            </div>
        </div>

        <!-- Tableau des versements -->
        <table class="fees-table">
            <thead>
                <tr>
                    <th width="15%">Date</th>
                    <th width="12%">Reçu N°</th>
                    <th width="25%">Étudiant</th>
                    <th width="12%">Classe</th>
                    <th width="12%">Frais</th>
                    <th width="12%">Pénalité</th>
                    <th width="12%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($documentary_fees as $fee)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($fee->payment_date)->format('d/m/Y') }}</td>
                    <td><code style="font-size: 8px;">{{ $fee->receipt_number }}</code></td>
                    <td>
                        <strong>{{ $fee->student->first_name }} {{ $fee->student->last_name }}</strong>
                        <br><small style="color: #666;">{{ $fee->student->student_number }}</small>
                    </td>
                    <td>
                        @if($fee->student->classSeries && $fee->student->classSeries->schoolClass)
                            {{ $fee->student->classSeries->schoolClass->name }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="amount">{{ number_format($fee->fee_amount, 0, ',', ' ') }}</td>
                    <td class="amount penalty">
                        @if($fee->penalty_amount > 0)
                            {{ number_format($fee->penalty_amount, 0, ',', ' ') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="amount total">{{ number_format($fee->total_amount, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Résumé final -->
        <div class="footer-summary">
            <h3>RÉSUMÉ DE LA PÉRIODE</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin: 10px 0;">
                <div>
                    <div style="font-size: 10px; color: #666;">Frais de dossiers</div>
                    <div style="font-size: 12px; font-weight: bold; color: #007bff;">
                        {{ number_format($statistics['total_fee_amount'], 0, ',', ' ') }} FCFA
                    </div>
                </div>
                <div>
                    <div style="font-size: 10px; color: #666;">Pénalités</div>
                    <div style="font-size: 12px; font-weight: bold; color: #dc3545;">
                        {{ number_format($statistics['total_penalty_amount'], 0, ',', ' ') }} FCFA
                    </div>
                </div>
                <div>
                    <div style="font-size: 10px; color: #666;">TOTAL GÉNÉRAL</div>
                    <div class="total-amount">{{ number_format($statistics['total_amount'], 0, ',', ' ') }} FCFA</div>
                </div>
            </div>
        </div>

        <!-- Informations de génération -->
        <div class="generation-info">
            Rapport généré le {{ $generated_at }} - {{ $statistics['total_count'] }} versements du {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}
        </div>
    </div>
</body>
</html>