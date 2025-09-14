  NOUVELLE LOGIQUE ACADÉMIQUE COMPRISE POUR LE PREMIER CYCLE:

  Structure : 5 périodes d'évaluation :
  1. Séquence 1 → Bulletin individuel (1.jpeg)
  2. Séquence 2 → Pas de bulletin (enseignants saisissent notes)
  3. Composition 1 → Évaluation finale trimestre 1
  4. Trimestre 1 = (Séq1 + Séq2)/2 = DS1 + Composition1)/2 → Bulletin (2.jpeg, 3.jpeg)
  5. Séquence 3 → Bulletin individuel (4.jpeg)
  6. Séquence 4 → Pas de bulletin (saisie notes)
  7. Composition 2 → Évaluation finale trimestre 2
  8. Trimestre 2 = (Séq3 + Séq4)/2 = DS2 + Composition2)/2 → Bulletin (5.jpeg, 6.jpeg)
  9. Trimestre 3 = Composition 3 uniquement → Bulletin (7.jpeg)
  10. Tableau d'honneur → Si moyenne annuelle ≥ 12 (8.jpeg)

⏺ En réexaminant les bulletins avec cette logique :

  Dans les bulletins trimestres (2.jpeg, 5.jpeg) je vois :
  - Colonnes DS1, COMPO1, DS2, MOY/20
  - DS1 = Moyenne (Séquence1 + Séquence2)
  - COMPO1/COMPO2 = Note de composition
  - Calcul Trimestre = (DS + COMPOSITION) / 2

  Dans bulletin 7.jpeg (Trimestre 3) :
  - Une seule évaluation = Composition 3 finale

