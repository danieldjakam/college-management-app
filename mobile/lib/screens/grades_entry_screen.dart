import 'package:flutter/material.dart';
import '../models/evaluation.dart';
import '../models/student.dart';
import '../models/grade.dart';
import '../models/assignment.dart';
import '../services/api_service.dart';
import '../utils/constants.dart';

class GradesEntryScreen extends StatefulWidget {
  final Evaluation evaluation;
  final TeacherAssignment assignment;

  const GradesEntryScreen({
    super.key,
    required this.evaluation,
    required this.assignment,
  });

  @override
  State<GradesEntryScreen> createState() => _GradesEntryScreenState();
}

class _GradesEntryScreenState extends State<GradesEntryScreen> {
  final ApiService _apiService = ApiService();

  List<Student> _students = [];
  Map<int, Grade> _grades = {}; // studentId -> Grade
  Map<int, TextEditingController> _controllers = {}; // studentId -> Controller
  bool _isLoading = true;
  bool _isSaving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    // Dispose all controllers
    for (var controller in _controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final classSeriesId = widget.assignment.seriesSubject?.classSeries?.id;

      if (classSeriesId == null) {
        throw Exception('ID de la série de classe manquant');
      }

      print('📚 Loading students and grades for evaluation: ${widget.evaluation.id}');

      // Charger les élèves
      final studentsEndpoint = '${ApiConstants.studentsEndpoint}/$classSeriesId';
      final studentsResponse = await _apiService.get(studentsEndpoint);

      if (studentsResponse['success'] == true) {
        final studentsData = studentsResponse['data'];
        final studentsList = studentsData['students'] as List;

        // Charger les notes existantes
        final gradesEndpoint = '${ApiConstants.gradesEndpoint}/evaluation/${widget.evaluation.id}';
        final gradesResponse = await _apiService.get(gradesEndpoint);

        Map<int, Grade> gradesMap = {};
        if (gradesResponse['success'] == true) {
          final gradesData = gradesResponse['data'];
          final gradesList = gradesData['grades'] as List? ?? [];

          for (var item in gradesList) {
            // L'API retourne {student_id, student: {...}, grade: {...} ou null}
            final studentId = item['student_id'] as int;
            final gradeData = item['grade'];

            if (gradeData != null) {
              // Ajouter les champs manquants pour Grade.fromJson
              gradeData['student_id'] = studentId;
              gradeData['evaluation_id'] = widget.evaluation.id;

              final grade = Grade.fromJson(gradeData);
              gradesMap[studentId] = grade;
            }
          }
        }

        setState(() {
          _students = studentsList
              .map((json) => Student.fromJson(json))
              .toList();
          _grades = gradesMap;

          // Initialiser les controllers pour chaque élève
          for (var student in _students) {
            final grade = _grades[student.id];
            _controllers[student.id] = TextEditingController(
              text: grade?.score?.toString() ?? '',
            );
          }

          _isLoading = false;
        });

        print('✅ Loaded ${_students.length} students and ${_grades.length} grades');
      } else {
        throw Exception(studentsResponse['message'] ?? 'Erreur de chargement');
      }
    } catch (e) {
      print('❌ Load error: $e');

      String errorMessage = e.toString();
      errorMessage = errorMessage.replaceAll('Exception: ', '');

      setState(() {
        _error = errorMessage;
        _isLoading = false;
      });
    }
  }

  Future<void> _saveGrades() async {
    setState(() {
      _isSaving = true;
    });

    try {
      List<Map<String, dynamic>> gradesToSave = [];

      for (var student in _students) {
        final controller = _controllers[student.id]!;
        final existingGrade = _grades[student.id];

        final scoreText = controller.text.trim();
        final isAbsent = existingGrade?.isAbsent ?? false;
        final isExcused = existingGrade?.isExcused ?? false;

        if (scoreText.isNotEmpty || isAbsent || isExcused) {
          double? score;
          if (!isAbsent && !isExcused && scoreText.isNotEmpty) {
            score = double.tryParse(scoreText);
            if (score == null) {
              throw Exception('Note invalide pour ${student.displayName}');
            }
            if (score < 0 || score > widget.evaluation.maxScore) {
              throw Exception('Note hors limite pour ${student.displayName} (max: ${widget.evaluation.maxScore})');
            }
          }

          gradesToSave.add({
            if (existingGrade?.id != null) 'id': existingGrade!.id,
            'student_id': student.id,
            'score': score,
            'is_absent': isAbsent,
            'is_excused': isExcused,
          });
        }
      }

      print('💾 Saving ${gradesToSave.length} grades');

      // Envoyer au backend (bulk save)
      final response = await _apiService.post(
        '${ApiConstants.gradesEndpoint}/bulk',
        {
          'evaluation_id': widget.evaluation.id,
          'grades': gradesToSave,
        },
      );

      if (response['success'] == true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Notes enregistrées avec succès'),
              backgroundColor: Colors.green,
            ),
          );
        }

        // Recharger les données
        await _loadData();
      } else {
        throw Exception(response['message'] ?? 'Erreur lors de l\'enregistrement');
      }
    } catch (e) {
      print('❌ Save error: $e');

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur: ${e.toString().replaceAll('Exception: ', '')}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSaving = false;
        });
      }
    }
  }

  void _toggleAbsent(Student student) {
    setState(() {
      final currentGrade = _grades[student.id];
      _grades[student.id] = Grade(
        id: currentGrade?.id,
        evaluationId: widget.evaluation.id,
        studentId: student.id,
        isAbsent: !(currentGrade?.isAbsent ?? false),
        isExcused: false,
        student: student,
      );

      if (_grades[student.id]!.isAbsent) {
        _controllers[student.id]!.clear();
      }
    });
  }

  void _toggleExcused(Student student) {
    setState(() {
      final currentGrade = _grades[student.id];
      _grades[student.id] = Grade(
        id: currentGrade?.id,
        evaluationId: widget.evaluation.id,
        studentId: student.id,
        isAbsent: false,
        isExcused: !(currentGrade?.isExcused ?? false),
        student: student,
      );

      if (_grades[student.id]!.isExcused) {
        _controllers[student.id]!.clear();
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
            const Text('Saisie des notes'),
            Text(
              widget.evaluation.name,
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
        actions: [
          if (!_isLoading && !_isSaving)
            IconButton(
              icon: const Icon(Icons.save),
              onPressed: _saveGrades,
              tooltip: 'Enregistrer',
            ),
          if (_isSaving)
            const Padding(
              padding: EdgeInsets.all(16),
              child: SizedBox(
                width: 24,
                height: 24,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                ),
              ),
            ),
        ],
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
                          onPressed: _loadData,
                          icon: const Icon(Icons.refresh),
                          label: const Text(AppStrings.retry),
                        ),
                      ],
                    ),
                  ),
                )
              : Column(
                  children: [
                    // Info card
                    Container(
                      padding: const EdgeInsets.all(16),
                      color: Theme.of(context).primaryColor.withValues(alpha: 0.1),
                      child: Row(
                        children: [
                          Expanded(
                            flex: 2,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  widget.assignment.subjectName,
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                                Text(
                                  widget.assignment.className,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          Flexible(
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 6,
                              ),
                              decoration: BoxDecoration(
                                color: Theme.of(context).primaryColor,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                'Note /${widget.evaluation.maxScore}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 13,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    // Stats
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          _buildStatItem(
                            'Total',
                            _students.length.toString(),
                            Icons.people,
                            Colors.blue,
                          ),
                          _buildStatItem(
                            'Notés',
                            _getGradedCount().toString(),
                            Icons.edit,
                            Colors.green,
                          ),
                          _buildStatItem(
                            'Absents',
                            _getAbsentCount().toString(),
                            Icons.event_busy,
                            Colors.orange,
                          ),
                          _buildStatItem(
                            'Dispensés',
                            _getExcusedCount().toString(),
                            Icons.cancel,
                            Colors.purple,
                          ),
                        ],
                      ),
                    ),

                    const Divider(height: 1),

                    // Students list
                    Expanded(
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _students.length,
                        itemBuilder: (context, index) {
                          final student = _students[index];
                          final grade = _grades[student.id];
                          final controller = _controllers[student.id]!;

                          return _buildStudentGradeCard(
                            student,
                            grade,
                            controller,
                            index + 1,
                          );
                        },
                      ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildStatItem(String label, String value, IconData icon, Color color) {
    return Column(
      children: [
        Icon(icon, size: 24, color: color),
        const SizedBox(height: 4),
        Text(
          value,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: const TextStyle(fontSize: 12),
        ),
      ],
    );
  }

  Widget _buildStudentGradeCard(
    Student student,
    Grade? grade,
    TextEditingController controller,
    int position,
  ) {
    final isAbsent = grade?.isAbsent ?? false;
    final isExcused = grade?.isExcused ?? false;
    final isDisabled = isAbsent || isExcused;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            // Position
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: Theme.of(context).primaryColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Center(
                child: Text(
                  '$position',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).primaryColor,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 12),

            // Student info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    student.displayName,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                  Text(
                    student.displayMatricule,
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ),

            // Grade input
            SizedBox(
              width: 80,
              child: TextField(
                controller: controller,
                enabled: !isDisabled,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                textAlign: TextAlign.center,
                decoration: InputDecoration(
                  hintText: isAbsent ? 'ABS' : isExcused ? 'DISP' : '0',
                  border: const OutlineInputBorder(),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 8,
                  ),
                ),
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),

            const SizedBox(width: 8),

            // Actions
            PopupMenuButton<String>(
              icon: const Icon(Icons.more_vert),
              onSelected: (value) {
                if (value == 'absent') {
                  _toggleAbsent(student);
                } else if (value == 'excused') {
                  _toggleExcused(student);
                }
              },
              itemBuilder: (context) => [
                PopupMenuItem(
                  value: 'absent',
                  child: Row(
                    children: [
                      Icon(
                        isAbsent ? Icons.check_box : Icons.check_box_outline_blank,
                        size: 20,
                      ),
                      const SizedBox(width: 8),
                      const Text('Absent'),
                    ],
                  ),
                ),
                PopupMenuItem(
                  value: 'excused',
                  child: Row(
                    children: [
                      Icon(
                        isExcused ? Icons.check_box : Icons.check_box_outline_blank,
                        size: 20,
                      ),
                      const SizedBox(width: 8),
                      const Text('Dispensé'),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  int _getGradedCount() {
    return _students.where((student) {
      final grade = _grades[student.id];
      final controller = _controllers[student.id]!;
      return controller.text.isNotEmpty &&
             !(grade?.isAbsent ?? false) &&
             !(grade?.isExcused ?? false);
    }).length;
  }

  int _getAbsentCount() {
    return _grades.values.where((g) => g.isAbsent).length;
  }

  int _getExcusedCount() {
    return _grades.values.where((g) => g.isExcused).length;
  }
}
