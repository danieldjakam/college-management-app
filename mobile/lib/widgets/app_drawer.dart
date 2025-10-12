import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../services/storage_service.dart';
import '../utils/constants.dart';
import '../screens/login_screen.dart';
import '../screens/dashboard_screen.dart';
import '../screens/classes_screen.dart';
import '../screens/examens_screen.dart';
import '../screens/settings_screen.dart';

class AppDrawer extends StatefulWidget {
  final String currentRoute;

  const AppDrawer({
    super.key,
    this.currentRoute = '/dashboard',
  });

  @override
  State<AppDrawer> createState() => _AppDrawerState();
}

class _AppDrawerState extends State<AppDrawer> {
  final AuthService _authService = AuthService();
  final StorageService _storageService = StorageService();
  String _teacherName = '';
  String _teacherEmail = '';

  @override
  void initState() {
    super.initState();
    _loadUserInfo();
  }

  Future<void> _loadUserInfo() async {
    final name = await _storageService.getTeacherName();
    final email = await _storageService.getUserEmail();

    setState(() {
      _teacherName = name ?? 'Enseignant';
      _teacherEmail = email ?? '';
    });
  }

  Future<void> _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Voulez-vous vraiment vous déconnecter ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Annuler'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Déconnexion'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await _authService.logout();
      if (!mounted) return;

      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (route) => false,
      );
    }
  }

  void _navigateTo(String routeName, Widget screen) {
    // Ne pas naviguer si on est déjà sur cette page
    if (widget.currentRoute == routeName) {
      Navigator.pop(context);
      return;
    }

    Navigator.pop(context); // Fermer le drawer
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => screen),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Drawer(
      child: Column(
        children: [
          // En-tête avec profil
          UserAccountsDrawerHeader(
            decoration: const BoxDecoration(
              color: Color(AppColors.primaryColor),
            ),
            accountName: Text(
              _teacherName,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            accountEmail: Text(_teacherEmail),
            currentAccountPicture: const CircleAvatar(
              backgroundColor: Colors.white,
              child: Icon(
                Icons.person,
                size: 48,
                color: Color(AppColors.primaryColor),
              ),
            ),
          ),

          // Menu items
          Expanded(
            child: ListView(
              padding: EdgeInsets.zero,
              children: [
                _buildDrawerItem(
                  icon: Icons.home,
                  title: 'Home',
                  routeName: '/dashboard',
                  onTap: () => _navigateTo('/dashboard', const DashboardScreen()),
                ),
                _buildDrawerItem(
                  icon: Icons.class_,
                  title: 'Mes Classes',
                  routeName: '/classes',
                  onTap: () => _navigateTo('/classes', const ClassesScreen()),
                ),
                _buildDrawerItem(
                  icon: Icons.assignment,
                  title: 'Examens',
                  routeName: '/examens',
                  onTap: () => _navigateTo('/examens', const ExamensScreen()),
                ),
                _buildDrawerItem(
                  icon: Icons.settings,
                  title: 'Paramètres',
                  routeName: '/settings',
                  onTap: () => _navigateTo('/settings', const SettingsScreen()),
                ),
                const Divider(),
                ListTile(
                  leading: const Icon(Icons.logout, color: Colors.red),
                  title: const Text(
                    'Déconnexion',
                    style: TextStyle(color: Colors.red),
                  ),
                  onTap: _handleLogout,
                ),
              ],
            ),
          ),

          // Version
          Padding(
            padding: const EdgeInsets.all(16),
            child: Text(
              'Version 1.0.0',
              style: TextStyle(
                color: Colors.grey[600],
                fontSize: 12,
              ),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDrawerItem({
    required IconData icon,
    required String title,
    required String routeName,
    required VoidCallback onTap,
  }) {
    final isSelected = widget.currentRoute == routeName;

    return ListTile(
      leading: Icon(
        icon,
        color: isSelected ? const Color(AppColors.primaryColor) : null,
      ),
      title: Text(
        title,
        style: TextStyle(
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
          color: isSelected ? const Color(AppColors.primaryColor) : null,
        ),
      ),
      selected: isSelected,
      selectedTileColor: const Color(AppColors.primaryColor).withOpacity(0.1),
      onTap: onTap,
    );
  }
}
