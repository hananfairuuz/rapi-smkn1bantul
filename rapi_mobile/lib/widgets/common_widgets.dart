import 'package:flutter/material.dart';
import '../constants.dart';

class BrandLogo extends StatelessWidget {
  const BrandLogo({this.size = 36, super.key});

  final double size;

  @override
  Widget build(BuildContext context) => Image.asset(
    'assets/images/school-logo.png',
    width: size,
    height: size,
    fit: BoxFit.contain,
  );
}

class BrandWatermark extends StatelessWidget {
  const BrandWatermark({required this.child, super.key});

  final Widget child;

  @override
  Widget build(BuildContext context) => Stack(
    fit: StackFit.expand,
    children: [
      IgnorePointer(
        child: Center(
          child: FractionallySizedBox(
            widthFactor: 0.82,
            child: Opacity(
              opacity: 0.07,
              child: Image.asset(
                'assets/images/school-dashboard-watermark.png',
                fit: BoxFit.contain,
              ),
            ),
          ),
        ),
      ),
      child,
    ],
  );
}

class PageHeading extends StatelessWidget {
  const PageHeading({
    required this.title,
    required this.subtitle,
    this.trailing,
    super.key,
  });

  final String title;
  final String subtitle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 20),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.5,
                    color: ink,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: const TextStyle(color: muted, fontSize: 13),
                ),
              ],
            ),
          ),
          if (trailing != null) trailing!,
        ],
      ),
    );
  }
}

class DateLabel extends StatelessWidget {
  const DateLabel({required this.text, super.key});

  final String text;

  @override
  Widget build(BuildContext context) => Text(
    text.toUpperCase(),
    textAlign: TextAlign.end,
    style: const TextStyle(
      color: muted,
      fontSize: 11,
      fontWeight: FontWeight.w700,
      letterSpacing: 0.5,
    ),
  );
}

class StatCard extends StatelessWidget {
  const StatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.caption,
    this.iconColor = accentHover,
    this.iconBackground = accentSoft,
    this.valueColor = ink,
    super.key,
  });

  final IconData icon;
  final String label;
  final String value;
  final String caption;
  final Color iconColor;
  final Color iconBackground;
  final Color valueColor;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: iconBackground,
                    borderRadius: BorderRadius.circular(9),
                  ),
                  child: Icon(icon, color: iconColor, size: 20),
                ),
                const Spacer(),
                Text(
                  label.toUpperCase(),
                  style: const TextStyle(
                    color: muted,
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 0.6,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              value,
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.w800,
                color: valueColor,
                height: 1.1,
              ),
            ),
            const SizedBox(height: 4),
            Text(caption, style: const TextStyle(color: muted, fontSize: 12)),
          ],
        ),
      ),
    );
  }
}

class SectionCard extends StatelessWidget {
  const SectionCard({
    required this.title,
    required this.child,
    this.trailing,
    super.key,
  });

  final String title;
  final Widget child;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: ink,
                  ),
                ),
              ),
              if (trailing != null) trailing!,
            ],
          ),
          const SizedBox(height: 8),
          child,
        ],
      ),
    ),
  );
}

class QuickAction extends StatelessWidget {
  const QuickAction({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
    super.key,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => ListTile(
    contentPadding: EdgeInsets.zero,
    leading: Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: accentSoft,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Icon(icon, color: accentHover, size: 22),
    ),
    title: Text(
      title,
      style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
    ),
    subtitle: Text(
      subtitle,
      style: const TextStyle(color: muted, fontSize: 12),
    ),
    trailing: const Icon(Icons.chevron_right, color: muted),
    onTap: onTap,
  );
}

class NoticeCard extends StatelessWidget {
  const NoticeCard({required this.data, this.onDismiss, super.key});

  final Map<String, dynamic> data;
  final VoidCallback? onDismiss;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(
      color: accentSoft,
      borderRadius: BorderRadius.circular(10),
      border: Border.all(color: line),
    ),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(top: 2, right: 10),
          child: Icon(Icons.info_outline, color: accentHover, size: 20),
        ),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                data['title']?.toString() ?? 'Pemberitahuan',
                style: const TextStyle(
                  fontWeight: FontWeight.w800,
                  color: ink,
                  fontSize: 14,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                data['message']?.toString() ?? '',
                style: const TextStyle(fontSize: 13, color: ink),
              ),
            ],
          ),
        ),
        if (onDismiss != null)
          IconButton(
            tooltip: 'Tandai sudah dibaca',
            icon: const Icon(Icons.check, size: 18, color: accentHover),
            onPressed: onDismiss,
          ),
      ],
    ),
  );
}

