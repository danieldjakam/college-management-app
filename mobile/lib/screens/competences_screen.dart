import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../utils/constants.dart';

class CompetencesScreen extends StatefulWidget {
  final int classSeriesId;
  final String className;

  const CompetencesScreen({
    super.key,
    required this.classSeriesId,
    required this.className,
  });

  @override
  State<CompetencesScreen> createState() => _CompetencesScreenState();
}

class _CompetencesScreenState extends State<CompetencesScreen> {
  final ApiService _apiService = ApiService();

  List<Map<String, dynamic>> _subjects = [];
  List<Map<String, dynamic>> _trimesters = [];
  bool _isLoading = true;
  String? _error;

  int _selectedTrimesterIndex = 0;

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
      // Charger les matières de l'enseignant dans cette classe
      final subjectsResponse = await _apiService.get(
        '/subject-competences/class/${widget.classSeriesId}/subjects',
      );

      // Charger les trimestres
      final trimestersResponse = await _apiService.get('/trimesters');

      if (subjectsResponse['success'] == true) {
        setState(() {
          _subjects = List<Map<String, dynamic>>.from(subjectsResponse['data'] ?? []);
          _trimesters = List<Map<String, dynamic>>.from(
            trimestersResponse['data'] ?? [],
          );
          _isLoading = false;
        });
      } else {
        throw Exception('Erreur de chargement des données');
      }
    } catch (e) {
      setState(() {
        _error = e.toString().replaceAll('Exception: ', '');
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Compétences - ${widget.className}'),
        backgroundColor: Colors.purple,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.error_outline, size: 48, color: Colors.red),
                      const SizedBox(height: 16),
                      Text(_error!, style: TextStyle(color: Colors.red)),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadData,
                        child: const Text('Réessayer'),
                      ),
                    ],
                  ),
                )
              : Column(
                  children: [
                    // Sélecteur de trimestre
                    if (_trimesters.isNotEmpty)
                      Container(
                        color: Colors.grey[200],
                        padding: const EdgeInsets.all(8),
                        child: Row(
                          children: [
                            const Text(
                              'Trimestre:',
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: SegmentedButton<int>(
                                segments: _trimesters.asMap().entries.map((entry) {
                                  return ButtonSegment<int>(
                                    value: entry.key,
                                    label: Text('Trim ${entry.value['number']}'),
                                  );
                                }).toList(),
                                selected: {_selectedTrimesterIndex},
                                onSelectionChanged: (Set<int> newSelection) {
                                  setState(() {
                                    _selectedTrimesterIndex = newSelection.first;
                                  });
                                },
                              ),
                            ),
                          ],
                        ),
                      ),

                    // Liste des matières
                    Expanded(
                      child: _subjects.isEmpty
                          ? const Center(
                              child: Text('Aucune matière trouvée'),
                            )
                          : ListView.builder(
                              itemCount: _subjects.length,
                              itemBuilder: (context, index) {
                                final subject = _subjects[index];
                                return _SubjectCompetenceCard(
                                  subject: subject,
                                  trimester: _trimesters[_selectedTrimesterIndex],
                                  classSeriesId: widget.classSeriesId,
                                  onSaved: () {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(
                                        content: Text('Compétences enregistrées!'),
                                        backgroundColor: Colors.green,
                                      ),
                                    );
                                  },
                                );
                              },
                            ),
                    ),
                  ],
                ),
    );
  }
}

class _SubjectCompetenceCard extends StatefulWidget {
  final Map<String, dynamic> subject;
  final Map<String, dynamic> trimester;
  final int classSeriesId;
  final VoidCallback onSaved;

  const _SubjectCompetenceCard({
    required this.subject,
    required this.trimester,
    required this.classSeriesId,
    required this.onSaved,
  });

  @override
  State<_SubjectCompetenceCard> createState() => _SubjectCompetenceCardState();
}

class _SubjectCompetenceCardState extends State<_SubjectCompetenceCard> {
  final ApiService _apiService = ApiService();
  final _competence1Controller = TextEditingController();
  final _competence2Controller = TextEditingController();

  bool _isLoading = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _loadCompetences();
  }

  @override
  void didUpdateWidget(_SubjectCompetenceCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.trimester['id'] != widget.trimester['id']) {
      _loadCompetences();
    }
  }

  Future<void> _loadCompetences() async {
    setState(() => _isLoading = true);

    try {
      final response = await _apiService.get(
        '/subject-competences/${widget.classSeriesId}/${widget.subject['id']}/${widget.trimester['id']}',
      );

      if (response['data'] != null) {
        _competence1Controller.text = response['data']['competence_1'] ?? '';
        _competence2Controller.text = response['data']['competence_2'] ?? '';
      }

      setState(() => _isLoading = false);
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _saveCompetences() async {
    setState(() => _isSaving = true);

    try {
      await _apiService.post('/subject-competences', {
        'class_series_id': widget.classSeriesId,
        'class_series_subject_id': widget.subject['id'],
        'trimester_id': widget.trimester['id'],
        'competence_1': _competence1Controller.text,
        'competence_2': _competence2Controller.text,
      });

      widget.onSaved();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Erreur: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      setState(() => _isSaving = false);
    }
  }

  @override
  void dispose() {
    _competence1Controller.dispose();
    _competence2Controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final subjectData = widget.subject['subject'] ?? widget.subject;

    return Card(
      margin: const EdgeInsets.all(8),
      child: ExpansionTile(
        leading: const Icon(Icons.book, color: Colors.purple),
        title: Text(
          subjectData['name'] ?? 'Matière',
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        children: [
          if (_isLoading)
            const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            )
          else
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  TextField(
                    controller: _competence1Controller,
                    decoration: const InputDecoration(
                      labelText: 'Compétence 1',
                      border: OutlineInputBorder(),
                      hintText: 'Entrez la première compétence évaluée...',
                    ),
                    maxLines: 2,
                  ),
                  const SizedBox(height: 16),
                  TextField(
                    controller: _competence2Controller,
                    decoration: const InputDecoration(
                      labelText: 'Compétence 2',
                      border: OutlineInputBorder(),
                      hintText: 'Entrez la deuxième compétence évaluée...',
                    ),
                    maxLines: 2,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: _isSaving ? null : _saveCompetences,
                    icon: _isSaving
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.save),
                    label: Text(_isSaving ? 'Enregistrement...' : 'Enregistrer'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.purple,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
