import 'student.dart';

class Grade {
  final int? id;
  final int evaluationId;
  final int studentId;
  final double? score;
  final bool isAbsent;
  final bool isExcused;
  final String? comment;
  final Student? student;

  Grade({
    this.id,
    required this.evaluationId,
    required this.studentId,
    this.score,
    this.isAbsent = false,
    this.isExcused = false,
    this.comment,
    this.student,
  });

  factory Grade.fromJson(Map<String, dynamic> json) {
    // Convertir score en double
    double? scoreValue;
    if (json['score'] != null) {
      if (json['score'] is String) {
        scoreValue = double.tryParse(json['score']);
      } else if (json['score'] is num) {
        scoreValue = (json['score'] as num).toDouble();
      }
    }

    return Grade(
      id: json['id'],
      evaluationId: json['evaluation_id'],
      studentId: json['student_id'],
      score: scoreValue,
      isAbsent: json['is_absent'] ?? false,
      isExcused: json['is_excused'] ?? false,
      comment: json['comment'],
      student: json['student'] != null ? Student.fromJson(json['student']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'evaluation_id': evaluationId,
      'student_id': studentId,
      'score': score,
      'is_absent': isAbsent,
      'is_excused': isExcused,
      'comment': comment,
    };
  }

  Grade copyWith({
    int? id,
    int? evaluationId,
    int? studentId,
    double? score,
    bool? isAbsent,
    bool? isExcused,
    String? comment,
    Student? student,
  }) {
    return Grade(
      id: id ?? this.id,
      evaluationId: evaluationId ?? this.evaluationId,
      studentId: studentId ?? this.studentId,
      score: score ?? this.score,
      isAbsent: isAbsent ?? this.isAbsent,
      isExcused: isExcused ?? this.isExcused,
      comment: comment ?? this.comment,
      student: student ?? this.student,
    );
  }

  String get displayScore {
    if (isAbsent) return 'Absent';
    if (isExcused) return 'Dispensé';
    if (score == null) return '-';
    return score!.toStringAsFixed(2);
  }
}
