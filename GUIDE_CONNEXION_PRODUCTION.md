# 🔗 GUIDE : Connexion Locale à la Base de Données de Production

## 📋 Objectif

Connecter votre application locale directement à la base de données de production via un tunnel SSH sécurisé, sans avoir à exporter/importer les données.

---

## ⚡ Méthode : Tunnel SSH

### Étape 1 : Créer le tunnel SSH

**Dans un terminal dédié** (gardez-le ouvert tant que vous travaillez) :

```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app

# Lancer le tunnel
./connect_to_production_db.sh
```

**Ou manuellement :**

```bash
ssh -L 3307:127.0.0.1:3306 adminChrisDev@31.207.34.69 -N
```

**Explication :**
- `-L 3307:127.0.0.1:3306` : Redirige le port local 3307 vers le port 3306 du serveur distant
- `-N` : Ne pas exécuter de commande (juste le tunnel)
- Le tunnel reste ouvert tant que vous ne fermez pas ce terminal

**Vous devriez voir :**
```
🔐 Création du tunnel SSH vers la base de données de production...

⚠️  ATTENTION: Ce tunnel permet d'accéder directement à la base de production!
⚠️  Soyez prudent avec les modifications.

Une fois le tunnel établi:
  - Hôte: 127.0.0.1
  - Port: 3307 (local)
  - Base: c0admin
  - User: c0admin_cpb
  - Pass: Estuaire@2025

Pour arrêter le tunnel: Ctrl+C

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[Le curseur attend ici - c'est normal, le tunnel est actif]
```

⚠️ **IMPORTANT** : Laissez ce terminal ouvert ! Le tunnel fonctionne tant qu'il reste ouvert.

---

### Étape 2 : Configurer Laravel pour utiliser la production

**Dans un NOUVEAU terminal :**

```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/back

# Sauvegarder votre .env local actuel
cp .env .env.local.backup

# Utiliser la config production
cp .env.production .env
```

**Ou éditez manuellement `back/.env` :**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307          # ← Port du tunnel local
DB_DATABASE=c0admin
DB_USERNAME=c0admin_cpb
DB_PASSWORD=Estuaire@2025
```

---

### Étape 3 : Tester la connexion

```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/back

# Test via Artisan
php artisan db:show

# Ou via Tinker
php artisan tinker
```

Dans Tinker, testez :

```php
// Compter les étudiants
DB::table('students')->count();

// Compter les notes
DB::table('grades')->count();

// Vérifier l'année scolaire courante
DB::table('school_years')->where('is_current', 1)->first();

// Sortir
exit
```

**Si tout fonctionne, vous verrez les vraies données de production !**

---

### Étape 4 : Lancer l'application locale

**Backend :**

```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/back
php artisan serve
```

**Frontend (nouveau terminal) :**

```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/front
npm start
```

Accédez à : **http://localhost:3006**

Vous verrez maintenant **les vraies données de production** dans votre application locale !

---

## 🐛 Diagnostiquer le Problème

Maintenant que vous êtes connecté à la production, vous pouvez :

### A. Via l'interface web (http://localhost:3006)

- Connectez-vous avec vos identifiants admin
- Naviguez vers l'étudiant/classe concerné(e)
- Reproduisez le problème de calcul
- Vérifiez les données affichées

### B. Via Tinker (ligne de commande)

```bash
cd back
php artisan tinker
```

**Exemple : Analyser un bulletin avec erreur de calcul**

```php
use App\Services\BulletinService;
$service = new BulletinService();

// Remplacez par les vrais IDs
$studentId = 123;
$trimesterId = 1;
$subjectId = 5; // Ex: Mathématiques

// Calculer le DS
$ds = $service->calculateDSAverage($trimesterId, $studentId, $subjectId);
echo "DS Average: $ds\n";

// Voir les notes individuelles (Deuxième Cycle)
$seqGrades = $service->getIndividualSequenceGrades($trimesterId, $studentId, $subjectId);
print_r($seqGrades);

// Voir la note de composition
$comp = $service->getCompositionGrade($trimesterId, $studentId, $subjectId);
echo "Composition: $comp\n";

// Calculer la moyenne du trimestre
$avg = $service->calculateTrimesterGrade($trimesterId, $studentId, $subjectId, 'premier');
echo "Moyenne Trimestre: $avg\n";
```

**Exemple : Analyser un problème de paiement**

```php
$studentId = 123; // Remplacez

// Voir tous les paiements
$payments = DB::table('payments')
    ->where('student_id', $studentId)
    ->get();
print_r($payments);

// Voir les détails de paiement
$details = DB::table('payment_details')
    ->join('payments', 'payments.id', '=', 'payment_details.payment_id')
    ->where('payments.student_id', $studentId)
    ->select('payment_details.*', 'payments.payment_date')
    ->get();
print_r($details);

// Voir les réductions manuelles
$discounts = DB::table('student_manual_discounts')
    ->where('student_id', $studentId)
    ->get();
print_r($discounts);

