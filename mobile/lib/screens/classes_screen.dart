import 'package:flutter/material.dart';
import '../models/assignment.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../utils/constants.dart';
import '../widgets/app_drawer.dart';
import 'competences_screen.dart';

class ClassesScreen extends StatefulWidget {
  const ClassesScreen({super.key});

  @override
  State<ClassesScreen> createState() => _ClassesScreenState();
}

class _ClassesScreenState extends State<ClassesScreen> {
  final ApiService _apiService = ApiService();
  final StorageService _storageService = StorageService();

  List<ClassInfo> _classes = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadClasses();
  }

  Future<void> _loadClasses() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final teacherId = await _storageService.getTeacherId();

      if (teacherId == null) {
        throw Exception('ID enseignant manquant');
      }

      final endpoint = '${ApiConstants.teacherAssignmentsEndpoint}/$teacherId';
      final response = await _apiService.get(endpoint);

      if (response['success'] == true) {
        final data = response['data'];
        final assignmentsList = data['assignments'] as List;

        final assignments = assignmentsList
            .map((json) => TeacherAssignment.fromJson(json))
            .toList();

        // Regrouper les affectations par classe
        _classes = _groupAssignmentsByClass(assignments);

        setState(() {
          _isLoading = false;
        });
      } else {
        throw Exception(response['message'] ?? 'Erreur de chargement');
      }
    } catch (e) {
      setState(() {
        _error = e.toString().replaceAll('Exception: ', '');
        _isLoading = false;
      });
    }
  }

  List<ClassInfo> _groupAssignmentsByClass(List<TeacherAssignment> assignments) {
    final Map<int, ClassInfo> classesMap = {};

    for (var assignment in assignments) {
      final classSeries = assignment.seriesSubject?.classSeries;
      if (classSeries == null) continue;

      final classId = classSeries.id;

      if (!classesMap.containsKey(classId)) {
        classesMap[classId] = ClassInfo(
          id: classId,
          name: classSeries.name,
          schoolClass: classSeries.schoolClass,
          subjects: [],
        );
      }

      // Ajouter la matière à cette classe
      final subject = assignment.seriesSubject?.subject;
      if (subject != null) {
        classesMap[classId]!.subjects.add(SubjectInfo(
          name: subject.name,
          coefficient: assignment.coefficient,
        ));
      }
    }

    final classList = classesMap.values.toList();
    // Trier par nom de classe
    classList.sort((a, b) => a.name.compareTo(b.name));

    return classList;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Classes'),
      ),
      drawer: const AppDrawer(currentRoute: '/classes'),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
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
                          onPressed: _loadClasses,
                          icon: const Icon(Icons.refresh),
                          label: const Text('Réessayer'),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadClasses,
                  child: _classes.isEmpty
                      ? const Center(
                          child: Text(
                            'Aucune classe assignée',
                            style: TextStyle(color: Colors.grey, fontSize: 16),
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _classes.length,
                          itemBuilder: (context, index) {
                            final classInfo = _classes[index];
                            return _buildClassCard(classInfo);
                          },
                        ),
                ),
    );
  }

  Widget _buildClassCard(ClassInfo classInfo) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 2,
      child: ExpansionTile(
        leading: CircleAvatar(
          backgroundColor: const Color(AppColors.primaryColor),
          child: Text(
            classInfo.subjects.length.toString(),
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
        title: Text(
          classInfo.name,
          style: const TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 18,
          ),
        ),
        subtitle: Text(
          '${classInfo.subjects.length} matière${classInfo.subjects.length > 1 ? 's' : ''}',
          style: TextStyle(color: Colors.grey[600]),
        ),
        children: [
          const Divider(height: 1),
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: classInfo.subjects.length,
            itemBuilder: (context, index) {
              final subject = classInfo.subjects[index];
              return ListTile(
                leading: const Icon(
                  Icons.book,
                  color: Color(AppColors.secondaryColor),
                ),
                title: Text(subject.name),
                trailing: subject.coefficient != null
                    ? Chip(
                        label: Text(
                          'Coef: ${subject.coefficient}',
                          style: const TextStyle(fontSize: 12),
                        ),
                        backgroundColor: Colors.blue[50],
                      )
                    : null,
              );
            },
          ),
          const Divider(height: 1),
          Padding(
            padding: const EdgeInsets.all(16),
            child: ElevatedButton.icon(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => CompetencesScreen(
                      classSeriesId: classInfo.id,
                      className: classInfo.name,
                    ),
                  ),
                );
              },
              icon: const Icon(Icons.assignment),
              label: const Text('Gérer les compétences'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.purple,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// Modèles pour regrouper les données
class ClassInfo {
  final int id;
  final String name;
  final SchoolClass? schoolClass;
  final List<SubjectInfo> subjects;

  ClassInfo({
    required this.id,
    required this.name,
    this.schoolClass,
    required this.subjects,
  });
}

class SubjectInfo {
  final String name;
  final double? coefficient;

  SubjectInfo({
    required this.name,
    this.coefficient,
  });
}
