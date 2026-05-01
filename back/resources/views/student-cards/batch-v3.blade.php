<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cartes d'Identite Scolaires - {{ $className }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { margin: 5mm 3mm; size: A4 portrait; }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            background: white;
        }

        .page {
            width: 100%;
            page-break-after: always;
            position: relative;
        }
        .page:last-child { page-break-after: avoid; }

        .cards-grid { width: 100%; height: 100%; position: relative; }

        /* 2 colonnes x 5 lignes = 10 cartes par page A4
           Page utile: 204mm x 287mm (A4 210x297 - marges 3+3 x 5+5)
           Carte: 100mm x 56mm (espace entre = 4mm horizontal, 1.4mm vertical)
        */
        .card-wrapper {
            width: 100mm;
            height: 56mm;
            position: absolute;
            page-break-inside: avoid;
        }

        /* Colonne 1 (gauche) */
        .card-wrapper:nth-child(1)  { top: 0mm;      left: 0mm; }
        .card-wrapper:nth-child(3)  { top: 57.4mm;   left: 0mm; }
        .card-wrapper:nth-child(5)  { top: 114.8mm;  left: 0mm; }
        .card-wrapper:nth-child(7)  { top: 172.2mm;  left: 0mm; }
        .card-wrapper:nth-child(9)  { top: 229.6mm;  left: 0mm; }
        /* Colonne 2 (droite - a l'extremite) */
        .card-wrapper:nth-child(2)  { top: 0mm;      left: 104mm; }
        .card-wrapper:nth-child(4)  { top: 57.4mm;   left: 104mm; }
        .card-wrapper:nth-child(6)  { top: 114.8mm;  left: 104mm; }
        .card-wrapper:nth-child(8)  { top: 172.2mm;  left: 104mm; }
        .card-wrapper:nth-child(10) { top: 229.6mm;  left: 104mm; }

        .card {
            width: 100%; height: 100%; position: relative;
            overflow: hidden; background: #ffffff;
            border-radius: 3mm; border: 0.5px solid #ccc;
        }

        .republic-bar {
            position: absolute; top: 0; left: 0; right: 0;
            height: 6.5mm; background: #f8f8f0;
            border-bottom: 0.3px solid #ddd; z-index: 4;
            text-align: center; padding-top: 0.5mm;
        }
        .republic-bar table { width: 100%; border-collapse: collapse; }
        .republic-bar td { vertical-align: middle; padding: 0; }
        .flag-cell { width: 8mm; text-align: center; }
        .flag { display: inline-block; width: 5mm; height: 3.2mm; border: 0.3px solid #ccc; overflow: hidden; }
        .flag-stripe { display: inline-block; width: 33.33%; height: 100%; float: left; }
        .flag-green { background: #009639; }
        .flag-red { background: #CE1126; }
        .flag-yellow { background: #FCD116; }
        .republic-text { text-align: center; }
        .republic-name { font-size: 6pt; font-weight: bold; color: #1a1a1a; letter-spacing: 0.3px; }
        .republic-motto { font-size: 5pt; color: #555; font-style: italic; margin-top: 0.3mm; }

        .card-header {
            position: absolute; top: 6.5mm; left: 0; right: 0;
            height: 10mm; background: #5B2C87; z-index: 2;
        }
        .header-logo { position: absolute; top: 1mm; left: 2.5mm; width: 9mm; height: 9mm; }
        .header-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: 50%; border: 1px solid rgba(255,255,255,0.7); background: #ffffff; }
        .header-logo-placeholder { width: 8mm; height: 8mm; background: rgba(255,255,255,0.15); border-radius: 50%; border: 0.5px solid rgba(255,255,255,0.3); }
        .header-text { position: absolute; top: 1mm; left: 12mm; right: 14mm; text-align: center; color: white; }
        .header-title { font-size: 7pt; font-weight: bold; letter-spacing: 0.3px; text-transform: uppercase; color: #ffffff; }
        .header-subtitle { font-size: 6pt; letter-spacing: 0.8px; margin-top: 0.8mm; color: #9B59B6; font-weight: bold; }

        .chevron-top { position: absolute; top: 6.5mm; right: 0; width: 12mm; height: 10mm; z-index: 3; overflow: hidden; }
        .chevron-top-inner { position: absolute; top: 1mm; right: -2mm; width: 0; height: 0; border-top: 4mm solid #9B59B6; border-bottom: 4mm solid transparent; border-left: 5mm solid transparent; border-right: 5mm solid #9B59B6; }
        .chevron-top-inner2 { position: absolute; top: 1mm; right: 2.5mm; width: 0; height: 0; border-top: 4mm solid transparent; border-bottom: 4mm solid transparent; border-right: 3.5mm solid rgba(155, 89, 182, 0.35); }
        .chevron-bottom { position: absolute; bottom: 0; right: 0; width: 12mm; height: 8mm; z-index: 3; overflow: hidden; }
        .chevron-bottom-inner { position: absolute; bottom: -1mm; right: -2mm; width: 0; height: 0; border-bottom: 4mm solid #9B59B6; border-top: 4mm solid transparent; border-left: 5mm solid transparent; border-right: 5mm solid #9B59B6; }
        .chevron-bottom-inner2 { position: absolute; bottom: -1mm; right: 2.5mm; width: 0; height: 0; border-top: 4mm solid transparent; border-bottom: 4mm solid transparent; border-right: 3.5mm solid rgba(155, 89, 182, 0.35); }

        .photo-zone { position: absolute; top: 18mm; left: 3mm; width: 19mm; height: 24mm; border: 1.5px solid #5B2C87; border-radius: 1mm; overflow: hidden; background: #f0e6f6; z-index: 2; }
        .photo-zone img { width: 100%; height: 100%; object-fit: cover; }
        .photo-placeholder { width: 100%; height: 100%; display: table; text-align: center; color: #999; font-size: 5pt; }
        .photo-placeholder span { display: table-cell; vertical-align: middle; }

        .info-zone { position: absolute; top: 17.5mm; left: 24mm; right: 3mm; }
        .info-matricule { font-size: 6pt; color: #555; font-weight: bold; margin-bottom: 0.3mm; }
        .info-matricule-label { color: #888; font-weight: normal; }
        .class-badge { position: absolute; top: 17.5mm; right: 3mm; background: #5B2C87; color: #fff; font-size: 5.5pt; font-weight: bold; padding: 0.8mm 2mm; border-radius: 1.5mm; z-index: 2; }
        .info-name { font-size: 9pt; font-weight: bold; color: #1a1a1a; margin-top: 0.5mm; margin-bottom: 0.8mm; text-transform: uppercase; }
        .info-details { font-size: 6pt; color: #333; line-height: 1.6; }
        .info-details-line { margin-bottom: 0.3mm; }
        .info-label { color: #666; font-weight: bold; text-transform: uppercase; font-size: 5.5pt; }
        .info-value { color: #1a1a1a; }

        .qr-zone { position: absolute; bottom: 5.5mm; right: 16mm; width: 12mm; height: 12mm; z-index: 2; }
        .qr-zone img { width: 100%; height: 100%; }

        .card-footer { position: absolute; bottom: 2mm; left: 3mm; right: 26mm; text-align: left; }
        .footer-year { font-size: 5.5pt; color: #5B2C87; font-weight: bold; }
        .footer-school { font-size: 3.5pt; color: #999; margin-top: 0.3mm; }
        .footer-expires { font-size: 5pt; color: #9B59B6; font-weight: bold; position: absolute; bottom: 1.5mm; right: 14mm; }

        .watermark { position: absolute; top: 20mm; left: 25mm; width: 35mm; height: 35mm; z-index: 1; opacity: 0.10; }
        .watermark img { width: 100%; height: 100%; object-fit: contain; }

        .bottom-accent { position: absolute; bottom: 0; left: 0; right: 0; height: 0.8mm; background: #5B2C87; }
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
            @for ($i = $page * $cardsPerPage; $i < min(($page + 1) * $cardsPerPage, $totalCards); $i++)
            @php $card = $cards[$i]; @endphp

            <div class="card-wrapper">
                <div class="card">
                    <div class="republic-bar">
                        <table><tr>
                            <td class="flag-cell"><div class="flag"><div class="flag-stripe flag-green"></div><div class="flag-stripe flag-red"></div><div class="flag-stripe flag-yellow"></div></div></td>
                            <td class="republic-text"><div class="republic-name">REPUBLIQUE DU CAMEROUN</div><div class="republic-motto">PAIX - TRAVAIL - PATRIE</div></td>
                            <td class="flag-cell"><div class="flag"><div class="flag-stripe flag-green"></div><div class="flag-stripe flag-red"></div><div class="flag-stripe flag-yellow"></div></div></td>
                        </tr></table>
                    </div>

                    <div class="card-header">
                        <div class="header-logo">
                            @if(isset($logoBase64) && $logoBase64)
                                <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                            @else
                                <div class="header-logo-placeholder"></div>
                            @endif
                        </div>
                        <div class="header-text">
                            <div class="header-title">{{ $schoolName ?? 'College Polyvalent Bilingue' }}</div>
                            <div class="header-subtitle">CARTE D'IDENTITE SCOLAIRE</div>
                        </div>
                    </div>

                    <div class="chevron-top"><div class="chevron-top-inner"></div><div class="chevron-top-inner2"></div></div>
                    <div class="chevron-bottom"><div class="chevron-bottom-inner"></div><div class="chevron-bottom-inner2"></div></div>

                    @if(isset($logoBase64) && $logoBase64)
                    <div class="watermark"><img src="data:image/png;base64,{{ $logoBase64 }}" alt=""></div>
                    @endif

                    <div class="photo-zone">
                        @if(isset($card['photo_base64']) && $card['photo_base64'])
                            <img src="{{ $card['photo_base64'] }}" alt="Photo">
                        @elseif(isset($card['photo_url']) && $card['photo_url'])
                            <img src="{{ $card['photo_url'] }}" alt="Photo">
                        @else
                            <div class="photo-placeholder"><span>PHOTO</span></div>
                        @endif
                    </div>

                    <div class="class-badge">{{ $card['classe'] }}</div>

                    <div class="info-zone">
                        <div class="info-matricule"><span class="info-matricule-label">NO :</span> {{ $card['matricule'] }}</div>
                        <div class="info-name">{{ $card['nom'] }} {{ $card['prenom'] }}</div>
                        <div class="info-details">
                            <div class="info-details-line"><span class="info-label">Ne(e) le :</span> <span class="info-value">{{ $card['date_naissance'] }}</span></div>
                            <div class="info-details-line"><span class="info-label">Sexe :</span> <span class="info-value">{{ isset($card['student']) ? ($card['student']->gender ?? 'M') : 'M' }}</span> &nbsp;&nbsp; <span class="info-label">Classe :</span> <span class="info-value">{{ $card['classe'] }}</span></div>
                            <div class="info-details-line"><span class="info-label">Parent :</span> <span class="info-value">{{ $card['parent_contact'] }}</span></div>
                        </div>
                    </div>

                    <div class="qr-zone"><img src="data:image/png;base64,{{ $card['qr_code'] }}" alt="QR"></div>

                    <div class="card-footer">
                        <div class="footer-year">ANNEE : {{ $academicYear }}</div>
                        <div class="footer-school">College Polyvalent Bilingue de Douala</div>
                    </div>
                    <div class="footer-expires">VALIDE : {{ $academicYear }}</div>

                    <div class="bottom-accent"></div>
                </div>
            </div>
            @endfor
        </div>
    </div>
    @endfor
</body>
</html>
