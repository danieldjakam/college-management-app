import 'package:flutter/material.dart';
import '../models/assignment.dart';
import '../models/evaluation.dart';
import '../services/api_service.dart';
import '../utils/constants.dart';
import 'grades_entry_screen.dart';

class EvaluationsScreen extends StatefulWidget {
  final TeacherAssignment assignment;

  const EvaluationsScreen({
    super.key,
    required this.assignment,
  });

  @override
  State<EvaluationsScreen> createState() => _EvaluationsScreenState();
}

class _EvaluationsScreenState extends State<EvaluationsScreen> {
  final ApiService _apiService = ApiService();

  List<Trimester> _trimesters = [];
  List<Map<String, dynamic>> _trimestersRawData = [];
  List<Sequence> _sequences = [];
  List<Evaluation> _evaluations = [];
  bool _isLoading = true;
  String? _error;
  int? _selectedTrimesterId;
  int? _selectedSequenceId;

  @override
  void initState() {
    super.initState();
    _loadTrimesters();
  }

  Future<void> _loadTrimesters() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      print('📚 Loading trimesters...');

      final endpoint = ApiConstants.trimestersEndpoint;
      print('📡 Fetching trimesters from: ${ApiConstants.baseUrl}$endpoint');

      final response = await _apiService.get(endpoint);

      print('✅ Response received: $response');

