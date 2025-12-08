# 🎓 DESIGN CARTE D'IDENTITÉ SCOLAIRE
## COLLÈGE POLYVALENT BILINGUE DE DOUALA

---

## 📐 DIMENSIONS
- **Format**: 85.6 mm × 54 mm (format carte de crédit standard)
- **Orientation**: Paysage (horizontale)
- **Impression**: Recto uniquement (pas de verso)

---

## 🎨 RECTO DE LA CARTE

```
┌─────────────────────────────────────────────────────────────────────────┐
│ 🇨🇲              RÉPUBLIQUE DU CAMEROUN                                 │
│                 PAIX - TRAVAIL - PATRIE                                 │
│ ─────────────────────────────────────────────────────────────────────── │
│                                                                         │
│  ┌──────────┐   COLLÈGE POLYVALENT BILINGUE      ┌────────┐           │
│  │          │   DE DOUALA                         │ LOGO   │           │
│  │  PHOTO   │                                     │ ÉCOLE  │           │
│  │  ÉLÈVE   │   CARTE D'IDENTITÉ SCOLAIRE         └────────┘           │
│  │ 3x4 cm   │   ───────────────────────────────                        │
│  │          │                                                           │
│  │          │   NOM: KAMDEM FOGANG                                     │
│  └──────────┘   PRÉNOM: Jean Paul                                      │
│                 MATRICULE: CPB2024001                                   │
│                 CLASSE: 6ème A                                         │
│                 NÉ(E) LE: 15/03/2012                                   │
│                 PARENT/TUTEUR: +237 6XX XXX XXX                        │
│                 ANNÉE SCOLAIRE: 2024-2025                              │
│                                                                         │
│                           ┌─────────┐              _______________     │
│                           │   QR    │              Signature           │
│                           │  CODE   │              Directeur           │
│                           └─────────┘                                  │
└─────────────────────────────────────────────────────────────────────────┘
```

### Éléments du RECTO:
1. **Bandeau supérieur** (vert-rouge-jaune - couleurs Cameroun)
   - Drapeau 🇨🇲
   - "RÉPUBLIQUE DU CAMEROUN"
   - Devise: "PAIX - TRAVAIL - PATRIE"

2. **En-tête du corps**
   - **À GAUCHE**: Photo élève (3x4 cm)
   - **AU CENTRE**:
     - Nom du collège "COLLÈGE POLYVALENT BILINGUE DE DOUALA"
     - "CARTE D'IDENTITÉ SCOLAIRE"
   - **À DROITE**: Logo du collège (même ligne que le nom)

3. **Informations élève** (sous la photo)
   - NOM (en majuscules)
   - PRÉNOM(s)
   - MATRICULE
   - CLASSE
   - DATE DE NAISSANCE
   - **PARENT/TUTEUR** (numéro de téléphone)
   - ANNÉE SCOLAIRE

4. **Pied de carte**
   - **AU CENTRE**: QR Code (2.5x2.5 cm)
   - **À DROITE**: Zone de signature du Directeur

---

## 📱 CONTENU DU QR CODE

Quand on scanne le QR Code, on obtient:

```json
{
  "type": "student_id",
  "college": {
    "name": "COLLÈGE POLYVALENT BILINGUE DE DOUALA",
    "logo_url": "https://cpbdouala.cm/logo.png"
  },
  "student": {
    "matricule": "CPB2024001",
    "nom": "KAMDEM FOGANG",
    "prenom": "Jean Paul",
    "classe": "6ème A",
    "annee_scolaire": "2024-2025",
    "photo_url": "https://cpbdouala.cm/photos/CPB2024001.jpg"
  },
  "verification_url": "https://cpbdouala.cm/verify/CPB2024001"
}
```

---

## 🎨 PALETTE DE COULEURS

### Couleurs principales:
- **Vert**: #009639 (du drapeau camerounais)
- **Rouge**: #CE1126 (du drapeau camerounais)
- **Jaune**: #FCD116 (du drapeau camerounais)
- **Bleu marine**: #003366 (pour le texte principal)
- **Blanc**: #FFFFFF (fond)
- **Gris**: #666666 (texte secondaire)

