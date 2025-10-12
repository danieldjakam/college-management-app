class Evaluation {
  final int id;
  final int? sequenceId;
  final int? seriesSubjectId;
  final String name;
  final double maxScore;
  final DateTime date;
  final String? description;
  final bool isActive;

  Evaluation({
    required this.id,
    this.sequenceId,
    this.seriesSubjectId,
    required this.name,
    required this.maxScore,
    required this.date,
    this.description,
    this.isActive = true,
  });

  factory Evaluation.fromJson(Map<String, dynamic> json) {
    // Convertir max_score en double
    double maxScoreValue;
    final maxScoreField = json['max_score'] ?? json['max_grade'];
    if (maxScoreField is String) {
      maxScoreValue = double.tryParse(maxScoreField) ?? 20.0;
    } else if (maxScoreField is num) {
      maxScoreValue = (maxScoreField as num).toDouble();
    } else {
      maxScoreValue = 20.0;
    }

    return Evaluation(
      id: json['id'],
      sequenceId: json['sequence_id'],
      seriesSubjectId: json['series_subject_id'] ?? json['class_series_subject_id'],
      name: json['name'] ?? '',
      maxScore: maxScoreValue,
      date: DateTime.parse(json['date']),
      description: json['description'],
      isActive: json['is_active'] ?? true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'sequence_id': sequenceId,
      'series_subject_id': seriesSubjectId,
      'name': name,
      'max_score': maxScore,
      'date': date.toIso8601String(),
      'description': description,
      'is_active': isActive,
    };
  }

  String get formattedDate {
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }
}

class Sequence {
  final int id;
  final int trimesterId;
  final String name;
  final int number;
  final DateTime startDate;
  final DateTime endDate;
  final bool isActive;

  Sequence({
    required this.id,
    required this.trimesterId,
    required this.name,
    required this.number,
    required this.startDate,
    required this.endDate,
    this.isActive = true,
  });

  factory Sequence.fromJson(Map<String, dynamic> json) {
    return Sequence(
      id: json['id'],
      trimesterId: json['trimester_id'],
      name: json['name'] ?? '',
      number: json['number'] ?? json['order'] ?? 1,
      startDate: DateTime.parse(json['start_date']),
      endDate: DateTime.parse(json['end_date']),
      isActive: json['is_active'] ?? true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'trimester_id': trimesterId,
      'name': name,
      'number': number,
      'start_date': startDate.toIso8601String(),
      'end_date': endDate.toIso8601String(),
      'is_active': isActive,
    };
  }
}

class Trimester {
  final int id;
  final int schoolYearId;
  final String name;
  final int number;
  final DateTime startDate;
  final DateTime endDate;
  final bool isActive;

  Trimester({
    required this.id,
    required this.schoolYearId,
    required this.name,
    required this.number,
    required this.startDate,
    required this.endDate,
    this.isActive = true,
  });

  factory Trimester.fromJson(Map<String, dynamic> json) {
    return Trimester(
      id: json['id'],
      schoolYearId: json['school_year_id'],
      name: json['name'] ?? '',
      number: json['number'] ?? json['order'] ?? 1,
      startDate: DateTime.parse(json['start_date']),
      endDate: DateTime.parse(json['end_date']),
      isActive: json['is_active'] ?? true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'school_year_id': schoolYearId,
      'name': name,
      'number': number,
      'start_date': startDate.toIso8601String(),
      'end_date': endDate.toIso8601String(),
      'is_active': isActive,
    };
  }
}
