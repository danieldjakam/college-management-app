# 📱 Guide de Gestion des Queues WhatsApp

## 🎯 Vue d'ensemble

Le système d'envoi de notifications WhatsApp utilise **Laravel Queues** pour envoyer les messages en arrière-plan de manière asynchrone. Cela permet d'éviter les timeouts et d'améliorer l'expérience utilisateur lors de l'envoi de messages à de nombreux parents.

## ⚙️ Configuration

### Configuration actuelle
- **Queue Driver**: `database` (défini dans `.env`)
- **Table**: `jobs` (créée automatiquement lors de la migration)
- **Job**: `SendWhatsAppNotification`

### 🛡️ Protection Anti-Spam (Rate Limiting)

Le système intègre une protection anti-spam pour éviter d'être bloqué par WhatsApp :

- **Limite** : 10 messages maximum par période
- **Période** : 30 minutes
- **Comportement** : Après 10 envois, le système attend automatiquement 30 minutes avant de continuer

#### Comment ça fonctionne ?

```
Messages 1-10    : Envoyés immédiatement
Message 11       : ⏸️  Mis en pause automatiquement
                   ⏳ Attend 30 minutes
Message 11-20    : Envoyés après la pause
Message 21       : ⏸️  Nouvelle pause de 30 minutes
...etc
```

#### Configuration du rate limiting

Vous pouvez modifier ces paramètres dans `/app/Jobs/SendWhatsAppNotification.php` :

```php
const MAX_MESSAGES_PER_PERIOD = 10;      // Nombre max de messages
const RATE_LIMIT_PERIOD_MINUTES = 30;    // Durée de la période en minutes
```

#### Commandes de gestion du rate limiting

**Voir les statistiques actuelles :**
```bash
php artisan whatsapp:reset-rate-limit --show
```

**Réinitialiser le compteur (avec confirmation) :**
```bash
php artisan whatsapp:reset-rate-limit
```

**Réinitialiser manuellement via Tinker :**
```bash
php artisan tinker
> Cache::forget('whatsapp_rate_limit');
```

## 🚀 Démarrage du Worker

### En développement

Pour traiter les jobs de la queue, vous devez démarrer un worker Laravel :

```bash
php artisan queue:work
```

### Options utiles

```bash
# Traiter les jobs avec rechargement automatique (recommandé en dev)
php artisan queue:work --tries=3 --timeout=90

# Traiter seulement 10 jobs puis s'arrêter
php artisan queue:work --max-jobs=10

# Traiter les jobs pendant 1 heure puis s'arrêter
php artisan queue:work --max-time=3600

# Mode verbeux pour voir les détails
php artisan queue:work --verbose
```

### Paramètres importants

- `--tries=3` : Nombre de tentatives en cas d'échec
- `--timeout=90` : Timeout de 90 secondes par job
- `--queue=default` : Nom de la queue à traiter (par défaut: `default`)

## 🏭 En production (serveur en ligne)

### Option 1: Supervisor (RECOMMANDÉ)

Installer Supervisor pour gérer le worker automatiquement :

```bash
sudo apt-get install supervisor
```

Créer le fichier de configuration `/etc/supervisor/conf.d/laravel-worker.conf` :

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /chemin/vers/votre/projet/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/chemin/vers/votre/projet/storage/logs/worker.log
stopwaitsecs=3600
```

Démarrer Supervisor :

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Option 2: Systemd Service

Créer le fichier `/etc/systemd/system/laravel-worker.service` :

```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/chemin/vers/votre/projet
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Activer et démarrer le service :

```bash
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker
sudo systemctl status laravel-worker
```

### Option 3: Cron Job (pas recommandé)

Ajouter cette ligne au crontab :

```bash
* * * * * cd /chemin/vers/votre/projet && php artisan schedule:run >> /dev/null 2>&1
```

