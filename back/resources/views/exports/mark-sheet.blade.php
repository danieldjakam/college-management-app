<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Sheet - {{ $classSeries->name }} - {{ $subject->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 9px;
            color: #000;
            padding: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #7c3aed;
            padding-bottom: 10px;
            background-color: #f3e8ff;
            padding: 15px;
            color: #4c1d95;
        }

        .header-content {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .header-logo {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
            text-align: left;
        }

        .header-logo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .header-text {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            color: #4c1d95;
        }

        .header h2 {
            font-size: 12px;
            font-weight: normal;
            margin-bottom: 2px;
            color: #6d28d9;
        }

        .header h3 {
            font-size: 10px;
            font-weight: normal;
            color: #7c3aed;
        }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            font-size: 10px;
            background-color: #faf5ff;
            border: 2px solid #a78bfa;
            padding: 5px;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 5px 8px;
            width: 50%;
        }

        .info-label {
            font-weight: bold;
            color: #7c3aed;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            border: 1px solid #a78bfa;
            padding: 4px 2px;
            text-align: center;
            font-size: 8px;
        }

        th {
            background-color: #7c3aed;
            color: white;
            font-weight: bold;
        }

        .student-name {
            text-align: left;
            padding-left: 5px;
            font-size: 8px;
        }

        .header-row-1 th {
            font-weight: bold;
            font-size: 9px;
            padding: 5px 2px;
        }

        .header-row-2 th {
            font-weight: bold;
            font-size: 8px;
            padding: 4px 2px;
        }

        .student-row td {
            height: 20px;
        }

        .stats-section {
            background-color: #dbeafe;
            font-weight: bold;
            border-left: 4px solid #10b981;
        }

        .stats-label {
            text-align: left;
            padding-left: 10px;
            font-weight: bold;
            color: #047857;
        }

        .footer {
            margin-top: 15px;
            font-size: 9px;
        }

        .signature-section {
            display: table;
            width: 100%;
            margin-top: 20px;
            border-top: 3px solid #7c3aed;
            padding-top: 10px;
        }

        .signature-cell {
            display: table-cell;
            width: 50%;
            padding: 5px;
        }

        .col-fixed {
            width: 25px;
        }

        .col-matricule {
            width: 60px;
        }

        .col-name {
            width: 140px;
        }

        .col-eval {
            width: 35px;
        }

        .trimester-header {
            font-size: 9px;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- EN-TÊTE -->
    <div class="header">
        <div class="header-content">
            <div class="header-logo">
                @php
                    $logoPath = public_path('assets/logo.png');
                @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo">
                @endif
            </div>
            <div class="header-text">
                <h1>{{ $schoolSettings->school_name ?? 'COLLÈGE POLYVALENT BILINGUE DE DSCHANG (CPBD)' }}</h1>
                <h2>FICHE DE REPORT DE NOTES / MARK SHEET</h2>
                <h3>Année Scolaire {{ $schoolYear->name }}</h3>
            </div>
        </div>
    </div>

    <!-- INFORMATIONS -->
    <div class="info-section">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Class/Classe :</span> {{ $classSeries->schoolClass->name }} {{ $classSeries->name }}
            </div>
            <div class="info-cell">
                <span class="info-label">Subject/Matière :</span> {{ $subject->name }}
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Coefficient :</span> {{ $coefficient }}
            </div>
            <div class="info-cell">
                <span class="info-label">Teacher's Name/Nom de l'enseignant :</span> {{ $teacher ? ($teacher->full_name ?? $teacher->first_name . ' ' . $teacher->last_name) : '____________________' }}
            </div>
        </div>
    </div>

    <!-- TABLEAU DES NOTES -->
    <table>
        <thead>
            <!-- Ligne 1 : Trimestres -->
            <tr class="header-row-1">
                <th rowspan="2" class="col-fixed">N°</th>
                <th rowspan="2" class="col-matricule">Matricule</th>
                <th rowspan="2" class="col-name">Nom et Prénom</th>
                <th colspan="3" class="trimester-header">TRIMESTRE 1</th>
                <th colspan="3" class="trimester-header">TRIMESTRE 2</th>
                <th colspan="3" class="trimester-header">TRIMESTRE 3</th>
            </tr>
            <!-- Ligne 2 : Types d'évaluations -->
            <tr class="header-row-2">
                <th class="col-eval">EVAL1</th>
                <th class="col-eval">EVAL2</th>
                <th class="col-eval">COMP</th>
                <th class="col-eval">EVAL3</th>
                <th class="col-eval">EVAL4</th>
                <th class="col-eval">COMP</th>
                <th class="col-eval">EVAL5</th>
                <th class="col-eval">EVAL6</th>
                <th class="col-eval">COMP</th>
            </tr>
        </thead>
        <tbody>
            <!-- LIGNES DES ÉLÈVES -->
            @foreach($students as $index => $student)
            <tr class="student-row">
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->student_number }}</td>
                <td class="student-name">{{ $student->full_name }}</td>

                <!-- Trimestre 1 -->
                @php
                    $t1_eval1 = $evaluations ? $evaluations->where('student_id', $student->id)->where('trimester', 1)->where('evaluation_type', 'eval1')->first() : null;
                    $t1_eval2 = $evaluations ? $evaluations->where('student_id', $student->id)->where('trimester', 1)->where('evaluation_type', 'eval2')->first() : null;
                    $t1_comp = $evaluations ? $evaluations->where('student_id', $student->id)->where('trimester', 1)->where('evaluation_type', 'comp')->first() : null;
                @endphp
                <td>{{ $t1_eval1 ? ($t1_eval1->is_absent ? 'ABS' : $t1_eval1->score) : '' }}</td>
                <td>{{ $t1_eval2 ? ($t1_eval2->is_absent ? 'ABS' : $t1_eval2->score) : '' }}</td>
                <td>{{ $t1_comp ? ($t1_comp->is_absent ? 'ABS' : $t1_comp->score) : '' }}</td>

                <!-- Trimestre 2 -->
                @php
                    $t2_eval1 = $evaluations ? $evaluations->where('student_id', $student->id)->where('trimester', 2)->where('evaluation_type', 'eval1')->first() : null;
                    $t2_eval2 = $evaluations ? $evaluations->where('student_id', $student->id)->where('trimester', 2)->where('evaluation_type', 'eval2')->first() : null;
                    $t2_comp = $evaluations ? $evaluations->where('student_id', $student->id)->where('trimester', 2)->where('evaluation_type', 'comp')->first() : null;
                @endphp
                <td>{{ $t2_eval1 ? ($t2_eval1->is_absent ? 'ABS' : $t2_eval1->score) : '' }}</td>
                <td>{{ $t2_eval2 ? ($t2_eval2->is_absent ? 'ABS' : $t2_eval2->score) : '' }}</td>
                <td>{{ $t2_comp ? ($t2_comp->is_absent ? 'ABS' : $t2_comp->score) : '' }}</td>

                <!-- Trimestre 3 -->
                @php
                    $t3_eval1 = $evaluations ? $evaluations->where('student_id', $student->id)->where('trimester', 3)->where('evaluation_type', 'eval1')->first() : null;
                    $t3_eval2 = $evaluations ? $evaluations->where('student_id', $student->id)->where('trimester', 3)->where('evaluation_type', 'eval2')->first() : null;
                    $t3_comp = $evaluations ? $evaluations->where('student_id', $student->id)->where('trimester', 3)->where('evaluation_type', 'comp')->first() : null;
                @endphp
                <td>{{ $t3_eval1 ? ($t3_eval1->is_absent ? 'ABS' : $t3_eval1->score) : '' }}</td>
                <td>{{ $t3_eval2 ? ($t3_eval2->is_absent ? 'ABS' : $t3_eval2->score) : '' }}</td>
                <td>{{ $t3_comp ? ($t3_comp->is_absent ? 'ABS' : $t3_comp->score) : '' }}</td>
            </tr>
            @endforeach

            <!-- SECTION STATISTIQUES -->
            <tr class="stats-section">
                <td colspan="3" class="stats-label">STATISTICS / STATISTIQUES</td>
                <td colspan="9"></td>
            </tr>

            <!-- Ligne : Effectif Présent -->
            <tr class="stats-section">
                <td colspan="3" class="stats-label">Sat/Effectif Présent</td>
                @php
                    $stats = [];
                    for($t = 1; $t <= 3; $t++) {
                        foreach(['eval1', 'eval2', 'comp'] as $type) {
                            $count = $evaluations ? $evaluations->where('trimester', $t)->where('evaluation_type', $type)->where('is_absent', false)->whereNotNull('score')->count() : 0;
                            $stats[$t][$type]['present'] = $count;
                        }
                    }
                @endphp
                <td>{{ $stats[1]['eval1']['present'] ?? '' }}</td>
                <td>{{ $stats[1]['eval2']['present'] ?? '' }}</td>
                <td>{{ $stats[1]['comp']['present'] ?? '' }}</td>
                <td>{{ $stats[2]['eval1']['present'] ?? '' }}</td>
                <td>{{ $stats[2]['eval2']['present'] ?? '' }}</td>
                <td>{{ $stats[2]['comp']['present'] ?? '' }}</td>
                <td>{{ $stats[3]['eval1']['present'] ?? '' }}</td>
                <td>{{ $stats[3]['eval2']['present'] ?? '' }}</td>
                <td>{{ $stats[3]['comp']['present'] ?? '' }}</td>
            </tr>

            <!-- Ligne : Admis -->
            <tr class="stats-section">
                <td colspan="3" class="stats-label">Passed/Admis</td>
                @php
                    for($t = 1; $t <= 3; $t++) {
                        foreach(['eval1', 'eval2', 'comp'] as $type) {
                            $count = $evaluations ? $evaluations->where('trimester', $t)->where('evaluation_type', $type)->where('is_absent', false)->where('score', '>=', 10)->count() : 0;
                            $stats[$t][$type]['passed'] = $count;
                        }
                    }
                @endphp
                <td>{{ $stats[1]['eval1']['passed'] ?? '' }}</td>
                <td>{{ $stats[1]['eval2']['passed'] ?? '' }}</td>
                <td>{{ $stats[1]['comp']['passed'] ?? '' }}</td>
                <td>{{ $stats[2]['eval1']['passed'] ?? '' }}</td>
                <td>{{ $stats[2]['eval2']['passed'] ?? '' }}</td>
                <td>{{ $stats[2]['comp']['passed'] ?? '' }}</td>
                <td>{{ $stats[3]['eval1']['passed'] ?? '' }}</td>
                <td>{{ $stats[3]['eval2']['passed'] ?? '' }}</td>
                <td>{{ $stats[3]['comp']['passed'] ?? '' }}</td>
            </tr>

            <!-- Ligne : Échoué -->
            <tr class="stats-section">
                <td colspan="3" class="stats-label">Failed/Échoué</td>
                @php
                    for($t = 1; $t <= 3; $t++) {
                        foreach(['eval1', 'eval2', 'comp'] as $type) {
                            $count = $evaluations ? $evaluations->where('trimester', $t)->where('evaluation_type', $type)->where('is_absent', false)->where('score', '<', 10)->count() : 0;
                            $stats[$t][$type]['failed'] = $count;
                        }
                    }
                @endphp
                <td>{{ $stats[1]['eval1']['failed'] ?? '' }}</td>
                <td>{{ $stats[1]['eval2']['failed'] ?? '' }}</td>
                <td>{{ $stats[1]['comp']['failed'] ?? '' }}</td>
                <td>{{ $stats[2]['eval1']['failed'] ?? '' }}</td>
                <td>{{ $stats[2]['eval2']['failed'] ?? '' }}</td>
                <td>{{ $stats[2]['comp']['failed'] ?? '' }}</td>
                <td>{{ $stats[3]['eval1']['failed'] ?? '' }}</td>
                <td>{{ $stats[3]['eval2']['failed'] ?? '' }}</td>
                <td>{{ $stats[3]['comp']['failed'] ?? '' }}</td>
            </tr>

            <!-- Ligne : % Réussite -->
            <tr class="stats-section">
                <td colspan="3" class="stats-label">%Passed/Réussite</td>
                @php
                    for($t = 1; $t <= 3; $t++) {
                        foreach(['eval1', 'eval2', 'comp'] as $type) {
                            $present = $stats[$t][$type]['present'] ?? 0;
                            $passed = $stats[$t][$type]['passed'] ?? 0;
                            $pct = $present > 0 ? round(($passed / $present) * 100, 1) : 0;
                            $stats[$t][$type]['pct_passed'] = $pct;
                        }
                    }
                @endphp
                <td>{{ ($stats[1]['eval1']['pct_passed'] ?? 0) > 0 ? $stats[1]['eval1']['pct_passed'].'%' : '' }}</td>
                <td>{{ ($stats[1]['eval2']['pct_passed'] ?? 0) > 0 ? $stats[1]['eval2']['pct_passed'].'%' : '' }}</td>
                <td>{{ ($stats[1]['comp']['pct_passed'] ?? 0) > 0 ? $stats[1]['comp']['pct_passed'].'%' : '' }}</td>
                <td>{{ ($stats[2]['eval1']['pct_passed'] ?? 0) > 0 ? $stats[2]['eval1']['pct_passed'].'%' : '' }}</td>
                <td>{{ ($stats[2]['eval2']['pct_passed'] ?? 0) > 0 ? $stats[2]['eval2']['pct_passed'].'%' : '' }}</td>
                <td>{{ ($stats[2]['comp']['pct_passed'] ?? 0) > 0 ? $stats[2]['comp']['pct_passed'].'%' : '' }}</td>
                <td>{{ ($stats[3]['eval1']['pct_passed'] ?? 0) > 0 ? $stats[3]['eval1']['pct_passed'].'%' : '' }}</td>
                <td>{{ ($stats[3]['eval2']['pct_passed'] ?? 0) > 0 ? $stats[3]['eval2']['pct_passed'].'%' : '' }}</td>
                <td>{{ ($stats[3]['comp']['pct_passed'] ?? 0) > 0 ? $stats[3]['comp']['pct_passed'].'%' : '' }}</td>
            </tr>

            <!-- Ligne : % Échec -->
            <tr class="stats-section">
                <td colspan="3" class="stats-label">%Failed/Échec</td>
                @php
                    for($t = 1; $t <= 3; $t++) {
                        foreach(['eval1', 'eval2', 'comp'] as $type) {
                            $present = $stats[$t][$type]['present'] ?? 0;
                            $failed = $stats[$t][$type]['failed'] ?? 0;
                            $pct = $present > 0 ? round(($failed / $present) * 100, 1) : 0;
                            $stats[$t][$type]['pct_failed'] = $pct;
                        }
                    }
                @endphp
                <td>{{ ($stats[1]['eval1']['pct_failed'] ?? 0) > 0 ? $stats[1]['eval1']['pct_failed'].'%' : '' }}</td>
                <td>{{ ($stats[1]['eval2']['pct_failed'] ?? 0) > 0 ? $stats[1]['eval2']['pct_failed'].'%' : '' }}</td>
                <td>{{ ($stats[1]['comp']['pct_failed'] ?? 0) > 0 ? $stats[1]['comp']['pct_failed'].'%' : '' }}</td>
                <td>{{ ($stats[2]['eval1']['pct_failed'] ?? 0) > 0 ? $stats[2]['eval1']['pct_failed'].'%' : '' }}</td>
                <td>{{ ($stats[2]['eval2']['pct_failed'] ?? 0) > 0 ? $stats[2]['eval2']['pct_failed'].'%' : '' }}</td>
                <td>{{ ($stats[2]['comp']['pct_failed'] ?? 0) > 0 ? $stats[2]['comp']['pct_failed'].'%' : '' }}</td>
                <td>{{ ($stats[3]['eval1']['pct_failed'] ?? 0) > 0 ? $stats[3]['eval1']['pct_failed'].'%' : '' }}</td>
                <td>{{ ($stats[3]['eval2']['pct_failed'] ?? 0) > 0 ? $stats[3]['eval2']['pct_failed'].'%' : '' }}</td>
                <td>{{ ($stats[3]['comp']['pct_failed'] ?? 0) > 0 ? $stats[3]['comp']['pct_failed'].'%' : '' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- PIED DE PAGE -->
    <div class="footer">
        <div class="signature-section">
            <div class="signature-cell">
                <strong>Signature de l'enseignant :</strong> _____________________
            </div>
            <div class="signature-cell" style="text-align: right;">
                <strong>Date :</strong> _______________
            </div>
        </div>
    </div>
</body>
</html>
