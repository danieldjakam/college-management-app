-- ========================================
  -- SCRIPT DE CORRECTION PRODUCTION
  -- ========================================

  START TRANSACTION;

  -- ÉTAPE 1: Transférer les 6 notes uniques
  INSERT INTO grades (student_id, evaluation_id, sequence_id,
   trimester_id, school_year_id, class_series_subject_id,
  score, max_score, coefficient, is_absent, is_excused,
  comment, created_at, updated_at)
  SELECT student_id, 673, sequence_id, trimester_id,
  school_year_id, class_series_subject_id, score, max_score,
  coefficient, is_absent, is_excused, comment, NOW(), NOW()
  FROM grades WHERE evaluation_id = 5 AND student_id IN (752,
   902, 927)
  ON DUPLICATE KEY UPDATE updated_at = NOW();

  INSERT INTO grades (student_id, evaluation_id, sequence_id,
   trimester_id, school_year_id, class_series_subject_id,
  score, max_score, coefficient, is_absent, is_excused,
  comment, created_at, updated_at)
  SELECT student_id, 506, sequence_id, trimester_id,
  school_year_id, class_series_subject_id, score, max_score,
  coefficient, is_absent, is_excused, comment, NOW(), NOW()
  FROM grades WHERE evaluation_id = 11 AND student_id = 927
  ON DUPLICATE KEY UPDATE updated_at = NOW();

  INSERT INTO grades (student_id, evaluation_id, sequence_id,
   trimester_id, school_year_id, class_series_subject_id,
  score, max_score, coefficient, is_absent, is_excused,
  comment, created_at, updated_at)
  SELECT student_id, 57, sequence_id, trimester_id,
  school_year_id, class_series_subject_id, score, max_score,
  coefficient, is_absent, is_excused, comment, NOW(), NOW()
  FROM grades WHERE evaluation_id = 12 AND student_id = 680
  ON DUPLICATE KEY UPDATE updated_at = NOW();

  INSERT INTO grades (student_id, evaluation_id, sequence_id,
   trimester_id, school_year_id, class_series_subject_id,
  score, max_score, coefficient, is_absent, is_excused,
  comment, created_at, updated_at)
  SELECT student_id, 21, sequence_id, trimester_id,
  school_year_id, class_series_subject_id, score, max_score,
  coefficient, is_absent, is_excused, comment, NOW(), NOW()
  FROM grades WHERE evaluation_id = 14 AND student_id = 680
  ON DUPLICATE KEY UPDATE updated_at = NOW();

  -- ÉTAPE 2: Supprimer les 4 évaluations en conflit
  DELETE FROM grades WHERE evaluation_id IN (5, 11, 12, 14);
  DELETE FROM evaluations WHERE id IN (5, 11, 12, 14);

  -- ÉTAPE 3: Réassigner les 9 évaluations restantes
  UPDATE evaluations SET teacher_id = 140 WHERE id IN (2, 3,
  4, 6, 7, 8);
  UPDATE evaluations SET teacher_id = 132 WHERE id IN (9,
  10);
  UPDATE evaluations SET teacher_id = 31 WHERE id = 13;

  -- Vérification
  SELECT 'Evaluations restantes pour teacher_id 105:',
  COUNT(*) FROM evaluations WHERE teacher_id = 105;

  COMMIT;
