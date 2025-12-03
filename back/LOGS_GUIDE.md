# 📋 GUIDE DE GESTION DES LOGS LARAVEL

## 🔴 Problème Actuel

**Fichier de log trop volumineux**: `laravel.log` = 994M (presque 1 GB)

**Conséquences**:
- ❌ Impossible à ouvrir dans un éditeur
- ❌ Ralentit les performances
- ❌ Consomme de l'espace disque inutilement

---

## ⚡ SOLUTION IMMÉDIATE (2 minutes)

### Étape 1: Nettoyer le Log Actuel

```bash
cd /Users/redwolf-dark/Documents/Projet/college-management-app/back

# Nettoyer et archiver
bash clean_logs.sh
```

**Ce script va**:
- ✅ Archiver `laravel.log` → `archives/laravel_20251203_HHMMSS.log.gz`
- ✅ Créer un nouveau fichier vide
- ✅ Supprimer les archives de +30 jours

### Étape 2: Activer la Rotation Automatique

Modifier le fichier `.env`:

```bash
# Ouvrir .env
nano .env

# Ajouter/Modifier ces lignes:
LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_DAILY_DAYS=7
```

**Explication**:
- `LOG_CHANNEL=daily` → Crée un nouveau fichier chaque jour
- `LOG_LEVEL=info` → Logue INFO, WARNING, ERROR (pas DEBUG)
- `LOG_DAILY_DAYS=7` → Garde les logs des 7 derniers jours

### Étape 3: Vider le Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🔧 UTILISATION DES SCRIPTS

### Script 1: Viewer de Logs (view_logs.sh)

```bash
# Voir les 100 dernières lignes
bash view_logs.sh last

# Voir seulement les erreurs
bash view_logs.sh errors

# Voir les logs d'aujourd'hui
bash view_logs.sh today

# Rechercher un mot-clé
bash view_logs.sh search "bulletin"

# Suivre les logs en temps réel
bash view_logs.sh live

# Vérifier la taille du fichier
bash view_logs.sh size

# Nettoyer les logs
bash view_logs.sh clean
```

### Script 2: Nettoyage Manuel (clean_logs.sh)

```bash
# Nettoyer et archiver les logs
bash clean_logs.sh
```

---

## 📊 CONFIGURATION AVANCÉE

### Option 1: Rotation Quotidienne (RECOMMANDÉ)

**Avantages**:
- ✅ Fichiers plus petits (1 par jour)
- ✅ Rotation automatique
- ✅ Facile à chercher par date

**Configuration** (déjà dans `config/logging.php`):

```php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'info'),  // Changer 'debug' → 'info'
        'days' => env('LOG_DAILY_DAYS', 7),   // Garder 7 jours
        'replace_placeholders' => true,
    ],
]
```

**Fichiers créés**:
```
storage/logs/laravel-2025-12-01.log
storage/logs/laravel-2025-12-02.log
storage/logs/laravel-2025-12-03.log (aujourd'hui)
...
```

### Option 2: Niveau de Log Adapté

**Niveaux disponibles** (du moins au plus critique):
1. `debug` - Toutes les informations (TRÈS VERBEUX)
2. `info` - Informations générales (RECOMMANDÉ)
3. `notice` - Événements normaux mais significatifs
4. `warning` - Avertissements
5. `error` - Erreurs qui n'empêchent pas l'application
6. `critical` - Erreurs critiques
7. `alert` - Action immédiate requise
8. `emergency` - Système inutilisable

**Recommandation par environnement**:

```bash
# Développement local
LOG_LEVEL=debug

# Staging/Test
LOG_LEVEL=info

# Production
LOG_LEVEL=warning  # Seulement warnings et erreurs
```

---

## 🔍 COMMANDES UTILES

### Voir les Logs Sans Script

```bash
# Dernières lignes
tail -50 storage/logs/laravel.log

# Logs en temps réel
tail -f storage/logs/laravel.log

# Rechercher "ERROR"
grep "ERROR" storage/logs/laravel.log | tail -20

# Rechercher aujourd'hui
grep "$(date +%Y-%m-%d)" storage/logs/laravel.log

# Compter les erreurs
grep -c "ERROR" storage/logs/laravel.log

# Voir les erreurs d'une heure spécifique
grep "2025-12-03 14:" storage/logs/laravel.log | grep ERROR
```

### Vérifier la Taille

```bash
# Taille du fichier
du -h storage/logs/laravel.log

# Nombre de lignes
wc -l storage/logs/laravel.log

# Taille totale du dossier logs
du -sh storage/logs/
```

### Archiver Manuellement