class ErrorBanner extends StatelessWidget {
  const ErrorBanner({required this.message, super.key});

  final String message;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    decoration: BoxDecoration(
      color: dangerSoft,
      borderRadius: BorderRadius.circular(8),
      border: Border.all(color: dangerBorder),
    ),
    child: Row(
      children: [
        const Icon(Icons.error_outline, color: dangerText, size: 18),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            message,
            style: const TextStyle(
              color: dangerText,
              fontSize: 13,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ],
    ),
  );
}

class SuccessBanner extends StatelessWidget {
  const SuccessBanner({required this.message, super.key});

  final String message;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    decoration: BoxDecoration(
      color: successSoft,
      borderRadius: BorderRadius.circular(8),
      border: Border.all(color: successBorder),
    ),
    child: Row(
      children: [
        const Icon(Icons.check_circle_outline, color: successText, size: 18),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            message,
            style: const TextStyle(
              color: successText,
              fontSize: 13,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ],
    ),
  );
}

class ErrorState extends StatelessWidget {
  const ErrorState({required this.error, required this.onRetry, super.key});

  final Object? error;
  final Future<void> Function() onRetry;

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.cloud_off_outlined, size: 42, color: muted),
          const SizedBox(height: 12),
          Text(
            error.toString().replaceFirst('Exception: ', ''),
            textAlign: TextAlign.center,
            style: const TextStyle(color: muted, fontSize: 14),
          ),
          const SizedBox(height: 14),
          OutlinedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: const Text('Coba lagi'),
          ),
        ],
      ),
    ),
  );
}

class LoadingState extends StatelessWidget {
  const LoadingState({super.key});

  @override
  Widget build(BuildContext context) =>
      const Center(child: CircularProgressIndicator(color: accent));
}

class EmptyState extends StatelessWidget {
  const EmptyState({required this.message, super.key});

  final String message;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 24),
    child: Center(
      child: Text(message, style: const TextStyle(color: muted, fontSize: 14)),
    ),
  );
}

class StatusBadge extends StatelessWidget {
  const StatusBadge({required this.status, super.key});

  final String status;

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color fg;
    String label;
    switch (status) {
      case 'approved':
        bg = successSoft;
        fg = successText;
        label = 'DISETUJUI';
      case 'rejected':
        bg = dangerSoft;
        fg = dangerText;
        label = 'DITOLAK';
      case 'pending':
        bg = warningSoft;
        fg = pendingText;
        label = 'PENDING';
      case 'Sudah Diperbaiki':
        bg = successSoft;
        fg = successText;
        label = 'SUDAH DIPERBAIKI';
      case 'Menunggu Spare Part':
        bg = warningSoft;
        fg = pendingText;
        label = 'MENUNGGU SPARE PART';
      case 'Belum Diperbaiki':
        bg = dangerSoft;
        fg = dangerText;
        label = 'BELUM DIPERBAIKI';
      default:
        bg = canvas;
        fg = muted;
        label = status.toUpperCase();
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: fg,
          fontSize: 10,
          fontWeight: FontWeight.w800,
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}

// Fullscreen interactive photo view with zoom & pan
class PhotoViewDialog extends StatelessWidget {
  const PhotoViewDialog({
    required this.photoUri,
    required this.headers,
    this.title = 'Foto Bukti Kerusakan',
    super.key,
  });

  final Uri photoUri;
  final Map<String, String> headers;
  final String title;

