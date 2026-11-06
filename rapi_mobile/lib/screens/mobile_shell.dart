import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import 'calendar_screen.dart';
import 'complaints_screen.dart';
import 'damaged_goods_report_screen.dart';
import 'dashboard_screen.dart';
import 'events_screen.dart';
import 'profile_screen.dart';
import 'rooms_screen.dart';
import '../widgets/common_widgets.dart';

class MobileShell extends StatefulWidget {
  const MobileShell({
    required this.api,
    required this.user,
    required this.onLogout,
    required this.onUserUpdated,
    super.key,
  });

  final ApiService api;
  final Map<String, dynamic> user;
  final VoidCallback onLogout;
  final ValueChanged<Map<String, dynamic>> onUserUpdated;

  @override
  State<MobileShell> createState() => _MobileShellState();
}

class _MobileShellState extends State<MobileShell> {
  int _selectedIndex = 0;
  final GlobalKey<ComplaintsScreenState> _complaintsKey = GlobalKey();

  bool get _isPic => widget.user['role'] == 'pic';
  bool get _canManageEvents =>
      ['admin', 'guru', 'organisasi'].contains(widget.user['role']);
  bool get _canCreateComplaints =>
      ['admin', 'guru', 'organisasi'].contains(widget.user['role']);

  List<_NavDestination> get _destinations => [
    const _NavDestination('Beranda', Icons.dashboard_outlined, Icons.dashboard),
    if (_canManageEvents)
      const _NavDestination(
        'Agenda',
        Icons.event_note_outlined,
        Icons.event_note,
      ),
    const _NavDestination(
      'Kalender',
      Icons.calendar_month_outlined,
      Icons.calendar_month,
    ),
    if (_isPic)
      const _NavDestination(
        'Ruangan',
        Icons.meeting_room_outlined,
        Icons.meeting_room,
      ),
    if (_isPic)
      const _NavDestination(
        'Laporan',
        Icons.summarize_outlined,
        Icons.summarize,
      ),
    const _NavDestination(
      'Pengaduan',
      Icons.warning_amber_outlined,
      Icons.warning_amber,
    ),
    const _NavDestination('Profil', Icons.person_outline, Icons.person),
  ];

  void _navigateToTab(String label) {
    final idx = _destinations.indexWhere((d) => d.label == label);
    if (idx >= 0) {
      setState(() => _selectedIndex = idx);
    }
  }

  void _handleReportDamage(Map<String, dynamic> event) {
    _navigateToTab('Pengaduan');
    // Open create complaint modal with initialEvent after frame
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _complaintsKey.currentState?.openCreateForm(initialEvent: event);
    });
  }

  Future<void> _logout() async {
    try {
      await widget.api.logout();
    } catch (_) {
      // Local token cleared regardless
    } finally {
      widget.onLogout();
    }
  }

  @override
  Widget build(BuildContext context) {
    // Immediate safety guard: Admin cannot use mobile shell
    if (widget.user['role'] == 'admin') {
      WidgetsBinding.instance.addPostFrameCallback((_) => _logout());
      return const Scaffold(
        body: Center(
          child: Text(
            'Akun Admin hanya dapat masuk melalui versi desktop.',
            style: TextStyle(color: dangerText),
          ),
        ),
      );
    }

    final userName = widget.user['name']?.toString() ?? 'Pengguna';
    final role = widget.user['role']?.toString();

    final pages = <Widget>[
      DashboardScreen(
        api: widget.api,
        user: widget.user,
        onOpenAgenda: _canManageEvents ? () => _navigateToTab('Agenda') : null,
        onOpenCalendar: () => _navigateToTab('Kalender'),
        onOpenComplaints: () => _navigateToTab('Pengaduan'),
        onOpenRooms: _isPic ? () => _navigateToTab('Ruangan') : null,
        onOpenReport: _isPic ? () => _navigateToTab('Laporan') : null,
        onReportDamage: _handleReportDamage,
      ),
      if (_canManageEvents)
        EventsScreen(
          api: widget.api,
          user: widget.user,
          onReportDamage: _handleReportDamage,
        ),
      CalendarScreen(
        api: widget.api,
        currentUser: widget.user,
        onReportDamage: _handleReportDamage,
      ),
      if (_isPic) RoomsScreen(api: widget.api),
      if (_isPic) DamagedGoodsReportScreen(api: widget.api),
      ComplaintsScreen(
        key: _complaintsKey,
        api: widget.api,
        user: widget.user,
        canCreate: _canCreateComplaints,
      ),
      ProfileScreen(
        api: widget.api,
        user: widget.user,
        onLogout: _logout,
        onUserUpdated: widget.onUserUpdated,
      ),
    ];

    return Scaffold(
      backgroundColor: canvas,
      appBar: AppBar(
        title: Row(
          children: [
            const BrandLogo(size: 32),
            const SizedBox(width: 8),
            const Text(
              'RAPI-Sekolah',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: Colors.white,
                letterSpacing: -0.3,
              ),
            ),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      userName,
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                        color: Colors.white,
                      ),
                    ),
                    Text(
                      roleName(role),
                      style: const TextStyle(fontSize: 10, color: sidebarMuted),
                    ),
                  ],
                ),
                const SizedBox(width: 8),
                CircleAvatar(
                  radius: 17,
                  backgroundColor: accentSoft,
                  foregroundColor: accentHover,
                  child: Text(
                    userName.isEmpty ? 'U' : userName[0].toUpperCase(),
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 13,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
      body: BrandWatermark(
        child: IndexedStack(
          index: _selectedIndex.clamp(0, pages.length - 1),
          children: pages,
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex.clamp(0, _destinations.length - 1),
        onDestinationSelected: (index) =>
            setState(() => _selectedIndex = index),
        destinations: [
          for (final d in _destinations)
            NavigationDestination(
              icon: Icon(d.icon),
              selectedIcon: Icon(d.selectedIcon, color: Colors.white),
              label: d.label,
            ),
        ],
      ),
    );
  }
}

class _NavDestination {
  const _NavDestination(this.label, this.icon, this.selectedIcon);

  final String label;
  final IconData icon;
  final IconData selectedIcon;
}
