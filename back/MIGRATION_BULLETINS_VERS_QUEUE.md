# 🔄 MIGRATION: Génération Bulletins → Queue

## 📋 Guide Complet de Migration

Ce guide explique comment migrer la génération de bulletins de **synchrone** (bloque la requête HTTP) à **asynchrone** (via queue).

---

## ✅ Fichiers Créés

Les fichiers suivants ont été créés et sont prêts à utiliser:

1. ✅ `app/Jobs/GenerateBulletinsBatchJob.php` - Job pour génération en lot
2. ✅ `app/Notifications/BulletinBatchCompleted.php` - Notification de fin
3. ✅ `OPERATIONS_A_METTRE_EN_QUEUE.md` - Documentation complète

---

## 🔧 ÉTAPE 1: Modifier BulletinController

### Méthode `batchGenerate()` - AVANT (Synchrone)

```php
// Ligne 284-394 actuelle
public function batchGenerate(Request $request)
{
    $startTime = microtime(true);

    // ... récupération étudiants ...

    foreach ($studentBatch as $student) {
        // Génération bulletin (1-2s par étudiant)
        $bulletinData = $this->bulletinService->generateSequenceBulletinData(...);
        $htmlContent = $this->bulletinService->renderBulletinTemplate(...);
        $filePath = $this->bulletinService->generatePDF($htmlContent, $filename);
        // ... insert ...
    }

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2); // 30-60s pour 30 étudiants !

    return response()->json([
        'message' => 'Batch generation completed',
        'duration_seconds' => $duration
    ]);
}
```

**Problème**: L'utilisateur attend 30-60 secondes, risque de timeout 504.

---

### Méthode `batchGenerate()` - APRÈS (Asynchrone)

```php
use App\Jobs\GenerateBulletinsBatchJob;

public function batchGenerate(Request $request)
{
    try {
        // Validation rapide
        $request->validate([
            'class_id' => 'required|exists:class_series,id',
            'bulletin_type' => 'required|in:sequence,trimester',
            'period_identifier' => 'required|string',
            'force' => 'boolean'
        ]);

        // Vérification rapide du nombre d'étudiants
        $studentCount = Student::where('class_series_id', $request->class_id)
            ->where('is_active', true)
            ->count();

        if ($studentCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun étudiant actif trouvé dans cette classe'
            ], 404);
        }

        // Dispatch du job (retour immédiat)
        GenerateBulletinsBatchJob::dispatch(
            $request->class_id,
            $request->bulletin_type,
            $request->period_identifier,
            auth()->id(),
            $request->force ?? false
        );

        return response()->json([
            'success' => true,
            'message' => "Génération de {$studentCount} bulletins en cours. Vous serez notifié quand ce sera terminé.",
            'status' => 'processing',
            'student_count' => $studentCount,
            'estimated_time_minutes' => ceil($studentCount * 1.5 / 60) // ~1.5s par bulletin
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du lancement de la génération',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

**Avantages**:
- ✅ Retour immédiat (< 200ms)
- ✅ Aucun timeout
- ✅ Peut générer 100+ bulletins
- ✅ Notification automatique à la fin

---

## 🔧 ÉTAPE 2: Ajouter Endpoint de Statut (Optionnel)

Pour permettre au frontend de vérifier la progression:

```php
/**
 * Vérifier le statut d'une génération en cours
 */
public function getBatchStatus(Request $request)
{
    $request->validate([
        'class_id' => 'required|exists:class_series,id',
        'bulletin_type' => 'required|in:sequence,trimester',
        'period_identifier' => 'required|string'
    ]);

    $totalStudents = Student::where('class_series_id', $request->class_id)
        ->where('is_active', true)
        ->count();

    $generatedCount = BulletinGeneration::whereIn('student_id', function($query) use ($request) {
            $query->select('id')
                ->from('students')
                ->where('class_series_id', $request->class_id)
                ->where('is_active', true);
        })
        ->where('period_type', $request->bulletin_type)
        ->where('period_identifier', $request->period_identifier)
        ->count();

    $percentage = $totalStudents > 0 ? round(($generatedCount / $totalStudents) * 100, 1) : 0;

    return response()->json([
        'status' => $generatedCount === $totalStudents ? 'completed' : 'processing',
        'progress' => [
            'total' => $totalStudents,
            'generated' => $generatedCount,
            'remaining' => $totalStudents - $generatedCount,
            'percentage' => $percentage
        ]
    ]);
}
```

**Route à ajouter** dans `routes/api.php`:
```php
Route::get('/bulletins/batch-status', [BulletinController::class, 'getBatchStatus'])->middleware('auth:api');
```

---

## 🔧 ÉTAPE 3: Configurer le Queue Worker

### Sur le Serveur de Production

```bash
# 1. Vérifier que la table jobs existe
php artisan queue:table
php artisan migrate

