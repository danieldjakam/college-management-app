class ApiConstants {
  // URL de l'API backend
  // Production: 'http://admin1.cpb-douala.com/api'
  // Local iOS Simulator: 'http://127.0.0.1:8001/api'
  // Local Android Emulator: 'http://10.0.2.2:8001/api'
  // Local (MacOS/PC direct): 'http://192.168.x.x:8001/api' (votre IP locale)

  // ✅ MODE PRODUCTION ACTIVÉ
  // Backend production CPB Douala
  static const String baseUrl = 'http://admin1.cpb-douala.com/api';

  // Endpoints
  static const String loginEndpoint = '/auth/login';
  static const String logoutEndpoint = '/auth/logout';
  static const String changePasswordEndpoint = '/auth/change-password';
  static const String updateProfileEndpoint = '/auth/update-profile';
  static const String teacherAssignmentsEndpoint = '/teacher-assignments/teacher';
  static const String trimestersEndpoint = '/trimesters/teacher';
  static const String sequencesEndpoint = '/sequences';
  static const String studentsEndpoint = '/students/class-series';
  static const String evaluationsEndpoint = '/evaluations';
  static const String gradesEndpoint = '/grades';

  // Timeouts
  static const int connectionTimeout = 30000; // 30 secondes
  static const int receiveTimeout = 30000;
}

class AppColors {
  static const int primaryColor = 0xFF2196F3; // Bleu
  static const int secondaryColor = 0xFF4CAF50; // Vert
  static const int errorColor = 0xFFF44336; // Rouge
  static const int warningColor = 0xFFFF9800; // Orange
  static const int successColor = 0xFF4CAF50; // Vert
}

class StorageKeys {
  static const String authToken = 'auth_token';
  static const String userId = 'user_id';
  static const String userName = 'user_name';
  static const String userEmail = 'user_email';
  static const String userRole = 'user_role';
  static const String teacherId = 'teacher_id';
  static const String teacherName = 'teacher_name';
  static const String biometricEnabled = 'biometric_enabled';
  static const String biometricUsername = 'biometric_username';
  static const String biometricPassword = 'biometric_password';
}

class AppStrings {
  // App
  static const String appName = 'CPB Enseignant';
  static const String appVersion = '1.0.0';

  // Auth
  static const String login = 'Connexion';
  static const String logout = 'Déconnexion';
  static const String username = 'Nom d\'utilisateur';
  static const String password = 'Mot de passe';
  static const String loginSuccess = 'Connexion réussie';
  static const String loginError = 'Identifiants incorrects';

  // Dashboard
  static const String dashboard = 'Tableau de bord';
  static const String mySubjects = 'Mes matières';
  static const String myClasses = 'Mes classes';
  static const String statistics = 'Statistiques';

  // Trimesters
  static const String trimesters = 'Trimestres';
  static const String sequences = 'Séquences';
  static const String composition = 'Composition';

  // Grades
  static const String grades = 'Notes';
  static const String enterGrades = 'Saisir les notes';
  static const String createEvaluation = 'Créer une évaluation';
  static const String evaluationName = 'Nom de l\'évaluation';
  static const String maxGrade = 'Note maximale';
  static const String evaluationDate = 'Date de l\'évaluation';

  // Students
  static const String students = 'Élèves';
  static const String studentList = 'Liste des élèves';
  static const String absent = 'Absent';
  static const String excused = 'Dispensé';

  // Errors
  static const String errorOccurred = 'Une erreur est survenue';
  static const String noInternet = 'Pas de connexion internet';
  static const String serverError = 'Erreur serveur';
  static const String loadingError = 'Erreur de chargement';

  // Common
  static const String loading = 'Chargement...';
  static const String save = 'Enregistrer';
  static const String cancel = 'Annuler';
  static const String confirm = 'Confirmer';
  static const String delete = 'Supprimer';
  static const String edit = 'Modifier';
  static const String search = 'Rechercher';
  static const String filter = 'Filtrer';
  static const String noData = 'Aucune donnée';
  static const String retry = 'Réessayer';
}
