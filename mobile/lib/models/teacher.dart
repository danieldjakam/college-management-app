class Teacher {
  final int id;
  final String firstName;
  final String lastName;
  final String? email;
  final String? phone;
  final String? fullName;

  Teacher({
    required this.id,
    required this.firstName,
    required this.lastName,
    this.email,
    this.phone,
    this.fullName,
  });

  factory Teacher.fromJson(Map<String, dynamic> json) {
    return Teacher(
      id: json['id'],
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      email: json['email'],
      phone: json['phone'],
      fullName: json['full_name'] ?? '${json['first_name']} ${json['last_name']}',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'phone': phone,
      'full_name': fullName,
    };
  }

  String get displayName => fullName ?? '$firstName $lastName';
}
