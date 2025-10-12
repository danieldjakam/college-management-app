import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../utils/constants.dart';

class StorageService {
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();

  // Token (stockage sécurisé)
  Future<void> saveToken(String token) async {
    await _secureStorage.write(key: StorageKeys.authToken, value: token);
  }

  Future<String?> getToken() async {
    return await _secureStorage.read(key: StorageKeys.authToken);
  }

  Future<void> deleteToken() async {
    await _secureStorage.delete(key: StorageKeys.authToken);
  }

  // Données utilisateur (SharedPreferences)
  Future<void> saveUserId(int userId) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(StorageKeys.userId, userId);
  }

  Future<int?> getUserId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt(StorageKeys.userId);
  }

  Future<void> saveUserName(String userName) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(StorageKeys.userName, userName);
  }

  Future<String?> getUserName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(StorageKeys.userName);
  }

  Future<void> saveUserRole(String role) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(StorageKeys.userRole, role);
  }

  Future<String?> getUserRole() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(StorageKeys.userRole);
  }

  Future<void> saveTeacherId(int teacherId) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(StorageKeys.teacherId, teacherId);
  }

  Future<int?> getTeacherId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt(StorageKeys.teacherId);
  }

  Future<void> saveTeacherName(String teacherName) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(StorageKeys.teacherName, teacherName);
  }

  Future<String?> getTeacherName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(StorageKeys.teacherName);
  }

  Future<void> saveUserEmail(String email) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(StorageKeys.userEmail, email);
  }

  Future<String?> getUserEmail() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(StorageKeys.userEmail);
  }

  // Biometric authentication
  Future<void> setBiometricEnabled(bool enabled) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(StorageKeys.biometricEnabled, enabled);
  }

  Future<bool> getBiometricEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(StorageKeys.biometricEnabled) ?? false;
  }

  // Save credentials for biometric login (stored securely)
  Future<void> saveBiometricCredentials(String username, String password) async {
    await _secureStorage.write(key: StorageKeys.biometricUsername, value: username);
    await _secureStorage.write(key: StorageKeys.biometricPassword, value: password);
  }

  Future<Map<String, String>?> getBiometricCredentials() async {
    final username = await _secureStorage.read(key: StorageKeys.biometricUsername);
    final password = await _secureStorage.read(key: StorageKeys.biometricPassword);

    if (username != null && password != null) {
      return {'username': username, 'password': password};
    }
    return null;
  }

  Future<void> deleteBiometricCredentials() async {
    await _secureStorage.delete(key: StorageKeys.biometricUsername);
    await _secureStorage.delete(key: StorageKeys.biometricPassword);
  }

  // Effacer toutes les données
  Future<void> clearAll() async {
    await deleteToken();
    await deleteBiometricCredentials();
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }

  // Vérifier si l'utilisateur est connecté
  Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }
}
