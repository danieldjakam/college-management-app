<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Carte Scolaire - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        @page {
            margin: 0;
            size: 85.6mm 54mm;
        }

        @page:first {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            background: white;
            margin: 0;
            padding: 0;
            width: 85.6mm;
            height: 54mm;
            overflow: hidden;
        }

        .card-container {
            width: 100%;
            height: 54mm;
            background: white;
            border: 2px solid #7c3aed;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        .card-header {
            background: #f3e8ff;
            padding: 2px 4px;
            border-bottom: 2px solid #7c3aed;
            text-align: center;
            font-size: 7px;
            font-weight: bold;
        }

        .header-content {
            display: table;
            width: 100%;
        }

        .flag-section {
            display: table-cell;
            width: 30%;
            font-size: 5px;
            vertical-align: middle;
            text-align: left;
            color: #4c1d95;
        }

        .school-section {
            display: table-cell;
            width: 40%;
            text-align: center;
            vertical-align: middle;
        }

        .school-name {
            font-size: 9px;
            font-weight: bold;
            color: #7c3aed;
            margin: 2px 0;
        }

        .card-title {
            font-size: 7px;
            color: #6d28d9;
            font-weight: bold;
        }

        .logo-section {
            display: table-cell;
            width: 30%;
            text-align: right;
            vertical-align: middle;
            padding-right: 4px;
        }

        .logo {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }

        .card-body {
            padding: 4px;
            position: relative;
            min-height: 30mm;
        }

        .photo-container {
            float: left;
            margin-right: 4px;
        }

        .photo {
            width: 35px;
            height: 42px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 2px;
        }

        .info-section {
            font-size: 8px;
            line-height: 1.2;
            padding-right: 45px;
        }

        .year-info {
            font-size: 6px;
            margin-bottom: 2px;
            font-weight: bold;
            color: #7c3aed;
        }

        .info-line {
            margin-bottom: 1px;
            font-size: 8px;
        }

        .info-line strong {
            color: #4c1d95;
        }

        .qr-code {
            position: absolute;
            top: 22px;
            right: 4px;
            width: 38px;
            height: 38px;
            border: 1px solid #7c3aed;
            border-radius: 2px;
            padding: 1px;
            background: white;
        }

        .qr-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .footer {
            position: absolute;
            bottom: 2px;
            left: 0;
            right: 45px;
            text-align: center;
            font-size: 4px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="card-header">
            <div class="header-content">
                <div class="flag-section">
                    RÉPUBLIQUE DU CAMEROUN<br />
                    Paix - Travail - Patrie
                </div>
                <div class="school-section">
                    <div class="school-name">{{ strtoupper($schoolSettings->school_name ?? 'ÉTABLISSEMENT SCOLAIRE') }}</div>
                    <div class="card-title">CARTE D'IDENTITÉ SCOLAIRE</div>
                </div>
                <div class="logo-section">
                    @if($logoBase64)
                        <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo" class="logo" />
                    @else
                        <div style="width: 20px; height: 20px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 2px;"></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="photo-container">
                @if($studentPhotoBase64)
                    <img src="data:image/png;base64,{{ $studentPhotoBase64 }}" alt="Photo" class="photo" />
                @else
                    <div style="width: 35px; height: 42px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 2px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 6px;">PHOTO</div>
                @endif
            </div>

            <div class="info-section">
                <div class="year-info">
                    ANNÉE SCOLAIRE : {{ $schoolYear->year ?? date('Y') . ' - ' . (date('Y') + 1) }}
                </div>

                <div class="info-line">
                    <strong>Nom :</strong> {{ strtoupper($student->last_name) }}
                </div>

                <div class="info-line">
                    <strong>Prénom :</strong> {{ $student->first_name }}
                </div>

                <div class="info-line">
                    <strong>Né(e) le :</strong> {{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') : '' }}
                    <strong>À :</strong> {{ $student->place_of_birth ?? 'DOUALA' }}
                </div>

                <div class="info-line">
                    <strong>Matricule :</strong> {{ $student->matricule ?? substr(date('Y'), -2) . str_pad($student->id, 4, '0', STR_PAD_LEFT) . 'AB' }}
                </div>

                <div class="info-line">
                    <strong>Sexe :</strong> {{ $student->gender === 'F' ? 'F' : 'M' }}
                    <strong style="margin-left: 10px;">Classe :</strong> {{ $student->classSeries->name ?? '' }}
                </div>

                @if($student->parent_name)
                    <div class="info-line">
                        <strong>Parent/Tuteur :</strong> {{ $student->parent_name }}
                    </div>
                @endif

                <div class="info-line">
                    <strong>Contact :</strong> {{ $student->phone ?? $student->parent_phone ?? 'Non renseigné' }}
                </div>
            </div>

            <div class="qr-code">
                <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code" class="qr-image" />
            </div>
        </div>

        <div class="footer">
            Generated by Djephix (www.djephix.com) © {{ date('Y') }} ALL RIGHTS RESERVED
        </div>
    </div>
</body>
</html>
