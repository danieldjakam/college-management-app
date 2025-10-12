import 'package:flutter/material.dart';
import '../models/assignment.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import '../services/storage_service.dart';
import '../utils/constants.dart';
import '../widgets/app_drawer.dart';
import 'login_screen.dart';
import 'subject_detail_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final ApiService _apiService = ApiService();
  final AuthService _authService = AuthService();
  final StorageService _storageService = StorageService();

  List<TeacherAssignment> _assignments = [];
  bool _isLoading = true;
  String? _error;
  String _teacherName = '';
  int? _teacherId;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      // Charger le nom de l'enseignant
      final teacherName = await _storageService.getTeacherName();
      final teacherId = await _storageService.getTeacherId();

      print('📊 Loading dashboard data...');
      print('👤 Teacher name: $teacherName');
      print('🆔 Teacher ID: $teacherId');

      if (teacherId == null) {
        throw Exception('ID enseignant manquant');
      }

      // Sauvegarder pour affichage dans l'écran d'erreur
      _teacherId = teacherId;

      // Charger les affectations
      final endpoint = '${ApiConstants.teacherAssignmentsEndpoint}/$teacherId';
      print('📡 Fetching assignments from: ${ApiConstants.baseUrl}$endpoint');

      final response = await _apiService.get(endpoint);

      print('✅ Response received: $response');

      if (response['success'] == true) {
        final data = response['data'];
        print('📦 Data received: $data');

        final assignmentsList = data['assignments'] as List;
        print('📚 Assignments count: ${assignmentsList.length}');

        setState(() {
          _teacherName = teacherName ?? 'Enseignant';
          _assignments = assignmentsList
              .map((json) => TeacherAssignment.fromJson(json))
              .toList();
          _isLoading = false;
        });

        print('✅ Dashboard loaded successfully');
      } else {
        final errorMsg = response['message'] ?? 'Erreur de chargement';
        print('❌ API returned error: $errorMsg');
        throw Exception(errorMsg);
      }
    } catch (e) {
      print('❌ Dashboard load error: $e');

      // Extraire un message d'erreur plus court et plus lisible
      String errorMessage = e.toString();
      if (errorMessage.contains('Erreur serveur') || errorMessage.contains('<!DOCTYPE html>')) {
        errorMessage = 'Le serveur backend a rencontré une erreur. Veuillez contacter l\'administrateur.';
      } else {
        errorMessage = errorMessage.replaceAll('Exception: ', '');
        // Limiter la longueur du message d'erreur
        if (errorMessage.length > 200) {
          errorMessage = errorMessage.substring(0, 200) + '...';
        }
      }

      setState(() {
        _error = errorMessage;
        _isLoading = false;
      });
    }
  }

  Future<void> _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Voulez-vous vraiment vous déconnecter ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Déconnexion'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await _authService.logout();
      if (!mounted) return;

      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const LoginScreen()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.dashboard),
      ),
      drawer: const AppDrawer(currentRoute: '/dashboard'),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.error_outline,
                          size: 64,
                          color: Colors.red[300],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: Colors.red),
                        ),
                        const SizedBox(height: 24),
                        ElevatedButton.icon(
                          onPressed: _loadData,
                          icon: const Icon(Icons.refresh),
                          label: const Text(AppStrings.retry),
                        ),
                        const SizedBox(height: 48),
                        const Divider(),
                        const SizedBox(height: 16),
                        Text(
                          'Informations de diagnostic',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 16),
                        Card(
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('Endpoint API:', style: const TextStyle(fontWeight: FontWeight.bold)),
                                Text('${ApiConstants.baseUrl}${ApiConstants.teacherAssignmentsEndpoint}/$_teacherId', style: const TextStyle(fontSize: 12)),
                                const SizedBox(height: 8),
                                Text('Teacher ID:', style: const TextStyle(fontWeight: FontWeight.bold)),
                                Text('$_teacherId'),
                                const SizedBox(height: 8),
                                Text('Solution suggérée:', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.blue)),
                                const Text('Vérifiez que le serveur backend est configuré correctement et que l\'année scolaire active est définie dans la base de données.', style: TextStyle(fontSize: 12)),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadData,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        // En-tête avec info enseignant
                        Container(
                          padding: const EdgeInsets.all(24),
                          decoration: BoxDecoration(
                            color: Theme.of(context).primaryColor,
                            borderRadius: const BorderRadius.only(
                              bottomLeft: Radius.circular(24),
                              bottomRight: Radius.circular(24),
                            ),
                          ),
                          child: Column(
                            children: [
                              const CircleAvatar(
                                radius: 40,
                                backgroundColor: Colors.white,
                                child: Icon(
                                  Icons.person,
                                  size: 48,
                                  color: Color(AppColors.primaryColor),
                                ),
                              ),
                              const SizedBox(height: 16),
                              Text(
                                _teacherName,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Enseignant',
                                style: TextStyle(
                                  color: Colors.white.withOpacity(0.9),
                                  fontSize: 16,
                                ),
                              ),
                            ],
                          ),
                        ),

                        // Statistiques
                        Padding(
                          padding: const EdgeInsets.all(16),
                          child: Row(
                            children: [
                              Expanded(
                                child: _buildStatCard(
                                  'Matières',
                                  _assignments.length.toString(),
                                  Icons.book,
                                  const Color(AppColors.primaryColor),
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: _buildStatCard(
                                  'Classes',
                                  _getUniqueClassesCount().toString(),
                                  Icons.class_,
                                  const Color(AppColors.successColor),
                                ),
                              ),
                            ],
                          ),
                        ),

                        // Liste des matières
                        Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                AppStrings.mySubjects,
                                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                      fontWeight: FontWeight.bold,
                                    ),
                              ),
                              const SizedBox(height: 16),
                              if (_assignments.isEmpty)
                                const Center(
                                  child: Padding(
                                    padding: EdgeInsets.all(32),
                                    child: Text(
                                      'Aucune matière assignée',
                                      style: TextStyle(color: Colors.grey),
                                    ),
                                  ),
                                )
                              else
                                ..._assignments.map((assignment) => _buildSubjectCard(assignment)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Icon(icon, size: 32, color: color),
            const SizedBox(height: 8),
            Text(
              value,
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            Text(
              title,
              style: const TextStyle(color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSubjectCard(TeacherAssignment assignment) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: const Color(AppColors.primaryColor),
          child: const Icon(Icons.subject, color: Colors.white),
        ),
        title: Text(
          assignment.subjectName,
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Classe: ${assignment.className}'),
            if (assignment.coefficient != null)
              Text('Coefficient: ${assignment.coefficient}'),
          ],
        ),
        trailing: const Icon(Icons.arrow_forward_ios, size: 16),
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (context) => SubjectDetailScreen(assignment: assignment),
            ),
          );
        },
      ),
    );
  }

  int _getUniqueClassesCount() {
    final classIds = <int>{};
    for (var assignment in _assignments) {
      final classId = assignment.seriesSubject?.classSeries?.schoolClass?.id;
      if (classId != null) {
        classIds.add(classId);
      }
    }
    return classIds.length;
  }
}
