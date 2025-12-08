<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartes d'Identité Scolaires - {{ $className }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 10mm;
            size: A4 portrait;
        }

        body {
            font-family: Arial, sans-serif;
            background: white;
        }

        .page {
            width: 100%;
            page-break-after: always;
        }

        .cards-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 8mm;
        }

        .card-wrapper {
            width: 85.6mm;
            height: 54mm;
            margin-bottom: 8mm;
            page-break-inside: avoid;
        }

        .card {
            width: 85.6mm;
            height: 54mm;
            border: 2px solid #003366;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            position: relative;
        }

        /* Bandeau République du Cameroun */
        .header-band {
            background: linear-gradient(to right, #009639 0%, #009639 33%, #CE1126 33%, #CE1126 66%, #FCD116 66%, #FCD116 100%);
            height: 8mm;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 7pt;
            text-align: center;
            padding: 0 2mm;
            border-bottom: 1px solid #003366;
        }

        .header-band .text-wrapper {
            background: rgba(0, 51, 102, 0.85);
            padding: 1mm 3mm;
            border-radius: 3px;
            width: 100%;
        }

        .header-band .republic {
            font-size: 7pt;
            letter-spacing: 0.3px;
        }

        .header-band .devise {
            font-size: 5pt;
            margin-top: 0.5mm;
            letter-spacing: 0.5px;
        }

        /* Corps de la carte */
        .card-body {
            padding: 2mm 3mm;
            height: calc(54mm - 8mm);
            position: relative;
        }

        /* En-tête avec nom collège et logo */
        .college-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2mm;
            border-bottom: 1px solid #003366;
            padding-bottom: 1mm;
        }

        .college-name {
            flex: 1;
            padding-right: 2mm;
        }

        .college-name h2 {
            color: #003366;
            font-size: 7pt;
            font-weight: bold;
            line-height: 1.2;
            margin: 0;
        }

        .college-name p {
            color: #666;
            font-size: 5pt;
            margin: 0.5mm 0 0 0;
            font-weight: bold;
        }

        .college-logo {
            width: 12mm;
            height: 12mm;
            border: 1px solid #003366;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
            flex-shrink: 0;
        }

        .college-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Section contenu avec photo et infos */
        .content-section {
            display: flex;
            gap: 2mm;
            margin-bottom: 1mm;
        }

        /* Photo élève */
        .student-photo {
            width: 18mm;
            height: 22mm;
            border: 1px solid #003366;
            border-radius: 3px;
            overflow: hidden;
            background: #f0f0f0;
            flex-shrink: 0;
        }

        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Informations élève */
        .student-info {
            flex: 1;
            font-size: 6pt;
            line-height: 1.3;
        }

        .info-row {
            margin-bottom: 0.8mm;
            display: flex;
        }

        .info-label {
            font-weight: bold;
            color: #003366;
            min-width: 15mm;
        }

        .info-value {
            color: #000;
            flex: 1;
        }

        /* Pied de carte avec QR et signature */
        .card-footer {
            position: absolute;
            bottom: 2mm;
            left: 3mm;
            right: 3mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .qr-section {
            text-align: center;
            flex: 1;
        }

        .qr-code {
            width: 12mm;
            height: 12mm;
            margin: 0 auto;
            border: 1px solid #ccc;
            border-radius: 2px;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            display: block;
        }

        .signature-section {
            text-align: center;
            width: 22mm;
        }

        .signature-line {
            border-top: 1px solid #666;
            margin-top: 1mm;
            padding-top: 0.5mm;
            font-size: 4pt;
            color: #666;
        }

        /* Styles d'impression */
        @media print {
            .page-break {
                page-break-before: always;
            }

            .card-wrapper:nth-child(10n) {
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    @php
        $chunkedCards = array_chunk($cards, 10);
    @endphp

    @foreach($chunkedCards as $pageIndex => $pageCards)
        <div class="page {{ $pageIndex > 0 ? 'page-break' : '' }}">
            <div class="cards-grid">
                @foreach($pageCards as $card)
                    <div class="card-wrapper">
                        <div class="card">
                            <!-- Bandeau République -->
                            <div class="header-band">
                                <div class="text-wrapper">
                                    <div class="republic">RÉPUBLIQUE DU CAMEROUN</div>
                                    <div class="devise">PAIX - TRAVAIL - PATRIE</div>
                                </div>
                            </div>

                            <!-- Corps de la carte -->
                            <div class="card-body">
                                <!-- En-tête collège + logo -->
                                <div class="college-header">
                                    <div class="college-name">
                                        <h2>COLLÈGE POLYVALENT<br>BILINGUE DE DOUALA</h2>
                                        <p>CARTE D'IDENTITÉ SCOLAIRE</p>
                                    </div>
                                    <div class="college-logo">
                                        <img src="{{ public_path('images/logo-college.png') }}" alt="Logo" onerror="this.style.display='none'">
                                    </div>
                                </div>

                                <!-- Photo + Infos -->
                                <div class="content-section">
                                    <div class="student-photo">
                                        @if($card['photo_url'])
                                            <img src="{{ public_path($card['photo_url']) }}" alt="Photo">
                                        @endif
                                    </div>

                                    <div class="student-info">
                                        <div class="info-row">
                                            <span class="info-label">NOM:</span>
                                            <span class="info-value">{{ $card['nom'] }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">PRÉNOM:</span>
                                            <span class="info-value">{{ $card['prenom'] }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">MATRICULE:</span>
                                            <span class="info-value">{{ $card['matricule'] }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">CLASSE:</span>
                                            <span class="info-value">{{ $card['classe'] }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">NÉ(E) LE:</span>
                                            <span class="info-value">{{ $card['date_naissance'] }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">PARENT:</span>
                                            <span class="info-value">{{ $card['parent_contact'] }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">ANNÉE:</span>
                                            <span class="info-value">{{ $academicYear }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pied: QR Code + Signature -->
                                <div class="card-footer">
                                    <div class="qr-section">
                                        <div class="qr-code">
                                            <img src="data:image/svg+xml;base64,{{ $card['qr_code'] }}" alt="QR Code">
                                        </div>
                                    </div>
                                    <div class="signature-section">
                                        <div class="signature-line">
                                            Signature Directeur
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</body>
</html>
