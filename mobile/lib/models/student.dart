class Student {
  final int id;
  final String firstName;
  final String lastName;
  final String? studentNumber;
  final String? matricule;
  final String? photoUrl;
  final String? fullName;

  Student({
    required this.id,
    required this.firstName,
    required this.lastName,
    this.studentNumber,
    this.matricule,
    this.photoUrl,
    this.fullName,
  });

  factory Student.fromJson(Map<String, dynamic> json) {
    return Student(
      id: json['id'],
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      studentNumber: json['student_number'],
      matricule: json['matricule'] ?? json['student_number'],
      photoUrl: json['photo_url'],
      fullName: json['full_name'] ?? '${json['first_name']} ${json['last_name']}',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'student_number': studentNumber,
      'matricule': matricule,
      'photo_url': photoUrl,
      'full_name': fullName,
    };
  }

  String get displayName => fullName ?? '$firstName $lastName';
  String get displayMatricule => matricule ?? studentNumber ?? 'N/A';
}