      if (response['success'] == true) {
        final data = response['data'];
        print('📦 Data received: $data');

        // L'API retourne directement un tableau dans 'data', pas un objet avec 'trimesters'
        final trimestersList = data as List;
        print('📅 Trimesters count: ${trimestersList.length}');

        setState(() {
          _trimestersRawData = List<Map<String, dynamic>>.from(trimestersList);
          _trimesters = trimestersList
              .map((json) => Trimester.fromJson(json))
              .toList();
          _isLoading = false;
        });

        print('✅ Trimesters loaded successfully');
      } else {
        final errorMsg = response['message'] ?? 'Erreur de chargement';
        print('❌ API returned error: $errorMsg');
        throw Exception(errorMsg);
      }
    } catch (e) {
      print('❌ Trimesters load error: $e');

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

  Future<void> _loadSequences(int trimesterId) async {
    setState(() {
      _isLoading = true;
      _error = null;
      _selectedTrimesterId = trimesterId;
      _sequences = [];
      _evaluations = [];
      _selectedSequenceId = null;
    });

    try {
      print('📚 Loading sequences for trimester: $trimesterId');

      // Les sequences sont déjà incluses dans les trimesters
      final trimesterData = _trimestersRawData.firstWhere((t) => t['id'] == trimesterId);

      if (trimesterData['sequences'] != null) {
        final sequencesList = trimesterData['sequences'] as List;
        print('📝 Sequences count: ${sequencesList.length}');

        setState(() {
          _sequences = sequencesList
              .map((json) => Sequence.fromJson(json))
              .toList();
          _isLoading = false;
        });

        print('✅ Sequences loaded successfully');
      } else {
        setState(() {
          _isLoading = false;
        });
      }
    } catch (e) {
      print('❌ Sequences load error: $e');

      String errorMessage = e.toString();
      errorMessage = errorMessage.replaceAll('Exception: ', '');

      setState(() {
        _error = errorMessage;
        _isLoading = false;
      });
    }
  }

  Future<void> _loadEvaluations(int sequenceId) async {
    setState(() {
      _isLoading = true;
      _error = null;
      _selectedSequenceId = sequenceId;
      _evaluations = [];
    });

    try {
      final classSeriesSubjectId = widget.assignment.classSeriesSubjectId;

      print('📚 Loading evaluations for sequence: $sequenceId, subject: $classSeriesSubjectId');

      final endpoint = '${ApiConstants.evaluationsEndpoint}?sequence_id=$sequenceId&series_subject_id=$classSeriesSubjectId';
      print('📡 Fetching evaluations from: ${ApiConstants.baseUrl}$endpoint');

      final response = await _apiService.get(endpoint);

      print('✅ Response received: $response');

      if (response['success'] == true) {
        final data = response['data'];
        print('📦 Data received: $data');

        // L'API retourne directement un tableau
        final evaluationsList = data is List ? data : (data['evaluations'] as List);
        print('📋 Evaluations count: ${evaluationsList.length}');

        setState(() {
          _evaluations = evaluationsList
              .map((json) => Evaluation.fromJson(json))
              .toList();
          _isLoading = false;
        });

        print('✅ Evaluations loaded successfully');
      } else {
        final errorMsg = response['message'] ?? 'Erreur de chargement';
        print('❌ API returned error: $errorMsg');
        throw Exception(errorMsg);
      }
    } catch (e) {
      print('❌ Evaluations load error: $e');

      String errorMessage = e.toString();
      errorMessage = errorMessage.replaceAll('Exception: ', '');

      setState(() {
        _error = errorMessage;
        _isLoading = false;
      });
    }
  }

  void _showCreateEvaluationDialog() {
    if (_selectedSequenceId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Veuillez sélectionner une séquence'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final nameController = TextEditingController();
    final maxGradeController = TextEditingController(text: '20');
    final descriptionController = TextEditingController();
    DateTime selectedDate = DateTime.now();
    String evaluationType = 'composition'; // Par défaut: composition

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: const Text('Créer une évaluation'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: nameController,
                  decoration: const InputDecoration(
                    labelText: 'Nom de l\'évaluation',
                    hintText: 'Ex: Devoir 1, Interrogation...',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: evaluationType,
                  decoration: const InputDecoration(
                    labelText: 'Type d\'évaluation',
                    border: OutlineInputBorder(),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'composition', child: Text('Composition')),
                    DropdownMenuItem(value: 'tp', child: Text('Travaux Pratiques (TP)')),
                  ],
                  onChanged: (value) {
                    if (value != null) {
                      setState(() {
                        evaluationType = value;
                      });
                    }
                  },
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: maxGradeController,
                  decoration: const InputDecoration(
                    labelText: 'Note maximale',
                    border: OutlineInputBorder(),
                  ),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: descriptionController,
                  decoration: const InputDecoration(
                    labelText: 'Description (optionnel)',
                    border: OutlineInputBorder(),
                  ),
                  maxLines: 2,
                ),
                const SizedBox(height: 16),
                ListTile(
                  title: const Text('Date'),
                  subtitle: Text(
                    '${selectedDate.day}/${selectedDate.month}/${selectedDate.year}',
                  ),
                  trailing: const Icon(Icons.calendar_today),
                  onTap: () async {
                    final date = await showDatePicker(
                      context: context,
                      initialDate: selectedDate,
                      firstDate: DateTime(2020),
                      lastDate: DateTime(2030),
                    );
                    if (date != null) {
                      setState(() {
                        selectedDate = date;
                      });
                    }
                  },
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () async {
                if (nameController.text.isEmpty) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Veuillez saisir un nom'),
                      backgroundColor: Colors.red,
                    ),
                  );
                  return;
                }

                final maxGrade = double.tryParse(maxGradeController.text);
                if (maxGrade == null || maxGrade <= 0) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Note maximale invalide'),
                      backgroundColor: Colors.red,
                    ),
                  );
                  return;
                }

                Navigator.pop(context);
                await _createEvaluation(
                  nameController.text,
                  evaluationType,
                  maxGrade,
                  selectedDate,
                  descriptionController.text.isEmpty ? null : descriptionController.text,
                );
              },
              child: const Text('Créer'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _createEvaluation(
    String name,
    String type,
    double maxGrade,
    DateTime date,
    String? description,
  ) async {
    try {
      final data = {
        'sequence_id': _selectedSequenceId,
        'series_subject_id': widget.assignment.classSeriesSubjectId,
        'teacher_id': widget.assignment.teacherId,
        'name': name,
        'type': type,
        'max_score': maxGrade,
        'date': date.toIso8601String().split('T')[0],
        'description': description,
      };

      print('📝 Creating evaluation: $data');

      final response = await _apiService.post(ApiConstants.evaluationsEndpoint, data);

      if (response['success'] == true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Évaluation créée avec succès'),
              backgroundColor: Colors.green,
            ),
          );
        }

        // Recharger les évaluations
        _loadEvaluations(_selectedSequenceId!);
      } else {
        throw Exception(response['message'] ?? 'Erreur lors de la création');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Évaluations'),
            Text(
              '${widget.assignment.subjectName} - ${widget.assignment.className}',
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
      ),
      floatingActionButton: _selectedSequenceId != null
          ? FloatingActionButton.extended(
              onPressed: _showCreateEvaluationDialog,
              icon: const Icon(Icons.add),
              label: const Text('Nouvelle évaluation'),
            )
          : null,
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
                          onPressed: _loadTrimesters,
                          icon: const Icon(Icons.refresh),
                          label: const Text(AppStrings.retry),
                        ),
                      ],
                    ),
                  ),
                )
              : Column(
                  children: [
                    // Trimesters selection
                    Container(
                      padding: const EdgeInsets.all(16),
                      color: Theme.of(context).primaryColor.withValues(alpha: 0.1),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Sélectionnez un trimestre',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: _trimesters.map((trimester) {
                              final isSelected = _selectedTrimesterId == trimester.id;
                              return ChoiceChip(
                                label: Text(trimester.name),
                                selected: isSelected,
                                onSelected: (selected) {
                                  if (selected) {
                                    _loadSequences(trimester.id);
                                  }
                                },
                                selectedColor: Theme.of(context).primaryColor,
                                labelStyle: TextStyle(
                                  color: isSelected ? Colors.white : null,
                                  fontWeight: isSelected ? FontWeight.bold : null,
                                ),
                              );
                            }).toList(),
                          ),
                        ],
                      ),
                    ),

                    // Sequences selection
                    if (_sequences.isNotEmpty)
                      Container(
                        padding: const EdgeInsets.all(16),
                        color: Colors.grey[100],
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Sélectionnez une séquence',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 12),
                            Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: _sequences.map((sequence) {
                                final isSelected = _selectedSequenceId == sequence.id;
                                return ChoiceChip(
                                  label: Text(sequence.name),
                                  selected: isSelected,
                                  onSelected: (selected) {
                                    if (selected) {
                                      _loadEvaluations(sequence.id);
                                    }
                                  },
                                  selectedColor: const Color(AppColors.secondaryColor),
                                  labelStyle: TextStyle(
                                    color: isSelected ? Colors.white : null,
                                    fontWeight: isSelected ? FontWeight.bold : null,
                                  ),
                                );
                              }).toList(),
                            ),
                          ],
                        ),
                      ),

                    // Evaluations list
                    Expanded(
                      child: _selectedSequenceId == null
                          ? Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    Icons.assignment_outlined,
                                    size: 64,
                                    color: Colors.grey[400],
                                  ),
                                  const SizedBox(height: 16),
                                  Text(
                                    'Sélectionnez une séquence pour voir les évaluations',
                                    style: TextStyle(
                                      color: Colors.grey[600],
                                      fontSize: 16,
                                    ),
                                    textAlign: TextAlign.center,
                                  ),
                                ],
                              ),
                            )
                          : _evaluations.isEmpty
                              ? Center(
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                        Icons.assignment_outlined,
                                        size: 64,
                                        color: Colors.grey[400],
                                      ),
                                      const SizedBox(height: 16),
                                      Text(
                                        'Aucune évaluation pour cette séquence',
                                        style: TextStyle(
                                          color: Colors.grey[600],
                                          fontSize: 16,
                                        ),
                                      ),
                                      const SizedBox(height: 16),
                                      ElevatedButton.icon(
                                        onPressed: _showCreateEvaluationDialog,
                                        icon: const Icon(Icons.add),
                                        label: const Text('Créer une évaluation'),
                                      ),
                                    ],
                                  ),
                                )
                              : ListView.builder(
                                  padding: const EdgeInsets.all(16),
                                  itemCount: _evaluations.length,
                                  itemBuilder: (context, index) {
                                    final evaluation = _evaluations[index];
                                    return _buildEvaluationCard(evaluation);
                                  },
                                ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildEvaluationCard(Evaluation evaluation) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: const Color(AppColors.secondaryColor),
          child: const Icon(Icons.assignment, color: Colors.white),
        ),
        title: Text(
          evaluation.name,
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Note sur ${evaluation.maxScore}'),
            Text('Date: ${evaluation.formattedDate}'),
            if (evaluation.description != null)
              Text(
                evaluation.description!,
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey[600],
                  fontStyle: FontStyle.italic,
                ),
              ),
          ],
        ),
        trailing: const Icon(Icons.arrow_forward_ios, size: 16),
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (context) => GradesEntryScreen(
                evaluation: evaluation,
                assignment: widget.assignment,
              ),
            ),
          );
        },
      ),
    );
  }
}