### Utilisation:
- Bandeau supérieur: Tricolore (vert-rouge-jaune)
- Nom du collège: Bleu marine
- Informations élève: Noir
- Bordures: Bleu marine

---

## 📋 SPÉCIFICATIONS TECHNIQUES

### Technologie proposée:
1. **Backend (Laravel)**:
   - Route: `POST /api/student-cards/generate`
   - Utiliser **DomPDF** (déjà installé)
   - Template Blade: `resources/views/student-cards/template.blade.php`

2. **Frontend (React)**:
   - Page: `front/src/pages/Admin/StudentCards.jsx`
   - Sélection élève(s) ou classe entière
   - Prévisualisation avant impression
   - Téléchargement PDF

3. **QR Code**:
   - Package: `simplesoftwareio/simple-qrcode` (déjà installé ✓)
   - Format: Base64 embedded dans le PDF
   - Données: JSON avec infos élève + logo collège

4. **Base de données**:
   - Table: `student_cards` (nouvelle)
   - Champs: id, student_id, qr_code_data, generated_at, academic_year
   - Récupération du numéro parent depuis la table `students` (champ `parent_contact`)

---

## 🖨️ OPTIONS D'IMPRESSION

1. **Individuel**: 1 carte par page A4 (pour plastification - RECTO SEULEMENT)
2. **Planche**: 10 cartes par page A4 (format économique - 2 colonnes x 5 lignes)
3. **Export**: PDF téléchargeable pour imprimerie professionnelle

---

## 🔒 SÉCURITÉ

- Watermark discret avec logo du collège en arrière-plan
- Numéro unique (matricule) sur chaque carte
- QR Code crypté avec timestamp de génération
- Vérification en ligne via URL unique

---

## 📸 EXAMPLE VISUEL (Design simplifié)

**RECTO UNIQUEMENT**:
```
╔═════════════════════════════════════════════════════════════════════╗
║ 🇨🇲     RÉPUBLIQUE DU CAMEROUN - PAIX TRAVAIL PATRIE              ║
╠═════════════════════════════════════════════════════════════════════╣
║                                                                     ║
║  ┌────────┐  COLLÈGE POLYVALENT BILINGUE      ┌──────┐            ║
║  │ PHOTO  │  DE DOUALA                         │ LOGO │            ║
║  │ ÉLÈVE  │                                    │ CPB  │            ║
║  │        │  CARTE D'IDENTITÉ SCOLAIRE         └──────┘            ║
║  │ 3x4cm  │  ════════════════════════════                          ║
║  │        │                                                         ║
║  │        │  📛 NOM: KAMDEM FOGANG                                 ║
║  └────────┘  📝 PRÉNOM: Jean Paul                                  ║
║              🔢 MATRICULE: CPB2024001                              ║
║              📚 CLASSE: 6ème A                                     ║
║              🎂 NÉ(E) LE: 15/03/2012                               ║
║              📞 PARENT/TUTEUR: +237 690 123 456                    ║
║              📅 ANNÉE: 2024-2025                                   ║
║                                                                     ║
║                      ┌────────┐           ___________________      ║
║                      │   QR   │           Signature Directeur      ║
║                      │  CODE  │                                    ║
║                      └────────┘                                    ║
╚═════════════════════════════════════════════════════════════════════╝
```

---

## ✅ PROCHAINES ÉTAPES

1. ✅ Validation du design par vous
2. ⏳ Création de la migration + table `student_cards`
3. ⏳ Développement du contrôleur Laravel `StudentCardController`
4. ⏳ Création du template Blade avec design HTML/CSS
5. ⏳ Intégration QR Code avec logo collège
6. ⏳ Développement interface React
7. ⏳ Tests et génération PDF
8. ⏳ Ajout fonctionnalité scanner QR (mobile app Flutter)

---

**Qu'en pensez-vous ? Voulez-vous que je commence l'implémentation ou ajuster le design ?**