Puis dans `app/Console/Kernel.php` :

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('queue:work --stop-when-empty')->everyMinute();
}
```

## 📊 Commandes de monitoring

### Voir les jobs en cours
```bash
php artisan queue:monitor
```

### Voir les statistiques des queues
```bash
php artisan tinker
> DB::table('jobs')->count(); // Nombre de jobs en attente
> DB::table('failed_jobs')->count(); // Nombre de jobs échoués
```

### Voir les jobs échoués
```bash
php artisan queue:failed
```

### Réessayer les jobs échoués
```bash
# Réessayer tous les jobs échoués
php artisan queue:retry all

# Réessayer un job spécifique
php artisan queue:retry [job-id]
```

### Supprimer les jobs échoués
```bash
# Supprimer tous les jobs échoués
php artisan queue:flush

# Supprimer un job spécifique
php artisan queue:forget [job-id]
```

## 🔍 Logs et Débogage

### Logs Laravel
Les logs sont stockés dans `storage/logs/laravel.log`.

Rechercher les logs WhatsApp :
```bash
tail -f storage/logs/laravel.log | grep "WhatsApp"
```

### Logs spécifiques au Job
Le Job `SendWhatsAppNotification` enregistre :
- ✅ Succès d'envoi : `Message WhatsApp envoyé avec succès`
- ❌ Échecs : `Erreur lors de l'envoi WhatsApp`
- 📄 Pièces jointes : `Pièce jointe envoyée`

## 🎯 Flux de travail

### 1. Envoi d'une notification
1. L'admin envoie une notification via le frontend
2. Le `NotificationController` crée un `CommunicationLog` avec statut "pending"
3. Pour chaque destinataire, un job `SendWhatsAppNotification` est dispatché
4. Les jobs sont ajoutés à la table `jobs`

### 2. Traitement par le Worker
1. Le worker récupère un job de la queue
2. Le job exécute la méthode `handle()`
3. Le message WhatsApp est envoyé via `WhatsAppService`
4. Le statut du destinataire est mis à jour dans `CommunicationLog`
5. En cas d'échec, le job est réessayé jusqu'à 3 fois

### 3. Mise à jour en temps réel
Les statuts (`sent`, `failed`, `pending`) sont mis à jour dans la table `communication_logs` et peuvent être consultés dans l'historique des notifications.

## 🛠️ Troubleshooting

### Le worker ne démarre pas
```bash
# Vérifier les permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# Vérifier la configuration
php artisan config:cache
php artisan queue:restart
```

### Les jobs restent bloqués
```bash
# Redémarrer le worker
php artisan queue:restart

# Vérifier la table jobs
php artisan tinker
> DB::table('jobs')->get();
```

### Trop de jobs échoués
```bash
# Analyser les erreurs
php artisan queue:failed

# Regarder les logs
tail -100 storage/logs/laravel.log
```

## 📈 Performance

### Nombre de workers recommandés
- **Petit volume** (< 100 messages/jour) : 1 worker
- **Moyen volume** (100-1000 messages/jour) : 2-3 workers
- **Gros volume** (> 1000 messages/jour) : 4-6 workers

### Optimisations
- Utiliser Redis au lieu de database pour de meilleures performances
- Configurer plusieurs workers en parallèle avec Supervisor
- Activer l'horizon de Laravel pour un monitoring avancé

## 🔄 Passage à Redis (optionnel, pour de meilleures performances)

### 1. Installer Redis
```bash
sudo apt-get install redis-server
composer require predis/predis
```

### 2. Modifier .env
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Redémarrer le worker
```bash
php artisan config:cache
php artisan queue:restart
php artisan queue:work redis
```

## 📚 Ressources

- [Documentation Laravel Queues](https://laravel.com/docs/11.x/queues)
- [Laravel Horizon](https://laravel.com/docs/11.x/horizon) (monitoring avancé)
- [Supervisor Documentation](http://supervisord.org/)

---

**💡 Note importante** : En production, assurez-vous que le worker tourne en permanence via Supervisor ou Systemd pour que les messages soient envoyés automatiquement !