  static void show(
    BuildContext context,
    Uri photoUri,
    Map<String, String> headers, {
    String title = 'Foto Bukti Kerusakan',
  }) {
    showDialog(
      context: context,
      builder: (_) =>
          PhotoViewDialog(photoUri: photoUri, headers: headers, title: title),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Dialog.fullscreen(
      child: Scaffold(
        backgroundColor: Colors.black,
        appBar: AppBar(
          backgroundColor: Colors.black87,
          foregroundColor: Colors.white,
          title: Text(title, style: const TextStyle(fontSize: 16)),
          actions: [
            IconButton(
              icon: const Icon(Icons.close),
              onPressed: () => Navigator.pop(context),
            ),
          ],
        ),
        body: Center(
          child: InteractiveViewer(
            minScale: 0.5,
            maxScale: 4.0,
            child: Image.network(
              photoUri.toString(),
              headers: headers,
              fit: BoxFit.contain,
              errorBuilder: (_, __, ___) => const Center(
                child: Text(
                  'Foto tidak dapat dimuat.',
                  style: TextStyle(color: Colors.white70),
                ),
              ),
              loadingBuilder: (context, child, progress) {
                if (progress == null) return child;
                return const Center(
                  child: CircularProgressIndicator(color: accent),
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}

// Modal bottom sheet to view detailed information of an event
void showEventDetailSheet({
  required BuildContext context,
  required Map<String, dynamic> event,
  VoidCallback? onReportDamage,
}) {
  final room = asMap(event['room']);
  final user = asMap(event['user']);
  final status = event['status']?.toString() ?? 'pending';
  final isOvernight = eventEndsNextDay(event);

  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
    ),
    builder: (context) {
      return Padding(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 28),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      event['title']?.toString() ?? 'Agenda',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                        color: ink,
                      ),
                    ),
                  ),
                  StatusBadge(status: status),
                ],
              ),
              const SizedBox(height: 14),
              _detailRow(
                Icons.meeting_room_outlined,
                'Ruangan',
                '${room['name'] ?? 'Ruangan'}${room['location'] != null && room['location'].toString().isNotEmpty ? ' (${room['location']})' : ''}',
              ),
              _detailRow(
                Icons.calendar_today_outlined,
                'Tanggal',
                eventDateRange(event),
              ),
              _detailRow(
                Icons.access_time,
                'Waktu',
                '${shortTime(event['start_time'])}–${shortTime(event['end_time'])}${isOvernight && event['end_date'] == null ? ' (+1 hari berikutnya)' : ''}',
              ),
              if (event['borrower_name'] != null &&
                  event['borrower_name'].toString().isNotEmpty)
                _detailRow(
                  Icons.person_outline,
                  'Peminjam Ruangan',
                  '${event['borrower_name']}${event['borrower_whatsapp'] != null ? ' (${event['borrower_whatsapp']})' : ''}',
                ),
              if (user['name'] != null)
                _detailRow(
                  Icons.account_circle_outlined,
                  'Akun Pemohon',
                  user['name'].toString(),
                ),
              if (event['description'] != null &&
                  event['description'].toString().trim().isNotEmpty) ...[
                const SizedBox(height: 10),
                const Text(
                  'Deskripsi Kegiatan',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                ),
                const SizedBox(height: 4),
                Text(
                  event['description'].toString(),
                  style: const TextStyle(color: ink, fontSize: 14),
                ),
              ],
              if (event['move_reason'] != null &&
                  event['move_reason'].toString().trim().isNotEmpty) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: warningSoft,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info, color: warningText, size: 18),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Ruangan dipindahkan: ${event['move_reason']}',
                          style: const TextStyle(
                            color: warningText,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              if (status == 'rejected' &&
                  event['rejection_reason'] != null &&
                  event['rejection_reason'].toString().trim().isNotEmpty) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: dangerSoft,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.cancel, color: dangerText, size: 18),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Alasan penolakan: ${event['rejection_reason']}',
                          style: const TextStyle(
                            color: dangerText,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              if (onReportDamage != null && status == 'approved') ...[
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(
                      backgroundColor: warning,
                      foregroundColor: Colors.white,
                    ),
                    onPressed: () {
                      Navigator.pop(context);
                      onReportDamage();
                    },
                    icon: const Icon(Icons.report_problem_outlined),
                    label: const Text('Catat Barang Rusak'),
                  ),
                ),
              ],
            ],
          ),
        ),
      );
    },
  );
}

Widget _detailRow(IconData icon, String label, String value) {
  return Padding(
    padding: const EdgeInsets.symmetric(vertical: 6),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: muted),
        const SizedBox(width: 10),
        SizedBox(
          width: 120,
          child: Text(
            label,
            style: const TextStyle(color: muted, fontSize: 13),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(
              fontWeight: FontWeight.w600,
              color: ink,
              fontSize: 13,
            ),
          ),
        ),
      ],
    ),
  );
}
