<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Liste des Classes</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #7c3aed;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 8px;
        }

        .logo {
            max-width: 80px;
            max-height: 80px;
            object-fit: contain;
        }

        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: #7c3aed;
            margin: 5px 0;
            text-transform: uppercase;
        }

        .school-info {
            font-size: 9px;
            color: #4c1d95;
            margin: 2px 0;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a1a;
            margin: 10px 0 5px 0;
            text-transform: uppercase;
        }

        .generation-date {
            font-size: 8px;
            color: #666;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead {
            background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%);
            color: white;
        }

        th {
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #6d28d9;
        }

        tbody tr:nth-child(even) {
            background-color: #f3e8ff;
        }

        tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        tbody tr:hover {
            background-color: #e9d5ff;
        }

        td {
            padding: 6px 6px;
            border: 1px solid #ddd;
            font-size: 9px;
        }

        .status-active {
            color: #16a34a;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc2626;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 10mm;
            left: 15mm;
            right: 15mm;
            text-align: center;
            font-size: 8px;
            color: #666;
            padding-top: 5px;
            border-top: 1px solid #ddd;
        }

        .section-badge {
            background-color: #7c3aed;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- En-tête avec logo -->
    <div class="header">
        @if($schoolSettings && $schoolSettings->logo)
            <div class="logo-container">
                <img src="{{ public_path('storage/' . $schoolSettings->logo) }}" alt="Logo" class="logo">
            </div>
        @endif

        <div class="school-name">
            {{ $schoolSettings->school_name ?? 'Établissement Scolaire' }}
        </div>

        @if($schoolSettings)
            @if($schoolSettings->address)
                <div class="school-info">{{ $schoolSettings->address }}</div>
            @endif
            @if($schoolSettings->phone || $schoolSettings->email)
                <div class="school-info">
                    @if($schoolSettings->phone)Tél: {{ $schoolSettings->phone }}@endif
                    @if($schoolSettings->phone && $schoolSettings->email) | @endif
                    @if($schoolSettings->email)Email: {{ $schoolSettings->email }}@endif
                </div>
            @endif
        @endif

        <div class="document-title">Liste des Classes et Séries</div>
        <div class="generation-date">
            Généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}
            @if($filters && (isset($filters['section_id']) || isset($filters['level_id'])))
                - Filtré
            @endif
        </div>
    </div>

    <!-- Tableau des données -->
    @if($classes->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 15%;">Classe</th>
                    <th style="width: 12%;">Section</th>
                    <th style="width: 12%;">Niveau</th>
                    <th style="width: 12%;">Série</th>
                    <th style="width: 10%;">Code</th>
                    <th style="width: 8%;" class="text-center">Capacité</th>
                    <th style="width: 8%;" class="text-center">Statut Classe</th>
                    <th style="width: 8%;" class="text-center">Statut Série</th>
                    <th style="width: 10%;">Date Création</th>
                </tr>
            </thead>
            <tbody>
                @foreach($classes as $row)
                    <tr>
                        <td class="text-center">{{ $row->class_id }}</td>
                        <td><strong>{{ $row->class_name }}</strong></td>
                        <td><span class="section-badge">{{ $row->section_name }}</span></td>
                        <td>{{ $row->level_name }}</td>
                        <td>{{ $row->serie_name }}</td>
                        <td>{{ $row->serie_code ?? '-' }}</td>
                        <td class="text-center">{{ $row->serie_capacity ?? '-' }}</td>
                        <td class="text-center">
                            <span class="{{ $row->class_status ? 'status-active' : 'status-inactive' }}">
                                {{ $row->class_status ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if(is_bool($row->serie_status))
                                <span class="{{ $row->serie_status ? 'status-active' : 'status-inactive' }}">
                                    {{ $row->serie_status ? 'Actif' : 'Inactif' }}
                                </span>
                            @else
                                {{ $row->serie_status }}
                            @endif
                        </td>
                        <td>{{ $row->class_created_at ? \Carbon\Carbon::parse($row->class_created_at)->format('d/m/Y') : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            Aucune classe trouvée avec les critères sélectionnés.
        </div>
    @endif

    <!-- Pied de page -->
    <div class="footer">
        <div>{{ $schoolSettings->school_name ?? 'Établissement Scolaire' }} - Liste des Classes</div>
        <div>Document confidentiel - Page {PAGENO}/{nbpg}</div>
    </div>
</body>
</html>
