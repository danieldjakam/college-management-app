<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Récapitulatif Paie - {{ $period->libelle_periode }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #0066cc;
            margin: 0;
            font-size: 28px;
        }
        
        .header h2 {
            color: #666;
            margin: 5px 0;
            font-size: 18px;
            font-weight: normal;
        }
        
        .period-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #0066cc;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .stats-row {
            display: table-row;
        }
        
        .stats-cell {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .stats-cell.header {
            background-color: #0066cc;
            color: white;
            font-weight: bold;
        }
        
        .stats-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .positive {
            color: #28a745;
        }
        
        .negative {
            color: #dc3545;
        }
        
        .warning {
            color: #ffc107;
        }
        
        .info {
            color: #17a2b8;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .summary-table th,
        .summary-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .summary-table th {
            background-color: #0066cc;
            color: white;
            font-weight: bold;
        }
        
        .summary-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .section-title {
            color: #0066cc;
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0 15px 0;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 5px;
        }
        
        .payment-modes {
            display: table;
            width: 100%;
            margin: 20px 0;
        }
        
        .mode-row {
            display: table-row;
        }
        
        .mode-cell {
            display: table-cell;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        
        .mode-cell.header {
            background-color: #6c757d;
            color: white;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            color: #666;
        }
        
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        
        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RÉCAPITULATIF DE PAIE</h1>
        <h2>COLLEGE POLYVALENT BILINGUE DE DOUALA</h2>
        <p style="font-size: 16px; margin: 10px 0;"><strong>{{ $period->libelle_periode }}</strong></p>
    </div>

    <div class="period-info">
        <div style="display: table; width: 100%;">
            <div style="display: table-row;">
                <div style="display: table-cell; width: 50%;">
                    <strong>Période:</strong> Du {{ $period->date_debut->format('d/m/Y') }} au {{ $period->date_fin->format('d/m/Y') }}<br>
                    <strong>Statut:</strong> 
                    @switch($period->statut)
                        @case('ouverte')
                            <span style="color: #17a2b8;">Ouverte</span>
                            @break
                        @case('calculee')
                            <span style="color: #ffc107;">Calculée</span>
                            @break
                        @case('validee')
                            <span style="color: #fd7e14;">Validée</span>
                            @break
                        @case('payee')
                            <span style="color: #28a745;">Payée</span>
                            @break
                    @endswitch
                </div>
                <div style="display: table-cell; width: 50%;">
                    <strong>Date de création:</strong> {{ $period->created_at->format('d/m/Y') }}<br>
                    @if($period->date_paie)
                        <strong>Date de paie:</strong> {{ $period->date_paie->format('d/m/Y') }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="section-title">STATISTIQUES GÉNÉRALES</div>
    
    <div class="stats-grid">
        <div class="stats-row">
            <div class="stats-cell header">Employés</div>
            <div class="stats-cell header">Salaire Brut Total</div>
            <div class="stats-cell header">Salaire Net Total</div>
        </div>
        <div class="stats-row">
            <div class="stats-cell">
                <div class="stats-value info">{{ $stats['total_employees'] }}</div>
                <small>Employés actifs</small>
            </div>
            <div class="stats-cell">
                <div class="stats-value positive">{{ number_format($stats['total_gross'], 0, ',', ' ') }}</div>
                <small>FCFA</small>
            </div>
            <div class="stats-cell">
                <div class="stats-value positive">{{ number_format($stats['total_net'], 0, ',', ' ') }}</div>
                <small>FCFA</small>
            </div>
        </div>
    </div>

    <div class="stats-grid" style="margin-top: 20px;">
        <div class="stats-row">
            <div class="stats-cell header">Total Retenues</div>
            <div class="stats-cell header">Total Coupures</div>
            <div class="stats-cell header">Économie Réalisée</div>
        </div>
        <div class="stats-row">
            <div class="stats-cell">
                <div class="stats-value negative">{{ number_format($stats['total_deductions'], 0, ',', ' ') }}</div>
                <small>FCFA</small>
            </div>
            <div class="stats-cell">
                <div class="stats-value negative">{{ number_format($stats['total_cuts'], 0, ',', ' ') }}</div>
                <small>Coupures actives</small>
            </div>
            <div class="stats-cell">
                <div class="stats-value info">{{ number_format($stats['total_gross'] - $stats['total_net'], 0, ',', ' ') }}</div>
                <small>FCFA</small>
            </div>
        </div>
    </div>

    <div class="section-title">RÉPARTITION PAR MODE DE PAIEMENT</div>
    
    <div class="payment-modes">
        <div class="mode-row">
            <div class="mode-cell header">Mode de Paiement</div>
            <div class="mode-cell header">Nombre d'Employés</div>
            <div class="mode-cell header">Pourcentage</div>
        </div>
        @foreach($stats['payment_modes'] as $mode => $count)
        <div class="mode-row">
            <div class="mode-cell">
                @switch($mode)
                    @case('cash')
                        Espèces
                        @break
                    @case('check')
                        Chèque
                        @break
                    @case('bank_transfer')
                        Virement bancaire
                        @break
                    @default
                        {{ $mode }}
                @endswitch
            </div>
            <div class="mode-cell">{{ $count }}</div>
            <div class="mode-cell">{{ round(($count / $stats['total_employees']) * 100, 1) }}%</div>
        </div>
        @endforeach
    </div>

    <div class="section-title">DÉTAIL DES EMPLOYÉS</div>
    
    <table class="summary-table">
        <thead>
            <tr>
                <th>Employé</th>
                <th>Fonction</th>
                <th>Mode Paiement</th>
                <th class="amount">Salaire Brut</th>
                <th class="amount">Retenues</th>
                <th class="amount">Coupures</th>
                <th class="amount">Salaire Net</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($period->payslips->sortBy('employee.user.nom') as $payslip)
            <tr>
                <td>
                    <strong>{{ $payslip->employee->user->nom_complet }}</strong><br>
                    <small>{{ $payslip->employee->user->email }}</small>
                </td>
                <td>{{ $payslip->employee->fonction ?? 'N/A' }}</td>
                <td>
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
                </td>
                <td class="amount positive">{{ number_format($payslip->salaire_brut, 0, ',', ' ') }}</td>
                <td class="amount negative">{{ number_format($payslip->employee->retenue_fixe, 0, ',', ' ') }}</td>
                <td class="amount negative">{{ number_format($payslip->montant_coupures, 0, ',', ' ') }}</td>
                <td class="amount positive"><strong>{{ number_format($payslip->salaire_net, 0, ',', ' ') }}</strong></td>
                <td>
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
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #0066cc; color: white; font-weight: bold;">
                <td colspan="3"><strong>TOTAUX</strong></td>
                <td class="amount">{{ number_format($stats['total_gross'], 0, ',', ' ') }}</td>
                <td class="amount">{{ number_format($period->payslips->sum('employee.retenue_fixe'), 0, ',', ' ') }}</td>
                <td class="amount">{{ number_format($stats['total_deductions'], 0, ',', ' ') }}</td>
                <td class="amount">{{ number_format($stats['total_net'], 0, ',', ' ') }}</td>
                <td>-</td>
            </tr>
        </tfoot>
    </table>

    @if($period->salaryCuts->where('statut', 'active')->count() > 0)
    <div class="section-title">COUPURES DE SALAIRE ACTIVES</div>
    
    <table class="summary-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Employé</th>
                <th>Motif</th>
                <th class="amount">Montant</th>
                <th>Notification</th>
            </tr>
        </thead>
        <tbody>
            @foreach($period->salaryCuts->where('statut', 'active')->sortBy('date_coupure') as $cut)
            <tr>
                <td>{{ $cut->date_coupure->format('d/m/Y') }}</td>
                <td>{{ $cut->employee->user->nom_complet }}</td>
                <td>{{ Str::limit($cut->motif, 50) }}</td>
                <td class="amount negative">{{ number_format($cut->montant_coupe, 0, ',', ' ') }}</td>
                <td>
                    @switch($cut->notification_status)
                        @case('sent')
                            <span style="color: #28a745;">Envoyée</span>
                            @break
                        @case('failed')
                            <span style="color: #dc3545;">Échec</span>
                            @break
                        @case('pending')
                            <span style="color: #ffc107;">En attente</span>
                            @break
                    @endswitch
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="section-title">RÉSUMÉ FINANCIER</div>
    
    <table class="summary-table">
        <tbody>
            <tr>
                <td><strong>Masse salariale brute</strong></td>
                <td class="amount positive"><strong>{{ number_format($stats['total_gross'], 0, ',', ' ') }} FCFA</strong></td>
            </tr>
            <tr>
                <td>Retenues fixes totales</td>
                <td class="amount negative">{{ number_format($period->payslips->sum('employee.retenue_fixe'), 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td>Coupures de salaire</td>
                <td class="amount negative">{{ number_format($stats['total_deductions'], 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr style="background-color: #28a745; color: white;">
                <td><strong>MASSE SALARIALE NETTE À PAYER</strong></td>
                <td class="amount"><strong>{{ number_format($stats['total_net'], 0, ',', ' ') }} FCFA</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <strong>Préparé par</strong><br>
            <small>Service Comptabilité</small>
            <div class="signature-line">
                Signature et Date
            </div>
        </div>
        <div class="signature-box">
            <strong>Approuvé par</strong><br>
            <small>Direction</small>
            <div class="signature-line">
                Signature et Cachet
            </div>
        </div>
    </div>

    <div class="footer">
        <p><strong>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</strong></p>
        <p>COLLEGE POLYVALENT BILINGUE DE DOUALA - Système de Gestion de Paie</p>
        <p>Ce récapitulatif concerne {{ $stats['total_employees'] }} employé(s) pour un montant total de {{ number_format($stats['total_net'], 0, ',', ' ') }} FCFA</p>
    </div>
</body>
</html>