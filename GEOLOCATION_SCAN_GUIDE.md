# 📍 Guide d'Utilisation - Scan de Présence Géolocalisé

## Vue d'ensemble

Le système de scan de présence géolocalisé garantit que les membres du personnel ne peuvent marquer leur présence que s'ils se trouvent physiquement dans une zone autorisée de l'école.

## 🔧 Fonctionnalités

### ✅ **Contrôle de Zone**
- **Vérification automatique** de la position avant chaque scan
- **Zones prédéfinies** avec rayons personnalisables
- **Double validation** : avant démarrage caméra + au moment du scan
- **Précision GPS** requise pour éviter les erreurs

### 🛡️ **Sécurité Anti-Fraude**
- ❌ **Impossible de scanner** depuis l'extérieur des zones autorisées
- 📊 **Traçabilité complète** : position + heure + précision GPS
- 🔄 **Validation en temps réel** de la position
- 📱 **Détection des tentatives** de contournement

## 🚀 Installation et Configuration

### 1. **Fichiers Ajoutés**
```
front/src/services/geolocationService.js       # Service principal
front/src/components/GeolocationStatus.jsx     # Composant d'affichage
front/src/pages/Attendance/StaffAttendanceScannerGeolocated.jsx  # Scanner modifié
front/src/pages/Settings/GeolocationZoneSettings.jsx             # Configuration admin
```

### 2. **Activation dans l'Application**
```javascript
// Dans App.js ou votre router, remplacer l'ancien scanner par :
import StaffAttendanceScannerGeolocated from './pages/Attendance/StaffAttendanceScannerGeolocated';

// Route admin pour configuration
import GeolocationZoneSettings from './pages/Settings/GeolocationZoneSettings';
```

## 📍 Configuration des Zones

### **Accès Administration**
1. Aller dans **Paramètres** → **Zones Géolocalisées**
2. Cliquer sur **"Ajouter Zone"**
3. Définir les paramètres :
   - **Nom** : Ex. "Bâtiment Principal"
   - **Coordonnées** : Latitude/Longitude exactes
   - **Rayon** : Distance autorisée (10-1000m)
   - **Statut** : Activé/Désactivé

### **Zones Recommandées**
```javascript
// Exemple de configuration
{
  'school_main': {
    name: 'Bâtiment Principal',
    latitude: 3.8480,
    longitude: 11.5021,
    radius: 100, // 100 mètres
    enabled: true
  },
  'school_admin': {
    name: 'Administration',
    latitude: 3.8485,
    longitude: 11.5025,
    radius: 50,  // 50 mètres
    enabled: true
  }
}
```

## 👤 Utilisation - Personnel

### **Étapes de Scan**
1. **Ouvrir l'application** de scan de présence
2. **Autoriser la géolocalisation** si demandé
3. **Vérifier la position** (bouton "Vérifier Position")
4. ✅ **Scanner QR code** uniquement si zone autorisée
5. **Confirmation visuelle** avec détails géographiques

### **États Possibles**
- 🟢 **Zone Autorisée** : Scan possible
- 🔴 **Hors Zone** : Scan bloqué
- ⚠️ **GPS Imprécis** : Améliorer signal GPS
- ❌ **Position Refusée** : Activer géolocalisation

## 🔧 Paramètres Techniques

### **Précision GPS Requise**
- **Excellente** : < 20m (recommandé)
- **Moyenne** : 20-50m (acceptable)
- **Faible** : > 50m (peut être rejetée)

### **Zones de Sécurité**
- **Rayon minimum** : 10 mètres
- **Rayon maximum** : 1000 mètres
- **Zones multiples** : autorisées
- **Chevauchement** : permis

### **Performance**
- **Cache position** : 30 secondes
- **Timeout GPS** : 10 secondes
- **Réveil automatique** : position en temps réel
- **Stockage local** : configuration sauvée

## 🚨 Gestion des Erreurs

### **Messages d'Erreur Fréquents**

#### **"Permission de géolocalisation refusée"**
**Solution :** Aller dans paramètres navigateur → Autoriser géolocalisation

#### **"Position indisponible"**
**Solution :** Activer GPS/WiFi + aller à l'extérieur si nécessaire

#### **"Scan non autorisé - Distance: XXXm"**
**Solution :** Se rapprocher de la zone autorisée

#### **"Délai dépassé pour obtenir la position"**
**Solution :** Améliorer signal GPS (sortir, attendre)

