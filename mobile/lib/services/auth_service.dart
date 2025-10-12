import '../models/user.dart';
import '../utils/constants.dart';
import 'api_service.dart';
import 'storage_service.dart';

class AuthService {
  final ApiService _apiService = ApiService();
  final StorageService _storageService = StorageService();

  // Login
  Future<User> login(String username, String password) async {
    try {
      print('🔐 Attempting login for user: $username');
      print('📡 API Endpoint: ${ApiConstants.baseUrl}${ApiConstants.loginEndpoint}');

      final response = await _apiService.post(
        ApiConstants.loginEndpoint,
        {
          'username': username,
          'password': password,
        },
      );

      print('✅ Response received: $response');

      // Vérifier le succès
      if (response['success'] != true) {
        final errorMessage = response['message'] ?? 'Échec de la connexion';
        print('❌ Login failed: $errorMessage');
        throw Exception(errorMessage);
      }

      // Extraire les données utilisateur (le backend retourne 'user' pas 'data')
      final userData = response['user'];
      if (userData == null) {
        print('❌ No user data in response');
        print('Response keys: ${response.keys.toList()}');
        throw Exception('Données utilisateur manquantes dans la réponse');
      }

      // Extraire le token (le backend retourne 'access_token' pas 'token')
      final token = response['access_token'] ?? response['token'];

      if (token == null) {
        print('❌ No token in response');
        print('Response structure: ${response.keys.toList()}');
        throw Exception('Token manquant dans la réponse');
      }

      print('✅ Token received: ${token.substring(0, 10)}...');

      // Créer l'objet User
      final user = User.fromJson({
        ...userData,
        'token': token,
      });

      print('✅ User object created: ${user.username} (${user.role})');

      // Sauvegarder les données
      await _storageService.saveToken(token);
      await _storageService.saveUserId(user.id);
      await _storageService.saveUserName(user.username);
      await _storageService.saveUserRole(user.role);

      // Sauvegarder le nom complet de l'enseignant depuis la réponse API
      final teacherFullName = userData['name'] as String?;
      if (teacherFullName != null) {
        await _storageService.saveTeacherName(teacherFullName);
        print('✅ Teacher name saved: $teacherFullName');
      }

      if (user.teacherId != null) {
        await _storageService.saveTeacherId(user.teacherId!);
        print('✅ Teacher ID saved: ${user.teacherId}');
      }

      print('✅ Login successful');
      return user;
    } catch (e) {
      print('❌ Login error: $e');
      throw Exception('Erreur de connexion: $e');
    }
  }

  // Logout
  Future<void> logout() async {
    try {
      // Appeler l'API de logout
      await _apiService.post(ApiConstants.logoutEndpoint, {});
    } catch (e) {
      // Ignorer les erreurs de logout API
    } finally {
      // Toujours effacer les données locales
      await _storageService.clearAll();
    }
  }

  // Vérifier si l'utilisateur est connecté
  Future<bool> isLoggedIn() async {
    return await _storageService.isLoggedIn();
  }

  // Obtenir l'utilisateur actuel depuis le stockage
  Future<User?> getCurrentUser() async {
    final token = await _storageService.getToken();
    if (token == null) return null;

    final userId = await _storageService.getUserId();
    final userName = await _storageService.getUserName();
    final userRole = await _storageService.getUserRole();
    final teacherId = await _storageService.getTeacherId();

    if (userId == null || userName == null || userRole == null) {
      return null;
    }

    return User(
      id: userId,
      username: userName,
      role: userRole,
      teacherId: teacherId,
      token: token,
    );
  }
}
