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
  int _unreadNotifCount = 0;
  List<dynamic> _notifications = [];

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  Future<void> _fetchNotifications() async {
    try {
      final res = await widget.api.getNotifications();
      if (mounted) {
        setState(() {
          _notifications = List<dynamic>.from(res['notifications'] as List? ?? []);
          _unreadNotifCount = (res['unread_count'] as num? ?? 0).toInt();
        });
      }
    } catch (_) {
      // ignore
    }
  }

  Future<void> _markAllRead() async {
    try {
      await widget.api.markAllNotificationsRead();
      await _fetchNotifications();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Semua notifikasi ditandai sudah dibaca.')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal memperbarui: $e')),
        );
      }
    }
  }

  Future<void> _markOneRead(int id) async {
    try {
      await widget.api.markNotificationRead(id);
      await _fetchNotifications();
    } catch (_) {
      // ignore
    }
  }

  void _onNotificationTap(Map<String, dynamic> notif) async {
    Navigator.of(context).pop();
    final id = notif['id'] as int?;
    if (id != null && notif['read_at'] == null) {
      await _markOneRead(id);
    }

    final text = '${notif['title']} ${notif['message']}'.toLowerCase();
    if (text.contains('fasilitas') ||
        text.contains('kerusakan') ||
        text.contains('pengaduan') ||
        text.contains('perbaikan')) {
      _navigateToTab('Pengaduan');
      return;
    }

    if (text.contains('penugasan pic') || text.contains('pic ruangan')) {
      if (_canAccessRooms) {
        _navigateToTab('Ruangan');
      }
      return;
    }

    if (notif['event_id'] != null ||
        text.contains('agenda') ||
        text.contains('peminjaman') ||
        text.contains('jadwal') ||
        text.contains('dikembalikan')) {
      if (_canManageEvents) {
        _navigateToTab('Agenda');
      } else {
        _navigateToTab('Kalender');
      }
      return;
    }
  }

  void _showNotificationsSheet() {
    _fetchNotifications();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) => Container(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.75,
          ),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Center(
                child: Container(
                  margin: const EdgeInsets.only(top: 10, bottom: 6),
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: line,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                child: Row(
                  children: [
                    const Icon(Icons.notifications_active, color: accentHover, size: 22),
                    const SizedBox(width: 8),
                    const Text(
                      'Notifikasi',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: ink,
                      ),
                    ),
                    if (_unreadNotifCount > 0) ...[
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: dangerSoft,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: dangerBorder),
                        ),
                        child: Text(
                          '$_unreadNotifCount baru',
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: dangerText,
                          ),
                        ),
                      ),
                    ],
                    const Spacer(),
                    if (_unreadNotifCount > 0)
                      TextButton.icon(
                        style: TextButton.styleFrom(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          visualDensity: VisualDensity.compact,
                        ),
                        icon: const Icon(Icons.done_all, size: 16, color: accentHover),
                        label: const Text(
                          'Tandai dibaca',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                            color: accentHover,
                          ),
                        ),
                        onPressed: () async {
                          await _markAllRead();
                          setSheetState(() {});
                        },
                      ),
                  ],
                ),
              ),
              const Divider(height: 1, color: line),
              Flexible(
                child: _notifications.isEmpty
                    ? const Padding(
                        padding: EdgeInsets.symmetric(vertical: 48, horizontal: 24),
                        child: Center(
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.notifications_none_outlined, size: 48, color: muted),
                              SizedBox(height: 12),
                              Text(
                                'Tidak ada notifikasi',
                                style: TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w700,
                                  color: ink,
                                ),
                              ),
                              SizedBox(height: 4),
                              Text(
                                'Pemberitahuan agenda atau pengaduan akan muncul di sini.',
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 12, color: muted),
                              ),
                            ],
                          ),
                        ),
                      )
                    : ListView.separated(
                        shrinkWrap: true,
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        itemCount: _notifications.length,
                        separatorBuilder: (_, __) => const Divider(height: 1, color: line),
                        itemBuilder: (context, index) {
                          final item = asMap(_notifications[index]);
                          final isUnread = item['read_at'] == null;
                          final id = item['id'] as int?;

                          return InkWell(
                            onTap: () => _onNotificationTap(item),
                            child: Container(
                              color: isUnread ? accentSoft.withOpacity(0.4) : Colors.transparent,
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Padding(
                                    padding: const EdgeInsets.only(top: 2, right: 12),
                                    child: Icon(
                                      isUnread
                                          ? Icons.mark_email_unread_outlined
                                          : Icons.mark_email_read_outlined,
                                      size: 20,
                                      color: isUnread ? accentHover : muted,
                                    ),
                                  ),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          item['title']?.toString() ?? 'Pemberitahuan',
                                          style: TextStyle(
                                            fontSize: 13,
                                            fontWeight: isUnread ? FontWeight.w800 : FontWeight.w600,
                                            color: ink,
                                          ),
                                        ),
                                        const SizedBox(height: 3),
                                        Text(
                                          item['message']?.toString() ?? '',
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: isUnread ? ink : muted,
                                            height: 1.35,
                                          ),
                                        ),
                                        if (item['created_at'] != null) ...[
                                          const SizedBox(height: 4),
                                          Text(
                                            formatDateValue(item['created_at']),
                                            style: const TextStyle(fontSize: 10, color: muted),
                                          ),
                                        ],
                                      ],
                                    ),
                                  ),
                                  if (isUnread && id != null)
                                    IconButton(
                                      icon: const Icon(Icons.check, size: 18, color: accentHover),
                                      tooltip: 'Tandai dibaca',
                                      onPressed: () async {
                                        await _markOneRead(id);
                                        setSheetState(() {});
                                      },
                                    ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  bool get _isPic => widget.user['role'] == 'pic';
  bool get _canManageEvents =>
      ['admin', 'guru', 'organisasi'].contains(widget.user['role']);
  bool get _canCreateComplaints =>
      ['admin', 'guru', 'organisasi'].contains(widget.user['role']);
  bool get _canAccessRooms =>
      ['admin', 'guru', 'organisasi', 'pic'].contains(widget.user['role']);
  bool get _canManageRooms => widget.user['role'] == 'admin';

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
    if (_canAccessRooms)
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
        onOpenRooms: _canAccessRooms ? () => _navigateToTab('Ruangan') : null,
        onOpenReport: _isPic ? () => _navigateToTab('Laporan') : null,
        onReportDamage: _handleReportDamage,
        onNotificationsChanged: _fetchNotifications,
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
      if (_canAccessRooms)
        RoomsScreen(
          api: widget.api,
          user: widget.user,
          canManage: _canManageRooms,
        ),
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
          IconButton(
            tooltip: 'Notifikasi',
            icon: Badge(
              isLabelVisible: _unreadNotifCount > 0,
              label: Text(
                _unreadNotifCount > 99 ? '99+' : '$_unreadNotifCount',
                style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
              ),
              backgroundColor: danger,
              child: const Icon(
                Icons.notifications_outlined,
                color: Colors.white,
                size: 24,
              ),
            ),
            onPressed: _showNotificationsSheet,
          ),
          Padding(
            padding: const EdgeInsets.only(right: 16, left: 4),
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
        onDestinationSelected: (index) {
          setState(() => _selectedIndex = index);
          _fetchNotifications();
        },
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
