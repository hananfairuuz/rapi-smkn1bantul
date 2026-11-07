import 'package:flutter/material.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class EventsScreen extends StatefulWidget {
  const EventsScreen({
    required this.api,
    required this.user,
    this.onReportDamage,
    super.key,
  });

  final ApiService api;
  final Map<String, dynamic> user;
  final ValueChanged<Map<String, dynamic>>? onReportDamage;

  @override
  State<EventsScreen> createState() => _EventsScreenState();
}

class _EventsScreenState extends State<EventsScreen> {
  late Future<List<dynamic>> _events;
  String _filter = 'all';
  String _search = '';
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _events = widget.api.getEvents();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  bool _isUpcoming(Map<String, dynamic> event, DateTime now) {
    if (event['status'] != 'approved') return false;
    final date = DateTime.tryParse(event['date']?.toString() ?? '');
    if (date == null) return false;
    final today = DateTime(now.year, now.month, now.day);
    final lastUpcomingDay = today
        .add(const Duration(days: 4))
        .subtract(const Duration(microseconds: 1));
    final startDay = DateTime(date.year, date.month, date.day);
    final startTime = event['start_time']?.toString() ?? '00:00';
    final timeParts = startTime.split(':');
    final startAt = DateTime(
      date.year,
      date.month,
      date.day,
      int.tryParse(timeParts.first) ?? 0,
      timeParts.length > 1 ? int.tryParse(timeParts[1]) ?? 0 : 0,
    );
    return !startAt.isBefore(now) &&
        !startDay.isBefore(today) &&
        !startAt.isAfter(lastUpcomingDay);
  }

  bool _isToday(Map<String, dynamic> event, DateTime now) {
    if (event['status'] != 'approved') return false;
    final startDate = DateTime.tryParse(event['date']?.toString() ?? '');
    if (startDate == null) return false;
    final endDate =
        DateTime.tryParse(event['end_date']?.toString() ?? '') ?? startDate;
    final today = DateTime(now.year, now.month, now.day);
    var effectiveEndDate = DateTime(endDate.year, endDate.month, endDate.day);
    final endTimeParts = (event['end_time']?.toString() ?? '00:00').split(':');
    final startTimeParts = (event['start_time']?.toString() ?? '00:00').split(
      ':',
    );
    final endMinutes =
        (int.tryParse(endTimeParts.first) ?? 0) * 60 +
        (endTimeParts.length > 1 ? int.tryParse(endTimeParts[1]) ?? 0 : 0);
    final startMinutes =
        (int.tryParse(startTimeParts.first) ?? 0) * 60 +
        (startTimeParts.length > 1 ? int.tryParse(startTimeParts[1]) ?? 0 : 0);
    if (event['end_date'] == null && endMinutes < startMinutes) {
      effectiveEndDate = DateTime(
        startDate.year,
        startDate.month,
        startDate.day,
      ).add(const Duration(days: 1));
    }
    return !today.isBefore(
          DateTime(startDate.year, startDate.month, startDate.day),
        ) &&
        !today.isAfter(effectiveEndDate);
  }

  String _searchableText(Map<String, dynamic> event) {
    final room = asMap(event['room']);
    return [
      event['title'],
      event['description'],
      event['borrower_name'],
      room['name'],
    ].map((value) => value?.toString() ?? '').join(' ').toLowerCase();
  }

  Future<void> _reload() async {
    final next = widget.api.getEvents();
    setState(() => _events = next);
    await next;
  }

