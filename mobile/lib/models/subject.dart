class Subject {
  final int id;
  final String name;
  final String? code;
  final int? coefficient;

  Subject({
    required this.id,
    required this.name,
    this.code,
    this.coefficient,
  });

  factory Subject.fromJson(Map<String, dynamic> json) {
    return Subject(
      id: json['id'],
      name: json['name'] ?? '',
      code: json['code'],
      coefficient: json['coefficient'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'code': code,
      'coefficient': coefficient,
    };
  }
}
