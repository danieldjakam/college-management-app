# 📚 GUIDE D'ANALYSE DE LA BASE DE DONNÉES - SYSTÈME DE GESTION SCOLAIRE

> **Date de création:** 21/11/2025
> **Auteur:** Documentation système
> **Version:** 1.0

---

## 🎯 ARCHITECTURE DU SYSTÈME

### ⚠️ IMPORTANT : SYSTÈME À DEUX NIVEAUX

Le système utilise **DEUX tables distinctes** pour gérer les enseignants :

#### 1️⃣ **Table `users`** (Authentification)
- **Rôle:** Gestion des connexions et accès à l'application
- **Colonnes principales:** `id`, `username`, `password`, `email`, `role`, `contact`
- **Usage:** Authentification, permissions, accès système

#### 2️⃣ **Table `teachers`** (Données pédagogiques)
- **Rôle:** Gestion des données pédagogiques et assignations
- **Colonnes principales:** `id`, `user_id`, `first_name`, `last_name`, `phone_number`, `email`
- **Usage:** Assignations de matières, bulletins, évaluations

### 🔗 **RELATION ENTRE LES TABLES**

```
teachers.user_id → users.id

Exemple:
teachers.id = 9   | teachers.user_id = 33  → users.id = 33  (MARGUERITE PAMOWA MARIE)
teachers.id = 33  | teachers.user_id = 125 → users.id = 125 (SANTANA MOUKORY)
```

---

## 📊 TABLES PRINCIPALES

### 🎓 **ENSEIGNANTS**

#### Table `users`
```sql
SELECT id, username, name, email, contact, role
FROM users
WHERE role = 'teacher'
```

#### Table `teachers`
```sql
SELECT t.id, t.first_name, t.last_name, t.user_id, u.username
FROM teachers t
LEFT JOIN users u ON t.user_id = u.id
```

#### ⚠️ **RÈGLE CRUCIALE**
**`teacher_assignments.teacher_id` → `teachers.id` (PAS `users.id` !)**

---

### 📝 **ASSIGNATIONS**

#### Table `teacher_assignments`
- **Colonnes:** `id`, `teacher_id`, `class_series_subject_id`, `school_year_id`, `is_active`
- **Relation:** `teacher_id` → **`teachers.id`** ⚠️

```sql
-- Pour obtenir les assignations d'un enseignant
SELECT
    ta.id,
    t.first_name || ' ' || t.last_name as teacher_name,
    sub.name as subject,
    sc.name as class_name,
    cs.name as serie
FROM teacher_assignments ta
JOIN teachers t ON ta.teacher_id = t.id
JOIN class_series_subjects css ON ta.class_series_subject_id = css.id
JOIN class_series cs ON css.class_series_id = cs.id
JOIN school_classes sc ON cs.class_id = sc.id
JOIN subjects sub ON css.subject_id = sub.id
WHERE ta.teacher_id = ? -- ICI: teachers.id, PAS users.id !
AND ta.school_year_id = 1
AND ta.is_active = 1
```

---

### 📖 **ÉVALUATIONS ET NOTES**

#### Table `evaluations`
- **Colonnes:** `id`, `name`, `teacher_id`, `sequence_id`, `class_series_subject_id`, `date`
- **Relation:** `teacher_id` → **`teachers.id`** ⚠️

#### Table `grades` (Notes)
- **Colonnes:** `id`, `student_id`, `evaluation_id`, `sequence_id`, `score`, `coefficient`
- **Relation:** Lié aux évaluations, pas directement aux enseignants

```sql
-- Pour obtenir toutes les notes d'un enseignant
SELECT
    t.first_name || ' ' || t.last_name as teacher_name,
    s.name as student_name,
    sub.name as subject,
    g.score,
    g.sequence_id
FROM grades g
JOIN evaluations e ON g.evaluation_id = e.id
JOIN teachers t ON e.teacher_id = t.id
JOIN students s ON g.student_id = s.id
JOIN class_series_subjects css ON e.class_series_subject_id = css.id
JOIN subjects sub ON css.subject_id = sub.id
WHERE e.teacher_id = ? -- ICI: teachers.id, PAS users.id !
ORDER BY g.sequence_id, sub.name
```

---

## 🔍 REQUÊTES TYPES POUR ANALYSE

### 1️⃣ **Trouver un enseignant par son nom**

