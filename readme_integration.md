# Guide d'Intégration - Fonctionnalité Scan QR Flutter

## Configuration Base de Données

### Informations de connexion (.env)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=c0admin
DB_USERNAME=root
DB_PASSWORD=
```

## Structure de la Table Principale

### Table `staff_attendances`

Cette table gère les présences du personnel via scan QR code.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire auto-incrémentée |
| `user_id` | bigint | ID de l'utilisateur (FK vers `users`) |
| `supervisor_id` | bigint | ID du surveillant qui enregistre (FK vers `users`) |
| `school_year_id` | bigint | ID de l'année scolaire (FK vers `school_years`) |
| `attendance_date` | date | Date de présence |
| `scanned_at` | timestamp | Moment précis du scan QR |
| `scanned_qr_code` | varchar(100) | Le code QR exact scanné |
| `is_present` | boolean | Présent (true) ou absent (false) |
| `event_type` | enum | Type d'événement: 'entry', 'exit', 'auto' |
| `staff_type` | enum | Type de personnel: 'teacher', 'accountant', 'supervisor', 'admin' |
| `work_hours` | decimal(5,2) | Heures de travail effectuées |
| `late_minutes` | integer | Minutes de retard (défaut: 0) |
| `early_departure_minutes` | integer | Minutes de départ anticipé |
| `notes` | text | Notes ou observations |
| `created_at` | timestamp | Date de création |
| `updated_at` | timestamp | Date de mise à jour |

### Index et Contraintes
- Index composé: `['user_id', 'attendance_date']`
- Index par type: `['staff_type', 'attendance_date']` 
- Contrainte unique: `['user_id', 'attendance_date', 'event_type']`

## Logique de Fonctionnement

### 1. Génération des QR Codes
Chaque membre du personnel a un QR code unique au format:
```
STAFF_{user_id}
```

### 2. Système Entrée/Sortie

#### Détection Automatique (`event_type = 'auto'`)
- **Première présence du jour** → `event_type = 'entry'` + `is_present = true`
- **Après une entrée** → `event_type = 'exit'` + `is_present = false`
- **Après une sortie** → `event_type = 'entry'` + `is_present = true`

#### Types d'Événements
| Type | Description | is_present |
|------|-------------|------------|
| `entry` | Arrivée/Entrée | `true` |
| `exit` | Départ/Sortie | `false` |
| `auto` | Détection automatique | Selon contexte |

### 3. Gestion des Retards
- **Horaires normaux**: 7h00 - 8h30
- **Seuil de retard**: 8h30
- **Calcul**: Minutes après 8h30 = `late_minutes`

### 4. Calcul du Temps de Travail

#### Personnel Permanent (admin, comptable, secrétaire, surveillant)
- **Avec sortie**: Temps réel calculé
- **Sans sortie**: Demi-journée automatique (4h = 240 minutes)
- **Limite**: 17h30 maximum (pas d'heures supplémentaires)

#### Enseignants
- **Avec sortie**: Temps réel calculé
- **Sans sortie**: Statut "En cours"

### 5. Protection Anti-Spam
- **Délai minimum**: 5 secondes entre deux scans
- **Code d'erreur**: 429 (Too Many Requests)

## API Endpoints pour Flutter

### 1. Scanner un QR Code
```http
POST /api/staff-attendance/scan-qr
```

**Body:**
```json
{
  "staff_qr_code": "STAFF_123",
  "supervisor_id": 1,
  "event_type": "auto"
}
```

**Réponse Succès:**
```json
{
  "success": true,
  "message": "Entrée enregistrée avec succès",
  "data": {
    "staff_member": {
      "id": 123,
      "name": "Nom Utilisateur",
      "role": "teacher",
      "staff_type": "teacher"
    },
    "attendance": {
      "id": 456,
      "event_type": "entry",
      "scanned_at": "2025-09-08 08:15:00",
      "is_present": true,
      "late_minutes": 0
    },
    "event_type": "entry",
    "scan_time": "08:15:00",
    "daily_work_time": 0
  }
}
```

### 2. Obtenir les Présences du Jour
```http
GET /api/staff-attendance/daily
```

**Paramètres:**
- `date`: Date (YYYY-MM-DD, optionnel, défaut: aujourd'hui)
- `staff_type`: Type de personnel (optionnel)

### 3. Génerer QR Code pour un Personnel
```http
POST /api/staff-attendance/generate-qr
```

**Body:**
```json
{
  "user_id": 123
}
```

## Intégration Flutter

### 1. Configuration Base de Données
```dart
class DatabaseConfig {
  static const String host = '127.0.0.1';
  static const int port = 3306;
  static const String database = 'c0admin';
  static const String username = 'root';
  static const String password = '';
}
```

### 2. Modèle de Données
```dart
class StaffAttendance {
  final int id;
  final int userId;
  final int supervisorId;
  final int schoolYearId;
  final DateTime attendanceDate;
  final DateTime scannedAt;
  final String scannedQrCode;
  final bool isPresent;
  final String eventType; // 'entry', 'exit', 'auto'
  final String staffType;
  final int lateMinutes;
  final double? workHours;
  
  // Constructeur et méthodes...
}
```

### 3. Service de Scan
```dart
class AttendanceService {
  Future<ApiResponse> scanQRCode(String qrCode, int supervisorId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/staff-attendance/scan-qr'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'staff_qr_code': qrCode,
        'supervisor_id': supervisorId,
        'event_type': 'auto'
      }),
    );
    
    return ApiResponse.fromJson(json.decode(response.body));
  }
}
```

## États et Gestion d'Erreurs

### Codes d'Erreur Courants
| Code | Message | Description |
|------|---------|-------------|
| 404 | QR Code non trouvé | Personnel inexistant ou inactif |
| 403 | Rôle non autorisé | Utilisateur sans droit de présence |
| 429 | Scan trop récent | Protection anti-spam (< 5 secondes) |
| 400 | Année scolaire manquante | Pas d'année scolaire active |

### Gestion des États
```dart
enum ScanState {
  idle,      // En attente
  scanning,  // Scan en cours
  success,   // Scan réussi
  error,     // Erreur
  duplicate  // Scan trop récent
}
```

## Notifications

Le système envoie automatiquement des notifications WhatsApp après chaque scan réussi (via `WhatsAppService`).

## Logs et Debug

Tous les scans (réussis et échoués) sont loggés avec:
- QR code scanné
- Utilisateur trouvé
- Type d'événement détecté
- Informations de validation

Les logs permettent de tracer et déboguer les problèmes de scan.