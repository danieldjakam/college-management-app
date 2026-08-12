// ==========================================
// Types pour les données du bulletin
// ==========================================

export interface Student {
  id: number;
  first_name: string;
  last_name: string;
  matricule: string;
  date_of_birth: string;
  gender: string;
  lieu_naissance?: string;
  photo?: string;
  redoublant?: boolean;
}

export interface SubjectGrade {
  subject_id: number;
  subject_name: string;
  subject_code?: string;
  coefficient: number;
  group: "A" | "B" | "C" | "D";
  // Séquences individuelles (deuxième cycle)
  seq1?: number | null;
  seq2?: number | null;
  // DS moyen (premier cycle)
  ds_average?: number | null;
  // Composition
  composition?: number | null;
  // Moyenne sur 20
  average_on_20: number | null;
  // Moyenne pondérée (average_on_20 * coefficient)
  weighted_average: number | null;
  // Rang dans la matière
  subject_rank?: number | null;
  // Appréciation
  appreciation?: string;
  // Enseignant
  teacher_name?: string;
  // Absent?
  is_absent?: boolean;
  // Min/Max/Moyenne de la classe pour cette matière
  class_min?: number | null;
  class_max?: number | null;
  class_avg?: number | null;
}

export interface SubjectGroup {
  code: "A" | "B" | "C" | "D";
  name: string;
  name_en?: string;
  header: string;
  header_en?: string;
  subjects: SubjectGrade[];
  group_total_coef: number;
  group_total_weighted: number;
  group_average: number | null;
}

export interface StudentBulletinData {
  student: Student;
  class_name: string;
  class_series_name?: string;
  section_name?: string;
  cycle_type: "premier" | "deuxieme";
  period_type: "sequence" | "trimester" | "annual";
  period_label: string;
  school_year: string;
  trimester_number?: number;
  sequence_number?: number;
  // Subject groups with grades
  groups: SubjectGroup[];
  // General stats
  total_coefficient: number;
  total_weighted: number;
  general_average: number | null;
  rank: number | null;
  total_students: number;
  // Class stats
  class_average: number | null;
  class_min: number | null;
  class_max: number | null;
  pass_rate: number | null;
  // Appreciations
  general_appreciation?: string;
  conseil_de_classe?: string;
  // Main teacher
  main_teacher_name?: string;
  // Profile/conduct
  absences_total?: number;
  absences_justified?: number;
  conduct_note?: number;
}

export interface BulletinGenerateRequest {
  class_id: number;
  period_type: "sequence" | "trimester" | "annual";
  period_identifier: string; // "seq1", "trim1", "annual"
  template?: string; // "francophone_apc" | "deuxieme_cycle" | "anglophone"
}

export interface BulletinGenerateSingleRequest {
  student_id: number;
  period_type: "sequence" | "trimester" | "annual";
  period_identifier: string;
  template?: string;
}

// Logo and school info
export interface SchoolInfo {
  name: string;
  name_en?: string;
  address?: string;
  phone?: string;
  email?: string;
  logo_base64?: string;
  motto_fr?: string;
  motto_en?: string;
  bp?: string;
}
