<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulletin de Paie - {{ $payslip->employee->user->nom_complet }}</title>
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
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #0066cc;
            margin: 0;
            font-size: 24px;
        }
        
        .header h2 {
            color: #666;
            margin: 5px 0;
            font-size: 16px;
            font-weight: normal;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-cell {
            display: table-cell;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .info-cell.label {
            font-weight: bold;
            width: 30%;
            background-color: #f8f9fa;
        }
        
        .salary-section {
            background-color: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .salary-section h3 {
            color: #0066cc;
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .salary-table th,
        .salary-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .salary-table th {
            background-color: #0066cc;
            color: white;
            font-weight: bold;
        }
        
        .salary-table tr:nth-child(even) {
            background-color: #f9f9f9;
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
        
        .total-row {
            background-color: #0066cc !important;
            color: white !important;
            font-weight: bold;
        }
        
        .cuts-section {
            margin: 20px 0;
        }
        
        .cuts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .cuts-table th,
        .cuts-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        .cuts-table th {
            background-color: #dc3545;
            color: white;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
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
        <h1>BULLETIN DE PAIE</h1>
        <h2>COLLEGE POLYVALENT BILINGUE DE DOUALA</h2>
        <p>Période: {{ $payslip->period->libelle_periode }}</p>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell label">Employé:</div>
            <div class="info-cell">{{ $payslip->employee->user->nom_complet }}</div>
            <div class="info-cell label">Matricule:</div>
            <div class="info-cell">{{ $payslip->employee->matricule_employee ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell label">Email:</div>
            <div class="info-cell">{{ $payslip->employee->user->email }}</div>
            <div class="info-cell label">Téléphone:</div>
            <div class="info-cell">{{ $payslip->employee->telephone_whatsapp ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell label">Fonction:</div>
            <div class="info-cell">{{ $payslip->employee->fonction ?? 'N/A' }}</div>
            <div class="info-cell label">Mode de paiement:</div>
            <div class="info-cell">
                @switch($payslip->mode_paiement)
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
                        {{ $payslip->mode_paiement }}
                @endswitch
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell label">Date de création:</div>
            <div class="info-cell">{{ $payslip->created_at->format('d/m/Y à H:i') }}</div>
            <div class="info-cell label">Statut:</div>
            <div class="info-cell">
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
        </div>
    </div>

    <div class="salary-section">
        <h3>Détail des Rémunérations et Retenues</h3>
        
        <table class="salary-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount">Montant (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Salaire de base</td>
                    <td class="amount positive">{{ number_format($payslip->employee->salaire_base, 0, ',', ' ') }}</td>
                </tr>
                @if($payslip->employee->prime_fixe > 0)
                <tr>
                    <td>Prime fixe</td>
                    <td class="amount positive">{{ number_format($payslip->employee->prime_fixe, 0, ',', ' ') }}</td>
                </tr>
                @endif
                @if($payslip->employee->autre_prime > 0)
                <tr>
                    <td>Autres primes</td>
                    <td class="amount positive">{{ number_format($payslip->employee->autre_prime, 0, ',', ' ') }}</td>
                </tr>
                @endif
                <tr style="background-color: #e8f5e8;">
                    <td><strong>SALAIRE BRUT</strong></td>
                    <td class="amount positive"><strong>{{ number_format($payslip->salaire_brut, 0, ',', ' ') }}</strong></td>
                </tr>
                @if($payslip->employee->retenue_fixe > 0)
                <tr>
                    <td>Retenue fixe</td>
                    <td class="amount negative">-{{ number_format($payslip->employee->retenue_fixe, 0, ',', ' ') }}</td>
                </tr>
                @endif
                @if($payslip->montant_coupures > 0)
                <tr>
                    <td>Coupures de salaire</td>
                    <td class="amount negative">-{{ number_format($payslip->montant_coupures, 0, ',', ' ') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td><strong>SALAIRE NET À PAYER</strong></td>
                    <td class="amount"><strong>{{ number_format($payslip->salaire_net, 0, ',', ' ') }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($payslip->salaryCuts && $payslip->salaryCuts->count() > 0)
    <div class="cuts-section">
        <h3 style="color: #dc3545;">Détail des Coupures de Salaire</h3>
        <table class="cuts-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Motif</th>
                    <th class="amount">Montant (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payslip->salaryCuts->where('statut', 'active') as $cut)
                <tr>
                    <td>{{ $cut->date_coupure->format('d/m/Y') }}</td>
                    <td>{{ $cut->motif }}</td>
                    <td class="amount">{{ number_format($cut->montant_coupe, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <strong>L'Employé</strong>
            <div class="signature-line">
                {{ $payslip->employee->user->nom_complet }}
            </div>
        </div>
        <div class="signature-box">
            <strong>Le Comptable</strong>
            <div class="signature-line">
                Signature et Cachet
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Ce bulletin de paie a été généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>COLLEGE POLYVALENT BILINGUE DE DOUALA - Système de Gestion de Paie</p>
    </div>
</body>
</html>