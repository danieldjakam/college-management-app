# 🎯 Guide d'Accès - Configuration Géolocalisation

## 📍 **OÙ TROUVER LA CONFIGURATION**

### **Méthode 1 : Via les Paramètres (Recommandé)**
1. **Connectez-vous** en tant qu'administrateur
2. Allez dans **Paramètres** (icône engrenage dans la sidebar)
3. Vous verrez maintenant **3 onglets** :
   - ⚙️ Paramètres Généraux
   - 🏆 Bourses par Classe  
   - 📍 **Zones Géolocalisées** ← **NOUVEAU !**

4. **Cliquez** sur l'onglet **"Zones Géolocalisées"**

### **Méthode 2 : URL Directe**
```
http://votre-domaine.com/settings
```
Puis cliquer sur l'onglet "Zones Géolocalisées"

---

## 🎛️ **INTERFACE DE CONFIGURATION**

### **Carte d'État Géolocalisation**
En haut de la page Paramètres, vous verrez maintenant une carte bleue :
```
┌─────────────────────────────────────────┐
│ 📍 Contrôle Géolocalisation - Présences │
├─────────────────────────────────────────┤
│ ❌ Aucune zone configurée               │
│ Scan de présence non sécurisé          │
│                                         │
│ [Zones: 0] [Actives: 0] [Inactives: 0] │
│                                         │
│     [ 🔧 Configurer les Zones ]         │
└─────────────────────────────────────────┘
```

### **Fonctionnalités Disponibles**
- ✅ **Statut en temps réel** des zones
- 📊 **Compteurs** zones totales/actives
- 🎯 **Bouton d'accès rapide** à la configuration
- ⚠️ **Alertes** si aucune zone configurée

---

## 🚀 **PREMIÈRE CONFIGURATION**

### **Étapes Simples :**

1. **Aller à Paramètres** → Onglet **"Zones Géolocalisées"**

2. **Cliquer** "Ajouter Zone"

3. **Remplir le formulaire :**
   - **Nom** : "École - Bâtiment Principal"
   - **Rayon** : 100 (mètres)
   - **Cliquer** "Utiliser Position Actuelle" (depuis l'école !)
   - **Cocher** "Zone activée"

4. **Sauvegarder** 

5. **Tester** avec le bouton "Tester Position"

---

## 🔍 **VÉRIFICATION**

### **Après Configuration :**
- La carte d'état passera au **VERT** ✅
- Message : *"✅ 1 zone active - Configuration optimale"*
- Le scanner de présence sera maintenant **sécurisé**

### **Test du Scanner :**
1. Aller à **Scanner de Présence** (`/staff-attendance-scanner`)
2. Vérifier que le message dit : *"✅ Zone autorisée"*
3. Scanner fonctionne normalement
4. Sortir de l'école → Scanner se bloque automatiquement ❌

---

## 📧 **SUPPORT**

Si vous ne voyez toujours pas la configuration :

1. **Vérifier** que vous êtes connecté en **admin**
2. **Actualiser** la page (F5)
3. **Vider le cache** navigateur
4. **Vérifier** l'URL : `/settings`

---

## 🎯 **RÉSUMÉ RAPIDE**

```
Admin → Paramètres → Onglet "Zones Géolocalisées" → Ajouter Zone → TERMINÉ !
```

La géolocalisation sera désormais **obligatoire** pour tous les scans de présence ! 🛡️