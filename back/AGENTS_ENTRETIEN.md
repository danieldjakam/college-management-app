# Agents d'Entretien - Guide d'utilisation

## Rôle: `agent_entretien`

Le rôle **agent_entretien** a été ajouté au système pour gérer le personnel d'entretien de l'établissement.

## Permissions

Les agents d'entretien ont des permissions limitées:
- ✅ Consultation de leur propre profil
- ✅ Mise à jour de leur propre profil
- ❌ Aucun accès aux données académiques (notes, bulletins, etc.)
- ❌ Aucun accès aux données financières
- ❌ Aucun accès aux données des élèves ou enseignants

## Comment créer un Agent d'Entretien

### Option 1: Via l'API (Inscription)

**Endpoint:** `POST /api/auth/register`

**Exemple de requête:**
```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jean DUPONT",
    "username": "jean.dupont",
    "email": "jean.dupont@cpb.cm",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "agent_entretien"
  }'
```

### Option 2: Via l'interface Web

1. Accédez à la section **Gestion des Utilisateurs**
2. Cliquez sur **Ajouter un Utilisateur**
3. Remplissez le formulaire:
   - **Nom complet:** Jean DUPONT
   - **Nom d'utilisateur:** jean.dupont
   - **Email:** jean.dupont@cpb.cm
   - **Mot de passe:** ••••••••
   - **Rôle:** Agent d'Entretien
4. Cliquez sur **Enregistrer**

### Option 3: Via Tinker (Console Laravel)

```bash
php artisan tinker
```

```php
use App\Models\User;

User::create([
    'name' => 'Jean DUPONT',
    'username' => 'jean.dupont',
    'email' => 'jean.dupont@cpb.cm',
    'password' => bcrypt('password123'),
    'role' => 'agent_entretien'
]);
```

## Connexion d'un Agent d'Entretien

**Endpoint:** `POST /api/auth/login`

```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "jean.dupont",
    "password": "password123"
  }'
```

## Routes accessibles

Les agents d'entretien peuvent accéder aux routes suivantes:
- `GET /api/user/profile` - Voir son propre profil
- `PUT /api/user/profile` - Modifier son propre profil
- `POST /api/auth/logout` - Se déconnecter

## Exemple de création rapide

Pour créer rapidement un agent d'entretien de test:

```bash
php artisan tinker --execute="
use App\Models\User;
User::create([
    'name' => 'Agent Test',
    'username' => 'agent.test',
    'email' => 'agent.test@cpb.cm',
    'password' => bcrypt('password'),
    'role' => 'agent_entretien'
]);
echo 'Agent d\'entretien créé avec succès!' . PHP_EOL;
"
```

Connexion:
- **Username:** agent.test
- **Password:** password

## Liste de tous les rôles disponibles

1. `admin` - Administrateur (accès complet)
2. `principal` - Directeur
3. `secretaire` - Secrétaire
4. `accountant` - Comptable
5. `comptable_superieur` - Comptable supérieur
6. `teacher` - Enseignant
7. `student` - Étudiant
8. `parent` - Parent
9. `surveillant_general` - Surveillant général
10. `general_accountant` - Comptable général
11. **`agent_entretien` - Agent d'entretien** ⭐ NOUVEAU

## Notes importantes

- Les agents d'entretien n'apparaissent **pas** dans la liste des enseignants
- Ils n'ont **aucun accès** aux modules académiques
- Leur profil est géré comme tout autre utilisateur du système
- Pour des permissions supplémentaires, contactez l'administrateur système
