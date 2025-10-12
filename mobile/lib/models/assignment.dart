import 'subject.dart';

class ClassSeries {
  final int id;
  final String name;
  final SchoolClass? schoolClass;

  ClassSeries({
    required this.id,
    required this.name,
    this.schoolClass,
  });

  factory ClassSeries.fromJson(Map<String, dynamic> json) {
    return ClassSeries(
      id: json['id'],
      name: json['name'] ?? '',
      schoolClass: json['school_class'] != null
          ? SchoolClass.fromJson(json['school_class'])
          : null,
    );
  }
}

class SchoolClass {
  final int id;
  final String name;
  final Level? level;

  SchoolClass({
    required this.id,
    required this.name,
    this.level,
  });

  factory SchoolClass.fromJson(Map<String, dynamic> json) {
    return SchoolClass(
      id: json['id'],
      name: json['name'] ?? '',
      level: json['level'] != null ? Level.fromJson(json['level']) : null,
    );
  }
}

class Level {
  final int id;
  final String name;

  Level({
    required this.id,
    required this.name,
  });

  factory Level.fromJson(Map<String, dynamic> json) {
    return Level(
      id: json['id'],
      name: json['name'] ?? '',
    );
  }
}

class SeriesSubject {
  final int id;
  final Subject? subject;
  final ClassSeries? classSeries;
  final double? coefficient;

  SeriesSubject({
    required this.id,
    this.subject,
    this.classSeries,
    this.coefficient,
  });

  factory SeriesSubject.fromJson(Map<String, dynamic> json) {
    // Convertir le coefficient en double, qu'il soit String ou num
    double? coef;
    if (json['coefficient'] != null) {
      if (json['coefficient'] is String) {
        coef = double.tryParse(json['coefficient']);
      } else if (json['coefficient'] is num) {
        coef = (json['coefficient'] as num).toDouble();
      }
    }

    return SeriesSubject(
      id: json['id'],
      subject: json['subject'] != null
          ? Subject.fromJson(json['subject'])
          : null,
      classSeries: json['class_series'] != null
          ? ClassSeries.fromJson(json['class_series'])
          : null,
      coefficient: coef,
    );
  }
}

class TeacherAssignment {
  final int id;
  final int teacherId;
  final int classSeriesSubjectId;
  final SeriesSubject? seriesSubject;
  final bool isActive;

  TeacherAssignment({
    required this.id,
    required this.teacherId,
    required this.classSeriesSubjectId,
    this.seriesSubject,
    this.isActive = true,
  });

  factory TeacherAssignment.fromJson(Map<String, dynamic> json) {
    return TeacherAssignment(
      id: json['id'],
      teacherId: json['teacher_id'],
      classSeriesSubjectId: json['class_series_subject_id'],
      seriesSubject: json['series_subject'] != null
          ? SeriesSubject.fromJson(json['series_subject'])
          : null,
      isActive: json['is_active'] ?? true,
    );
  }

  String get subjectName => seriesSubject?.subject?.name ?? 'Matière inconnue';
  String get className => seriesSubject?.classSeries?.name ?? 'Classe inconnue';
  double? get coefficient => seriesSubject?.coefficient;
}
