import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';
import '../services/biometric_service.dart';
import '../utils/constants.dart';
import '../widgets/app_drawer.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final AuthService _authService = AuthService();
  final ApiService _apiService = ApiService();
  final StorageService _storageService = StorageService();
  final BiometricService _biometricService = BiometricService();

  String _teacherName = '';
  String _teacherEmail = '';
  bool _biometricEnabled = false;
  bool _biometricAvailable = false;
  String _biometricTypes = '';
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final name = await _storageService.getTeacherName();
      final email = await _storageService.getUserEmail();
      final biometric = await _storageService.getBiometricEnabled();

      // Vérifier si la biométrie est disponible
      final biometricAvailable = await _biometricService.isBiometricAvailable();
      final biometricTypes = await _biometricService.getBiometricTypesDescription();

      // Logs de débogage
      print('🔍 DEBUG Biometric:');
      print('   - Available: $biometricAvailable');
      print('   - Types: $biometricTypes');
      print('   - Enabled: $biometric');

      setState(() {
        _teacherName = name ?? 'Enseignant';
        _teacherEmail = email ?? '';
        _biometricEnabled = biometric;
        _biometricAvailable = biometricAvailable;
        _biometricTypes = biometricTypes;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _showChangeName() async {
    final TextEditingController controller = TextEditingController(text: _teacherName);

    final result = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Modifier le nom'),
        content: TextField(
          controller: controller,
          decoration: const InputDecoration(
            labelText: 'Nom complet',
            border: OutlineInputBorder(),
          ),
          autofocus: true,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, controller.text),
            child: const Text('Enregistrer'),
          ),
        ],
      ),
    );

    if (result != null && result.isNotEmpty && result != _teacherName) {
      try {
        // Appeler l'API pour mettre à jour le profil
        final response = await _apiService.put(
          ApiConstants.updateProfileEndpoint,
          {'name': result},
        );

        if (response['success'] == true) {
          // Mettre à jour le stockage local
          await _storageService.saveTeacherName(result);
          setState(() {
            _teacherName = result;
          });

          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Nom modifié avec succès'),
                backgroundColor: Colors.green,
              ),
            );
          }
        } else {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(response['message'] ?? 'Erreur lors de la modification'),
                backgroundColor: Colors.red,
              ),
            );
          }
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Erreur: ${e.toString().replaceAll('Exception: ', '')}'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  Future<void> _showChangePassword() async {
    final TextEditingController currentPasswordController = TextEditingController();
    final TextEditingController newPasswordController = TextEditingController();
    final TextEditingController confirmPasswordController = TextEditingController();
    bool obscureCurrent = true;
    bool obscureNew = true;
    bool obscureConfirm = true;

    final result = await showDialog<Map<String, String>>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Changer le mot de passe'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: currentPasswordController,
                  obscureText: obscureCurrent,
                  decoration: InputDecoration(
                    labelText: 'Mot de passe actuel',
                    border: const OutlineInputBorder(),
                    suffixIcon: IconButton(
                      icon: Icon(obscureCurrent ? Icons.visibility : Icons.visibility_off),
                      onPressed: () => setDialogState(() => obscureCurrent = !obscureCurrent),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: newPasswordController,
                  obscureText: obscureNew,
                  decoration: InputDecoration(
                    labelText: 'Nouveau mot de passe',
                    border: const OutlineInputBorder(),
                    suffixIcon: IconButton(
                      icon: Icon(obscureNew ? Icons.visibility : Icons.visibility_off),
                      onPressed: () => setDialogState(() => obscureNew = !obscureNew),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: confirmPasswordController,
                  obscureText: obscureConfirm,
                  decoration: InputDecoration(
                    labelText: 'Confirmer le mot de passe',
                    border: const OutlineInputBorder(),
                    suffixIcon: IconButton(
                      icon: Icon(obscureConfirm ? Icons.visibility : Icons.visibility_off),
                      onPressed: () => setDialogState(() => obscureConfirm = !obscureConfirm),
                    ),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () {
                if (newPasswordController.text != confirmPasswordController.text) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Les mots de passe ne correspondent pas')),
                  );
                  return;
                }

                if (newPasswordController.text.length < 8) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Le mot de passe doit contenir au moins 8 caractères')),
                  );
                  return;
                }

                Navigator.pop(context, {
                  'current': currentPasswordController.text,
                  'new': newPasswordController.text,
                });
              },
              child: const Text('Changer'),
            ),
          ],
        ),
      ),
    );

    if (result != null) {
      try {
        // Appeler l'API pour changer le mot de passe
        final response = await _apiService.put(
          ApiConstants.changePasswordEndpoint,
          {
            'current_password': result['current']!,
            'new_password': result['new']!,
            'new_password_confirmation': result['new']!,
          },
        );

        if (!mounted) return;

        if (response['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Mot de passe modifié avec succès'),
              backgroundColor: Colors.green,
            ),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(response['message'] ?? 'Erreur lors du changement de mot de passe'),
              backgroundColor: Colors.red,
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Erreur: ${e.toString().replaceAll('Exception: ', '')}'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  Future<void> _toggleBiometric(bool value) async {
    if (value) {
      // Vérifier si la biométrie est disponible
      if (!_biometricAvailable) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('L\'authentification biométrique n\'est pas disponible sur cet appareil'),
              backgroundColor: Colors.red,
            ),
          );
        }
        return;
      }

      // Demander l'authentification biométrique pour activer
      final authenticated = await _biometricService.authenticate(
        localizedReason: 'Authentifiez-vous pour activer la connexion biométrique',
      );

      if (authenticated) {
        // Sauvegarder les identifiants actuels si l'utilisateur est connecté
        final credentials = await _storageService.getBiometricCredentials();
        if (credentials == null) {
          // Demander à l'utilisateur ses identifiants pour les sauvegarder
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Veuillez vous reconnecter pour activer la connexion biométrique'),
                backgroundColor: Colors.orange,
              ),
            );
          }
          return;
        }

        await _storageService.setBiometricEnabled(true);
        setState(() {
          _biometricEnabled = true;
        });

        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Connexion biométrique activée ($_biometricTypes)'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Authentification échouée'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } else {
      // Désactiver la connexion biométrique
      await _storageService.setBiometricEnabled(false);
      setState(() {
        _biometricEnabled = false;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Connexion biométrique désactivée'),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Paramètres'),
      ),
      drawer: const AppDrawer(currentRoute: '/settings'),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              children: [
                // Section Profil
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 24, 16, 8),
                  child: Text(
                    'Profil',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(AppColors.primaryColor),
                    ),
                  ),
                ),
                Card(
                  margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Column(
                    children: [
                      ListTile(
                        leading: const Icon(Icons.person, color: Color(AppColors.primaryColor)),
                        title: const Text('Nom'),
                        subtitle: Text(_teacherName),
                        trailing: const Icon(Icons.edit, size: 20),
                        onTap: _showChangeName,
                      ),
                      const Divider(height: 1),
                      ListTile(
                        leading: const Icon(Icons.email, color: Color(AppColors.primaryColor)),
                        title: const Text('Email'),
                        subtitle: Text(_teacherEmail),
                        enabled: false,
                      ),
                    ],
                  ),
                ),

                // Section Sécurité
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 24, 16, 8),
                  child: Text(
                    'Sécurité',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(AppColors.primaryColor),
                    ),
                  ),
                ),
                Card(
                  margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Column(
                    children: [
                      ListTile(
                        leading: const Icon(Icons.lock, color: Color(AppColors.primaryColor)),
                        title: const Text('Changer le mot de passe'),
                        trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                        onTap: _showChangePassword,
                      ),
                      if (_biometricAvailable) const Divider(height: 1),
                      if (_biometricAvailable)
                        SwitchListTile(
                          secondary: const Icon(Icons.fingerprint, color: Color(AppColors.primaryColor)),
                          title: const Text('Authentification biométrique'),
                          subtitle: Text(_biometricTypes),
                          value: _biometricEnabled,
                          onChanged: _toggleBiometric,
                        ),
                    ],
                  ),
                ),

                // Section À propos
                const Padding(
                  padding: EdgeInsets.fromLTRB(16, 24, 16, 8),
                  child: Text(
                    'À propos',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(AppColors.primaryColor),
                    ),
                  ),
                ),
                Card(
                  margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Column(
                    children: [
                      const ListTile(
                        leading: Icon(Icons.info, color: Color(AppColors.primaryColor)),
                        title: Text('Version'),
                        subtitle: Text('1.0.0'),
                      ),
                      const Divider(height: 1),
                      ListTile(
                        leading: const Icon(Icons.school, color: Color(AppColors.primaryColor)),
                        title: const Text('CPB Enseignant'),
                        subtitle: const Text('Application mobile pour enseignants'),
                        onTap: () {
                          showAboutDialog(
                            context: context,
                            applicationName: 'CPB Enseignant',
                            applicationVersion: '1.0.0',
                            applicationIcon: const Icon(
                              Icons.school,
                              size: 48,
                              color: Color(AppColors.primaryColor),
                            ),
                            children: [
                              const Text('Application de gestion pour les enseignants du CPB Douala'),
                            ],
                          );
                        },
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 24),
              ],
            ),
    );
  }
}
