<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Besoins Approuvés</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #9333ea;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #9333ea;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .header h2 {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .header .meta {
            font-size: 9px;
            color: #666;
        }

        .summary {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #9333ea;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            padding: 8px;
            text-align: center;
            width: 33.33%;
        }

        .summary-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #9333ea;
        }

        .summary-value.success {
            color: #10b981;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #9333ea;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        .amount {
            font-weight: bold;
            color: #059669;
            text-align: right;
        }

        .date {
            color: #666;
            font-size: 8px;
        }

        .approver {
            font-style: italic;
            color: #6b7280;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }

        .total-row {
            font-weight: bold;
            background: #fef3c7 !important;
            border-top: 2px solid #9333ea;
        }

        .total-row td {
            padding: 12px 8px;
            font-size: 11px;
        }

        .period-info {
            background: #ede9fe;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 10px;
            color: #5b21b6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>COLLÈGE POLYVALENT BILINGUE DE DOUALA</h1>
        <h2>Rapport des Besoins Approuvés</h2>
        <div class="meta">
            Généré le {{ $generated_at }}
        </div>
    </div>

    @if($from_date || $to_date)
    <div class="period-info">
        <strong>Période:</strong>
        @if($from_date && $to_date)
            Du {{ \Carbon\Carbon::parse($from_date)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($to_date)->format('d/m/Y') }}
        @elseif($from_date)
            À partir du {{ \Carbon\Carbon::parse($from_date)->format('d/m/Y') }}
        @elseif($to_date)
            Jusqu'au {{ \Carbon\Carbon::parse($to_date)->format('d/m/Y') }}
        @endif
    </div>
    @endif

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Nombre de besoins approuvés</div>
                <div class="summary-value success">{{ $total_count }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Montant total approuvé</div>
                <div class="summary-value">{{ number_format($total_amount, 0, ',', ' ') }} FCFA</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Montant moyen</div>
                <div class="summary-value">{{ $total_count > 0 ? number_format($total_amount / $total_count, 0, ',', ' ') : 0 }} FCFA</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 20%;">Nom du Besoin</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 12%;">Montant</th>
                <th style="width: 15%;">Demandeur</th>
                <th style="width: 10%;">Date Approbation</th>
                <th style="width: 8%;">Approuvé par</th>
            </tr>
        </thead>
        <tbody>
            @forelse($needs as $index => $need)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $need->name }}</strong></td>
                <td>{{ Str::limit($need->description, 100) }}</td>
                <td class="amount">{{ number_format($need->amount, 0, ',', ' ') }} FCFA</td>
                <td>{{ $need->user ? $need->user->name : 'N/A' }}</td>
                <td class="date">{{ $need->approved_at ? $need->approved_at->format('d/m/Y H:i') : 'N/A' }}</td>
                <td class="approver">{{ $need->approvedBy ? $need->approvedBy->name : 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #9ca3af;">
                    Aucun besoin approuvé trouvé pour cette période
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($total_count > 0)
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL GÉNÉRAL:</td>
                <td class="amount">{{ number_format($total_amount, 0, ',', ' ') }} FCFA</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>Ce document est généré automatiquement par le système de gestion du CPBD</p>
        <p>Pour toute question, veuillez contacter l'administration</p>
    </div>
</body>
</html>
