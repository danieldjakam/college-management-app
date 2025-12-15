<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau d'Honneur - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        @page {
            margin: 8mm;
            size: A4 landscape;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.1;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .main-border {
            border: 4px solid #009B3A;
            padding: 4mm;
        }

        /* En-tête */
        .header-table {
            width: 100%;
            margin-bottom: 2mm;
        }

        .header-table td {
            vertical-align: top;
            font-size: 6.5pt;
            line-height: 1.0;
        }

        .header-left {
            width: 35%;
            text-align: left;
        }

        .header-center {
            width: 30%;
            text-align: center;
        }

        .header-right {
            width: 35%;
            text-align: right;
        }

        .header-table strong {
            font-size: 7pt;
            font-weight: bold;
        }

        .logo {
            max-width: 35mm;
            max-height: 30mm;
            height: auto;
        }

        /* Titre */
        .main-title {
            text-align: center;
            margin: 2mm 0;
            padding-bottom: 1mm;
            border-bottom: 2px solid #CE1126;
        }

        .main-title h1 {
            font-size: 16pt;
            font-weight: bold;
            margin: 0 0 1mm 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .main-title .subtitle {
            font-size: 10pt;
            font-style: italic;
            color: #555;
        }

        /* Corps */
        .certificate-intro {
            text-align: center;
            font-size: 9pt;
            margin: 2mm 0;
        }

        .certificate-intro strong {
            font-weight: bold;
        }

        /* Informations élève */
        .student-info-table {
            width: 90%;
            margin: 2mm auto;
            background-color: #f9f9f9;
            border: 2px solid #009B3A;
        }

        .student-info-table td {
            padding: 1.5mm;
            border-bottom: 1px solid #ddd;
            font-size: 8pt;
        }

        .student-info-table tr:last-child td {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            width: 45%;
            color: #333;
        }

        .info-value {
            font-weight: bold;
            color: #000;
        }

        .mention-badge {
            display: inline-block;
            padding: 1mm 3mm;
            background-color: #009B3A;
            color: white;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
        }

        /* Texte de certification */
        .certification-text {
            text-align: center;
            font-size: 9pt;
            margin: 2mm 0;
        }

        .certification-text strong {
            font-weight: bold;
        }

        .certification-text em {
            font-style: italic;
            font-size: 7pt;
            color: #666;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            margin-top: 3mm;
        }

        .signature-cell {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 1mm;
        }

        .signature-title {
            font-weight: bold;
            font-size: 7pt;
            margin-bottom: 6mm;
            text-transform: uppercase;
        }

        .signature-line {
            border-top: 1px solid #000;
            padding-top: 1mm;
            font-size: 6pt;
            color: #666;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 2mm;
            font-size: 6pt;
            font-style: italic;
            color: #666;
        }

        .small-text {
            font-size: 5.5pt;
        }
    </style>
</head>
<body>
    <div class="main-border">
        <!-- En-tête bilingue -->
        <table class="header-table">
            <tr>
                <!-- Partie française -->
                <td class="header-left">
                    <strong>RÉPUBLIQUE DU CAMEROUN</strong><br>
                    Paix - Travail - Patrie<br>
                    <span class="small-text">***************</span><br>
                    MINISTÈRE DES ENSEIGNEMENTS<br>
                    SECONDAIRES<br>
                    <span class="small-text">***************</span><br>
                    DÉLÉGATION RÉGIONALE<br>
                    DU LITTORAL
                </td>

                <!-- Logo central -->
                <td class="header-center">
                    @if(!empty($logo_base64))
                        <img src="{{ $logo_base64 }}" alt="Logo École" class="logo">
                    @else
                        <div style="font-size: 30pt; color: #009B3A; font-weight: bold;">CPB<br><span style="font-size: 10pt;">DOUALA</span></div>
                    @endif
                </td>

                <!-- Partie anglaise -->
                <td class="header-right">
                    <strong>REPUBLIC OF CAMEROON</strong><br>
                    Peace - Work - Fatherland<br>
                    <span class="small-text">***************</span><br>
                    MINISTRY OF SECONDARY<br>
                    EDUCATION<br>
                    <span class="small-text">***************</span><br>
                    LITTORAL REGIONAL<br>
                    DELEGATION
                </td>
            </tr>
        </table>

        <!-- Titre principal -->
        <div class="main-title">
            <h1>Tableau d'Honneur</h1>
            <div class="subtitle">Honor Roll Certificate</div>
        </div>

        <!-- Introduction -->
        <div class="certificate-intro">
            Le Chef d'Établissement du <strong>COLLÈGE POLYVALENT BILINGUE DE DOUALA</strong><br>
            <br>
            Certifie que
        </div>

        <!-- Informations de l'élève -->
        <table class="student-info-table">
            <tr>
                <td class="info-label">Nom et Prénom(s) / Full Name:</td>
                <td class="info-value">{{ strtoupper($student->last_name) }} {{ $student->first_name }}</td>
            </tr>
            <tr>
                <td class="info-label">Né(e) le / Born on:</td>
                <td class="info-value">{{ \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="info-label">Classe / Class:</td>
                <td class="info-value">{{ $class_name }}</td>
            </tr>
            <tr>
                <td class="info-label">Période / Period:</td>
                <td class="info-value">{{ $trimester->number }}{{ $trimester->number == 1 ? 'er' : 'ème' }} Trimestre {{ $academic_year }}</td>
            </tr>
            <tr>
                <td class="info-label">Moyenne Générale / General Average:</td>
                <td class="info-value">{{ number_format($average, 2) }}/20</td>
            </tr>
            <tr>
                <td class="info-label">Rang / Rank:</td>
                <td class="info-value">{{ $rank }}{{ $rank == 1 ? 'er' : 'ème' }}</td>
            </tr>
            <tr>
                <td class="info-label">Mention / Grade:</td>
                <td class="info-value">
                    <span class="mention-badge">{{ strtoupper($mention) }}</span>
                </td>
            </tr>
        </table>

        <!-- Texte de certification -->
        <div class="certification-text">
            <strong>A accompli un travail remarquable et figure au Tableau d'Honneur</strong><br>
            <em>Has accomplished remarkable work and appears on the Honor Roll</em>
            <br><br>
            En foi de quoi, le présent certificat lui est délivré pour servir et valoir ce que de droit.<br>
            <em>In witness whereof, this certificate is issued to serve for all intents and purposes.</em>
        </div>

        <!-- Signatures -->
        <table class="signatures-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-title">Le Principal</div>
                    <div class="signature-line">Principal</div>
                </td>
                <td class="signature-cell">
                    <div class="signature-title">Principal Adjoint</div>
                    <div class="signature-line">Vice Principal</div>
                </td>
                <td class="signature-cell">
                    <div class="signature-title">Le Président du Conseil d'École</div>
                    <div class="signature-line">School Board President</div>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            Fait à Douala, le {{ $generation_date }} - Made in Douala on {{ $generation_date }}
        </div>
    </div>
</body>
</html>