  Future<void> _createEvent() async {
    final created = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => EventForm(api: widget.api, user: widget.user),
    );
    if (created == true) {
      await _reload();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Pengajuan agenda berhasil dikirim dan menunggu persetujuan.',
            ),
          ),
        );
      }
    }
  }

  bool _canDeleteEvent(Map<String, dynamic> event) {
    return event['can_delete'] == true;
  }

  Future<void> _deleteEvent(Map<String, dynamic> event) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus agenda?'),
        content: Text(
          'Agenda "${event['title'] ?? 'Agenda'}" akan dihapus permanen.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: danger),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Hapus'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await widget.api.deleteEvent(event['id'] as int);
      await _reload();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Agenda berhasil dihapus.')),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Gagal menghapus agenda: $error')),
        );
      }
    }
  }

  Future<void> _handleCheckout(Map<String, dynamic> event) async {
    final eventId = event['id'] as int;
    final room = asMap(event['room']);
    String condition = 'aman';
    final notesController = TextEditingController();
    bool submitting = false;
    String? errorMessage;

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.fromLTRB(
                20,
                16,
                20,
                MediaQuery.viewInsetsOf(context).bottom + 20,
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Serah Terima Ruangan',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: ink,
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(context, false),
                        ),
                      ],
                    ),
                    Text(
                      'Konfirmasi pengembalian ${room['name'] ?? 'ruangan'} setelah agenda "${event['title'] ?? 'Agenda'}" selesai digunakan.',
                      style: const TextStyle(color: muted, fontSize: 13),
                    ),
                    const SizedBox(height: 14),
                    if (errorMessage != null) ...[
                      ErrorBanner(message: errorMessage!),
                      const SizedBox(height: 12),
                    ],
                    const Text(
                      'Kondisi Fasilitas Ruangan saat Pengembalian:',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                        color: ink,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      decoration: BoxDecoration(
                        border: Border.all(
                          color: condition == 'aman' ? success : line,
                          width: condition == 'aman' ? 2 : 1,
                        ),
                        borderRadius: BorderRadius.circular(10),
                        color: condition == 'aman' ? const Color(0xFFF0FDF4) : Colors.white,
                      ),
                      child: RadioListTile<String>(
                        value: 'aman',
                        groupValue: condition,
                        activeColor: success,
                        title: const Text(
                          'Aman, Bersih, & Lengkap',
                          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
                        ),
                        subtitle: const Text(
                          'Seluruh fasilitas, sarana, dan kebersihan ruangan dalam kondisi baik.',
                          style: TextStyle(fontSize: 12, color: muted),
                        ),
                        onChanged: (val) {
                          if (val != null) setModalState(() => condition = val);
                        },
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      decoration: BoxDecoration(
                        border: Border.all(
                          color: condition == 'rusak' ? danger : line,
                          width: condition == 'rusak' ? 2 : 1,
                        ),
                        borderRadius: BorderRadius.circular(10),
                        color: condition == 'rusak' ? const Color(0xFFFEF2F2) : Colors.white,
                      ),
                      child: RadioListTile<String>(
                        value: 'rusak',
                        groupValue: condition,
                        activeColor: danger,
                        title: const Text(
                          'Ada Kerusakan Fasilitas',
                          style: TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 14,
                            color: dangerText,
                          ),
                        ),
                        subtitle: const Text(
                          'Ditemukan kerusakan/kehilangan barang selama atau setelah pemakaian.',
                          style: TextStyle(fontSize: 12, color: muted),
                        ),
                        onChanged: (val) {
                          if (val != null) setModalState(() => condition = val);
                        },
                      ),
                    ),
                    const SizedBox(height: 14),
                    TextField(
                      controller: notesController,
                      maxLines: 3,
                      decoration: const InputDecoration(
                        labelText: 'Catatan Pengembalian (Opsional)',
                        hintText: 'Contoh: AC dan lampu sudah dimatikan, kunci diserahkan ke piket.',
                      ),
                    ),
                    const SizedBox(height: 18),
                    FilledButton(
                      style: FilledButton.styleFrom(
                        backgroundColor: condition == 'rusak'
                            ? const Color(0xFFDC2626)
                            : const Color(0xFF0284C7),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed: submitting
                          ? null
                          : () async {
                              setModalState(() {
                                submitting = true;
                                errorMessage = null;
                              });
                              try {
                                await widget.api.checkoutEvent(
                                  eventId,
                                  condition: condition,
                                  notes: notesController.text.trim(),
                                );
                                if (ctx.mounted) {
                                  Navigator.pop(ctx, true);
                                }
                              } catch (e) {
                                setModalState(() {
                                  submitting = false;
                                  errorMessage = e.toString();
                                });
                              }
                            },
                      child: submitting
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              condition == 'rusak'
                                  ? 'Konfirmasi & Lanjut Lapor Kerusakan'
                                  : 'Konfirmasi Ruangan Selesai Digunakan',
                              style: const TextStyle(fontWeight: FontWeight.w700),
                            ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );

    if (result == true) {
      await _reload();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Ruangan berhasil dikonfirmasi selesai digunakan.'),
            backgroundColor: success,
          ),
        );
      }
      if (condition == 'rusak' && widget.onReportDamage != null) {
        widget.onReportDamage!(event);
      }
    }
  }

  Future<void> _showReceiptDialog(Map<String, dynamic> event) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => const Center(child: CircularProgressIndicator()),
    );

    final Map<String, dynamic> receiptData;
    try {
      final res = await widget.api.getEventReceipt(event['id'] as int);
      receiptData = asMap(res['receipt']);
    } catch (e) {
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gagal memuat surat izin: $e'),
            backgroundColor: danger,
          ),
        );
      }
      return;
    }

    if (mounted) {
      Navigator.pop(context);
    }

    if (!mounted) return;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => _ReceiptSheet(
        receipt: receiptData,
        onPrintPdf: () => _printReceiptPdf(receiptData),
      ),
    );
  }

  Future<void> _printReceiptPdf(Map<String, dynamic> receipt) async {
    final pdf = pw.Document();

    pdf.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.all(36),
        build: (pw.Context ctx) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              pw.Center(
                child: pw.Column(
                  children: [
                    pw.Text(
                      'PEMERINTAH DAERAH DAERAH ISTIMEWA YOGYAKARTA',
                      style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 11),
                    ),
                    pw.Text(
                      'DINAS PENDIDIKAN, PEMUDA, DAN OLAHRAGA',
                      style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 11),
                    ),
                    pw.SizedBox(height: 2),
                    pw.Text(
                      'SMK NEGERI 1 BANTUL',
                      style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 16),
                    ),
                    pw.Text(
                      'Jalan Parangtritis KM 11, Sabdodadi, Bantul, D.I. Yogyakarta 55715',
                      style: const pw.TextStyle(fontSize: 9.5),
                    ),
                    pw.Text(
                      'Telepon: (0274) 367156 | Website: https://rapi-smkn1bantul.web.id',
                      style: const pw.TextStyle(fontSize: 9.5),
                    ),
                  ],
                ),
              ),
              pw.SizedBox(height: 8),
              pw.Divider(thickness: 1.5),
              pw.SizedBox(height: 12),
              pw.Center(
                child: pw.Column(
                  children: [
                    pw.Text(
                      'SURAT BUKTI IZIN PEMINJAMAN RUANGAN',
                      style: pw.TextStyle(
                        fontWeight: pw.FontWeight.bold,
                        fontSize: 13,
                        decoration: pw.TextDecoration.underline,
                      ),
                    ),
                    pw.SizedBox(height: 4),
                    pw.Text(
                      'Nomor Verifikasi: ${receipt['verification_code'] ?? '-'}',
                      style: const pw.TextStyle(fontSize: 10),
                    ),
                  ],
                ),
              ),
              pw.SizedBox(height: 14),
              pw.Text(
                'Berdasarkan pengajuan permohonan melalui Sistem RAPI (Ruang dan Agenda Pinjam), dengan ini diberikan izin pemakaian fasilitas ruangan sekolah kepada:',
                style: const pw.TextStyle(fontSize: 10),
              ),
              pw.SizedBox(height: 12),
              pw.TableHelper.fromTextArray(
                headers: ['Informasi', 'Rincian Penggunaan Ruangan'],
                data: [
                  ['Nama Agenda / Kegiatan', receipt['title']?.toString() ?? '-'],
                  ['Ruangan Dipinjam', receipt['room_name']?.toString() ?? '-'],
                  ['Lokasi Ruangan', receipt['room_location']?.toString() ?? '-'],
                  ['Penanggung Jawab / Peminjam', receipt['borrower_name']?.toString() ?? '-'],
                  ['Kontak WhatsApp', receipt['borrower_whatsapp']?.toString() ?? '-'],
                  ['Hari & Tanggal Pelaksanaan', receipt['date_formatted']?.toString() ?? receipt['date']?.toString() ?? '-'],
                  ['Waktu Penggunaan', '${receipt['start_time']} - ${receipt['end_time']} WIB'],
                  ['Diajukan Oleh (Akun)', receipt['applicant_name']?.toString() ?? '-'],
                  ['Waktu Disetujui', receipt['approved_at']?.toString() ?? '-'],
                  ['Status Verifikasi', 'RESMI DISETUJUI & TERCATAT SISTEM'],
                  [
                    'Status Pengembalian',
                    receipt['returned_at'] != null
                        ? 'Selesai & Dikembalikan pada ${receipt['returned_at']} (Kondisi: ${receipt['checkout_condition'] == 'rusak' ? 'Ada Kerusakan Dilaporkan' : 'Aman & Bersih'})'
                        : 'Belum Selesai / Belum Dikembalikan',
                  ],
                ],
                headerStyle: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9.5),
                headerDecoration: const pw.BoxDecoration(color: PdfColors.grey200),
                cellStyle: const pw.TextStyle(fontSize: 9),
                cellPadding: const pw.EdgeInsets.symmetric(horizontal: 8, vertical: 5),
              ),
              if (receipt['description'] != null &&
                  receipt['description'].toString().trim().isNotEmpty) ...[
                pw.SizedBox(height: 10),
                pw.Text('Keterangan Kegiatan:',
                    style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9.5)),
                pw.SizedBox(height: 2),
                pw.Text(receipt['description'].toString(),
                    style: const pw.TextStyle(fontSize: 9)),
              ],
              pw.SizedBox(height: 12),
              pw.Container(
                padding: const pw.EdgeInsets.all(8),
                decoration: pw.BoxDecoration(
                  border: pw.Border.all(color: PdfColors.grey400, width: 0.5),
                  borderRadius: const pw.BorderRadius.all(pw.Radius.circular(4)),
                ),
                child: pw.Column(
                  crossAxisAlignment: pw.CrossAxisAlignment.start,
                  children: [
                    pw.Text('Ketentuan Pemakaian Fasilitas:',
                        style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 8.5)),
                    pw.SizedBox(height: 3),
                    pw.Text('1. Wajib menjaga ketertiban, kebersihan, dan keutuhan fasilitas selama pemakaian.',
                        style: const pw.TextStyle(fontSize: 8)),
                    pw.Text('2. Dilarang memindahkan atau mengubah tata letak barang/peralatan tanpa izin petugas sarpras.',
                        style: const pw.TextStyle(fontSize: 8)),
                    pw.Text('3. Setelah kegiatan selesai, wajib mematikan lampu & AC, membersihkan ruangan, serta melakukan konfirmasi pengembalian (check-out) melalui aplikasi RAPI.',
                        style: const pw.TextStyle(fontSize: 8)),
                  ],
                ),
              ),
              pw.Spacer(),
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.center,
                    children: [
                      pw.Text('Peminjam / Penanggung Jawab,', style: const pw.TextStyle(fontSize: 9.5)),
                      pw.SizedBox(height: 42),
                      pw.Text(receipt['borrower_name']?.toString() ?? '-',
                          style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9.5)),
                    ],
                  ),
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.center,
                    children: [
                      pw.Text('Bantul, ${receipt['date_formatted'] ?? ''}', style: const pw.TextStyle(fontSize: 9.5)),
                      pw.Text('Pengelola Sarpras / PIC Ruangan,', style: const pw.TextStyle(fontSize: 9.5)),
                      pw.SizedBox(height: 42),
                      pw.Text(receipt['pic_name']?.toString() ?? 'Pengelola Sarpras SMKN 1 Bantul',
                          style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 9.5)),
                    ],
                  ),
                ],
              ),
            ],
          );
        },
      ),
    );

    await Printing.layoutPdf(
      name: 'surat-izin-${receipt['verification_code'] ?? 'rapi'}.pdf',
      onLayout: (_) => pdf.save(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _createEvent,
        backgroundColor: accent,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add),
        label: const Text('Ajukan agenda'),
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _events,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorState(error: snapshot.error, onRetry: _reload);
          }
          if (!snapshot.hasData) return const LoadingState();

          final allEvents = snapshot.data!;
          final now = DateTime.now();
          final filteredEvents = allEvents.where((e) {
            final event = asMap(e);
            final status = event['status']?.toString();
            final matchesFilter = switch (_filter) {
              'upcoming' => _isUpcoming(event, now),
              'current' => status == 'approved',
              'today' => _isToday(event, now),
              'pending' => status == 'pending',
              'rejected' => status == 'rejected',
              _ => true,
            };
            return matchesFilter &&
                _searchableText(event).contains(_search.toLowerCase());
          }).toList();

          return RefreshIndicator(
            onRefresh: _reload,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
              children: [
                const PageHeading(
                  title: 'Agenda Ruangan',
                  subtitle:
                      'Cari agenda berdasarkan waktu dan status persetujuannya. Agenda disetujui otomatis ditandai terlaksana setelah selesai.',
                ),
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    labelText: 'Cari agenda',
                    hintText: 'Judul, ruangan, atau peminjam',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _search.isEmpty
                        ? null
                        : IconButton(
                            tooltip: 'Hapus pencarian',
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _search = '');
                            },
                            icon: const Icon(Icons.clear),
                          ),
                    border: const OutlineInputBorder(),
                  ),
                  onChanged: (value) => setState(() => _search = value.trim()),
                ),
                const SizedBox(height: 12),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _FilterChip(
                        label: 'Semua (${allEvents.length})',
                        selected: _filter == 'all',
                        onSelected: () => setState(() => _filter = 'all'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Mendatang (${allEvents.where((e) => _isUpcoming(asMap(e), now)).length})',
                        selected: _filter == 'upcoming',
                        onSelected: () => setState(() => _filter = 'upcoming'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Terkini · Disetujui (${allEvents.where((e) => asMap(e)['status'] == 'approved').length})',
                        selected: _filter == 'current',
                        onSelected: () => setState(() => _filter = 'current'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Hari ini (${allEvents.where((e) => _isToday(asMap(e), now)).length})',
                        selected: _filter == 'today',
                        onSelected: () => setState(() => _filter = 'today'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Pending · Belum disetujui (${allEvents.where((e) => asMap(e)['status'] == 'pending').length})',
                        selected: _filter == 'pending',
                        onSelected: () => setState(() => _filter = 'pending'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Ditolak (${allEvents.where((e) => asMap(e)['status'] == 'rejected').length})',
                        selected: _filter == 'rejected',
                        onSelected: () => setState(() => _filter = 'rejected'),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                if (filteredEvents.isEmpty)
                  const SectionCard(
                    title: 'Daftar Agenda',
                    child: EmptyState(
                      message: 'Tidak ada agenda pada kategori ini.',
                    ),
                  )
                else
                  for (final event in filteredEvents)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: EventCard(
                        data: asMap(event),
                        canDelete: _canDeleteEvent(asMap(event)),
                        onDelete: () => _deleteEvent(asMap(event)),
                        onCheckout: asMap(event)['can_checkout'] == true
                            ? () => _handleCheckout(asMap(event))
                            : null,
                        onViewReceipt: asMap(event)['status'] == 'approved'
                            ? () => _showReceiptDialog(asMap(event))
                            : null,
                        onTap: () {
                          showEventDetailSheet(
                            context: context,
                            event: asMap(event),
                            onCheckout: asMap(event)['can_checkout'] == true
                                ? () => _handleCheckout(asMap(event))
                                : null,
                            onViewReceipt: asMap(event)['status'] == 'approved'
                                ? () => _showReceiptDialog(asMap(event))
                                : null,
                            onReportDamage:
                                asMap(event)['status'] == 'approved' &&
                                    widget.onReportDamage != null
                                ? () => widget.onReportDamage!(asMap(event))
                                : null,
                          );
                        },
                        onReportDamage:
                            asMap(event)['status'] == 'approved' &&
                                widget.onReportDamage != null
                            ? () => widget.onReportDamage!(asMap(event))
                            : null,
                      ),
                    ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.selected,
    required this.onSelected,
  });

  final String label;
  final bool selected;
  final VoidCallback onSelected;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(
      label: Text(
        label,
        style: TextStyle(
          fontSize: 12,
          fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
          color: selected ? Colors.white : ink,
        ),
      ),
      selected: selected,
      selectedColor: accent,
      backgroundColor: surface,
      side: BorderSide(color: selected ? accent : line),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      onSelected: (_) => onSelected(),
    );
  }
}

class EventCard extends StatelessWidget {
  const EventCard({
    required this.data,
    this.onTap,
    this.onReportDamage,
    this.onCheckout,
    this.onViewReceipt,
    this.canDelete = false,
    this.onDelete,
    super.key,
  });

  final Map<String, dynamic> data;
  final VoidCallback? onTap;
  final VoidCallback? onReportDamage;
  final VoidCallback? onCheckout;
  final VoidCallback? onViewReceipt;
  final bool canDelete;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    final room = asMap(data['room']);
    final isOvernight = eventEndsNextDay(data);
    final status = data['status']?.toString() ?? 'pending';
    final canCheckout = data['can_checkout'] == true;
    final returnedAt = data['returned_at_formatted']?.toString() ?? data['returned_at']?.toString();
    final checkoutCondition = data['checkout_condition']?.toString();

    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      data['title']?.toString() ?? 'Agenda',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                        color: ink,
                      ),
                    ),
                  ),
                  StatusBadge(status: status),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(
                    Icons.meeting_room_outlined,
                    size: 16,
                    color: muted,
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      '${room['name'] ?? 'Ruangan'}${room['location'] != null && room['location'].toString().isNotEmpty ? ' (${room['location']})' : ''}',
                      style: const TextStyle(color: muted, fontSize: 13),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.access_time, size: 16, color: muted),
                  const SizedBox(width: 6),
                  Text(
                    '${eventDateRange(data)} · ${shortTime(data['start_time'])}–${shortTime(data['end_time'])}${isOvernight && data['end_date'] == null ? ' (+1 hari)' : ''}',
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                      color: ink,
                    ),
                  ),
                ],
              ),
              if (status == 'approved') ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(
                      Icons.check_circle_outline,
                      size: 16,
                      color: success,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      'Pelaksanaan: ${data['execution_status'] ?? 'Belum terlaksana'}',
                      style: const TextStyle(
                        color: successText,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ],
              if (data['borrower_name'] != null &&
                  data['borrower_name'].toString().isNotEmpty) ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.person_outline, size: 16, color: muted),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        'Peminjam: ${data['borrower_name']}${data['borrower_whatsapp'] != null ? ' (${data['borrower_whatsapp']})' : ''}',
                        style: const TextStyle(color: muted, fontSize: 12),
                      ),
                    ),
                  ],
                ),
              ],
              if (data['description'] != null &&
                  data['description'].toString().trim().isNotEmpty) ...[
                const Divider(height: 18),
                Text(
                  data['description'].toString(),
                  style: const TextStyle(color: ink, fontSize: 13),
                ),
              ],
              if (data['move_reason'] != null &&
                  data['move_reason'].toString().trim().isNotEmpty) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: warningSoft,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info, color: warningText, size: 16),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          'Ruangan dipindahkan: ${data['move_reason']}',
                          style: const TextStyle(
                            color: warningText,
                            fontSize: 12,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              if (status == 'rejected' &&
                  data['rejection_reason'] != null &&
                  data['rejection_reason'].toString().trim().isNotEmpty) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: dangerSoft,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.cancel, color: dangerText, size: 16),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          'Alasan penolakan: ${data['rejection_reason']}',
                          style: const TextStyle(
                            color: dangerText,
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              if (status == 'approved') ...[
                const SizedBox(height: 12),
                if (returnedAt != null) ...[
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF0FDF4),
                      border: Border.all(color: const Color(0xFFBBF7D0)),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.check_circle, color: Color(0xFF16A34A), size: 16),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            '✓ Dikembalikan ($returnedAt)${checkoutCondition == 'rusak' ? ' • Kondisi: Ada Kerusakan Dilaporkan' : ' • Aman & Bersih'}',
                            style: const TextStyle(
                              color: Color(0xFF15803D),
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                ] else if (canCheckout && onCheckout != null) ...[
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      style: FilledButton.styleFrom(
                        backgroundColor: const Color(0xFF0284C7),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 11),
                      ),
                      onPressed: onCheckout,
                      icon: const Icon(Icons.assignment_turned_in_outlined, size: 18),
                      label: const Text(
                        'Serah Terima / Pengembalian',
                        style: TextStyle(fontWeight: FontWeight.w700),
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                ],
                Row(
                  children: [
                    if (onViewReceipt != null)
                      Expanded(
                        child: OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF0F172A),
                            side: const BorderSide(color: line),
                            padding: const EdgeInsets.symmetric(vertical: 10),
                          ),
                          onPressed: onViewReceipt,
                          icon: const Icon(Icons.description_outlined, size: 16),
                          label: const Text('Surat Izin', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                        ),
                      ),
                    if (onViewReceipt != null && onReportDamage != null)
                      const SizedBox(width: 8),
                    if (onReportDamage != null)
                      Expanded(
                        child: OutlinedButton.icon(
                          style: OutlinedButton.styleFrom(
                            foregroundColor: warningText,
                            side: const BorderSide(color: accent),
                            padding: const EdgeInsets.symmetric(vertical: 10),
                          ),
                          onPressed: onReportDamage,
                          icon: const Icon(Icons.report_problem_outlined, size: 16),
                          label: const Text('Barang Rusak', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                        ),
                      ),
                  ],
                ),
              ],
              if (canDelete && onDelete != null) ...[
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(foregroundColor: danger),
                    onPressed: onDelete,
                    icon: const Icon(Icons.delete_outline, size: 18),
                    label: const Text('Hapus agenda'),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class EventForm extends StatefulWidget {
  const EventForm({required this.api, required this.user, super.key});

  final ApiService api;
  final Map<String, dynamic> user;

  @override
  State<EventForm> createState() => _EventFormState();
}

class _EventFormState extends State<EventForm> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _borrower = TextEditingController();
  final _whatsapp = TextEditingController();
  late DateTime _date;
  late DateTime _endDate;
  List<dynamic> _rooms = [];
  final Set<int> _selectedRoomIds = {};
  Map<String, dynamic> _periods = {};
  String _startLesson = '1';
  String _endLesson = '2';
  TimeOfDay _startCustomTime = const TimeOfDay(hour: 17, minute: 45);
  TimeOfDay _endCustomTime = const TimeOfDay(hour: 6, minute: 0);
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final minimumDate = DateUtils.dateOnly(
      DateTime.now().add(const Duration(days: 1)),
    );
    _date = minimumDate;
    _endDate = minimumDate;
    _borrower.text = widget.user['name']?.toString() ?? '';
    _whatsapp.text = widget.user['whatsapp']?.toString() ?? '';
    _loadOptions();
  }

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _borrower.dispose();
    _whatsapp.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    try {
      final results = await Future.wait([
        widget.api.getRooms(),
        widget.api.getLessonPeriods(),
      ]);
      if (!mounted) return;
      setState(() {
        _rooms = results[0] as List<dynamic>;
        _periods = asMap((results[1] as Map<String, dynamic>)['periods']);
        if (_rooms.isNotEmpty && _selectedRoomIds.isEmpty) {
          _selectedRoomIds.add(asMap(_rooms.first)['id'] as int);
        }
        _loading = false;
      });
    } catch (error) {
      if (mounted) {
        setState(() {
          _error = error.toString();
          _loading = false;
        });
      }
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateUtils.dateOnly(
        DateTime.now().add(const Duration(days: 1)),
      ),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() {
        _date = picked;
        if (_endDate.isBefore(_date)) _endDate = _date;
      });
    }
  }

  Future<void> _pickEndDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _endDate.isBefore(_date) ? _date : _endDate,
      firstDate: _date,
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _endDate = picked);
  }

  Future<void> _pickCustomTime({required bool start}) async {
    final picked = await showTimePicker(
      context: context,
      initialTime: start ? _startCustomTime : _endCustomTime,
    );
    if (picked == null) return;
    setState(() {
      if (start) {
        _startCustomTime = picked;
      } else {
        _endCustomTime = picked;
      }
    });
  }

  Future<void> _openRoomSelectionModal() async {
    final searchCtrl = TextEditingController();
    String query = '';

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final visibleRooms = _rooms.where((r) {
              if (query.isEmpty) return true;
              final map = asMap(r);
              final name = map['name']?.toString().toLowerCase() ?? '';
              final loc = map['location']?.toString().toLowerCase() ?? '';
              return name.contains(query) || loc.contains(query);
            }).toList();

            return Padding(
              padding: EdgeInsets.fromLTRB(
                20,
                16,
                20,
                MediaQuery.viewInsetsOf(context).bottom + 20,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Text(
                            'Pilih Ruangan',
                            style: TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.w800,
                              color: ink,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                            decoration: BoxDecoration(
                              color: const Color(0xFFDBEAFE),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Text(
                              'Bisa pilih > 1 ruangan',
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF1D4ED8),
                              ),
                            ),
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                  const Text(
                    'Centang satu atau beberapa ruangan sekaligus untuk kegiatan ini.',
                    style: TextStyle(color: muted, fontSize: 12),
                  ),
                  const SizedBox(height: 12),
                  // Search Bar
                  TextField(
                    controller: searchCtrl,
                    decoration: InputDecoration(
                      hintText: 'Cari nama ruangan atau lokasi...',
                      hintStyle: const TextStyle(fontSize: 12.5),
                      prefixIcon: const Icon(Icons.search, size: 18),
                      suffixIcon: query.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, size: 16),
                              onPressed: () {
                                searchCtrl.clear();
                                setModalState(() => query = '');
                              },
                            )
                          : null,
                      contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 12),
                      filled: true,
                      fillColor: surface,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: const BorderSide(color: inputBorder),
                      ),
                    ),
                    style: const TextStyle(fontSize: 13),
                    onChanged: (val) => setModalState(() => query = val.trim().toLowerCase()),
                  ),
                  const SizedBox(height: 10),
                  // Action Bar (Select all / Deselect all / Count)
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        query.isEmpty
                            ? 'Total ${_rooms.length} ruangan'
                            : 'Menampilkan ${visibleRooms.length} ruangan',
                        style: const TextStyle(fontSize: 11.5, color: muted, fontWeight: FontWeight.w600),
                      ),
                      Row(
                        children: [
                          TextButton(
                            onPressed: () {
                              setModalState(() {
                                for (final r in visibleRooms) {
                                  _selectedRoomIds.add(asMap(r)['id'] as int);
                                }
                              });
                              setState(() {});
                            },
                            style: TextButton.styleFrom(
                              visualDensity: VisualDensity.compact,
                              padding: const EdgeInsets.symmetric(horizontal: 8),
                            ),
                            child: const Text('Pilih Semua', style: TextStyle(fontSize: 11.5)),
                          ),
                          TextButton(
                            onPressed: () {
                              setModalState(() {
                                for (final r in visibleRooms) {
                                  _selectedRoomIds.remove(asMap(r)['id'] as int);
                                }
                              });
                              setState(() {});
                            },
                            style: TextButton.styleFrom(
                              visualDensity: VisualDensity.compact,
                              padding: const EdgeInsets.symmetric(horizontal: 8),
                            ),
                            child: const Text('Batal Pilih', style: TextStyle(fontSize: 11.5)),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  // Room List Container
                  Container(
                    constraints: BoxConstraints(
                      maxHeight: MediaQuery.sizeOf(context).height * 0.38,
                    ),
                    decoration: BoxDecoration(
                      color: surface,
                      border: Border.all(color: inputBorder),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: visibleRooms.isEmpty
                        ? const Padding(
                            padding: EdgeInsets.all(24),
                            child: Center(
                              child: Text(
                                'Tidak ada ruangan yang cocok.',
                                style: TextStyle(color: muted, fontSize: 13),
                              ),
                            ),
                          )
                        : ListView.separated(
                            shrinkWrap: true,
                            padding: const EdgeInsets.symmetric(vertical: 4),
                            itemCount: visibleRooms.length,
                            separatorBuilder: (_, __) => const Divider(
                              height: 1,
                              indent: 48,
                              color: line,
                            ),
                            itemBuilder: (context, index) {
                              final room = asMap(visibleRooms[index]);
                              final id = room['id'] as int;
                              final isSelected = _selectedRoomIds.contains(id);
                              final cap = room['capacity'];

                              return CheckboxListTile(
                                value: isSelected,
                                dense: true,
                                activeColor: accent,
                                controlAffinity: ListTileControlAffinity.leading,
                                title: Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        room['name']?.toString() ?? '',
                                        style: TextStyle(
                                          fontSize: 13,
                                          fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                                          color: isSelected ? ink : const Color(0xFF334155),
                                        ),
                                      ),
                                    ),
                                    if (cap != null)
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFE0F2FE),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          '$cap org',
                                          style: const TextStyle(
                                            fontSize: 10,
                                            fontWeight: FontWeight.w700,
                                            color: Color(0xFF0369A1),
                                          ),
                                        ),
                                      ),
                                  ],
                                ),
                                subtitle: Text(
                                  room['location']?.toString() ?? 'SMKN 1 Bantul',
                                  style: const TextStyle(fontSize: 11, color: muted),
                                ),
                                onChanged: (checked) {
                                  setModalState(() {
                                    if (checked == true) {
                                      _selectedRoomIds.add(id);
                                    } else {
                                      _selectedRoomIds.remove(id);
                                    }
                                  });
                                  setState(() {});
                                },
                              );
                            },
                          ),
                  ),
                  const SizedBox(height: 14),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: accent,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    onPressed: () => Navigator.pop(context),
                    child: Text(
                      'Tutup & Terapkan (${_selectedRoomIds.length} Dipilih)',
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _selectedRoomIds.isEmpty) {
      if (_selectedRoomIds.isEmpty) {
        setState(() => _error = 'Pilih minimal satu ruangan yang akan dipinjam.');
      }
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final fields = <String, String>{
        'room_id': _selectedRoomIds.first.toString(),
        'room_ids': _selectedRoomIds.join(','),
        'title': _title.text.trim(),
        'borrower_name': _borrower.text.trim(),
        'borrower_whatsapp': _whatsapp.text.trim(),
        'description': _description.text.trim(),
        'date': dateIso(_date),
        'end_date': dateIso(_endDate),
        'start_lesson': _startLesson,
        'end_lesson': _endLesson,
      };
      if (_startLesson == 'custom') {
        fields['start_custom_time'] = formatTimeOfDay(_startCustomTime);
      }
      if (_endLesson == 'custom') {
        fields['end_custom_time'] = formatTimeOfDay(_endCustomTime);
      }
      await widget.api.createEvent(fields);
      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(
        20,
        16,
        20,
        MediaQuery.viewInsetsOf(context).bottom + 20,
      ),
      child: _loading
          ? const SizedBox(height: 220, child: LoadingState())
          : Form(
              key: _formKey,
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Ajukan Agenda Ruangan',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: ink,
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.pop(context),
                        ),
                      ],
                    ),
                    const Text(
                      'Peminjaman diajukan paling lambat H-1. Pengajuan lebih dari satu hari tetap menunggu persetujuan admin.',
                      style: TextStyle(color: muted, fontSize: 12),
                    ),
                    const SizedBox(height: 14),
                    if (_error != null) ...[
                      ErrorBanner(message: _error!),
                      const SizedBox(height: 12),
                    ],
                    if (_rooms.isEmpty)
                      const ErrorBanner(
                        message: 'Belum ada ruangan yang tersedia.',
                      )
                    else ...[
                      // Header Row (Sama seperti Desktop)
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              const Text(
                                'Pilih Ruangan',
                                style: TextStyle(
                                  fontWeight: FontWeight.w800,
                                  fontSize: 13,
                                  color: ink,
                                ),
                              ),
                              const SizedBox(width: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFDBEAFE),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Text(
                                  'Bisa pilih lebih dari 1 ruangan',
                                  style: TextStyle(
                                    fontSize: 9.5,
                                    fontWeight: FontWeight.w700,
                                    color: Color(0xFF1D4ED8),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: const Color(0xFF0B192E),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              '${_selectedRoomIds.length} dipilih',
                              style: const TextStyle(
                                fontSize: 10.5,
                                fontWeight: FontWeight.w700,
                                color: Colors.white,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),

                      // Trigger Bar (Tampilan Persis seperti di Desktop)
                      InkWell(
                        onTap: () => _openRoomSelectionModal(),
                        borderRadius: BorderRadius.circular(10),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: _selectedRoomIds.isNotEmpty ? accent : const Color(0xFFCBD5E1),
                              width: 1.5,
                            ),
                            boxShadow: [
                              if (_selectedRoomIds.isNotEmpty)
                                BoxShadow(
                                  color: accent.withOpacity(0.12),
                                  blurRadius: 4,
                                  offset: const Offset(0, 1),
                                ),
                            ],
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.meeting_room_outlined, size: 20, color: accent),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  _selectedRoomIds.isEmpty
                                      ? 'Klik untuk cari & pilih ruangan...'
                                      : '${_selectedRoomIds.length} ruangan dipilih (Klik untuk ubah)',
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    color: _selectedRoomIds.isEmpty ? muted : ink,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Text(
                                'Pilih Ruangan',
                                style: TextStyle(
                                  fontSize: 11.5,
                                  color: muted,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              const SizedBox(width: 4),
                              const Icon(Icons.keyboard_arrow_down, size: 18, color: muted),
                            ],
                          ),
                        ),
                      ),

                      // Selected Chips (Ruangan Terpilih di bawah kotak)
                      if (_selectedRoomIds.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'RUANGAN TERPILIH:',
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF64748B),
                                  letterSpacing: 0.3,
                                ),
                              ),
                              const SizedBox(height: 6),
                              Wrap(
                                spacing: 6,
                                runSpacing: 4,
                                children: [
                                  for (final r in _rooms)
                                    if (_selectedRoomIds.contains(asMap(r)['id']))
                                      Container(
                                        padding: const EdgeInsets.fromLTRB(8, 4, 4, 4),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFFFFBEB),
                                          borderRadius: BorderRadius.circular(14),
                                          border: Border.all(color: const Color(0xFFFDE68A)),
                                        ),
                                        child: Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Text(
                                              asMap(r)['name']?.toString() ?? '',
                                              style: const TextStyle(
                                                fontSize: 11.5,
                                                fontWeight: FontWeight.w700,
                                                color: Color(0xFF92400E),
                                              ),
                                            ),
                                            if (asMap(r)['capacity'] != null) ...[
                                              const SizedBox(width: 4),
                                              Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFFFEF3C7),
                                                  borderRadius: BorderRadius.circular(6),
                                                ),
                                                child: Text(
                                                  '${asMap(r)['capacity']} org',
                                                  style: const TextStyle(
                                                    fontSize: 9.5,
                                                    fontWeight: FontWeight.w700,
                                                    color: Color(0xFFB45309),
                                                  ),
                                                ),
                                              ),
                                            ],
                                            const SizedBox(width: 4),
                                            GestureDetector(
                                              onTap: () {
                                                setState(() {
                                                  _selectedRoomIds.remove(asMap(r)['id'] as int);
                                                });
                                              },
                                              child: Container(
                                                width: 16,
                                                height: 16,
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFF92400E).withOpacity(0.12),
                                                  shape: BoxShape.circle,
                                                ),
                                                child: const Icon(
                                                  Icons.close,
                                                  size: 11,
                                                  color: Color(0xFF92400E),
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _title,
                      decoration: const InputDecoration(
                        labelText: 'Judul Agenda',
                        hintText: 'Contoh: Rapat OSIS, Praktik Kimia',
                      ),
                      validator: requiredValidator,
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: _pickDate,
                      icon: const Icon(Icons.calendar_today_outlined, size: 18),
                      label: Text('Tanggal: ${formatDate(_date)}'),
                    ),
                    const SizedBox(height: 8),
                    OutlinedButton.icon(
                      onPressed: _pickEndDate,
                      icon: const Icon(Icons.event_outlined, size: 18),
                      label: Text('Tanggal selesai: ${formatDate(_endDate)}'),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _startLesson,
                            decoration: const InputDecoration(
                              labelText: 'Jam Mulai',
                            ),
                            items: _periodOptions(),
                            onChanged: (value) => setState(() {
                              if (value != null) {
                                _startLesson = value;
                                if (value == 'custom') {
                                  _endLesson = 'custom';
                                } else if (_endLesson != 'custom' &&
                                    int.parse(_endLesson) < int.parse(value)) {
                                  _endLesson = value;
                                }
                              }
                            }),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: DropdownButtonFormField<String>(
                            value: _endLesson,
                            decoration: const InputDecoration(
                              labelText: 'Jam Selesai',
                            ),
                            items: _periodOptions(),
                            onChanged: (value) => setState(() {
                              if (value != null &&
                                  (_startLesson != 'custom' ||
                                      value == 'custom')) {
                                _endLesson = value;
                              }
                            }),
                          ),
                        ),
                      ],
                    ),
                    if (_startLesson == 'custom' || _endLesson == 'custom') ...[
                      const SizedBox(height: 8),
                      if (_startLesson == 'custom')
                        OutlinedButton.icon(
                          onPressed: () => _pickCustomTime(start: true),
                          icon: const Icon(Icons.schedule, size: 18),
                          label: Text(
                            'Mulai Bebas: ${formatTimeOfDay(_startCustomTime)}',
                          ),
                        ),
                      if (_endLesson == 'custom')
                        OutlinedButton.icon(
                          onPressed: () => _pickCustomTime(start: false),
                          icon: const Icon(Icons.schedule, size: 18),
                          label: Text(
                            'Selesai Bebas: ${formatTimeOfDay(_endCustomTime)}',
                          ),
                        ),
                      const SizedBox(height: 4),
                      const Text(
                        'Jam bebas berlaku setelah pukul 17.45 hingga pukul 06.00 hari berikutnya.',
                        style: TextStyle(color: muted, fontSize: 11),
                      ),
                    ],
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _borrower,
                      decoration: const InputDecoration(
                        labelText: 'Nama Peminjam Ruangan',
                      ),
                      validator: requiredValidator,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _whatsapp,
                      keyboardType: TextInputType.phone,
                      decoration: const InputDecoration(
                        labelText: 'Nomor WhatsApp Peminjam',
                        hintText: '62812...',
                      ),
                      validator: (value) =>
                          value == null ||
                              !RegExp(
                                r'^\+?[0-9]{8,20}$',
                              ).hasMatch(value.trim())
                          ? 'Masukkan nomor WhatsApp yang valid.'
                          : null,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _description,
                      minLines: 2,
                      maxLines: 4,
                      decoration: const InputDecoration(
                        labelText: 'Keterangan Agenda (Opsional)',
                      ),
                    ),
                    const SizedBox(height: 18),
                    FilledButton(
                      style: FilledButton.styleFrom(
                        backgroundColor: accent,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed:
                          _saving || _rooms.isEmpty || _selectedRoomIds.isEmpty
                              ? null
                              : _submit,
                      child: _saving
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              _selectedRoomIds.length > 1
                                  ? 'Ajukan ${_selectedRoomIds.length} Ruangan Sekaligus'
                                  : 'Kirim Pengajuan Agenda',
                              style: const TextStyle(fontWeight: FontWeight.w700),
                            ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  List<DropdownMenuItem<String>> _periodOptions() {
    return [
      for (final entry in _periods.entries)
        DropdownMenuItem(
          value: entry.key,
          child: Text(
            'Jam ke-${entry.key} (${asMap(entry.value)['start']}–${asMap(entry.value)['end']})',
            style: const TextStyle(fontSize: 13),
          ),
        ),
      const DropdownMenuItem(
        value: 'custom',
        child: Text('Jam bebas', style: TextStyle(fontSize: 13)),
      ),
    ];
  }
}

class _ReceiptSheet extends StatelessWidget {
  const _ReceiptSheet({
    required this.receipt,
    required this.onPrintPdf,
  });

  final Map<String, dynamic> receipt;
  final VoidCallback onPrintPdf;

  @override
  Widget build(BuildContext context) {
    final returnedAt = receipt['returned_at']?.toString();
    final checkoutCondition = receipt['checkout_condition']?.toString();

    return Padding(
      padding: EdgeInsets.fromLTRB(
        20,
        16,
        20,
        MediaQuery.viewInsetsOf(context).bottom + 20,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Row(
                  children: [
                    Icon(Icons.verified, color: success, size: 22),
                    SizedBox(width: 8),
                    Text(
                      'Surat Izin Ruangan',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: ink,
                      ),
                    ),
                  ],
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
            const Text(
              'Bukti izin resmi peminjaman ruangan yang telah disetujui.',
              style: TextStyle(color: muted, fontSize: 13),
            ),
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                border: Border.all(color: line),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Column(
                      children: [
                        const Text(
                          'PEMERINTAH DAERAH D.I. YOGYAKARTA',
                          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 11, color: muted),
                        ),
                        const Text(
                          'SMK NEGERI 1 BANTUL',
                          style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16, color: ink),
                        ),
                        const Text(
                          'Jl. Parangtritis KM 11, Sabdodadi, Bantul',
                          style: TextStyle(fontSize: 11, color: muted),
                        ),
                        const SizedBox(height: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xFFE0F2FE),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: const Color(0xFFBAE6FD)),
                          ),
                          child: Text(
                            'KODE VERIFIKASI: ${receipt['verification_code'] ?? '-'}',
                            style: const TextStyle(
                              color: Color(0xFF0369A1),
                              fontWeight: FontWeight.w800,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 20),
                  _infoItem('Nama Agenda', receipt['title']?.toString() ?? '-'),
                  _infoItem('Ruangan Dipinjam', '${receipt['room_name'] ?? '-'}${receipt['room_location'] != null ? ' (${receipt['room_location']})' : ''}'),
                  _infoItem('Hari & Tanggal', receipt['date_formatted']?.toString() ?? receipt['date']?.toString() ?? '-'),
                  _infoItem('Waktu Penggunaan', '${receipt['start_time']} - ${receipt['end_time']} WIB'),
                  _infoItem('Peminjam / PJ', '${receipt['borrower_name'] ?? '-'}${receipt['borrower_whatsapp'] != null ? ' (${receipt['borrower_whatsapp']})' : ''}'),
                  _infoItem('Akun Pemohon', receipt['applicant_name']?.toString() ?? '-'),
                  _infoItem('Pengelola / PIC', receipt['pic_name']?.toString() ?? 'Pengelola Sarpras'),
                  _infoItem('Waktu Disetujui', receipt['approved_at']?.toString() ?? '-'),
                  const SizedBox(height: 8),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: returnedAt != null ? const Color(0xFFF0FDF4) : const Color(0xFFFEF3C7),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: returnedAt != null ? const Color(0xFFBBF7D0) : const Color(0xFFFDE68A)),
                    ),
                    child: Row(
                      children: [
                        Icon(
                          returnedAt != null ? Icons.check_circle : Icons.schedule,
                          size: 16,
                          color: returnedAt != null ? success : warningText,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            returnedAt != null
                                ? 'Ruangan dikembalikan pada $returnedAt (${checkoutCondition == 'rusak' ? 'Ada Kerusakan Dilaporkan' : 'Aman & Bersih'})'
                                : 'Agenda aktif / belum dikonfirmasi pengembaliannya.',
                            style: TextStyle(
                              fontSize: 11.5,
                              fontWeight: FontWeight.w600,
                              color: returnedAt != null ? successText : warningText,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),
            FilledButton.icon(
              style: FilledButton.styleFrom(
                backgroundColor: accent,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
              onPressed: () {
                Navigator.pop(context);
                onPrintPdf();
              },
              icon: const Icon(Icons.print_outlined),
              label: const Text(
                'Cetak / Simpan PDF Surat Izin',
                style: TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _infoItem(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: const TextStyle(color: muted, fontSize: 12, fontWeight: FontWeight.w500),
            ),
          ),
          const Text(': ', style: TextStyle(color: muted, fontSize: 12)),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(color: ink, fontSize: 12, fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );
  }
}
