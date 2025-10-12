import 'dart:convert';
import 'package:http/http.dart' as http;
import '../utils/constants.dart';
import 'storage_service.dart';

class ApiService {
  final StorageService _storageService = StorageService();

  // Headers de base
  Future<Map<String, String>> _getHeaders() async {
    final token = await _storageService.getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // GET Request
  Future<Map<String, dynamic>> get(String endpoint) async {
    try {
      final url = Uri.parse('${ApiConstants.baseUrl}$endpoint');
      final headers = await _getHeaders();

      final response = await http
          .get(url, headers: headers)
          .timeout(const Duration(seconds: 30));

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur de connexion: $e');
    }
  }

  // POST Request
  Future<Map<String, dynamic>> post(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      final url = Uri.parse('${ApiConstants.baseUrl}$endpoint');
      final headers = await _getHeaders();

      final response = await http
          .post(
            url,
            headers: headers,
            body: json.encode(data),
          )
          .timeout(const Duration(seconds: 30));

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur de connexion: $e');
    }
  }

  // PUT Request
  Future<Map<String, dynamic>> put(
    String endpoint,
    Map<String, dynamic> data,
  ) async {
    try {
      final url = Uri.parse('${ApiConstants.baseUrl}$endpoint');
      final headers = await _getHeaders();

      final response = await http
          .put(
            url,
            headers: headers,
            body: json.encode(data),
          )
          .timeout(const Duration(seconds: 30));

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur de connexion: $e');
    }
  }

  // DELETE Request
  Future<Map<String, dynamic>> delete(String endpoint) async {
    try {
      final url = Uri.parse('${ApiConstants.baseUrl}$endpoint');
      final headers = await _getHeaders();

      final response = await http
          .delete(url, headers: headers)
          .timeout(const Duration(seconds: 30));

      return _handleResponse(response);
    } catch (e) {
      throw Exception('Erreur de connexion: $e');
    }
  }

  // Gérer la réponse
  Map<String, dynamic> _handleResponse(http.Response response) {
    final statusCode = response.statusCode;
    final body = response.body;

    print('📥 HTTP Response - Status: $statusCode');
    print('📥 HTTP Response - Body: ${body.length > 200 ? body.substring(0, 200) + "..." : body}');

    // Essayer de parser le JSON
    Map<String, dynamic> responseData = {};
    try {
      responseData = json.decode(body);
      print('✅ JSON parsed successfully');
    } catch (e) {
      print('❌ Failed to parse JSON: $e');
      responseData = {'message': body};
    }

    // Vérifier le statut
    if (statusCode >= 200 && statusCode < 300) {
      return responseData;
    } else if (statusCode == 401) {
      throw Exception('Non autorisé. Veuillez vous reconnecter.');
    } else if (statusCode == 403) {
      throw Exception('Accès interdit.');
    } else if (statusCode == 404) {
      throw Exception('Ressource introuvable.');
    } else if (statusCode == 500) {
      final message = responseData['message'] ?? 'Erreur serveur';
      print('❌ Server error 500: $message');
      print('❌ Full response: $responseData');
      throw Exception(message);
    } else {
      final message = responseData['message'] ?? 'Erreur inconnue';
      throw Exception(message);
    }
  }
}
