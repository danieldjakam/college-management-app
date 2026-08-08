<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau d'Honneur - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        @page {
            margin: 6mm;
            size: A4 landscape;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        .certificate-wrapper {
            border: 3px solid #009B3A;
            padding: 1.5mm;
        }

        .certificate-inner {
            border: 1.5px solid #CE1126;
            padding: 5mm 8mm;
            position: relative;
        }

        /* Corner decorations */
        .corner {
            position: absolute;
            width: 15mm;
            height: 15mm;
            border-color: #FCD116;
            border-style: solid;
        }
        .corner-tl { top: 1mm; left: 1mm; border-width: 2px 0 0 2px; }
        .corner-tr { top: 1mm; right: 1mm; border-width: 2px 2px 0 0; }
        .corner-bl { bottom: 1mm; left: 1mm; border-width: 0 0 2px 2px; }
        .corner-br { bottom: 1mm; right: 1mm; border-width: 0 2px 2px 0; }

        /* Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }

        .header-table td {
            vertical-align: top;
            font-size: 6.5pt;
            line-height: 1.15;
        }

        .header-left { width: 30%; text-align: left; }
        .header-center { width: 40%; text-align: center; }
        .header-right { width: 30%; text-align: right; }

        .header-table strong { font-size: 7pt; }

        .logo {
            max-width: 22mm;
            max-height: 22mm;
            height: auto;
        }

        .school-name {
            font-size: 7.5pt;
            font-weight: bold;
            color: #009B3A;
            margin-top: 1mm;
        }

        /* Cameroon colors separator */
        .separator {
            height: 2.5px;
            background: linear-gradient(to right, #009B3A 33%, #CE1126 33%, #CE1126 66%, #FCD116 66%);
            margin: 2mm 20mm;
        }

        /* Title */
        .title-section {
            text-align: center;
            margin: 3mm 0 1mm 0;
        }

        .title-section h1 {
            font-size: 20pt;
            font-weight: bold;
            margin: 0;
            color: #009B3A;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .title-section .subtitle {
            font-size: 11pt;
            font-style: italic;
            color: #888;
            margin-top: 0.5mm;
        }

        /* Decorative dots under title */
        .title-deco {
            text-align: center;
            margin: 1.5mm 0;
        }
        .deco-line {
            display: inline-block;
            width: 25mm;
            height: 0.4mm;
            background-color: #CE1126;
            vertical-align: middle;
        }
        .deco-dot {
            display: inline-block;
            width: 2mm;
            height: 2mm;
            background-color: #FCD116;
            border-radius: 50%;
            vertical-align: middle;
            margin: 0 1.5mm;
        }

        /* Intro */
        .intro-text {
            text-align: center;
            font-size: 9.5pt;
            margin: 2mm 0 1mm 0;
        }

        .certify-text {
            text-align: center;
            font-size: 11pt;
            font-style: italic;
            color: #009B3A;
            font-weight: bold;
            margin: 2mm 0;
        }

        /* Student info - 2 columns layout */
        .info-container {
            width: 88%;
            margin: 2mm auto;
        }

        .info-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-container td {
            padding: 1.8mm 3mm;
            font-size: 9.5pt;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-container tr:nth-child(odd) {
            background-color: #f7faf7;
        }

        .info-container tr:last-child td {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            width: 40%;
            color: #444;
            font-size: 8.5pt;
        }

        .info-value {
            font-weight: bold;
            color: #1a1a1a;
            font-size: 10pt;
        }

        .average-value {
            font-size: 12pt;
            color: #009B3A;
            font-weight: bold;
        }

        /* Mention badge */
        .mention-badge {
            display: inline-block;
            padding: 1mm 5mm;
            color: white;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 1mm;
        }
        .mention-excellent { background-color: #009B3A; }
        .mention-bien { background-color: #2563eb; }
        .mention-assez-bien { background-color: #d97706; }
        .mention-passable { background-color: #6b7280; }

        /* Certification */
        .certification-section {
            text-align: center;
            margin: 3mm 0 1mm 0;
        }

        .certification-main {
            font-size: 10.5pt;
            font-weight: bold;
        }

        .certification-en {
            font-size: 8pt;
            font-style: italic;
            color: #888;
        }

        .legal-text {
            text-align: center;
            margin: 2mm 0;
        }

        .legal-text-fr { font-size: 9pt; color: #333; }
        .legal-text-en { font-size: 7pt; font-style: italic; color: #999; }

        /* Signature */
        .signature-section {
            width: 100%;
            margin-top: 3mm;
        }

        .signature-section td {
            text-align: center;
            vertical-align: top;
            padding: 1mm;
        }

        .signature-title {
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8mm;
        }

        .signature-line {
            border-top: 1.5px solid #333;
            width: 40mm;
            margin: 0 auto;
            padding-top: 0.5mm;
            font-size: 7pt;
            color: #888;
            font-style: italic;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 1.5mm;
            font-size: 7pt;
            color: #999;
            font-style: italic;
        }

        .stars {
            color: #FCD116;
            font-size: 5.5pt;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="certificate-wrapper">
        <div class="certificate-inner">
            <div class="corner corner-tl"></div>
            <div class="corner corner-tr"></div>
            <div class="corner corner-bl"></div>
            <div class="corner corner-br"></div>

            <!-- Header -->
            <table class="header-table">
                <tr>
                    <td class="header-left">
                        <strong>REPUBLIQUE DU CAMEROUN</strong><br>
                        Paix - Travail - Patrie<br>
                        <span class="stars">* * * * * * *</span><br>
                        MINISTERE DES ENSEIGNEMENTS<br>
                        SECONDAIRES<br>
                        <span class="stars">* * * * * * *</span><br>
                        DELEGATION REGIONALE<br>
                        DU LITTORAL
                    </td>
                    <td class="header-center">
                        @if(!empty($logo_base64))
                            <img src="{{ $logo_base64 }}" alt="Logo" class="logo">
                        @else
                            <div style="font-size: 24pt; color: #009B3A; font-weight: bold; line-height: 1;">CPB</div>
                            <div style="font-size: 7pt; color: #009B3A;">DOUALA</div>
                        @endif
                        <div class="school-name">COLLEGE POLYVALENT BILINGUE DE DOUALA</div>
                    </td>
                    <td class="header-right">
                        <strong>REPUBLIC OF CAMEROON</strong><br>
                        Peace - Work - Fatherland<br>
                        <span class="stars">* * * * * * *</span><br>
                        MINISTRY OF SECONDARY<br>
                        EDUCATION<br>
                        <span class="stars">* * * * * * *</span><br>
                        LITTORAL REGIONAL<br>
                        DELEGATION
                    </td>
                </tr>
            </table>

            <div class="separator"></div>

            <!-- Title -->
            <div class="title-section">
                <h1>Tableau d'Honneur</h1>
                <div class="subtitle">Honor Roll Certificate</div>
            </div>

            <div class="title-deco">
                <span class="deco-line"></span>
                <span class="deco-dot"></span>
                <span class="deco-line"></span>
            </div>

            <!-- Intro -->
            <div class="intro-text">
                Le Chef d'Etablissement du
                <strong>COLLEGE POLYVALENT BILINGUE DE DOUALA</strong>
            </div>

            <div class="certify-text">Certifie que / Certifies that</div>

            <!-- Student info -->
            <div class="info-container">
                <table>
                    <tr>
                        <td class="info-label">Nom et Prenom(s) / Full Name:</td>
                        <td class="info-value">{{ strtoupper($student->last_name) }} {{ $student->first_name }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Ne(e) le / Born on:</td>
                        <td class="info-value">{{ \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Classe / Class:</td>
                        <td class="info-value">{{ $class_name }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Periode / Period:</td>
                        <td class="info-value">{{ $period_label ?? ($trimester ? $trimester->number . ($trimester->number == 1 ? 'er' : 'eme') . ' Trimestre' : 'Annee Scolaire') }} {{ $academic_year }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Moyenne Generale / General Average:</td>
                        <td class="info-value"><span class="average-value">{{ number_format($average, 2) }}/20</span></td>
                    </tr>
                    <tr>
                        <td class="info-label">Rang / Rank:</td>
                        <td class="info-value">{{ $rank }}{{ $rank == 1 ? 'er' : 'eme' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Mention / Grade:</td>
                        <td class="info-value">
                            @php
                                $mentionLower = strtolower($mention);
                                $mentionClass = 'mention-passable';
                                if (str_contains($mentionLower, 'excellent')) $mentionClass = 'mention-excellent';
                                elseif ($mentionLower === 'bien' || str_contains($mentionLower, 'très bien')) $mentionClass = 'mention-bien';
                                elseif (str_contains($mentionLower, 'assez')) $mentionClass = 'mention-assez-bien';
                            @endphp
                            <span class="mention-badge {{ $mentionClass }}">{{ strtoupper($mention) }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Certification -->
            <div class="certification-section">
                <div class="certification-main">A accompli un travail remarquable et figure au Tableau d'Honneur</div>
                <div class="certification-en">Has accomplished remarkable work and appears on the Honor Roll</div>
            </div>

            <div class="legal-text">
                <div class="legal-text-fr">En foi de quoi, le present certificat lui est delivre pour servir et valoir ce que de droit.</div>
                <div class="legal-text-en">In witness whereof, this certificate is issued to serve for all intents and purposes.</div>
            </div>

            <!-- Signature -->
            <table class="signature-section">
                <tr>
                    <td style="width: 55%;"></td>
                    <td style="width: 45%;">
                        <div class="signature-title">Le Principal</div>
                        <div class="signature-line">Principal</div>
                    </td>
                </tr>
            </table>

            <!-- Footer -->
            <div class="footer">
                Fait a Douala, le {{ $generation_date }} &middot; Made in Douala on {{ $generation_date }}
            </div>
        </div>
    </div>
</body>
</html>