# 2. Installer Supervisor (si pas déjà fait)
sudo apt install supervisor

# 3. Créer config Supervisor
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

**Contenu du fichier**:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/clients/client0/web46/web/college-management-app/back/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=web46
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/clients/client0/web46/web/college-management-app/back/storage/logs/worker.log
stopwaitsecs=3600
```

**Lancer le worker**:
```bash
# Recharger config
sudo supervisorctl reread
sudo supervisorctl update

# Démarrer
sudo supervisorctl start laravel-worker:*

# Vérifier statut
sudo supervisorctl status laravel-worker:*
```

**Résultat attendu**:
```
laravel-worker:laravel-worker_00   RUNNING   pid 12345, uptime 0:00:05
laravel-worker:laravel-worker_01   RUNNING   pid 12346, uptime 0:00:05
```

---

## 🔧 ÉTAPE 4: Tester en Local

### Test du Job

```bash
cd back

# Test unitaire du job
php artisan tinker --execute='
$classId = 106; // Remplacer par une vraie classe
$userId = 1; // Remplacer par votre user ID

App\Jobs\GenerateBulletinsBatchJob::dispatch(
    $classId,
    "sequence",
    "seq3",
    $userId,
    false
);

echo "✅ Job dispatché! Vérifiez la table jobs.\n";
'

# Vérifier que le job est dans la queue
php artisan tinker --execute='
$count = DB::table("jobs")->count();
echo "Jobs en attente: {$count}\n";
'

# Lancer le worker manuellement pour tester
php artisan queue:work --once

# Vérifier les logs
tail -50 storage/logs/laravel.log
```

### Résultat Attendu

Dans les logs, vous devriez voir:
```
[2025-12-03 22:00:00] local.INFO: 🚀 Début génération bulletins en lot {"class_id":106,...}
[2025-12-03 22:00:01] local.INFO: 📊 12 étudiants à traiter
[2025-12-03 22:00:01] local.INFO: 📦 Traitement lot 1 sur 2
[2025-12-03 22:00:15] local.INFO: ✅ Progression: 5/12 bulletins générés
[2025-12-03 22:00:25] local.INFO: ✅ Progression: 10/12 bulletins générés
[2025-12-03 22:00:30] local.INFO: 🎉 Génération terminée {"duration":"28.5s","generated":12,"errors":0}
```

---

## 🎨 ÉTAPE 5: Modifier le Frontend

### Avant (Appel Synchrone)

```javascript
// GradeEntry.jsx ou autre composant
const generateBulletins = async () => {
    setLoading(true);
    try {
        const response = await api.post('/api/bulletins/batch-generate', {
            class_id: classId,
            bulletin_type: 'sequence',
            period_identifier: 'seq3'
        });

        // L'utilisateur attend 30-60s ici !

        toast.success(`${response.data.generated_count} bulletins générés`);
    } catch (error) {
        toast.error('Erreur: ' + error.message);
    } finally {
        setLoading(false);
    }
};
```

---

### Après (Appel Asynchrone avec Polling)

```javascript
const generateBulletins = async () => {
    setLoading(true);
    try {
        // 1. Lancer la génération (retour immédiat)
        const response = await api.post('/api/bulletins/batch-generate', {
            class_id: classId,
            bulletin_type: 'sequence',
            period_identifier: 'seq3'
        });

        toast.info(response.data.message); // "Génération en cours..."
        setLoading(false);

        // 2. Polling pour vérifier la progression
        startProgressPolling();

    } catch (error) {
        toast.error('Erreur: ' + error.message);
        setLoading(false);
    }
};

const startProgressPolling = () => {
    const intervalId = setInterval(async () => {
        try {
            const response = await api.get('/api/bulletins/batch-status', {
                params: {
                    class_id: classId,
                    bulletin_type: 'sequence',
                    period_identifier: 'seq3'
                }
            });

            // Mettre à jour la barre de progression
            setProgress(response.data.progress.percentage);

            if (response.data.status === 'completed') {
                clearInterval(intervalId);
                toast.success(`✅ ${response.data.progress.generated} bulletins générés!`);
                refreshBulletinsList();
            }
        } catch (error) {
            clearInterval(intervalId);
            console.error('Erreur polling:', error);
        }
    }, 3000); // Vérifier toutes les 3 secondes

    // Nettoyer l'intervalle après 10 minutes max
    setTimeout(() => clearInterval(intervalId), 600000);
};
```

---

### Composant Progress Bar (Optionnel)

```jsx
import { Progress } from 'reactstrap';

