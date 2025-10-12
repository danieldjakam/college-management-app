import 'package:local_auth/local_auth.dart';
import 'package:flutter/services.dart';

class BiometricService {
  final LocalAuthentication _localAuth = LocalAuthentication();

  /// Vérifie si l'appareil supporte la biométrie
  Future<bool> canCheckBiometrics() async {
    try {
      return await _localAuth.canCheckBiometrics;
    } on PlatformException {
      return false;
    }
  }

  /// Vérifie si l'appareil est compatible avec la biométrie
  Future<bool> isDeviceSupported() async {
    try {
      return await _localAuth.isDeviceSupported();
    } on PlatformException {
      return false;
    }
  }

  /// Obtient la liste des types de biométrie disponibles
  /// Retourne une liste contenant:
  /// - BiometricType.face (Face ID sur iPhone)
  /// - BiometricType.fingerprint (Touch ID/Empreinte digitale)
  /// - BiometricType.iris (Scanner d'iris - rare)
  Future<List<BiometricType>> getAvailableBiometrics() async {
    try {
      return await _localAuth.getAvailableBiometrics();
    } on PlatformException {
      return [];
    }
  }

  /// Obtient une description lisible des méthodes biométriques disponibles
  Future<String> getBiometricTypesDescription() async {
    final availableBiometrics = await getAvailableBiometrics();

    if (availableBiometrics.isEmpty) {
      return 'Aucune méthode biométrique disponible';
    }

    final types = <String>[];
    if (availableBiometrics.contains(BiometricType.face)) {
      types.add('Face ID');
    }
    if (availableBiometrics.contains(BiometricType.fingerprint)) {
      types.add('Empreinte digitale');
    }
    if (availableBiometrics.contains(BiometricType.iris)) {
      types.add('Scanner d\'iris');
    }

    return types.join(' ou ');
  }

  /// Authentifie l'utilisateur avec la biométrie
  ///
  /// [localizedReason] - Message affiché à l'utilisateur
  /// Retourne true si l'authentification réussit, false sinon
  Future<bool> authenticate({
    required String localizedReason,
    bool useErrorDialogs = true,
    bool stickyAuth = true,
  }) async {
    try {
      // Vérifier si la biométrie est disponible
      final canAuthenticate = await canCheckBiometrics() && await isDeviceSupported();

      if (!canAuthenticate) {
        return false;
      }

      // Authentifier
      return await _localAuth.authenticate(
        localizedReason: localizedReason,
        options: AuthenticationOptions(
          useErrorDialogs: useErrorDialogs,
          stickyAuth: stickyAuth,
          biometricOnly: true, // Forcer l'utilisation de la biométrie uniquement
        ),
      );
    } on PlatformException catch (e) {
      print('❌ Biometric authentication error: ${e.message}');
      return false;
    } catch (e) {
      print('❌ Unexpected biometric error: $e');
      return false;
    }
  }

  /// Arrête l'authentification en cours
  Future<void> stopAuthentication() async {
    try {
      await _localAuth.stopAuthentication();
    } catch (e) {
      print('⚠️ Error stopping authentication: $e');
    }
  }

  /// Vérifie si la biométrie est disponible et configurée sur l'appareil
  /// Utilisé pour afficher/masquer l'option dans les paramètres
  Future<bool> isBiometricAvailable() async {
    final canCheck = await canCheckBiometrics();
    final isSupported = await isDeviceSupported();
    final availableBiometrics = await getAvailableBiometrics();

    return canCheck && isSupported && availableBiometrics.isNotEmpty;
  }

  /// Obtient un message d'erreur en français selon le code d'erreur
  String getErrorMessage(PlatformException error) {
    switch (error.code) {
      case 'NotAvailable':
        return 'L\'authentification biométrique n\'est pas disponible sur cet appareil';
      case 'NotEnrolled':
        return 'Aucune empreinte/visage n\'est enregistré sur cet appareil';
      case 'LockedOut':
        return 'Trop de tentatives. Veuillez réessayer plus tard';
      case 'PermanentlyLockedOut':
        return 'Authentification biométrique désactivée. Veuillez déverrouiller votre appareil';
      case 'PasscodeNotSet':
        return 'Aucun code PIN n\'est configuré sur cet appareil';
      default:
        return 'Erreur d\'authentification: ${error.message ?? 'Inconnue'}';
    }
  }
}