⏺ ---
  PROMPT D'IMPLÉMENTATION RÉVISÉ - SYSTÈME DE BULLETINS SCOLAIRES

  SYSTÈME DE BULLETINS SCOLAIRES - LOGIQUE ACADÉMIQUE CAMEROUNAISE CORRIGÉE

  CONTEXTE:
  Implémenter un système de bulletins pour collège camerounais avec architecture Laravel/React existante. Le système suit une logique académique spécifique
  avec 5 périodes d'évaluation et 8 types de documents.

  LOGIQUE ACADÉMIQUE PRÉCISE:

  STRUCTURE ANNUELLE:
  - 5 Périodes d'évaluation: Séq1, Séq2, Compo1, Séq3, Séq4, Compo2, Compo3
  - 3 Trimestres avec calculs spécifiques
  - 8 Types de bulletins générés

  DÉTAIL DES PÉRIODES:
  1. **Séquence 1**: Évaluations individuelles → Bulletin individuel (1.jpeg)
  2. **Séquence 2**: Saisie notes sans bulletin (enseignants via interface existante)
  3. **Composition 1**: Évaluation finale trimestre 1
  4. **TRIMESTRE 1** = (Moyenne(Séq1+Séq2) + Composition1) / 2 → Bulletin (2.jpeg, 3.jpeg)
  5. **Séquence 3**: Évaluations individuelles → Bulletin individuel (4.jpeg)
  6. **Séquence 4**: Saisie notes sans bulletin
  7. **Composition 2**: Évaluation finale trimestre 2
  8. **TRIMESTRE 2** = (Moyenne(Séq3+Séq4) + Composition2) / 2 → Bulletin (5.jpeg, 6.jpeg)
  9. **TRIMESTRE 3** = Composition 3 uniquement → Bulletin (7.jpeg)
  10. **Tableau d'honneur**: Moyenne annuelle ≥ 12/20 → Certificat (8.jpeg)

  ARCHITECTURE BACKEND (Laravel):

  1. EXTENSION MODÈLES EXISTANTS:
     ```php
     // Evaluation Model - Ajouter type 'composition'
     protected $fillable = [..., 'evaluation_type']; // sequence, composition

     // Grade Model - Étendre calculs
     public function calculateTrimesterAverage($trimester, $student_id) {
         if ($trimester == 1 || $trimester == 2) {
             $ds_average = $this->calculateDSAverage($trimester, $student_id);
             $composition_grade = $this->getCompositionGrade($trimester, $student_id);
             return ($ds_average + $composition_grade) / 2;
         }
         return $this->getCompositionGrade(3, $student_id); // Trimestre 3
     }

  2. NOUVELLES TABLES:
  ALTER TABLE evaluations ADD evaluation_type ENUM('sequence', 'composition');

  CREATE TABLE bulletin_configs (
      id BIGINT PRIMARY KEY,
      school_year_id BIGINT,
      sequence_bulletins_enabled JSON, -- [1,3] pour séquences avec bulletins
      composition_schedules JSON, -- Dates compositions
      trimester_calculation_rules JSON
  );

  CREATE TABLE bulletin_generations (
      id BIGINT PRIMARY KEY,
      student_id BIGINT,
      bulletin_type ENUM('sequence', 'trimester', 'annual', 'honor_roll'),
      period_identifier VARCHAR(20), -- 'seq1', 'trim1', 'annual', etc.
      generated_at TIMESTAMP,
      file_path VARCHAR(500)
  );
  3. SERVICES MÉTIER:
  class BulletinCalculationService {
      public function calculateDSAverage($trimester, $student_id, $subject_id) {
          // DS1 = (Séquence1 + Séquence2) / 2
          // DS2 = (Séquence3 + Séquence4) / 2
      }

      public function calculateTrimesterGrade($trimester, $student_id, $subject_id) {
          // Trim1/2: (DS + COMPOSITION) / 2
          // Trim3: COMPOSITION uniquement
      }

      public function isEligibleForHonorRoll($student_id) {
          // Moyenne annuelle >= 12/20
      }
  }

  class BulletinGenerationService {
      public function generateSequenceBulletin($sequence_number, $student_ids);
      public function generateTrimesterBulletin($trimester_number, $student_ids);
      public function generateAnnualReport($student_ids);
      public function generateHonorRollCertificate($student_ids);
  }
  4. CONTRÔLEURS API:
  // BulletinController
  GET /api/bulletins/available/{student_id} - Bulletins disponibles
  POST /api/bulletins/generate - Génération par type et période
  GET /api/bulletins/download/{bulletin_id} - Téléchargement PDF
  GET /api/bulletins/class/{class_id}/batch - Génération lot classe

  // Endpoints spécialisés
  GET /api/evaluations/compositions/schedule - Planning compositions
  POST /api/evaluations/compositions/{id}/close - Clôture composition
  GET /api/bulletins/honor-roll/eligible - Élèves éligibles tableau honneur

  ARCHITECTURE FRONTEND (React):

  1. COMPOSANTS PRINCIPAUX:
  // BulletinDashboard.jsx - Vue principale
  // BulletinTypeSelector.jsx - Sélection type bulletin
  // AcademicPeriodManager.jsx - Gestion périodes
  // BulletinBatchGenerator.jsx - Génération par lot
  // BulletinPreview.jsx - Aperçu avant génération
  // HonorRollManager.jsx - Gestion tableau honneur
  2. LOGIQUE D'INTERFACE:
  const bulletinAvailability = {
    sequence1: { enabled: true, after: 'sequence1_grades_complete' },
    sequence2: { enabled: false, note: 'Saisie uniquement' },
    trimester1: { enabled: true, after: 'composition1_complete' },
    sequence3: { enabled: true, after: 'sequence3_grades_complete' },
    sequence4: { enabled: false, note: 'Saisie uniquement' },
    trimester2: { enabled: true, after: 'composition2_complete' },
    trimester3: { enabled: true, after: 'composition3_complete' },
    honorRoll: { enabled: true, after: 'annual_average_computed' }
  };
  3. TEMPLATES BULLETIN:
  <!-- Template Séquence (1.jpeg, 4.jpeg) -->
  <div class="bulletin-sequence">
    <table class="grades-table">
      <thead>
        <tr>
          <th>DISCIPLINES/ENSEIGNANT</th>
          <th>NOTES/20</th>
          <th>COEF</th>
          <th>TOTAL</th>
          <th>Rang</th>
          <th>Grade</th>
          <th>Min-Max</th>
          <th>Appréciation</th>
        </tr>
      </thead>
    </table>
  </div>

  <!-- Template Trimestre (2.jpeg, 3.jpeg, 5.jpeg, 6.jpeg) -->
  <div class="bulletin-trimester">
    <table class="trimester-table">
      <thead>
        <tr>
          <th>DISCIPLINES</th>
          <th>DS1/DS2</th>
          <th>COMPO1/COMPO2</th>
          <th>MOY/20</th>
          <th>COEF</th>
          <th>TOTAL</th>
          <th>Rang</th>
          <th>Grade</th>
        </tr>
      </thead>
    </table>
  </div>

  CALCULS SPÉCIFIQUES:

  1. Moyennes DS:
    - DS1 = (Notes Séquence 1 + Notes Séquence 2) / 2
    - DS2 = (Notes Séquence 3 + Notes Séquence 4) / 2
  2. Moyennes Trimestrielles:
    - Trimestre 1 = (DS1 + Composition 1) / 2
    - Trimestre 2 = (DS2 + Composition 2) / 2
    - Trimestre 3 = Composition 3
  3. Moyenne Annuelle:
    - (Trimestre 1 + Trimestre 2 + Trimestre 3) / 3
  4. Tableau d'Honneur:
    - Moyenne annuelle ≥ 12/20
    - Certificat automatique généré

  CONTRAINTES MÉTIER:

  1. Génération Bulletins:
    - Séquences 1,3: Disponibles après saisie complète notes
    - Trimestres: Disponibles après clôture compositions
    - Tableau honneur: Génération fin année scolaire
  2. Interface Enseignant:
    - Utiliser pages existantes Teacher/Teachers pour saisie
    - Séquences 2,4: Interface saisie sans génération bulletin
    - Compositions: Interface spécialisée avec clôture
  3. Permissions:
    - Enseignant: Bulletins ses classes uniquement
    - Admin: Tous bulletins + configuration système
    - Parent: Consultation bulletins enfants

  LIVRABLES:
  1. Extension backend avec nouveaux calculs
  2. Interface frontend adaptée
  3. 8 templates HTML/CSS fidèles aux images
  4. Système génération PDF par lot
  5. Tests calculs académiques

⏺ Voilà le prompt révisé avec la logique corrigée !

  Compréhension mise à jour :
  - 5 périodes d'évaluation (Séq1, Séq2, Compo1, Séq3, Séq4, Compo2, Compo3)
  - Calculs spécifiques : DS = moyenne 2 séquences, Trimestre = (DS + Composition)/2
  - Trimestre 3 = Composition seule (pas de séquences)
  - Séquences 2 et 4 = Saisie notes uniquement (pas de bulletin)