```sql
-- Méthode correcte
SELECT
    t.id as teacher_id,
    t.first_name,
    t.last_name,
    t.user_id,
    u.username,
    u.contact
FROM teachers t
LEFT JOIN users u ON t.user_id = u.id
WHERE t.first_name LIKE '%SANTANA%'
   OR t.last_name LIKE '%MOUKORY%'
   OR u.name LIKE '%SANTANA%'
```

### 2️⃣ **Obtenir les assignations d'un enseignant**

```sql
-- Par nom d'utilisateur (username)
SELECT
    sub.name as matiere,
    sc.name as classe,
    cs.name as serie,
    css.coefficient
FROM teacher_assignments ta
JOIN teachers t ON ta.teacher_id = t.id
JOIN users u ON t.user_id = u.id
JOIN class_series_subjects css ON ta.class_series_subject_id = css.id
JOIN class_series cs ON css.class_series_id = cs.id
JOIN school_classes sc ON cs.class_id = sc.id
JOIN subjects sub ON css.subject_id = sub.id
WHERE u.username = 'santana_33'  -- Utiliser le username
AND ta.school_year_id = 1
AND ta.is_active = 1
ORDER BY sub.name, sc.name
```

### 3️⃣ **Compter les notes saisies par un enseignant**

```sql
SELECT
    t.first_name || ' ' || t.last_name as enseignant,
    e.sequence_id,
    COUNT(g.id) as nombre_notes
FROM teachers t
JOIN evaluations e ON t.id = e.teacher_id
JOIN grades g ON e.id = g.evaluation_id
WHERE t.id = ?  -- teachers.id
GROUP BY t.id, t.first_name, t.last_name, e.sequence_id
ORDER BY e.sequence_id
```

### 4️⃣ **Vérifier les notes pour une classe et une matière**

```sql
SELECT
    s.name as eleve,
    sub.name as matiere,
    g.score as note,
    g.sequence_id,
    t.first_name || ' ' || t.last_name as enseignant
FROM grades g
JOIN students s ON g.student_id = s.id
JOIN evaluations e ON g.evaluation_id = e.id
JOIN teachers t ON e.teacher_id = t.id
JOIN class_series_subjects css ON g.class_series_subject_id = css.id
JOIN class_series cs ON css.class_series_id = cs.id
JOIN subjects sub ON css.subject_id = sub.id
WHERE cs.name = '2nd F8 A'
AND sub.name = 'Anglais'
AND g.sequence_id = 1
ORDER BY s.name
```

---

## 🎯 EXEMPLES CONCRETS

### Exemple 1: SANTANA MOUKORY

```
users.id = 125 (username: santana_33, contact: +237695398487)
     ↓
teachers.id = 33 (first_name: SANTANA, last_name: MOUKORY, user_id: 125)
     ↓
teacher_assignments.teacher_id = 33
     ↓
Matières: Éducation de la Citoyenneté, Histoire, Hist-Geo
Classes: 2nd F8 A, 1er F8 B, 6ème B, 1ère C, 1ère D, etc.
```

### Exemple 2: MARGUERITE PAMOWA MARIE

```
users.id = 33 (username: 674134850, email: 674 13 48 50@school.local)
     ↓
teachers.id = 9 (first_name: MARGUERITE, last_name: PAMOWA MARIE, user_id: 33)
     ↓
teacher_assignments.teacher_id = 9
     ↓
Matières: Anglais
Classes: 2nd F8 A, 2nd F8 B, 2nd A4 ALL, 2nd C, Tle IH A, etc.
```

---

## ⚡ COMMANDES PHP ARTISAN TINKER

### Trouver un enseignant
```php
// Par username
$user = DB::table('users')->where('username', 'santana_33')->first();
$teacher = DB::table('teachers')->where('user_id', $user->id)->first();
echo "teachers.id: " . $teacher->id; // Utiliser cet ID pour les requêtes !

// Ou directement
$teacher = DB::table('teachers as t')
    ->join('users as u', 't.user_id', '=', 'u.id')
    ->where('u.username', 'santana_33')
    ->select('t.id', 't.first_name', 't.last_name', 'u.username')
    ->first();
```

