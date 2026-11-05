import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({
    required this.api,
    required this.user,
    this.onOpenAgenda,
    this.onOpenCalendar,
    this.onOpenComplaints,
    this.onOpenRooms,
    this.onOpenReport,
    this.onReportDamage,
    super.key,
  });

  final ApiService api;
  final Map<String, dynamic> user;
  final VoidCallback? onOpenAgenda;
  final VoidCallback? onOpenCalendar;
  final VoidCallback? onOpenComplaints;
  final VoidCallback? onOpenRooms;
  final VoidCallback? onOpenReport;
  final ValueChanged<Map<String, dynamic>>? onReportDamage;

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late Future<Map<String, dynamic>> _dashboard;

  @override
  void initState() {
    super.initState();
    _dashboard = widget.api.getDashboard();
  }

  Future<void> _refresh() async {
    final next = widget.api.getDashboard();
    setState(() => _dashboard = next);
    await next;
  }

  Future<void> _dismissNotification(int id) async {
    try {
      await widget.api.markNotificationRead(id);
      await _refresh();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Notifikasi ditandai sudah dibaca.')),
        );
      }
    } catch (_) {
      // ignore
    }
  }

  void _showEventDetails(Map<String, dynamic> event) {
    final userRole = widget.user['role']?.toString();
    final canReport =
        (userRole == 'guru' || userRole == 'organisasi') &&
        widget.onReportDamage != null &&
        event['status'] == 'approved';

    showEventDetailSheet(
      context: context,
      event: event,
      onReportDamage: canReport ? () => widget.onReportDamage!(event) : null,
    );
  }

  @override
  Widget build(BuildContext context) {
    final name = widget.user['name']?.toString() ?? 'Pengguna';
    final role = widget.user['role']?.toString();

    return FutureBuilder<Map<String, dynamic>>(
      future: _dashboard,
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return ErrorState(error: snapshot.error, onRetry: _refresh);
        }
        if (!snapshot.hasData) return const LoadingState();

        final data = snapshot.data!;
        final stats = asMap(data['stats']);
        final events = List<dynamic>.from(data['recent_events'] as List? ?? []);
        final notifications = List<dynamic>.from(
          data['notifications'] as List? ?? [],
        );

        final totalRooms = (stats['total_rooms'] as num? ?? 0).toInt();
        final roomsInUse = (stats['rooms_in_use'] as num? ?? 0).toInt();
        final availableRooms = (totalRooms - roomsInUse).clamp(0, 999);

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 28),
            children: [
              PageHeading(
                title: 'Selamat datang, $name!',
                subtitle:
                    'Ringkasan aktivitas dan infrastruktur sekolah hari ini.',
                trailing: DateLabel(text: formatDate(DateTime.now())),
              ),
              for (final notification in notifications) ...[
                NoticeCard(
                  data: asMap(notification),
                  onDismiss: () =>
                      _dismissNotification(asMap(notification)['id'] as int),
                ),
                const SizedBox(height: 12),
              ],
              StatCard(
                icon: Icons.meeting_room_outlined,
                label: 'Total Ruangan',
                value: '$totalRooms',
                caption: '$availableRooms tersedia · $roomsInUse terjadwal',
                iconColor: accentHover,
                iconBackground: accentSoft,
              ),
              const SizedBox(height: 10),
              StatCard(
                icon: Icons.event_available_outlined,
                label: 'Agenda Hari Ini',
                value: '${stats['today_events'] ?? 0}',
                caption: '${stats['ongoing_events'] ?? 0} sedang berlangsung',
                iconColor: warningText,
                iconBackground: warningSoft,
              ),
              const SizedBox(height: 10),
              StatCard(
                icon: Icons.warning_amber_outlined,
                label: 'Aduan Pending',
                value: '${stats['pending_complaints'] ?? 0}',
                caption: 'Perlu tindak lanjut PIC',
                iconColor: danger,
                iconBackground: dangerSoft,
                valueColor: danger,
              ),
              const SizedBox(height: 18),
              SectionCard(
                title: 'Agenda Terkini',
                trailing: widget.onOpenAgenda != null
                    ? TextButton(
                        onPressed: widget.onOpenAgenda,
                        child: const Text('Lihat semua'),
                      )
                    : null,
                child: events.isEmpty
                    ? const EmptyState(message: 'Belum ada agenda ruangan.')
                    : Column(
                        children: [
                          for (final event in events)
                            _RecentEventRow(
                              data: asMap(event),
                              onTap: () => _showEventDetails(asMap(event)),
                            ),
                        ],
                      ),
              ),
              const SizedBox(height: 14),
              SectionCard(
                title: 'Aksi Cepat',
                child: Column(
                  children: [
                    if (widget.onOpenAgenda != null)
                      QuickAction(
                        icon: Icons.event_available_outlined,
                        title: 'Ajukan agenda',
                        subtitle: 'Pesan ruangan sekolah',
                        onTap: widget.onOpenAgenda!,
                      ),
                    if (widget.onOpenCalendar != null)
                      QuickAction(
                        icon: Icons.calendar_month_outlined,
                        title: 'Lihat kalender',
                        subtitle: 'Periksa agenda yang sudah disetujui',
                        onTap: widget.onOpenCalendar!,
                      ),
                    if (widget.onOpenAgenda != null &&
                        (role == 'guru' || role == 'organisasi'))
                      QuickAction(
                        icon: Icons.list_alt_outlined,
                        title: 'Pantau agenda saya',
                        subtitle: 'Lihat pengajuan dan status agenda',
                        onTap: widget.onOpenAgenda!,
                      ),
                    if (widget.onOpenComplaints != null &&
                        (role == 'guru' || role == 'organisasi'))
                      QuickAction(
                        icon: Icons.report_gmailerrorred_outlined,
                        title: 'Lapor kerusakan',
                        subtitle: 'Kirim laporan fasilitas rusak',
                        onTap: widget.onOpenComplaints!,
                      ),
                    if (widget.onOpenComplaints != null && role == 'pic')
                      QuickAction(
                        icon: Icons.assignment_outlined,
                        title: 'Kelola pengaduan',
                        subtitle: 'Review & perbarui status perbaikan',
                        onTap: widget.onOpenComplaints!,
                      ),
                    if (widget.onOpenRooms != null && role == 'pic')
                      QuickAction(
                        icon: Icons.meeting_room_outlined,
                        title: 'Lihat ruangan',
                        subtitle: 'Periksa ruangan yang menjadi tanggung jawab',
                        onTap: widget.onOpenRooms!,
                      ),
                    if (widget.onOpenReport != null && role == 'pic')
                      QuickAction(
                        icon: Icons.summarize_outlined,
                        title: 'Laporan barang rusak',
                        subtitle: 'Lihat rekapitulasi bulanan',
                        onTap: widget.onOpenReport!,
                      ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _RecentEventRow extends StatelessWidget {
  const _RecentEventRow({required this.data, required this.onTap});

  final Map<String, dynamic> data;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final room = asMap(data['room']);
    final user = asMap(data['user']);
    final status = data['status']?.toString() ?? 'pending';
    final isOvernight = eventEndsNextDay(data);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 9, horizontal: 4),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 4,
              height: 44,
              decoration: BoxDecoration(
                color: statusColor(status),
                borderRadius: BorderRadius.circular(3),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    data['title']?.toString() ?? 'Agenda',
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                      color: ink,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${room['name'] ?? 'Ruangan'} · ${user['name'] ?? ''}',
                    style: const TextStyle(fontSize: 12, color: muted),
                  ),
                  const SizedBox(height: 1),
                  Text(
                    '${eventDateRange(data)} (${shortTime(data['start_time'])}–${shortTime(data['end_time'])}${isOvernight && data['end_date'] == null ? ' +1' : ''})',
                    style: const TextStyle(
                      fontSize: 11,
                      color: muted,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            StatusBadge(status: status),
          ],
        ),
      ),
    );
  }
}
