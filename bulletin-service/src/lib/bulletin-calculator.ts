import { SubjectGrade, SubjectGroup, StudentBulletinData } from "./types";

// ==========================================
// Logique de calcul des bulletins
// ==========================================
// TODO: L'utilisateur va fournir la logique de calcul.
// Pour l'instant, ce fichier contient la structure de base.
// La logique sera implémentée ici et les données brutes
// seront récupérées depuis Laravel.
// ==========================================

/**
 * Déterminer le type de cycle à partir du nom de classe
 */
export function determineCycleType(
  className: string
): "premier" | "deuxieme" {
  const lower = className.toLowerCase();
  const deuxiemeCycleKeywords = [
    "seconde",
    "2nde",
    "première",
    "1ère",
    "1ere",
    "terminale",
    "tle",
  ];

  for (const keyword of deuxiemeCycleKeywords) {
    if (lower.includes(keyword)) return "deuxieme";
  }

  return "premier";
}

/**
 * Calculer l'appréciation à partir d'une moyenne
 */
export function getAppreciation(average: number | null): string {
  if (average === null) return "";
  if (average >= 18) return "Excellent";
  if (average >= 16) return "Très Bien";
  if (average >= 14) return "Bien";
  if (average >= 12) return "Assez Bien";
  if (average >= 10) return "Passable";
  if (average >= 8) return "Insuffisant";
  if (average >= 6) return "Médiocre";
  return "Très Faible";
}

/**
 * Calculer la moyenne DS (Premier Cycle)
 * DS = (Séquence1 + Séquence2) / 2
 * Si une séquence manque et l'autre existe, la manquante = 0
 * Si les deux manquent, DS = null (coefficient annulé)
 */
export function calculateDS(
  seq1: number | null,
  seq2: number | null
): number | null {
  if (seq1 === null && seq2 === null) return null;
  return ((seq1 ?? 0) + (seq2 ?? 0)) / 2;
}

/**
 * Calculer la moyenne trimestrielle d'une matière (Premier Cycle)
 * M/20 = (DS + Composition) / 2
 * Trimestre 3: M/20 = Composition uniquement
 */
export function calculateTrimesterAveragePremier(
  ds: number | null,
  composition: number | null,
  trimesterNumber: number
): number | null {
  // Trimestre 3: composition uniquement
  if (trimesterNumber === 3) {
    return composition;
  }

  if (ds === null && composition === null) return null;
  return ((ds ?? 0) + (composition ?? 0)) / 2;
}

/**
 * Calculer la moyenne trimestrielle d'une matière (Deuxième Cycle)
 * M/20 = (Séquence1 + Séquence2 + Composition) / 3
 */
export function calculateTrimesterAverageDeuxieme(
  seq1: number | null,
  seq2: number | null,
  composition: number | null
): number | null {
  if (seq1 === null && seq2 === null && composition === null) return null;
  return ((seq1 ?? 0) + (seq2 ?? 0) + (composition ?? 0)) / 3;
}

/**
 * Calculer la moyenne générale pondérée
 * Moyenne = Σ(M/20 × Coef) / Σ(Coef)
 * Les matières sans notes sont exclues (coefficient annulé)
 */
export function calculateGeneralAverage(
  subjects: SubjectGrade[]
): { average: number | null; totalCoef: number; totalWeighted: number } {
  let totalCoef = 0;
  let totalWeighted = 0;

  for (const subject of subjects) {
    if (subject.average_on_20 !== null) {
      totalCoef += subject.coefficient;
      totalWeighted += subject.average_on_20 * subject.coefficient;
    }
  }

  if (totalCoef === 0) {
    return { average: null, totalCoef: 0, totalWeighted: 0 };
  }

  return {
    average: Math.round((totalWeighted / totalCoef) * 100) / 100,
    totalCoef,
    totalWeighted: Math.round(totalWeighted * 100) / 100,
  };
}

/**
 * Calculer les statistiques d'un groupe de matières
 */
export function calculateGroupStats(subjects: SubjectGrade[]): {
  totalCoef: number;
  totalWeighted: number;
  average: number | null;
} {
  let totalCoef = 0;
  let totalWeighted = 0;

  for (const subject of subjects) {
    if (subject.average_on_20 !== null) {
      totalCoef += subject.coefficient;
      totalWeighted += subject.average_on_20 * subject.coefficient;
    }
  }

  return {
    totalCoef,
    totalWeighted: Math.round(totalWeighted * 100) / 100,
    average:
      totalCoef > 0
        ? Math.round((totalWeighted / totalCoef) * 100) / 100
        : null,
  };
}

/**
 * Organiser les matières en groupes (A, B, C, D)
 */
export function organizeSubjectGroups(
  subjects: SubjectGrade[],
  groupDefinitions: {
    code: string;
    name: string;
    name_en?: string;
    header: string;
    header_en?: string;
  }[]
): SubjectGroup[] {
  const groups: SubjectGroup[] = [];

  for (const def of groupDefinitions) {
    const groupSubjects = subjects.filter(
      (s) => s.group === def.code
    );
    const stats = calculateGroupStats(groupSubjects);

    groups.push({
      code: def.code as "A" | "B" | "C" | "D",
      name: def.name,
      name_en: def.name_en,
      header: def.header,
      header_en: def.header_en,
      subjects: groupSubjects,
      group_total_coef: stats.totalCoef,
      group_total_weighted: stats.totalWeighted,
      group_average: stats.average,
    });
  }

  return groups;
}

/**
 * Calculer le rang d'un étudiant dans la classe
 */
export function calculateRank(
  studentAverage: number | null,
  allAverages: (number | null)[]
): number | null {
  if (studentAverage === null) return null;

  const validAverages = allAverages.filter(
    (a): a is number => a !== null
  );
  validAverages.sort((a, b) => b - a);

  return validAverages.indexOf(studentAverage) + 1;
}