const BulletinGenerationProgress = ({ progress }) => {
    if (progress === 0 || progress === 100) return null;

    return (
        <div className="alert alert-info">
            <h6>📊 Génération en cours...</h6>
            <Progress value={progress} color="primary">
                {progress}%
            </Progress>
            <small className="text-muted">
                Temps estimé restant: {Math.ceil((100 - progress) * 1.5 / 60)} minutes
            </small>
        </div>
    );
};
```

---

## ✅ CHECKLIST DE DÉPLOIEMENT

### Local (Test)

- [ ] Créer la migration pour la table `jobs` si pas déjà fait
- [ ] Tester le job manuellement avec `queue:work --once`
- [ ] Vérifier les logs (pas d'erreurs)
- [ ] Tester avec 5-10 bulletins
- [ ] Vérifier que les PDFs sont bien générés

### Serveur (Production)

- [ ] Pull du code (job + notification)
- [ ] Vérifier `.env`: `QUEUE_CONNECTION=database`
- [ ] Lancer migration si nécessaire: `php artisan migrate`
- [ ] Configurer Supervisor (voir ÉTAPE 3)
- [ ] Démarrer le worker: `sudo supervisorctl start laravel-worker:*`
- [ ] Vérifier statut: `sudo supervisorctl status`
- [ ] Tester avec une petite classe (5 étudiants)
- [ ] Vérifier les logs worker: `tail -f storage/logs/worker.log`
- [ ] Tester avec une classe moyenne (20 étudiants)
- [ ] Surveiller 24-48h

### Frontend

- [ ] Modifier l'appel API (synchrone → asynchrone)
- [ ] Ajouter polling de progression
- [ ] Ajouter barre de progression
- [ ] Tester UX complète
- [ ] Gérer les notifications (toast/alert)

---

## 🔍 MONITORING

### Commandes Utiles

```bash
# Voir les jobs en attente
php artisan queue:monitor

# Voir les jobs en cours d'exécution
ps aux | grep "queue:work"

# Voir les jobs échoués
php artisan queue:failed

# Réessayer un job échoué
php artisan queue:retry {id}

# Réessayer tous les jobs échoués
php artisan queue:retry all

# Logs worker en temps réel
tail -f storage/logs/worker.log

# Logs Laravel en temps réel
tail -f storage/logs/laravel.log | grep "Génération"
```

### Dashboard (Optionnel: Laravel Horizon)

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate

# Accès: http://votre-domaine.com/horizon
```

---

## 🚨 DÉPANNAGE

### Problème: Jobs ne s'exécutent pas

**Cause**: Worker pas démarré

**Solution**:
```bash
sudo supervisorctl status laravel-worker:*
# Si STOPPED:
sudo supervisorctl start laravel-worker:*
```

---

### Problème: Jobs échouent systématiquement

**Diagnostic**:
```bash
php artisan queue:failed
# Voir les détails d'un job échoué
php artisan queue:failed {id}
```

**Solution**: Vérifier les logs, corriger le problème, puis:
```bash
php artisan queue:retry {id}
```

---

### Problème: Worker consomme trop de mémoire

**Solution**: Redémarrer automatiquement après N jobs
```bash
# Dans supervisor config, modifier:
command=php artisan queue:work database --sleep=3 --tries=3 --max-jobs=100
```

---

### Problème: Utilisateur ne reçoit pas de notification

**Vérification**:
```bash
php artisan tinker --execute='
$count = DB::table("notifications")->where("notifiable_id", 1)->count();
echo "Notifications user 1: {$count}\n";
'
```

**Solution**: Vérifier que la table `notifications` existe et que le frontend appelle l'endpoint `/api/user/notifications`.

---

## 📊 IMPACT ATTENDU

### Performance

| Opération | Avant | Après |
|-----------|-------|-------|
| **Retour API** | 30-60s (bloquant) | < 200ms (immédiat) |
| **Max bulletins** | 30 (timeout) | 100+ (sans limite) |
| **UX utilisateur** | ❌ Attente forcée | ✅ Continue à travailler |

### Scalabilité

- ✅ Peut générer 1000+ bulletins en parallèle (selon nombre de workers)
- ✅ Aucun timeout 504
- ✅ Charge serveur mieux répartie
- ✅ Utilisateurs peuvent faire autre chose pendant la génération

---

**Temps d'implémentation**: 4-6 heures
**Complexité**: Moyenne
**Impact**: 🚀 **Critique - Élimine tous les timeouts sur les bulletins**

🎯 **Recommandation**: Déployer d'abord en staging, tester 2-3 jours, puis production.
