<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Identifiants Enseignants</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 15px;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #0066cc;
            margin: 0;
            font-size: 20px;
        }

        .header h2 {
            color: #666;
            margin: 5px 0 0;
            font-size: 13px;
            font-weight: normal;
        }

        .header .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .info-bar {
            background: #f0f4ff;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 10px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #0066cc;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f0f4ff;
        }

        .no-account {
            color: #dc3545;
            font-style: italic;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .confidential {
            text-align: center;
            color: #dc3545;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
        }

        .total {
            margin-top: 10px;
            font-size: 11px;
            color: #555;
        }

        @page {
            margin: 15mm 10mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $school_name ?? 'COLLEGE POLYVALENT BILINGUE DE DOUALA' }}</div>
        <h1>Liste des Identifiants de Connexion</h1>
        <h2>Enseignants - {{ $date_generation }}</h2>
    </div>

    <div class="confidential">DOCUMENT CONFIDENTIEL</div>

    @if($login_url)
    <div class="info-bar">
        <strong>Lien de connexion :</strong> {{ $login_url }}
        @if($default_password)
        &nbsp;&nbsp;|&nbsp;&nbsp;<strong>Mot de passe par défaut :</strong> {{ $default_password }}
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">N°</th>
                <th>Nom complet</th>
                <th>Téléphone</th>
                <th>Nom d'utilisateur</th>
                <th>Mot de passe</th>
                <th style="width: 50px;">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teachers as $index => $teacher)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $teacher['full_name'] }}</strong></td>
                <td>{{ $teacher['phone_number'] ?? '-' }}</td>
                <td>
                    @if($teacher['username'])
                        {{ $teacher['username'] }}
                    @else
                        <span class="no-account">Aucun compte</span>
                    @endif
                </td>
                <td>
                    @if($teacher['password_display'])
                        {{ $teacher['password_display'] }}
                    @else
                        <span style="color: #999;">****</span>
                    @endif
                </td>
                <td>
                    @if($teacher['has_account'])
                        <span class="badge badge-success">OK</span>
                    @else
                        <span class="badge badge-danger">Aucun</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        <strong>Total :</strong> {{ count($teachers) }} enseignant(s)
        &nbsp;|&nbsp; <strong>Avec compte :</strong> {{ collect($teachers)->where('has_account', true)->count() }}
        &nbsp;|&nbsp; <strong>Sans compte :</strong> {{ collect($teachers)->where('has_account', false)->count() }}
    </div>

    <div class="footer">
        Document généré le {{ $date_generation }} - Ce document est strictement confidentiel.
    </div>
</body>
</html>