### Obtenir les assignations
```php
$assignments = DB::table('teacher_assignments as ta')
    ->join('teachers as t', 'ta.teacher_id', '=', 't.id')
    ->join('users as u', 't.user_id', '=', 'u.id')
    ->join('class_series_subjects as css', 'ta.class_series_subject_id', '=', 'css.id')
    ->join('class_series as cs', 'css.class_series_id', '=', 'cs.id')
    ->join('school_classes as sc', 'cs.class_id', '=', 'sc.id')
    ->join('subjects as sub', 'css.subject_id', '=', 'sub.id')
    ->where('u.username', 'santana_33')
    ->where('ta.school_year_id', 1)
    ->select('sub.name as subject', 'sc.name as class', 'cs.name as serie')
    ->get();
```

### Compter les notes
```php
// Par username
$notes = DB::table('grades as g')
    ->join('evaluations as e', 'g.evaluation_id', '=', 'e.id')
    ->join('teachers as t', 'e.teacher_id', '=', 't.id')
    ->join('users as u', 't.user_id', '=', 'u.id')
    ->where('u.username', 'santana_33')
    ->where('g.sequence_id', 1)
    ->count();
```

---

## 🚨 ERREURS À ÉVITER

### ❌ **ERREUR 1: Utiliser users.id au lieu de teachers.id**
```php
// MAUVAIS ❌
$assignments = DB::table('teacher_assignments')
    ->where('teacher_id', 125) // 125 = users.id de SANTANA
    ->get();
// Résultat: Assignations d'une autre personne !

// BON ✅
$teacher = DB::table('teachers')->where('user_id', 125)->first();
$assignments = DB::table('teacher_assignments')
    ->where('teacher_id', $teacher->id) // 33 = teachers.id de SANTANA
    ->get();
```

### ❌ **ERREUR 2: Oublier la jointure teachers**
```php
// MAUVAIS ❌
$notes = DB::table('evaluations')
    ->where('teacher_id', 125) // Cherche dans teachers.id
    ->count();

// BON ✅
$teacher = DB::table('teachers')->where('user_id', 125)->first();
$notes = DB::table('evaluations')
    ->where('teacher_id', $teacher->id) // 33
    ->count();
```

### ❌ **ERREUR 3: Confondre les deux systèmes**
```
Connexion système: users.id
Assignations/Notes: teachers.id
TOUJOURS passer par la relation teachers.user_id !
```

---

## 📊 STATISTIQUES SYSTÈME (21/11/2025)

- **Total enseignants (users):** ~150
- **Total enseignants (teachers):** 144
- **Total assignations:** 961
- **Total évaluations:** 1 083
- **Total notes saisies:** 23 802
  - Séquence 1: 21 059 notes
  - Séquence 2: 2 537 notes
  - Séquence 3: 206 notes

---

## 🔧 REQUÊTES DE DIAGNOSTIC

### Vérifier la cohérence users ↔ teachers
```sql
-- Enseignants sans compte users
SELECT * FROM teachers WHERE user_id IS NULL OR user_id NOT IN (SELECT id FROM users);

-- Comptes users sans enregistrement teachers
SELECT u.id, u.username, u.name
FROM users u
WHERE u.role = 'teacher'
AND u.id NOT IN (SELECT user_id FROM teachers WHERE user_id IS NOT NULL);
```

### Vérifier les assignations orphelines
```sql
-- Assignations pointant vers des teachers supprimés
SELECT ta.id, ta.teacher_id, ta.class_series_subject_id
FROM teacher_assignments ta
LEFT JOIN teachers t ON ta.teacher_id = t.id
WHERE t.id IS NULL;
```

---

## 📝 NOTES IMPORTANTES

1. **Bulletins:** Utilisent `teachers.id` via la relation `teacher_assignments`
2. **Connexion:** Utilise `users.id` avec username/password
3. **Évaluations:** Créées par `teachers.id`, pas `users.id`
4. **Notes:** Liées aux évaluations, donc indirectement à `teachers.id`

---

## ✅ CHECKLIST POUR ANALYSER UN ENSEIGNANT

- [ ] 1. Récupérer `users.id` via username/nom
- [ ] 2. Trouver `teachers.id` via `teachers.user_id = users.id`
- [ ] 3. Utiliser `teachers.id` pour toutes les requêtes d'assignations
- [ ] 4. Utiliser `teachers.id` pour toutes les requêtes d'évaluations
- [ ] 5. Vérifier `school_year_id = 1` (année courante)
- [ ] 6. Vérifier `is_active = 1` pour les assignations actives

---

## 📞 CONTACT ET SUPPORT

Pour toute question sur ce guide, contacter l'administrateur système.

**Dernière mise à jour:** 21/11/2025 23:45
