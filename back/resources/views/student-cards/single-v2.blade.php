<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte d'Identité - {{ $card['prenom'] }} {{ $card['nom'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 0;
            size: 85.6mm 54mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: white;
        }

        .card-wrapper {
            width: 85.6mm;
            height: 54mm;
            position: relative;
            overflow: hidden;
        }

        .card {
            width: 100%;
            height: 100%;
            position: relative;
        }

        /* Image de fond (template) */
        .card-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .card-background img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Couche de données superposées */
        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        /* Photo élève - en haut à droite (cercle) */
        .student-photo {
            position: absolute;
            top: {{ $settings['photo_top'] ?? '11' }}mm;
            right: {{ $settings['photo_right'] ?? '8' }}mm;
            width: {{ $settings['photo_width'] ?? '24' }}mm;
            height: {{ $settings['photo_height'] ?? '24' }}mm;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #2d5a3d;
            background: #f0f0f0;
        }

        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Informations élève - VALEURS SEULEMENT */
        .student-info {
            position: absolute;
            top: {{ $settings['info_top'] ?? '20.5' }}mm;
            left: {{ $settings['info_left'] ?? '33' }}mm;
            font-size: {{ $settings['info_font_size'] ?? '4.5' }}pt;
            line-height: {{ $settings['info_line_height'] ?? '3.8' }}mm;
            letter-spacing: {{ $settings['info_letter_spacing'] ?? '0' }}px;
            color: #000;
            font-weight: 500;
            max-width: {{ $settings['info_max_width'] ?? '40' }}mm;
        }

        .info-value {
            display: block;
            color: #000;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* QR Code - en bas au centre */
        .qr-code {
            position: absolute;
            bottom: {{ $settings['qr_bottom'] ?? '4' }}mm;
            left: calc(50% + {{ $settings['qr_left_offset'] ?? '0' }}mm);
            transform: translateX(-50%);
            width: {{ $settings['qr_width'] ?? '14' }}mm;
            height: {{ $settings['qr_height'] ?? '14' }}mm;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            display: block;
        }
    </style>
</head>
<body>
    <div class="card-wrapper">
        <div class="card">
            <!-- Image de fond (template) -->
            <div class="card-background">
                <img src="{{ public_path('images/carte-template.png') }}" alt="Template">
            </div>

            <!-- Données superposées -->
            <div class="card-overlay">
                <!-- Photo élève -->
                <div class="student-photo">
                    @if($card['photo_url'])
                        <img src="{{ $card['photo_url'] }}" alt="Photo élève">
                    @endif
                </div>

                <!-- Informations élève - VALEURS SEULEMENT -->
                <div class="student-info">
                    <span class="info-value">{{ $card['nom'] }}</span>
                    <span class="info-value">{{ $card['prenom'] }}</span>
                    <span class="info-value">{{ $card['matricule'] }}</span>
                    <span class="info-value">{{ $card['classe'] }}</span>
                    <span class="info-value">{{ $card['date_naissance'] }}</span>
                    <span class="info-value">{{ $card['parent_contact'] }}</span>
                    <span class="info-value">{{ $academicYear }}</span>
                </div>

                <!-- QR Code -->
                <div class="qr-code">
                    <img src="data:image/svg+xml;base64,{{ $card['qr_code'] }}" alt="QR Code">
                </div>
            </div>
        </div>
    </div>
</body>
</html>
