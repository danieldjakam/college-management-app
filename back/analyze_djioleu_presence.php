<?php

echo "===========================================\n";
echo "ANALYSE DE PRÉSENCE DE FRANCK DJIOLEU (CONNEXION MANUELLE)\n";
echo "===========================================\n\n";

// 1. Lire le fichier .env manuellement
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "❌ Fichier .env introuvable.\n";
    exit(1);
}

$env = parse_ini_file($envFile);

$dbHost = $env['DB_HOST'] ?? '127.0.0.1';
$dbPort = $env['DB_PORT'] ?? '3306';
$dbDatabase = $env['DB_DATABASE'] ?? 'laravel';
$dbUsername = $env['DB_USERNAME'] ?? 'root';
$dbPassword = $env['DB_PASSWORD'] ?? '';

// 2. Connexion à la base de données avec mysqli
$mysqli = new mysqli($dbHost, $dbUsername, $dbPassword, $dbDatabase, $dbPort);

if ($mysqli->connect_error) {
    echo "❌ Erreur de connexion à la base de données: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "✅ Connexion à la base de données réussie.\n\n";

// 3. Retrouver l'utilisateur
$userName = "DJIOLEU FRANCK";
$stmt = $mysqli->prepare("SELECT id, name, role FROM users WHERE name LIKE ?");
$stmt->bind_param("s", $userName);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "❌ Utilisateur '{$userName}' introuvable.\n";
    $mysqli->close();
    exit(1);
}

echo "Utilisateur trouvé: {$user['name']} (ID: {$user['id']}, Rôle: {$user['role']})\n\n";

// 4. Définir la période d'analyse
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-30 days'));

echo "Période d'analyse: du " . date('d-m-Y', strtotime($startDate)) . " au " . date('d-m-Y', strtotime($endDate)) . "\n\n";

// 5. Récupérer les données de présence
$query = "SELECT attendance_date, event_type, scanned_at, late_minutes, early_departure_minutes FROM staff_attendances WHERE user_id = ? AND attendance_date BETWEEN ? AND ? ORDER BY attendance_date DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("iss", $user['id'], $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$attendances = [];
while ($row = $result->fetch_assoc()) {
    $attendances[substr($row['attendance_date'], 0, 10)][] = $row;
}
$stmt->close();

// 6. Afficher le détail des présences
echo "📅 DÉTAIL DES 30 DERNIERS JOURS\n";
echo "----------------------------------------------------------------------\n";
echo sprintf("%-12s | %-8s | %-15s | %-15s | %-s\n", "Date", "Statut", "Heure d'arrivée", "Heure de sortie", "Notes");
echo "----------------------------------------------------------------------\n";

if (empty($attendances)) {
    echo "Aucune donnée de présence trouvée pour cette période.\n";
} else {
    $currentDate = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    while ($currentDate <= $endDateObj) {
        $dateStr = $currentDate->format('Y-m-d');
        $dateFormatted = $currentDate->format('d-m-Y');

        if (isset($attendances[$dateStr])) {
            $dailyAttendances = $attendances[$dateStr];
            $entry = null;
            $exit = null;
            foreach ($dailyAttendances as $att) {
                if ($att['event_type'] == 'entry') $entry = $att;
                if ($att['event_type'] == 'exit') $exit = $att;
            }

            $status = $entry ? 'Présent' : 'Absent';
            $entryTime = $entry ? date('H:i:s', strtotime($entry['scanned_at'])) : '--:--:--';
            $exitTime = $exit ? date('H:i:s', strtotime($exit['scanned_at'])) : '--:--:--';

            $notes = [];
            if ($entry && $entry['late_minutes'] > 0) {
                $notes[] = "Retard ({$entry['late_minutes']} min)";
            }
            if ($exit && $exit['early_departure_minutes'] > 0) {
                $notes[] = "Départ anticipé ({$exit['early_departure_minutes']} min)";
            }
            if (empty($notes)) {
                $notes[] = 'OK';
            }

            echo sprintf("%-12s | %-8s | %-15s | %-15s | %-s\n", $dateFormatted, $status, $entryTime, $exitTime, implode(', ', $notes));
        } else {
            // Jour sans enregistrement, on pourrait l'afficher comme absent si c'est un jour de travail
            // Pour l'instant, on ne l'affiche pas pour ne pas surcharger
        }
        $currentDate->modify('+1 day');
    }
}
echo "----------------------------------------------------------------------\n";

$mysqli->close();

echo "\n✅ Analyse terminée.\n";