import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/evaluation.dart';
import '../services/api_service.dart';
import '../utils/constants.dart';
import '../widgets/app_drawer.dart';

class ExamensScreen extends StatefulWidget {
  const ExamensScreen({super.key});

  @override
  State<ExamensScreen> createState() => _ExamensScreenState();
}

class _ExamensScreenState extends State<ExamensScreen> {
  final ApiService _apiService = ApiService();

  List<Evaluation> _evaluations = [];
  List<Evaluation> _filteredEvaluations = [];
  bool _isLoading = true;
  String? _error;
  String _selectedFilter = 'all'; // all, trimester, sequence

  @override
  void initState() {
    super.initState();
    _loadEvaluations();
  }

  Future<void> _loadEvaluations() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final response = await _apiService.get(ApiConstants.evaluationsEndpoint);

      if (response['success'] == true || response['data'] != null) {
        final List<dynamic> evaluationsList = response['data'] ?? [];

        setState(() {
          _evaluations = evaluationsList
              .map((json) => Evaluation.fromJson(json))
              .toList();

          // Trier par date décroissante
          _evaluations.sort((a, b) => b.date.compareTo(a.date));

          _filteredEvaluations = _evaluations;
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

  void _applyFilter(String filter) {
    setState(() {
      _selectedFilter = filter;

      if (filter == 'all') {
        _filteredEvaluations = _evaluations;
      } else {
        _filteredEvaluations = _evaluations.where((eval) {
          // TODO: Ajouter la logique de filtrage selon le trimestre/séquence
          return true;
        }).toList();
      }
    });
  }

  Color _getTypeColor(String? type) {
    switch (type?.toLowerCase()) {
      case 'interrogation':
        return Colors.blue;
      case 'devoir':
        return Colors.orange;
      case 'composition':
        return Colors.purple;
      case 'examen':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _getTypeLabel(String? type) {
    switch (type?.toLowerCase()) {
      case 'interrogation':
        return 'Interrogation';
      case 'devoir':
        return 'Devoir';
      case 'composition':
        return 'Composition';
      case 'examen':
        return 'Examen';
      default:
        return type ?? 'Évaluation';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Examens'),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFilterDialog,
          ),
        ],
      ),
      drawer: const AppDrawer(currentRoute: '/examens'),
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
                          onPressed: _loadEvaluations,
                          icon: const Icon(Icons.refresh),
                          label: const Text('Réessayer'),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadEvaluations,
                  child: _filteredEvaluations.isEmpty
                      ? const Center(
                          child: Text(
                            'Aucune évaluation trouvée',
                            style: TextStyle(color: Colors.grey, fontSize: 16),
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: _filteredEvaluations.length,
                          itemBuilder: (context, index) {
                            final evaluation = _filteredEvaluations[index];
                            return _buildEvaluationCard(evaluation);
                          },
                        ),
                ),
    );
  }

  Widget _buildEvaluationCard(Evaluation evaluation) {
    final dateFormat = DateFormat('dd/MM/yyyy');

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      child: InkWell(
        onTap: () {
          // TODO: Naviguer vers les détails de l'évaluation
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: _getTypeColor(null).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                        color: _getTypeColor(null),
                        width: 1,
                      ),
                    ),
                    child: Text(
                      _getTypeLabel(null),
                      style: TextStyle(
                        color: _getTypeColor(null),
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                  const Spacer(),
                  Icon(
                    Icons.calendar_today,
                    size: 16,
                    color: Colors.grey[600],
                  ),
                  const SizedBox(width: 4),
                  Text(
                    dateFormat.format(evaluation.date),
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                evaluation.name,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              if (evaluation.description != null) ...[
                const SizedBox(height: 8),
                Text(
                  evaluation.description!,
                  style: TextStyle(
                    color: Colors.grey[700],
                    fontSize: 14,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              const SizedBox(height: 12),
              Row(
                children: [
                  Icon(
                    Icons.grade,
                    size: 16,
                    color: Colors.grey[600],
                  ),
                  const SizedBox(width: 4),
                  Text(
                    'Note sur ${evaluation.maxScore.toStringAsFixed(0)}',
                    style: TextStyle(
                      color: Colors.grey[600],
                      fontSize: 14,
                    ),
                  ),
                  const Spacer(),
                  Icon(
                    Icons.arrow_forward_ios,
                    size: 16,
                    color: Colors.grey[400],
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _showFilterDialog() async {
    final result = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Filtrer les évaluations'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            RadioListTile<String>(
              title: const Text('Toutes'),
              value: 'all',
              groupValue: _selectedFilter,
              onChanged: (value) => Navigator.pop(context, value),
            ),
            RadioListTile<String>(
              title: const Text('Par trimestre'),
              value: 'trimester',
              groupValue: _selectedFilter,
              onChanged: (value) => Navigator.pop(context, value),
            ),
            RadioListTile<String>(
              title: const Text('Par séquence'),
              value: 'sequence',
              groupValue: _selectedFilter,
              onChanged: (value) => Navigator.pop(context, value),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Annuler'),
          ),
        ],
      ),
    );

    if (result != null) {
      _applyFilter(result);
    }
  }
}
