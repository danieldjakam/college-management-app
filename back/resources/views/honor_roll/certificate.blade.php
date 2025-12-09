<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau d'Honneur - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            background: white;
            position: relative;
            width: 297mm;
            height: 210mm;
            padding: 15mm;
        }

        /* Bordure décorative avec les couleurs du drapeau camerounais */
        .border-decoration {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 8mm;
        }

        .border-left {
            left: 0;
            background: linear-gradient(to right,
                #009B3A 0%, #009B3A 33%,
                #CE1126 33%, #CE1126 66%,
                #FCD116 66%, #FCD116 100%);
        }

        .border-right {
            right: 0;
            background: linear-gradient(to left,
                #009B3A 0%, #009B3A 33%,
                #CE1126 33%, #CE1126 66%,
                #FCD116 66%, #FCD116 100%);
        }

        .container {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* En-tête bilingue */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15mm;
            padding: 0 10mm;
        }

        .header-section {
            width: 35%;
            text-align: center;
            font-size: 11pt;
            line-height: 1.4;
        }

        .header-section.french {
            text-align: left;
        }

        .header-section.english {
            text-align: right;
        }

        .header-logo {
            width: 20%;
            text-align: center;
        }

        .header-logo img {
            width: 70mm;
            height: auto;
            max-height: 60mm;
        }

        .header-section strong {
            display: block;
            margin-bottom: 2mm;
            font-size: 12pt;
            font-weight: bold;
        }

        .header-section .small {
            font-size: 10pt;
            font-style: italic;
        }

        /* Titre principal */
        .main-title {
            text-align: center;
            margin-bottom: 10mm;
        }

        .main-title h1 {
            font-size: 28pt;
            font-weight: bold;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 3mm;
            border-bottom: 3px solid #CE1126;
            display: inline-block;
            padding-bottom: 2mm;
        }

        .main-title .subtitle {
            font-size: 14pt;
            color: #555;
            font-style: italic;
            margin-top: 3mm;
        }

        /* Corps du certificat */
        .certificate-body {
            flex: 1;
            padding: 0 15mm;
            font-size: 13pt;
            line-height: 2;
            text-align: justify;
        }

        .certificate-body p {
            margin-bottom: 5mm;
        }

        .certificate-body .highlight {
            font-weight: bold;
            font-size: 14pt;
            color: #1a1a1a;
            text-transform: uppercase;
        }

        .student-info {
            background: linear-gradient(to right, rgba(0, 155, 58, 0.05), rgba(252, 209, 22, 0.05));
            padding: 8mm;
            border-left: 4px solid #009B3A;
            border-right: 4px solid #FCD116;
            margin: 8mm 0;
            border-radius: 5px;
        }

        .student-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-info td {
            padding: 3mm 5mm;
            font-size: 13pt;
        }

        .student-info td.label {
            font-weight: bold;
            width: 40%;
            color: #333;
        }

        .student-info td.value {
            color: #1a1a1a;
            font-weight: bold;
        }

        .mention-badge {
            display: inline-block;
            padding: 3mm 8mm;
            background: linear-gradient(135deg, #009B3A, #00C853);
            color: white;
            font-weight: bold;
            font-size: 15pt;
            border-radius: 25px;
            text-transform: uppercase;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 15mm;
            padding: 0 10mm;
        }

        .signature-block {
            text-align: center;
            width: 30%;
        }

        .signature-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 15mm;
            text-transform: uppercase;
        }

        .signature-line {
            border-top: 2px solid #333;
            margin-top: 10mm;
            padding-top: 2mm;
            font-size: 10pt;
            color: #666;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 8mm;
            font-size: 9pt;
            color: #666;
            font-style: italic;
        }

        /* Médailles décoratives */
        .decoration {
            position: absolute;
            opacity: 0.1;
            z-index: 0;
        }

        .decoration.top-left {
            top: 20mm;
            left: 15mm;
            font-size: 80pt;
            color: #009B3A;
        }

        .decoration.bottom-right {
            bottom: 20mm;
            right: 15mm;
            font-size: 80pt;
            color: #FCD116;
        }
    </style>
</head>
<body>
    <!-- Bordures latérales avec couleurs du drapeau -->
    <div class="border-decoration border-left"></div>
    <div class="border-decoration border-right"></div>

    <!-- Décorations (étoiles) -->
    <div class="decoration top-left">★</div>
    <div class="decoration bottom-right">★</div>

    <div class="container">
        <!-- En-tête bilingue -->
        <div class="header">
            <!-- Partie française (gauche) -->
            <div class="header-section french">
                <strong>RÉPUBLIQUE DU CAMEROUN</strong>
                <div>Paix - Travail - Patrie</div>
                <div class="small">***************</div>
                <div>MINISTÈRE DES ENSEIGNEMENTS</div>
                <div>SECONDAIRES</div>
                <div class="small">***************</div>
                <div>DÉLÉGATION RÉGIONALE</div>
                <div>DU LITTORAL</div>
            </div>

            <!-- Logo central -->
            <div class="header-logo">
                @if(file_exists(public_path('assets/logo.png')))
                    <img src="{{ public_path('assets/logo.png') }}" alt="Logo Collège">
                @endif
            </div>

            <!-- Partie anglaise (droite) -->
            <div class="header-section english">
                <strong>REPUBLIC OF CAMEROON</strong>
                <div>Peace - Work - Fatherland</div>
                <div class="small">***************</div>
                <div>MINISTRY OF SECONDARY</div>
                <div>EDUCATION</div>
                <div class="small">***************</div>
                <div>LITTORAL REGIONAL</div>
                <div>DELEGATION</div>
            </div>
        </div>

        <!-- Titre principal -->
        <div class="main-title">
            <h1>Tableau d'Honneur</h1>
            <div class="subtitle">Honor Roll Certificate</div>
        </div>

        <!-- Corps du certificat -->
        <div class="certificate-body">
            <p style="text-align: center; font-size: 14pt;">
                Le Chef d'Établissement du <strong>COLLÈGE POLYVALENT BILINGUE DE DOUALA</strong>
            </p>

            <p style="text-align: center; margin-bottom: 10mm;">
                Certifie que
            </p>

            <!-- Informations de l'élève -->
            <div class="student-info">
                <table>
                    <tr>
                        <td class="label">Nom et Prénom(s) / Full Name:</td>
                        <td class="value">{{ strtoupper($student->last_name) }} {{ $student->first_name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Né(e) le / Born on:</td>
                        <td class="value">{{ \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Classe / Class:</td>
                        <td class="value">{{ $class_name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Période / Period:</td>
                        <td class="value">{{ $trimester->trimester_number }}{{ $trimester->trimester_number == 1 ? 'er' : 'ème' }} Trimestre {{ $academic_year }}</td>
                    </tr>
                    <tr>
                        <td class="label">Moyenne Générale / General Average:</td>
                        <td class="value">{{ number_format($average, 2) }}/20</td>
                    </tr>
                    <tr>
                        <td class="label">Rang / Rank:</td>
                        <td class="value">{{ $rank }}{{ $rank == 1 ? 'er' : 'ème' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Mention / Grade:</td>
                        <td class="value">
                            <span class="mention-badge">{{ strtoupper($mention) }}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <p style="text-align: center; font-size: 14pt; margin-top: 10mm;">
                A accompli un travail remarquable et figure au <strong>Tableau d'Honneur</strong>
                <br>
                <em style="font-size: 12pt; color: #555;">
                    Has accomplished remarkable work and appears on the Honor Roll
                </em>
            </p>

            <p style="text-align: center; margin-top: 8mm; font-size: 13pt; color: #555;">
                En foi de quoi, le présent certificat lui est délivré pour servir et valoir ce que de droit.
                <br>
                <em style="font-size: 11pt;">
                    In witness whereof, this certificate is issued to serve for all intents and purposes.
                </em>
            </p>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-block">
                <div class="signature-title">Le Principal</div>
                <div class="signature-line">Principal</div>
            </div>

            <div class="signature-block">
                <div class="signature-title">Principal Adjoint</div>
                <div class="signature-line">Vice Principal</div>
            </div>

            <div class="signature-block">
                <div class="signature-title">Le Président du Conseil d'École</div>
                <div class="signature-line">School Board President</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Fait à Douala, le {{ $generation_date }} - Made in Douala on {{ $generation_date }}
        </div>
    </div>
</body>
</html>
