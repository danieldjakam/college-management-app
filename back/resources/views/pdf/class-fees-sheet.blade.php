<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche de Scolarité - {{ $class['name'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            color: #333;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .school-info {
            flex: 1;
            text-align: center;
            padding: 0 20px;
        }

        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .school-details {
            font-size: 10px;
            color: #555;
            line-height: 1.4;
        }

        .document-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            text-align: center;
            margin: 15px 0;
            text-transform: uppercase;
            background-color: #ecf0f1;
            padding: 10px;
            border-radius: 5px;
        }

        .class-title {
            font-size: 12px;
            font-weight: bold;
            color: #34495e;
            text-align: center;
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 8px;
        }

        thead {
            background-color: #34495e;
            color: white;
        }

        th, td {
            border: 1px solid #bdc3c7;
            padding: 6px 4px;
            text-align: center;
        }

        th {
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }

        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tbody tr:hover {
            background-color: #e8f4f8;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .amount {
            font-weight: 500;
            color: #27ae60;
        }

        .amount-negative {
            font-weight: 500;
            color: #e74c3c;
        }

        .totals-row {
            background-color: #d5dbdb !important;
            font-weight: bold;
            font-size: 9px;
        }

        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #ecf0f1;
            border-radius: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 10px;
        }

        .summary-label {
            font-weight: bold;
            color: #34495e;
        }

        .summary-value {
            color: #27ae60;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #7f8c8d;
            border-top: 2px solid #bdc3c7;
            padding-top: 10px;
        }

        .page-break {
            page-break-after: always;
        }

        @page {
            margin: 15px;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 15%; border: none; text-align: left;">
                    @if(file_exists($school['logo']))
                        <img src="{{ $school['logo'] }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td style="width: 70%; border: none; text-align: center;">
                    <div class="school-name">{{ $school['name'] }}</div>
                    <div class="school-details">
                        @if($school['address'])
                            <div>{{ $school['address'] }}</div>
                        @endif
                        @if($school['phone'] || $school['email'])
                            <div>
                                @if($school['phone'])
                                    Tél: {{ $school['phone'] }}
                                @endif
                                @if($school['phone'] && $school['email'])
                                    |
                                @endif
                                @if($school['email'])
                                    Email: {{ $school['email'] }}
                                @endif
                            </div>
                        @endif
                    </div>
                </td>
                <td style="width: 15%; border: none; text-align: right;">
                    <div style="font-size: 9px; color: #7f8c8d;">
                        Date: {{ $date_generation }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Titre du document -->
    <div class="document-title">
        Fiche de Paiement des Frais de Scolarité
    </div>

    <!-- Titre de la classe -->
    <div class="class-title">
        Classe de: {{ $class['name'] }}
        @if(isset($class['level']) && $class['level'])
            - Niveau: {{ $class['level']['name'] }}
        @endif
        @if(isset($class['section']) && $class['section'])
            - Section: {{ $class['section']['name'] }}
        @endif
    </div>

    <!-- Tableau des élèves -->
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">N°</th>
                <th style="width: 10%;">Matricule</th>
                <th style="width: 15%;" class="text-left">Nom</th>
                <th style="width: 15%;" class="text-left">Prénom</th>
                @foreach($tranches as $tranche)
                    <th style="width: {{ 30 / count($tranches) }}%;">{{ $tranche['name'] }}</th>
                @endforeach
                <th style="width: 7%;">Mt Rabais</th>
                <th style="width: 7%;">Mt Bourse</th>
                <th style="width: 8%;">Total Payé</th>
                <th style="width: 8%;">Reste à Payer</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
                <tr>
                    <td>{{ $student['numero'] }}</td>
                    <td>{{ $student['matricule'] }}</td>
                    <td class="text-left">{{ $student['nom'] }}</td>
                    <td class="text-left">{{ $student['prenom'] }}</td>
                    @foreach($tranches as $tranche)
                        <td class="amount">
                            {{ number_format($student['tranches'][$tranche['id']] ?? 0, 0, ',', ' ') }}
                        </td>
                    @endforeach
                    <td class="amount">{{ number_format($student['rabais'], 0, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($student['bourse'], 0, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($student['total_paye'], 0, ',', ' ') }}</td>
                    <td class="amount-negative">{{ number_format($student['reste_a_payer'], 0, ',', ' ') }}</td>
                </tr>
            @endforeach

            <!-- Ligne des totaux -->
            <tr class="totals-row">
                <td colspan="4" style="text-align: right; padding-right: 10px;">
                    TOTAUX ({{ $nombre_eleves }} élève{{ $nombre_eleves > 1 ? 's' : '' }})
                </td>
                @foreach($tranches as $tranche)
                    <td class="amount">
                        {{ number_format($totals['tranches'][$tranche['id']] ?? 0, 0, ',', ' ') }}
                    </td>
                @endforeach
                <td class="amount">{{ number_format($totals['rabais'], 0, ',', ' ') }}</td>
                <td class="amount">{{ number_format($totals['bourse'], 0, ',', ' ') }}</td>
                <td class="amount">{{ number_format($totals['total_paye'], 0, ',', ' ') }}</td>
                <td class="amount-negative">{{ number_format($totals['reste_a_payer'], 0, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Résumé -->
    <div class="summary">
        <div style="font-weight: bold; margin-bottom: 10px; font-size: 11px; color: #2c3e50;">
            RÉSUMÉ FINANCIER
        </div>
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="width: 50%; border: none; padding-right: 10px;">
                    <div class="summary-row">
                        <span class="summary-label">Nombre d'élèves:</span>
                        <span class="summary-value">{{ $nombre_eleves }}</span>
                    </div>
                    @foreach($tranches as $index => $tranche)
                        <div class="summary-row">
                            <span class="summary-label">Total {{ $tranche['name'] }}:</span>
                            <span class="summary-value">{{ number_format($totals['tranches'][$tranche['id']] ?? 0, 0, ',', ' ') }} FCFA</span>
                        </div>
                    @endforeach
                </td>
                <td style="width: 50%; border: none; padding-left: 10px;">
                    <div class="summary-row">
                        <span class="summary-label">Total Rabais:</span>
                        <span class="summary-value">{{ number_format($totals['rabais'], 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Total Bourses:</span>
                        <span class="summary-value">{{ number_format($totals['bourse'], 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="summary-row" style="border-top: 2px solid #34495e; padding-top: 5px; margin-top: 5px;">
                        <span class="summary-label" style="font-size: 11px;">TOTAL PAYÉ:</span>
                        <span class="summary-value" style="font-size: 11px; color: #27ae60;">{{ number_format($totals['total_paye'], 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label" style="font-size: 11px;">RESTE À PAYER:</span>
                        <span class="summary-value" style="font-size: 11px; color: #e74c3c;">{{ number_format($totals['reste_a_payer'], 0, ',', ' ') }} FCFA</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        Document généré automatiquement le {{ $date_generation }} - {{ $school['name'] }}
    </div>
</body>
</html>
