class User {
  final int id;
  final String username;
  final String role;
  final int? teacherId;
  final String? token;

  User({
    required this.id,
    required this.username,
    required this.role,
    this.teacherId,
    this.token,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      username: json['username'] ?? json['name'] ?? '',
      role: json['role'] ?? '',
      teacherId: json['teacher_id'],
      token: json['token'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'username': username,
      'role': role,
      'teacher_id': teacherId,
      'token': token,
    };
  }

  bool get isTeacher => role == 'teacher' || role == 'enseignant';
}