// Calculer le statut de paiement
use App\Services\PaymentStatusService;
$statusService = new PaymentStatusService();
$status = $statusService->calculateStudentPaymentStatus($studentId, 1); // 1 = school_year_id
print_r($status);
```

### C. Requêtes SQL directes

```bash
# Se connecter à MySQL via le tunnel
mysql -h 127.0.0.1 -P 3307 -u c0admin_cpb -p'Estuaire@2025' c0admin
```

Puis exécutez vos requêtes SQL :

```sql
-- Voir les notes d'un étudiant
SELECT
    s.name AS subject,
    g.score,
    g.max_score,
    g.coefficient,
    seq.name AS sequence
FROM grades g
JOIN class_series_subjects css ON g.class_series_subject_id = css.id
JOIN subjects s ON css.subject_id = s.id
JOIN sequences seq ON g.sequence_id = seq.id
WHERE g.student_id = 123
ORDER BY seq.id, s.name;

-- Quitter
exit;
```

---

## ⚠️ IMPORTANT : Précautions de Sécurité

### ✅ CE QU'IL FAUT FAIRE :

- ✅ Analyser et diagnostiquer les problèmes
- ✅ Lire les données
- ✅ Tester les calculs
- ✅ Reproduire les bugs

### ❌ CE QU'IL NE FAUT PAS FAIRE :

- ❌ **NE PAS** modifier les données directement (UPDATE, DELETE, INSERT)
- ❌ **NE PAS** supprimer des tables ou enregistrements
- ❌ **NE PAS** lancer des migrations
- ❌ **NE PAS** exécuter `php artisan migrate` ou `migrate:fresh`

### 🛡️ Si vous devez corriger des données :

1. **Testez d'abord en local** avec la base locale
2. **Créez un backup avant** toute modification
3. **Documentez** toutes les modifications
4. **Créez un script de correction** que vous pourrez rejouer

---

## 🔄 Revenir à la Configuration Locale

Quand vous avez terminé :

### 1. Arrêter le tunnel SSH

Dans le terminal où tourne le tunnel, appuyez sur **Ctrl+C**

### 2. Restaurer votre .env local

```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/back

# Restaurer la config locale
cp .env.local.backup .env

# Ou remettre manuellement :
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_USERNAME=root
# DB_PASSWORD=
```

### 3. Vérifier que vous êtes bien sur la base locale

```bash
php artisan tinker
```

```php
// Devrait retourner 0 ou un petit nombre si base locale vide
DB::table('students')->count();
exit
```

---

## 🆘 Dépannage

### Problème : "Connection refused" sur le port 3307

**Solution :**
- Vérifiez que le tunnel SSH est bien actif
- Relancez `./connect_to_production_db.sh`

### Problème : "Access denied for user"

**Solution :**
- Vérifiez les credentials dans `.env`
- User : `c0admin_cpb`
- Pass : `Estuaire@2025`
- Port : `3307` (pas 3306)

### Problème : Le tunnel se ferme tout seul

**Solution :**
- Ajoutez les options `-o ServerAliveInterval=60 -o ServerAliveCountMax=3`

```bash
ssh -L 3307:127.0.0.1:3306 \
    -o ServerAliveInterval=60 \
    -o ServerAliveCountMax=3 \
    adminChrisDev@31.207.34.69 -N
```

### Problème : Port 3307 déjà utilisé

**Solution :**
- Changez le port local (exemple : 3308)

```bash
ssh -L 3308:127.0.0.1:3306 adminChrisDev@31.207.34.69 -N
```

Et dans `.env` :
```
DB_PORT=3308
```

---

## 📊 Workflow Complet

```
┌─────────────────────────────────────────────────────────────┐
│  TERMINAL 1 : Tunnel SSH                                    │
│  $ ./connect_to_production_db.sh                            │
│  [Reste ouvert en permanence]                               │
└─────────────────────────────────────────────────────────────┘
                          │
                          │ Tunnel actif
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  TERMINAL 2 : Backend Laravel                               │
│  $ cd back                                                  │
│  $ cp .env.production .env                                  │
│  $ php artisan serve                                        │
└─────────────────────────────────────────────────────────────┘
                          │
                          │ API disponible
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  TERMINAL 3 : Frontend React                                │
│  $ cd front                                                 │
│  $ npm start                                                │
└─────────────────────────────────────────────────────────────┘
                          │
                          │ Interface web
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  NAVIGATEUR : http://localhost:3006                         │
│  [Voir les vraies données de production]                   │
└─────────────────────────────────────────────────────────────┘
```

---

## ✅ Avantages de cette Méthode

- ✅ **Pas d'export/import** : Gain de temps considérable
- ✅ **Données en temps réel** : Vous voyez exactement ce qui est en production
- ✅ **Sécurisé** : Connexion chiffrée via SSH
- ✅ **Réversible** : Retour facile à la base locale
- ✅ **Pratique** : Idéal pour le debugging et l'analyse

---

**Prêt à diagnostiquer le problème !** 🔍
