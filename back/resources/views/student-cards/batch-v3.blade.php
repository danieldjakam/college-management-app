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
            margin: 5mm;
            size: A4 portrait;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: white;
        }

        .page {
            width: 100%;
            page-break-after: always;
            position: relative;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        /* Grille de 2 colonnes × 5 lignes = 10 cartes */
        .cards-grid {
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 3mm;
            padding: 2mm;
        }

        .card-wrapper {
            width: 100%;
            height: 100%;
            page-break-inside: avoid;
        }

        .card {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
            background: #F5F1E8;
            border-radius: 3mm;
        }

        /* Cercles de couleur aux coins */
        .circle-green {
            position: absolute;
            top: -15mm;
            left: -15mm;
            width: 30mm;
            height: 30mm;
            background: #2D7F3E;
            border-radius: 50%;
            opacity: 0.8;
        }

        .circle-red {
            position: absolute;
            top: -15mm;
            right: -15mm;
            width: 30mm;
            height: 30mm;
            background: #E53935;
            border-radius: 50%;
            opacity: 0.8;
        }

        .circle-yellow {
            position: absolute;
            bottom: -15mm;
            left: -15mm;
            width: 30mm;
            height: 30mm;
            background: #FFC107;
            border-radius: 50%;
            opacity: 0.8;
        }

        /* En-tête République du Cameroun */
        .header {
            text-align: center;
            padding: 1mm 0;
            font-size: 2.5pt;
            font-weight: bold;
            color: #000;
        }

        .header-line1 {
            font-size: 3pt;
            letter-spacing: 0.2mm;
        }

        .header-line2 {
            font-size: 2.8pt;
            margin-top: 0.3mm;
        }

        /* Logo et nom école */
        .school-header {
            display: flex;
            align-items: center;
            padding: 0 3mm;
            margin: 1mm 0;
        }

        .logo {
            width: 8mm;
            height: 8mm;
            background: white;
            border: 0.3mm solid #7B2D8E;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2pt;
            font-weight: bold;
            color: #7B2D8E;
            margin-right: 1.5mm;
            flex-shrink: 0;
        }

        .school-name {
            font-size: 2.5pt;
            font-weight: bold;
            font-style: italic;
            line-height: 1.1;
        }

        /* Titre CARTE SCOLAIRE */
        .title {
            font-size: 6pt;
            font-weight: bold;
            color: #000;
            margin: 1mm 0 1mm 3mm;
            line-height: 1;
        }

        /* Container principal */
        .main-content {
            display: flex;
            padding: 0 3mm;
            margin-top: 1mm;
        }

        /* Colonne gauche - Infos */
        .info-section {
            flex: 1;
            padding-right: 2mm;
        }

        .info-line {
            font-size: {{ $settings['info_font_size'] ?? '2.8' }}pt;
            line-height: {{ $settings['info_line_height'] ?? '2.5' }}mm;
            color: #000;
            display: flex;
        }

        .info-label {
            font-weight: bold;
            min-width: 15mm;
        }

        .info-value {
            flex: 1;
        }

        /* Colonne droite - Photo */
        .photo-section {
            flex: 0 0 28mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-circle {
            width: {{ $settings['photo_width'] ?? '25' }}mm;
            height: {{ $settings['photo_height'] ?? '25' }}mm;
            border-radius: 50%;
            border: 0.5mm solid #2D7F3E;
            overflow: hidden;
            background: linear-gradient(to bottom, #87CEEB 0%, #87CEEB 60%, #90EE90 60%, #228B22 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 2mm;
            left: 3mm;
            right: 3mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .contact-info {
            font-size: 1.8pt;
            line-height: 1.3;
            color: #000;
        }

        .qr-section {
            text-align: center;
        }

        .qr-label {
            font-size: 2pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .qr-code {
            width: {{ $settings['qr_width'] ?? '12' }}mm;
            height: {{ $settings['qr_height'] ?? '12' }}mm;
            border: 0.3mm solid #000;
            border-radius: 1mm;
            padding: 0.5mm;
            background: white;
            margin-bottom: 0.5mm;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            display: block;
        }

        .signature {
            font-size: 1.8pt;
            font-weight: bold;
            line-height: 1.2;
        }
    </style>
</head>

<body>
    @php
    $cardsPerPage = 10;
    $totalCards = count($cards);
    $totalPages = ceil($totalCards / $cardsPerPage);
    @endphp

    @for ($page = 0; $page < $totalPages; $page++)
        <div class="page">
            <div class="cards-grid">
                @for ($cardIndex = $page * $cardsPerPage; $cardIndex < min(($page + 1) * $cardsPerPage, $totalCards); $cardIndex++)
                    @php $card = $cards[$cardIndex]; @endphp

                    <div class="card-wrapper">
                        <div class="card">
                            <!-- Cercles de couleur -->
                            <div class="circle-green"></div>
                            <div class="circle-red"></div>
                            <div class="circle-yellow"></div>

                            <!-- En-tête République du Cameroun -->
                            <div class="header">
                                <div class="header-line1">🇨🇲 RÉPUBLIQUE DU CAMEROUN 🇨🇲</div>
                                <div class="header-line2">PAIX – TRAVAIL – PATRIE</div>
                            </div>

                            <!-- Logo et nom école -->
                            <div class="school-header">
                                <div class="logo">CPBD</div>
                                <div class="school-name">
                                    Collège Polyvalent<br>
                                    Bilingue de Douala
                                </div>
                            </div>

                            <!-- Titre -->
                            <div class="title">
                                CARTE<br>SCOLAIRE
                            </div>

                            <!-- Contenu principal -->
                            <div class="main-content">
                                <!-- Informations élève -->
                                <div class="info-section">
                                    <div class="info-line">
                                        <span class="info-label">NOM:</span>
                                        <span class="info-value">{{ $card['nom'] }}</span>
                                    </div>
                                    <div class="info-line">
                                        <span class="info-label">PRÉNOM:</span>
                                        <span class="info-value">{{ $card['prenom'] }}</span>
                                    </div>
                                    <div class="info-line">
                                        <span class="info-label">MATRICULE:</span>
                                        <span class="info-value">{{ $card['matricule'] }}</span>
                                    </div>
                                    <div class="info-line">
                                        <span class="info-label">CLASSE:</span>
                                        <span class="info-value">{{ $card['classe'] }}</span>
                                    </div>
                                    <div class="info-line">
                                        <span class="info-label">NÉ(E) LE:</span>
                                        <span class="info-value">{{ $card['date_naissance'] }}</span>
                                    </div>
                                    <div class="info-line">
                                        <span class="info-label">PARENT/TUTEUR:</span>
                                        <span class="info-value" style="font-size: 2.3pt;">{{ $card['parent_contact'] }}</span>
                                    </div>
                                    <div class="info-line">
                                        <span class="info-label">ANNÉE SCOLAIRE:</span>
                                        <span class="info-value">{{ $academicYear }}</span>
                                    </div>
                                </div>

                                <!-- Photo élève -->
                                <div class="photo-section">
                                    <div class="photo-circle">
                                        @if($card['photo_url'])
                                            <img src="{{ $card['photo_url'] }}" alt="Photo">
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="footer">
                                <!-- Coordonnées -->
                                <div class="contact-info">
                                    <div>Phone: +237678469398 / +237688552219</div>
                                    <div style="font-weight: bold;">website: www.cpbd-douala.com</div>
                                    <div style="display: flex; align-items: center;">
                                        <span style="font-size: 3pt; margin-right: 0.5mm;">📍</span>
                                        <span style="font-weight: bold;">YASSA ENTRÉE PLANET VOYAGE</span>
                                    </div>
                                </div>

                                <!-- QR Code + Signature -->
                                <div class="qr-section">
                                    <div class="qr-label">SCAN ME</div>
                                    <div class="qr-code">
                                        <img src="data:image/svg+xml;base64,{{ $card['qr_code'] }}" alt="QR Code">
                                    </div>
                                    <div class="signature">
                                        Signature<br>Directeur
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    @endfor
</body>
</html>
