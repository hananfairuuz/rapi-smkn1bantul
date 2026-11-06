import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';
import 'calendar_screen.dart';
import 'public_about_screen.dart';
import 'public_help_screen.dart';
import 'public_services_screen.dart';

class PublicShell extends StatefulWidget {
  const PublicShell({
    required this.api,
    required this.onLogin,
    super.key,
  });

  final ApiService api;
  final VoidCallback onLogin;

  @override
  State<PublicShell> createState() => _PublicShellState();
}

class _PublicShellState extends State<PublicShell> {
  int _selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: canvas,
      appBar: AppBar(
        titleSpacing: 12,
        title: const Row(
          children: [
            BrandLogo(size: 32),
            SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'RAPI-Sekolah',
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                    fontSize: 16,
                    color: Colors.white,
                    letterSpacing: 0.2,
                  ),
                ),
                Text(
                  'SMK Negeri 1 Bantul',
                  style: TextStyle(
                    fontWeight: FontWeight.w500,
                    fontSize: 11,
                    color: Colors.white70,
                  ),
                ),
              ],
            ),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: FilledButton.icon(
              onPressed: widget.onLogin,
              style: FilledButton.styleFrom(
                backgroundColor: accent,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(20),
                ),
                elevation: 1,
              ),
              icon: const Icon(Icons.login, size: 16),
              label: const Text(
                'Masuk',
                style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w800),
              ),
            ),
          ),
        ],
      ),
      body: IndexedStack(
        index: _selectedIndex,
        children: [
          CalendarScreen(
            api: widget.api,
            isEmbedded: true,
          ),
          PublicAboutScreen(onLogin: widget.onLogin),
          PublicServicesScreen(onLogin: widget.onLogin),
          PublicHelpScreen(onLogin: widget.onLogin),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (index) => setState(() => _selectedIndex = index),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.calendar_month_outlined),
            selectedIcon: Icon(Icons.calendar_month),
            label: 'Kalender',
          ),
          NavigationDestination(
            icon: Icon(Icons.info_outline),
            selectedIcon: Icon(Icons.info),
            label: 'Tentang',
          ),
          NavigationDestination(
            icon: Icon(Icons.layers_outlined),
            selectedIcon: Icon(Icons.layers),
            label: 'Layanan',
          ),
          NavigationDestination(
            icon: Icon(Icons.support_agent_outlined),
            selectedIcon: Icon(Icons.support_agent),
            label: 'Bantuan',
          ),
        ],
      ),
    );
  }
}

