<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau d'Honneur - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 portrait;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        .certificate-wrapper {
            border: 3px solid #009B3A;
            padding: 2mm;
            height: 267mm;
            position: relative;
        }

        .certificate-inner {
            border: 1.5px solid #CE1126;
            padding: 8mm 10mm;
            height: 100%;
            position: relative;
        }

        /* Corner decorations */
        .corner {
            position: absolute;
            width: 18mm;
            height: 18mm;
            border-color: #FCD116;
            border-style: solid;
        }
        .corner-tl { top: 2mm; left: 2mm; border-width: 2px 0 0 2px; }
        .corner-tr { top: 2mm; right: 2mm; border-width: 2px 2px 0 0; }
        .corner-bl { bottom: 2mm; left: 2mm; border-width: 0 0 2px 2px; }
        .corner-br { bottom: 2mm; right: 2mm; border-width: 0 2px 2px 0; }

        /* Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4mm;
        }

        .header-table td {
            vertical-align: top;
            font-size: 7pt;
            line-height: 1.2;
        }

        .header-left {
            width: 33%;
            text-align: left;
        }

        .header-center {
            width: 34%;
            text-align: center;
        }

        .header-right {
            width: 33%;
            text-align: right;
        }

        .header-table strong {
            font-size: 7.5pt;
        }

        .logo {
            max-width: 28mm;
            max-height: 28mm;
            height: auto;
        }

        .school-name {
            font-size: 8pt;
            font-weight: bold;
            color: #009B3A;
            margin-top: 1mm;
        }

        /* Decorative separator */
        .separator {
            height: 3px;
            background: linear-gradient(to right, #009B3A 33%, #CE1126 33%, #CE1126 66%, #FCD116 66%);
            margin: 3mm 15mm;
        }

        /* Main title */
        .title-section {
            text-align: center;
            margin: 6mm 0 4mm 0;
        }

        .title-section h1 {
            font-size: 26pt;
            font-weight: bold;
            margin: 0;
            color: #009B3A;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .title-section .subtitle {
            font-size: 13pt;
            font-style: italic;
            color: #777;
            margin-top: 1mm;
            letter-spacing: 1px;
        }

        /* Decorative line under title */
        .title-underline {
            width: 60%;
            margin: 3mm auto;
            text-align: center;
        }
        .title-underline-inner {
            display: inline-block;
            width: 30mm;
            height: 0.5mm;
            background-color: #CE1126;
            margin: 0 2mm;
            vertical-align: middle;
        }
        .title-underline-dot {
            display: inline-block;
            width: 2.5mm;
            height: 2.5mm;
            background-color: #FCD116;
            border-radius: 50%;
            vertical-align: middle;
        }

        /* Intro text */
        .intro-text {
            text-align: center;
            font-size: 11pt;
            margin: 5mm 0 3mm 0;
            color: #333;
        }

        .intro-text .school-ref {
            font-weight: bold;
            font-size: 11.5pt;
            color: #1a1a1a;
        }

        .certify-text {
            text-align: center;
            font-size: 13pt;
            font-style: italic;
            color: #009B3A;
            font-weight: bold;
            margin: 4mm 0;
            letter-spacing: 0.5px;
        }

        /* Student info */
        .student-info-table {
            width: 82%;
            margin: 4mm auto;
            border-collapse: collapse;
        }

        .student-info-table td {
            padding: 2.5mm 4mm;
            font-size: 10.5pt;
            border-bottom: 1px solid #e0e0e0;
        }

        .student-info-table tr:last-child td {
            border-bottom: none;
        }

        .student-info-table tr:nth-child(odd) {
            background-color: #f7faf7;
        }

        .info-label {
            font-weight: bold;
            width: 48%;
            color: #444;
            font-size: 9.5pt;
        }

        .info-value {
            font-weight: bold;
            color: #1a1a1a;
            font-size: 11pt;
        }

        /* Mention badge */
        .mention-badge {
            display: inline-block;
            padding: 1.5mm 6mm;
            color: white;
            font-weight: bold;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 1mm;
        }

        .mention-excellent { background-color: #009B3A; }
        .mention-bien { background-color: #2563eb; }
        .mention-assez-bien { background-color: #d97706; }
        .mention-passable { background-color: #6b7280; }

        /* Certification text */
        .certification-section {
            text-align: center;
            margin: 6mm 0;
        }

        .certification-main {
            font-size: 12pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 1.5mm;
        }

        .certification-en {
            font-size: 9pt;
            font-style: italic;
            color: #888;
        }

        .legal-text {
            text-align: center;
            margin: 5mm 0 2mm 0;
        }

        .legal-text-fr {
            font-size: 10pt;
            color: #333;
        }

        .legal-text-en {
            font-size: 8pt;
            font-style: italic;
            color: #999;
        }

        /* Signature */
        .signature-section {
            width: 100%;
            margin-top: 10mm;
        }

        .signature-section td {
            text-align: center;
            vertical-align: top;
            padding: 2mm;
        }

        .signature-title {
            font-weight: bold;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12mm;
        }

        .signature-line {
            border-top: 1.5px solid #333;
            width: 45mm;
            margin: 0 auto;
            padding-top: 1mm;
            font-size: 7.5pt;
            color: #888;
            font-style: italic;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 4mm;
            font-size: 8pt;
            color: #999;
            font-style: italic;
        }

        .stars {
            color: #FCD116;
            font-size: 6pt;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="certificate-wrapper">
        <div class="certificate-inner">
            <!-- Corner decorations -->
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
                            <div style="font-size: 28pt; color: #009B3A; font-weight: bold; line-height: 1;">CPB</div>
                            <div style="font-size: 8pt; color: #009B3A;">DOUALA</div>
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

            <!-- Cameroon colors separator -->
            <div class="separator"></div>

            <!-- Title -->
            <div class="title-section">
                <h1>Tableau d'Honneur</h1>
                <div class="subtitle">Honor Roll Certificate</div>
            </div>

            <!-- Decorative underline -->
            <div class="title-underline">
                <span class="title-underline-inner"></span>
                <span class="title-underline-dot"></span>
                <span class="title-underline-inner"></span>
            </div>

            <!-- Introduction -->
            <div class="intro-text">
                Le Chef d'Etablissement du<br>
                <span class="school-ref">COLLEGE POLYVALENT BILINGUE DE DOUALA</span>
            </div>

            <div class="certify-text">
                Certifie que / Certifies that
            </div>

            <!-- Student information -->
            <table class="student-info-table">
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
                    <td class="info-value" style="font-size: 13pt; color: #009B3A;">{{ number_format($average, 2) }}/20</td>
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

            <!-- Certification text -->
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
                    <td style="width: 50%;"></td>
                    <td style="width: 50%;">
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
