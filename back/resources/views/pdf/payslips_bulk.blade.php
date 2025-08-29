<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulletins de Paie - {{ $period->libelle_periode }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 15px;
            color: #333;
        }
        
        .page-header {
            text-align: center;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .page-header h1 {
            color: #0066cc;
            margin: 0;
            font-size: 20px;
        }
        
        .page-header h2 {
            color: #666;
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        
        .payslip {
            page-break-inside: avoid;
            margin-bottom: 40px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #fdfdfd;
        }
        
        .payslip-header {
            background-color: #0066cc;
            color: white;
            padding: 10px;
            margin: -15px -15px 15px -15px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .employee-info {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-cell {
            display: table-cell;
            padding: 4px 8px;
            border-bottom: 1px solid #eee;
        }
        
        .info-cell.label {
            font-weight: bold;
            width: 25%;
            background-color: #f8f9fa;
        }
        
        .salary-summary {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        
        .summary-row {
            display: table-row;
        }
        
        .summary-cell {
            display: table-cell;
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        
        .summary-cell.header {
            background-color: #0066cc;
            color: white;
            font-weight: bold;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .positive {
            color: #28a745;
        }
        
        .negative {
            color: #dc3545;
        }
        
        .net-salary {
            background-color: #28a745;
            color: white;
            font-size: 11px;
        }
        
        .cuts-info {
            margin-top: 10px;
            font-size: 9px;
            color: #666;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 8px;
        }
        
        @media print {
            .payslip {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>BULLETINS DE PAIE COLLECTIFS</h1>
        <h2>COLLEGE POLYVALENT BILINGUE DE DOUALA</h2>
        <p>Période: {{ $period->libelle_periode }}</p>
        <p>Du {{ $period->date_debut->format('d/m/Y') }} au {{ $period->date_fin->format('d/m/Y') }}</p>
    </div>

    @foreach($payslips as $index => $payslip)
        @if($index > 0 && $index % 2 == 0)
            <div class="page-break"></div>
        @endif
        
        <div class="payslip">
            <div class="payslip-header">
                {{ $payslip->employee->user->nom_complet }} - Matricule: {{ $payslip->employee->matricule_employee ?? 'N/A' }}
            </div>
            
            <div class="employee-info">
                <div class="info-row">
                    <div class="info-cell label">Email:</div>
                    <div class="info-cell">{{ $payslip->employee->user->email }}</div>
                    <div class="info-cell label">Fonction:</div>
                    <div class="info-cell">{{ $payslip->employee->fonction ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-cell label">Téléphone:</div>
                    <div class="info-cell">{{ $payslip->employee->telephone_whatsapp ?? 'N/A' }}</div>
                    <div class="info-cell label">Mode paiement:</div>
                    <div class="info-cell">
                        @switch($payslip->mode_paiement)
                            @case('cash')
                                Espèces
                                @break
                            @case('check')
                                Chèque
                                @break
                            @case('bank_transfer')
                                Virement
                                @break
                            @default
                                {{ $payslip->mode_paiement }}
                        @endswitch
                    </div>
                </div>
            </div>

            <div class="salary-summary">
                <div class="summary-row">
                    <div class="summary-cell header">Salaire Base</div>
                    <div class="summary-cell header">Primes</div>
                    <div class="summary-cell header">Salaire Brut</div>
                    <div class="summary-cell header">Retenues</div>
                    <div class="summary-cell header">Coupures</div>
                    <div class="summary-cell header net-salary">Salaire Net</div>
                </div>
                <div class="summary-row">
                    <div class="summary-cell">{{ number_format($payslip->employee->salaire_base, 0, ',', ' ') }}</div>
                    <div class="summary-cell">{{ number_format($payslip->employee->prime_fixe + $payslip->employee->autre_prime, 0, ',', ' ') }}</div>
                    <div class="summary-cell positive">{{ number_format($payslip->salaire_brut, 0, ',', ' ') }}</div>
                    <div class="summary-cell negative">{{ number_format($payslip->employee->retenue_fixe, 0, ',', ' ') }}</div>
                    <div class="summary-cell negative">{{ number_format($payslip->montant_coupures, 0, ',', ' ') }}</div>
                    <div class="summary-cell net-salary"><strong>{{ number_format($payslip->salaire_net, 0, ',', ' ') }} FCFA</strong></div>
                </div>
            </div>

            @if($payslip->salaryCuts && $payslip->salaryCuts->where('statut', 'active')->count() > 0)
                <div class="cuts-info">
                    <strong>Détail des coupures:</strong><br>
                    @foreach($payslip->salaryCuts->where('statut', 'active') as $cut)
                        • {{ $cut->date_coupure->format('d/m') }}: {{ $cut->motif }} ({{ number_format($cut->montant_coupe, 0, ',', ' ') }} FCFA)<br>
                    @endforeach
                </div>
            @endif

            <div style="margin-top: 15px; font-size: 9px; color: #666;">
                <div style="float: left;">
                    Statut: 
                    @switch($payslip->statut)
                        @case('provisoire')
                            <span style="color: #ffc107;">Provisoire</span>
                            @break
                        @case('valide')
                            <span style="color: #28a745;">Validé</span>
                            @break
                        @case('disponible')
                            <span style="color: #17a2b8;">Disponible</span>
                            @break
                        @case('retire')
                            <span style="color: #6c757d;">Retiré</span>
                            @break
                    @endswitch
                </div>
                <div style="float: right;">
                    Créé le: {{ $payslip->created_at->format('d/m/Y') }}
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
    @endforeach

    <div class="footer">
        <p>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>COLLEGE POLYVALENT BILINGUE DE DOUALA - Système de Gestion de Paie</p>
        <p>Total des bulletins: {{ $payslips->count() }}</p>
    </div>
</body>
</html>