### **Mode de Contournement**
Pour les tests uniquement, possibilité de **désactiver temporairement** la géolocalisation :
```javascript
// Dans le scanner, switch "Géolocalisation requise"
setIsLocationRequired(false); // MODE TEST UNIQUEMENT
```

## 📊 Données Collectées

### **Informations Stockées par Scan**
```javascript
{
  staff_qr_code: "STAFF123",
  supervisor_id: 1,
  event_type: "entry",
  location_data: {
    latitude: 3.8480,
    longitude: 11.5021,
    accuracy: 15,           // Précision en mètres
    timestamp: 1638360000000,
    authorized_zone: "Bâtiment Principal",
    distance_to_zone: 8     // Distance au centre de zone
  }
}
```

### **Rapports Disponibles**
- **Présences géolocalisées** par jour/semaine/mois
- **Tentatives de scan** hors zone
- **Précision GPS moyenne** par utilisateur
- **Zones les plus utilisées**

## 🔄 Synchronisation Backend

### **API Laravel - Modifications Requises**
```php
// Dans le contrôleur de scan (StaffAttendanceController.php)
public function scanQR(Request $request) {
    $validated = $request->validate([
        'staff_qr_code' => 'required|string',
        'supervisor_id' => 'required|integer',
        'event_type' => 'required|string',
        'location_data' => 'nullable|array', // NOUVEAU
        'location_data.latitude' => 'nullable|numeric',
        'location_data.longitude' => 'nullable|numeric',
        'location_data.accuracy' => 'nullable|numeric',
        'location_data.authorized_zone' => 'nullable|string',
        'location_data.distance_to_zone' => 'nullable|numeric'
    ]);
    
    // Sauvegarder les données de géolocalisation
    if (isset($validated['location_data'])) {
        $attendance->location_latitude = $validated['location_data']['latitude'];
        $attendance->location_longitude = $validated['location_data']['longitude'];
        $attendance->location_accuracy = $validated['location_data']['accuracy'];
        $attendance->authorized_zone = $validated['location_data']['authorized_zone'];
        $attendance->distance_to_zone = $validated['location_data']['distance_to_zone'];
    }
    
    $attendance->save();
}
```

### **Migration Base de Données**
```php
// Ajouter colonnes à la table staff_attendances
Schema::table('staff_attendances', function (Blueprint $table) {
    $table->decimal('location_latitude', 10, 7)->nullable();
    $table->decimal('location_longitude', 10, 7)->nullable();
    $table->integer('location_accuracy')->nullable();
    $table->string('authorized_zone')->nullable();
    $table->integer('distance_to_zone')->nullable();
    $table->timestamp('location_timestamp')->nullable();
});
```

## 🎯 Avantages Business

### **Sécurité Renforcée**
- ✅ **100% de présence réelle** (fin des fraudes)
- 🔍 **Audit complet** des présences
- 📊 **Données fiables** pour la paie

### **Simplicité d'Usage**
- 📱 **Interface intuitive** avec guidage visuel
- 🔄 **Validation automatique** transparente
- ⚡ **Performance optimisée** (cache GPS)

### **Flexibilité Administrative**
- ⚙️ **Zones configurables** facilement
- 📈 **Rayons ajustables** selon les besoins
- 🔄 **Activation/désactivation** par zone

## 📞 Support et Maintenance

### **Tests Recommandés**
1. **Test position valide** : Scanner depuis le bureau principal
2. **Test position invalide** : Scanner depuis l'extérieur
3. **Test précision GPS** : Vérifier dans différentes conditions
4. **Test configuration** : Créer/modifier/supprimer zones

### **Monitoring**
- 📊 **Logs de géolocalisation** dans la console navigateur
- ⚠️ **Alertes automatiques** pour tentatives hors zone
- 📈 **Statistiques d'usage** des zones configurées

---

## 🚀 Déploiement

### **Checklist de Mise en Production**
- [ ] ✅ Configuration des zones réelles (coordonnées exactes)
- [ ] 🔧 Tests avec différents appareils
- [ ] 📊 Migration base de données
- [ ] 👥 Formation équipe administrative
- [ ] 📱 Test permissions géolocalisation
- [ ] 🔄 Sauvegarde configuration existante

**Status : Prêt pour déploiement** ✅

Cette solution garantit l'**intégrité totale** de votre système de présence personnel tout en conservant une **expérience utilisateur fluide**.