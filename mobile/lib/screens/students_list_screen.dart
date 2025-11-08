import 'package:flutter/material.dart';
import '../models/assignment.dart';
import '../models/student.dart';
import '../services/api_service.dart';
import '../utils/constants.dart';

class StudentsListScreen extends StatefulWidget {
  final TeacherAssignment assignment;

  const StudentsListScreen({
    super.key,
    required this.assignment,
  });

  @override
  State<StudentsListScreen> createState() => _StudentsListScreenState();
}

class _StudentsListScreenState extends State<StudentsListScreen> {
  final ApiService _apiService = ApiService();

  List<Student> _students = [];
  List<Student> _filteredStudents = [];
  bool _isLoading = true;
  String? _error;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _loadStudents();
  }

  Future<void> _loadStudents() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final classSeriesId = widget.assignment.seriesSubject?.classSeries?.id;

      if (classSeriesId == null) {
        throw Exception('ID de la série de classe manquant');
      }

      print('📚 Loading students for class series: $classSeriesId');

      final endpoint = '${ApiConstants.studentsEndpoint}/$classSeriesId';
      print('📡 Fetching students from: ${ApiConstants.baseUrl}$endpoint');

      final response = await _apiService.get(endpoint);

      print('✅ Response received: $response');

      if (response['success'] == true) {
        final data = response['data'];
        print('📦 Data received: $data');

        final studentsList = data['students'] as List;
        print('👥 Students count: ${studentsList.length}');

        setState(() {
          _students = studentsList
              .map((json) => Student.fromJson(json))
              .toList();

          // Tri alphabétique par nom complet (NOM Prénom)
          _students.sort((a, b) => a.displayName.toLowerCase().compareTo(b.displayName.toLowerCase()));

          _filteredStudents = _students;
          _isLoading = false;
        });

        print('✅ Students loaded successfully');
      } else {
        final errorMsg = response['message'] ?? 'Erreur de chargement';
        print('❌ API returned error: $errorMsg');
        throw Exception(errorMsg);
      }
    } catch (e) {
      print('❌ Students load error: $e');

      String errorMessage = e.toString();
      errorMessage = errorMessage.replaceAll('Exception: ', '');
      if (errorMessage.length > 200) {
        errorMessage = errorMessage.substring(0, 200) + '...';
      }

      setState(() {
        _error = errorMessage;
        _isLoading = false;
      });
    }
  }

  void _filterStudents(String query) {
    setState(() {
      _searchQuery = query;
      if (query.isEmpty) {
        _filteredStudents = _students;
      } else {
        _filteredStudents = _students.where((student) {
          final fullName = student.displayName.toLowerCase();
          final matricule = student.displayMatricule.toLowerCase();
          final searchLower = query.toLowerCase();
          return fullName.contains(searchLower) || matricule.contains(searchLower);
        }).toList();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Liste des élèves'),
            Text(
              widget.assignment.className,
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
      ),
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
                          onPressed: _loadStudents,
                          icon: const Icon(Icons.refresh),
                          label: const Text(AppStrings.retry),
                        ),
                      ],
                    ),
                  ),
                )
              : Column(
                  children: [
                    // Search bar
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: TextField(
                        decoration: InputDecoration(
                          hintText: 'Rechercher un élève...',
                          prefixIcon: const Icon(Icons.search),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          filled: true,
                          fillColor: Colors.grey[100],
                        ),
                        onChanged: _filterStudents,
                      ),
                    ),

                    // Statistics
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceAround,
                            children: [
                              _buildStatItem(
                                icon: Icons.people,
                                label: 'Total',
                                value: _students.length.toString(),
                                color: const Color(AppColors.primaryColor),
                              ),
                              Container(
                                height: 40,
                                width: 1,
                                color: Colors.grey[300],
                              ),
                              _buildStatItem(
                                icon: Icons.search,
                                label: 'Affichés',
                                value: _filteredStudents.length.toString(),
                                color: const Color(AppColors.secondaryColor),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Students list
                    Expanded(
                      child: _filteredStudents.isEmpty
                          ? Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    Icons.people_outline,
                                    size: 64,
                                    color: Colors.grey[400],
                                  ),
                                  const SizedBox(height: 16),
                                  Text(
                                    _searchQuery.isEmpty
                                        ? 'Aucun élève dans cette classe'
                                        : 'Aucun élève trouvé',
                                    style: TextStyle(
                                      color: Colors.grey[600],
                                      fontSize: 16,
                                    ),
                                  ),
                                ],
                              ),
                            )
                          : RefreshIndicator(
                              onRefresh: _loadStudents,
                              child: ListView.builder(
                                padding: const EdgeInsets.symmetric(horizontal: 16),
                                itemCount: _filteredStudents.length,
                                itemBuilder: (context, index) {
                                  final student = _filteredStudents[index];
                                  return _buildStudentCard(student, index + 1);
                                },
                              ),
                            ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildStatItem({
    required IconData icon,
    required String label,
    required String value,
    required Color color,
  }) {
    return Column(
      children: [
        Icon(icon, size: 28, color: color),
        const SizedBox(height: 8),
        Text(
          value,
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: Colors.grey[600],
          ),
        ),
      ],
    );
  }

  Widget _buildStudentCard(Student student, int position) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: const Color(AppColors.primaryColor),
          child: student.photoUrl != null
              ? ClipOval(
                  child: Image.network(
                    student.photoUrl!,
                    fit: BoxFit.cover,
                    width: 40,
                    height: 40,
                    errorBuilder: (context, error, stackTrace) {
                      return Text(
                        student.firstName[0].toUpperCase(),
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                        ),
                      );
                    },
                  ),
                )
              : Text(
                  student.firstName[0].toUpperCase(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
        ),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: Colors.grey[200],
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                '#$position',
                style: TextStyle(
                  fontSize: 10,
                  color: Colors.grey[700],
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                student.displayName,
                style: const TextStyle(fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
        subtitle: Text('Matricule: ${student.displayMatricule}'),
        trailing: const Icon(Icons.arrow_forward_ios, size: 16),
        onTap: () {
          // TODO: Naviguer vers les détails de l'élève
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Détails de ${student.displayName} - À implémenter'),
            ),
          );
        },
      ),
    );
  }
}
