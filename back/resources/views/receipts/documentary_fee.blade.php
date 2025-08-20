<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu Frais de Dossier - {{ $documentary_fee->receipt_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 10px;
            color: #333;
            line-height: 1.2;
        }

        .receipt-container {
            max-width: 100%;
            margin: 0;
            border: 2px solid #333;
            padding: 15px;
            position: relative;
            z-index: 1;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .school-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .school-logo {
            width: 60px;
            height: 60px;
            margin-right: 15px;
            border-radius: 5px;
            object-fit: cover;
        }

        .school-photo-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150px;
            height: 150px;
            opacity: 0.1;
            z-index: 0;
            border-radius: 10px;
            object-fit: cover;
        }

        .receipt-container {
            position: relative;
        }

        .school-details h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }

        .school-details p {
            margin: 1px 0;
            font-size: 10px;
            color: #666;
        }

        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 8px 0;
            color: #d32f2f;
        }

        .receipt-number {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .receipt-body {
            margin: 15px 0;
        }

        .info-section {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            padding: 2px 0;
            border-bottom: 1px dotted #ccc;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            font-size: 10px;
        }

        .info-value {
            color: #333;
            font-size: 10px;
        }

        .amount-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 12px;
            margin: 15px 0;
            text-align: center;
        }

        .amount-label {
            font-size: 12px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }

        .amount-value {
            font-size: 20px;
            font-weight: bold;
            color: #d32f2f;
            margin-bottom: 3px;
        }

        .payment-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 15px 0;
        }

        .payment-column {
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }

        .column-title {
            font-weight: bold;
            font-size: 11px;
            color: #495057;
            margin-bottom: 8px;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }

        .notes-section {
            margin: 15px 0;
            padding: 8px;
            background-color: #fff3cd;
            border-left: 3px solid #ffc107;
            font-size: 10px;
        }

        .notes-title {
            font-weight: bold;
            color: #856404;
            margin-bottom: 5px;
        }

        .notes-content {
            color: #856404;
            font-style: italic;
        }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #333;
            padding-top: 10px;
        }

        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }

        .signature-box {
            text-align: center;
            padding: 10px 0;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 30px;
            margin-bottom: 5px;
        }

        .signature-label {
            font-weight: bold;
            color: #555;
            font-size: 10px;
        }

        .generation-info {
            text-align: center;
            font-size: 8px;
            color: #999;
            margin-top: 10px;
        }

        .fee-type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .fee-type-frais_dossier {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        @media print {
            body {
                margin: 0;
                padding: 5px;
                font-size: 10px;
            }
            .receipt-container {
                border: 1px solid #333;
                padding: 10px;
            }
            .amount-value {
                font-size: 18px;
            }
            .receipt-title {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Photo de l'école en arrière-plan -->
        @if(isset($school_settings['school_photo']) && $school_settings['school_photo'])
            <img src="{{ public_path('storage/' . $school_settings['school_photo']) }}" alt="Photo École" class="school-photo-bg">
        @elseif(isset($school_settings['logo_path']) && $school_settings['logo_path'])
            <img src="{{ public_path('storage/' . $school_settings['logo_path']) }}" alt="Logo École" class="school-photo-bg">
        @endif

        <!-- En-tête compact -->
        <div class="header">
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

            <div class="receipt-title">Reçu de {{ $documentary_fee->getFeeTypeLabel() }}</div>
            <div class="receipt-number">N° {{ $documentary_fee->receipt_number }}</div>
        </div>

        <!-- Corps du reçu compact -->
        <div class="receipt-body">
            <!-- Informations principales en 2 colonnes -->
            <div class="payment-details">
                <div class="payment-column">
                    <div class="column-title">Informations Étudiant</div>
                    <div class="info-row">
                        <span class="info-label">Nom:</span>
                        <span class="info-value">{{ $documentary_fee->student->first_name }} {{ $documentary_fee->student->last_name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Matricule:</span>
                        <span class="info-value">{{ $documentary_fee->student->student_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Classe:</span>
                        <span class="info-value">
                            @if($documentary_fee->student->classSeries && $documentary_fee->student->classSeries->schoolClass)
                                {{ $documentary_fee->student->classSeries->schoolClass->name }} - {{ $documentary_fee->student->classSeries->name }}
                            @else
                                Non définie
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Année:</span>
                        <span class="info-value">{{ $documentary_fee->schoolYear->name }}</span>
                    </div>
                </div>

                <div class="payment-column">
                    <div class="column-title">Détails Paiement</div>
                    <div class="info-row">
                        <span class="info-label">Date:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($documentary_fee->payment_date)->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mode:</span>
                        <span class="info-value">
                            @switch($documentary_fee->payment_method)
                                @case('cash') Espèces @break
                                @case('cheque') Chèque @break
                                @case('transfer') Virement @break
                                @case('mobile_money') Mobile Money @break
                                @default {{ $documentary_fee->payment_method }}
                            @endswitch
                        </span>
                    </div>
                    @if($documentary_fee->reference_number)
                    <div class="info-row">
                        <span class="info-label">Référence:</span>
                        <span class="info-value">{{ $documentary_fee->reference_number }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Enregistré par:</span>
                        <span class="info-value">{{ $documentary_fee->createdByUser->name }}</span>
                    </div>
                </div>
            </div>

            <!-- Montant compact -->
            <div class="amount-section">
                @if($documentary_fee->penalty_amount > 0)
                    <div class="amount-label">Détail des montants</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 8px 0; font-size: 11px;">
                        <div style="text-align: center; padding: 5px; border: 1px solid #dee2e6; border-radius: 3px;">
                            <div style="font-weight: bold;">Frais de dossier</div>
                            <div style="font-size: 14px; color: #1976d2;">{{ number_format($documentary_fee->fee_amount, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div style="text-align: center; padding: 5px; border: 1px solid #dee2e6; border-radius: 3px;">
                            <div style="font-weight: bold;">Pénalité</div>
                            <div style="font-size: 14px; color: #d32f2f;">{{ number_format($documentary_fee->penalty_amount, 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                    <div class="amount-label" style="margin-top: 8px;">TOTAL perçu</div>
                    <div class="amount-value">{{ number_format($documentary_fee->total_amount, 0, ',', ' ') }} FCFA</div>
                @else
                    <div class="amount-label">Frais de dossier perçu</div>
                    <div class="amount-value">{{ number_format($documentary_fee->fee_amount, 0, ',', ' ') }} FCFA</div>
                @endif
            </div>

            <!-- Notes si présentes -->
            @if($documentary_fee->description || $documentary_fee->notes)
            <div class="notes-section">
                @if($documentary_fee->description)
                    <div class="notes-title">Description</div>
                    <div class="notes-content">{{ $documentary_fee->description }}</div>
                @endif
                @if($documentary_fee->notes)
                    <div class="notes-title">Notes</div>
                    <div class="notes-content">{{ $documentary_fee->notes }}</div>
                @endif
            </div>
            @endif
        </div>

        <!-- Pied de page compact -->
        <div class="footer">
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature de l'étudiant/Parent</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Signature et cachet de l'école</div>
                </div>
            </div>

            <div class="generation-info">
                Document généré le {{ $generated_at }} - Reçu valable sans signature et cachet
            </div>
        </div>
    </div>
</body>
</html>