```bash
# Compresser le fichier actuel
gzip -c storage/logs/laravel.log > storage/logs/archives/laravel_backup.log.gz

# Vider le fichier (garder le fichier)
echo "" > storage/logs/laravel.log

# Supprimer complètement
rm storage/logs/laravel.log
```

---

## 📈 MONITORING DES LOGS

### Créer des Alertes pour Erreurs Critiques

Ajouter dans `app/Exceptions/Handler.php`:

```php
public function report(Throwable $exception): void
{
    // Logger les erreurs critiques avec plus de contexte
    if ($this->shouldReport($exception)) {
        \Log::critical('Exception critique détectée', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'user_id' => auth()->id() ?? 'guest',
            'url' => request()->fullUrl(),
            'ip' => request()->ip(),
        ]);
    }

    parent::report($exception);
}
```

### Logs Personnalisés dans le Code

```php
// Informations générales
\Log::info('Bulletin généré', ['student_id' => $studentId, 'time' => $time]);

// Avertissements
\Log::warning('Performance lente', ['operation' => 'bulletin', 'time' => $time]);

// Erreurs
\Log::error('Échec génération bulletin', ['student_id' => $studentId, 'error' => $e->getMessage()]);

// Debug (seulement en développement)
\Log::debug('Calcul DS', ['ds' => $dsAverage, 'sequences' => $sequences]);
```

---

## 🗑️ NETTOYAGE AUTOMATIQUE (CRON)

### Configuration Cron pour Nettoyage Automatique

```bash
# Éditer le crontab
crontab -e

# Ajouter cette ligne (nettoie tous les dimanches à 2h du matin)
0 2 * * 0 cd /Users/redwolf-dark/Documents/Projet/college-management-app/back && bash clean_logs.sh >> /tmp/clean_logs_cron.log 2>&1
```

---

## 📊 ANALYSE DES LOGS

### Trouver les Requêtes Lentes

```bash
# Si vous loggez les temps d'exécution
grep "slow\|timeout\|exceeded" storage/logs/laravel.log | tail -20
```

### Analyser les Erreurs Fréquentes

```bash
# Top 10 des erreurs
grep "ERROR" storage/logs/laravel.log | cut -d':' -f4- | sort | uniq -c | sort -rn | head -10
```

### Statistiques par Jour

```bash
# Compter les logs par jour
grep -o "^\[20[0-9][0-9]-[0-9][0-9]-[0-9][0-9]" storage/logs/laravel.log | sort | uniq -c
```

---

## 🚀 CHECKLIST DE MISE EN PLACE

- [ ] **1. Nettoyer le log actuel**
  ```bash
  bash clean_logs.sh
  ```

- [ ] **2. Configurer la rotation automatique**
  ```bash
  # Dans .env
  LOG_CHANNEL=daily
  LOG_LEVEL=info
  LOG_DAILY_DAYS=7
  ```

- [ ] **3. Vider le cache**
  ```bash
  php artisan config:clear
  php artisan cache:clear
  ```

- [ ] **4. Tester la nouvelle configuration**
  ```bash
  php artisan tinker --execute="\Log::info('Test de log après configuration');"
  bash view_logs.sh last
  ```

- [ ] **5. Vérifier que ça fonctionne**
  ```bash
  bash view_logs.sh size
  # Devrait afficher une taille raisonnable (< 10M)
  ```

- [ ] **6. Configurer le cron (optionnel)**
  ```bash
  crontab -e
  # Ajouter la ligne de nettoyage automatique
  ```

---

## 🆘 DÉPANNAGE

### Problème: Les logs n'apparaissent toujours pas

**Vérifier les permissions**:
```bash
chmod -R 775 storage/logs
chown -R $USER:$USER storage/logs
```

**Vérifier la configuration**:
```bash
php artisan config:show logging
```

**Forcer la création du fichier**:
```bash
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
```

### Problème: "Permission denied" sur le fichier de log

```bash
# Corriger les permissions
sudo chown $USER:$USER storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
```

### Problème: Les vieux logs ne sont pas supprimés

**Vérification**:
```bash
# Voir tous les fichiers de logs
ls -lah storage/logs/

# Supprimer manuellement les vieux logs
find storage/logs -name "laravel-*.log" -mtime +7 -delete
```

---

## 📖 RESSOURCES

- [Laravel Logging Documentation](https://laravel.com/docs/12.x/logging)
- [Monolog Documentation](https://github.com/Seldaek/monolog)

---

**Version**: 1.0
**Date**: 2025-12-03
**Fichiers**: `view_logs.sh`, `clean_logs.sh`
