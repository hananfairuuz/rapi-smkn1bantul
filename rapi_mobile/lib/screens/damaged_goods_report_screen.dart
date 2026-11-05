import 'package:flutter/material.dart';
import 'package:file_saver/file_saver.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class DamagedGoodsReportScreen extends StatefulWidget {
  const DamagedGoodsReportScreen({required this.api, super.key});

  final ApiService api;

  @override
  State<DamagedGoodsReportScreen> createState() =>
      _DamagedGoodsReportScreenState();
}

class _DamagedGoodsReportScreenState extends State<DamagedGoodsReportScreen> {
  late DateTime _month;
  late DateTime _selectedDate;
  late Future<Map<String, dynamic>> _report;
  String _periodType = 'month';
  String _filter = 'all';
  bool _exporting = false;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _month = DateTime(now.year, now.month);
    _selectedDate = DateTime(now.year, now.month, now.day);
    _report = _loadReport();
  }

  Future<Map<String, dynamic>> _loadReport() =>
      widget.api.getDamagedGoodsReport(
        _periodType,
        _periodType == 'month' ? _month : _selectedDate,
      );

  void _selectPeriodType(String periodType) {
    setState(() {
      _periodType = periodType;
      _report = _loadReport();
    });
  }

  void _selectMonth(int month) {
    setState(() {
      _month = DateTime(_month.year, month);
      _selectedDate = DateTime(_month.year, month, 1);
      _report = _loadReport();
    });
  }

  void _selectYear(int year) {
    setState(() {
      _month = DateTime(year, _month.month);
      _selectedDate = DateTime(year, _month.month, 1);
      _report = _loadReport();
    });
  }

  Future<void> _selectDate() async {
    final selected = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(DateTime.now().year + 1, 12, 31),
    );
    if (selected == null) return;
    setState(() {
      _selectedDate = selected;
      _report = _loadReport();
    });
  }

  Future<void> _refresh() async {
    final next = _loadReport();
    setState(() => _report = next);
    await next;
  }

  String get _fileName {
    if (_periodType == 'month') {
      return 'laporan-barang-rusak-${_month.year}-${twoDigits(_month.month)}';
    }
    if (_periodType == 'week') {
      final start = _selectedDate.subtract(
        Duration(days: _selectedDate.weekday - DateTime.monday),
      );
      return 'laporan-barang-rusak-mingguan-${dateIso(start)}';
    }
    return 'laporan-barang-rusak-harian-${dateIso(_selectedDate)}';
  }

  Future<void> _printReport(Map<String, dynamic> data) async {
    setState(() => _exporting = true);
    try {
      final pdf = _createPdf(data);
      await Printing.layoutPdf(
        name: '$_fileName.pdf',
        onLayout: (_) => pdf.save(),
      );
    } catch (error) {
      _showExportError('Gagal membuka cetak / simpan PDF', error);
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  Future<void> _exportDocx() async {
    setState(() => _exporting = true);
    try {
      final bytes = await widget.api.getDamagedGoodsReportDocx(
        _periodType,
        _selectedDate,
      );
      final path = await FileSaver.instance.saveAs(
        name: _fileName,
        bytes: bytes,
        fileExtension: 'docx',
        mimeType: MimeType.microsoftWord,
      );
      if (path != null && mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('DOCX disimpan: $path')));
      }
    } catch (error) {
      _showExportError('Gagal menyiapkan laporan DOCX', error);
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  pw.Document _createPdf(Map<String, dynamic> data) {
    final complaints = List<dynamic>.from(data['complaints'] as List? ?? []);
    final repairs = asMap(data['by_repair']);
    final document = pw.Document();
    final rows = complaints.indexed.map((entry) {
      final complaint = asMap(entry.$2);
      final room = asMap(complaint['room']);
      final reporter = asMap(complaint['reporter']);
      final description = complaint['description']?.toString() ?? '';
      return [
        '${entry.$1 + 1}',
        _reportDate(complaint['created_at']),
        room['name']?.toString() ?? '-',
        [
          complaint['facility_name']?.toString() ?? '-',
          if (description.isNotEmpty) description,
        ].join('\n'),
        reporter['name']?.toString() ?? '-',
        complaint['status_repair']?.toString() ?? 'Belum Diperbaiki',
        complaint['repair_notes']?.toString().trim().isNotEmpty == true
            ? complaint['repair_notes'].toString()
            : '-',
      ];
    }).toList();
    if (rows.isEmpty) {
      rows.add([
        '-',
        '-',
        '-',
        'Tidak ada pengaduan pada periode ini.',
        '-',
        '-',
        '-',
      ]);
    }

    document.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4.landscape,
        margin: const pw.EdgeInsets.all(28),
        build: (_) => [
          pw.Text(
            'Laporan Barang Rusak RAPI-Sekolah',
            style: pw.TextStyle(fontSize: 18, fontWeight: pw.FontWeight.bold),
          ),
          pw.SizedBox(height: 6),
          pw.Text('Periode: ${data['period'] ?? '-'}'),
          pw.SizedBox(height: 14),
          pw.TableHelper.fromTextArray(
            headers: ['Ringkasan', 'Jumlah'],
            data: [
              ['Total Pengaduan', '${data['total'] ?? complaints.length}'],
              ['Belum Diperbaiki', '${repairs['Belum Diperbaiki'] ?? 0}'],
            ],
            headerStyle: pw.TextStyle(fontWeight: pw.FontWeight.bold),
            headerDecoration: const pw.BoxDecoration(color: PdfColors.grey300),
            cellStyle: const pw.TextStyle(fontSize: 9),
          ),
          pw.SizedBox(height: 16),
          pw.Text(
            'Pengaduan Barang/Fasilitas Rusak',
            style: pw.TextStyle(fontSize: 13, fontWeight: pw.FontWeight.bold),
          ),
          pw.SizedBox(height: 6),
          pw.TableHelper.fromTextArray(
            headers: [
              'No.',
              'Tanggal Laporan',
              'Ruangan',
              'Barang/Fasilitas',
              'Pelapor',
              'Status Perbaikan',
              'Catatan',
            ],
            data: rows,
            headerStyle: pw.TextStyle(
              fontSize: 8,
              fontWeight: pw.FontWeight.bold,
            ),
            headerDecoration: const pw.BoxDecoration(color: PdfColors.grey300),
            cellStyle: const pw.TextStyle(fontSize: 8),
            cellAlignment: pw.Alignment.topLeft,
            cellPadding: const pw.EdgeInsets.all(5),
          ),
        ],
      ),
    );
    return document;
  }

  String _reportDate(dynamic value) {
    if (value == null) return '-';
    final text = value.toString();
    if (text.length < 10) return text;
    try {
      final date = DateTime.parse(text.substring(0, 10));
      return '${twoDigits(date.day)}/${twoDigits(date.month)}/${date.year}';
    } on FormatException {
      return text;
    }
  }

  void _showExportError(String action, Object error) {
    if (!mounted) return;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text('$action: $error')));
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: _report,
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return ErrorState(error: snapshot.error, onRetry: _refresh);
        }
        if (!snapshot.hasData) return const LoadingState();

        final data = snapshot.data!;
        final approvals = asMap(data['by_approval']);
        final repairs = asMap(data['by_repair']);
        final allComplaints = List<dynamic>.from(
          data['complaints'] as List? ?? [],
        );

        final filteredComplaints = allComplaints.where((c) {
          if (_filter == 'all') return true;
          return asMap(c)['status_repair'] == _filter;
        }).toList();

        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const PageHeading(
                title: 'Laporan Barang Rusak',
                subtitle:
                    'Rekap pengaduan barang/fasilitas rusak pada ruangan yang menjadi tanggung jawab Anda.',
              ),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    children: [
                      DropdownButtonFormField<String>(
                        value: _periodType,
                        decoration: const InputDecoration(
                          labelText: 'Periode laporan',
                        ),
                        items: const [
                          DropdownMenuItem(
                            value: 'month',
                            child: Text('Bulanan'),
                          ),
                          DropdownMenuItem(
                            value: 'week',
                            child: Text('Mingguan'),
                          ),
                          DropdownMenuItem(value: 'day', child: Text('Harian')),
                        ],
                        onChanged: (value) {
                          if (value != null) _selectPeriodType(value);
                        },
                      ),
                      if (_periodType == 'month') ...[
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Expanded(
                              child: DropdownButtonFormField<int>(
                                value: _month.month,
                                decoration: const InputDecoration(
                                  labelText: 'Bulan',
                                ),
                                items: List.generate(
                                  12,
                                  (index) => DropdownMenuItem(
                                    value: index + 1,
                                    child: Text(monthName(index + 1)),
                                  ),
                                ),
                                onChanged: (value) {
                                  if (value != null) _selectMonth(value);
                                },
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: DropdownButtonFormField<int>(
                                value: _month.year,
                                decoration: const InputDecoration(
                                  labelText: 'Tahun',
                                ),
                                items: [
                                  for (
                                    var year = 2000;
                                    year <= DateTime.now().year + 1;
                                    year++
                                  )
                                    DropdownMenuItem(
                                      value: year,
                                      child: Text('$year'),
                                    ),
                                ],
                                onChanged: (value) {
                                  if (value != null) _selectYear(value);
                                },
                              ),
                            ),
                          ],
                        ),
                      ] else ...[
                        const SizedBox(height: 8),
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton.icon(
                            onPressed: _selectDate,
                            icon: const Icon(Icons.calendar_today_outlined),
                            label: Text(
                              'Tanggal pilihan: ${formatDateValue(dateIso(_selectedDate))}',
                            ),
                          ),
                        ),
                        if (_periodType == 'week')
                          const Align(
                            alignment: Alignment.centerLeft,
                            child: Text(
                              'Laporan mingguan dihitung dari hari Senin sampai Minggu.',
                              style: TextStyle(color: muted, fontSize: 12),
                            ),
                          ),
                      ],
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4),
                child: Text(
                  'Periode: ${data['period'] ?? '-'}',
                  style: const TextStyle(
                    color: muted,
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(height: 12),
              StatCard(
                icon: Icons.report_outlined,
                label: 'Total Pengaduan',
                value: '${data['total'] ?? 0}',
                caption:
                    '${approvals['pending'] ?? 0} menunggu persetujuan · '
                    '${approvals['approved'] ?? 0} disetujui',
                iconColor: warningText,
                iconBackground: warningSoft,
              ),
              const SizedBox(height: 10),
              StatCard(
                icon: Icons.handyman_outlined,
                label: 'Belum Diperbaiki',
                value: '${repairs['Belum Diperbaiki'] ?? 0}',
                caption:
                    '${repairs['Menunggu Spare Part'] ?? 0} menunggu spare part · '
                    '${repairs['Sudah Diperbaiki'] ?? 0} sudah diperbaiki',
                iconColor: success,
                iconBackground: successSoft,
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  FilledButton.icon(
                    onPressed: _exporting ? null : () => _printReport(data),
                    icon: const Icon(Icons.print_outlined),
                    label: const Text('Cetak / Simpan PDF'),
                  ),
                  OutlinedButton.icon(
                    onPressed: _exporting ? null : _exportDocx,
                    icon: const Icon(Icons.description_outlined),
                    label: const Text('Unduh DOCX'),
                  ),
                ],
              ),
              if (_exporting) ...[
                const SizedBox(height: 10),
                const LinearProgressIndicator(),
              ],
              const SizedBox(height: 16),
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    _FilterChip(
                      label: 'Semua (${allComplaints.length})',
                      selected: _filter == 'all',
                      onSelected: () => setState(() => _filter = 'all'),
                    ),
                    const SizedBox(width: 8),
                    _FilterChip(
                      label:
                          'Belum Diperbaiki (${repairs['Belum Diperbaiki'] ?? 0})',
                      selected: _filter == 'Belum Diperbaiki',
                      onSelected: () =>
                          setState(() => _filter = 'Belum Diperbaiki'),
                    ),
                    const SizedBox(width: 8),
                    _FilterChip(
                      label:
                          'Menunggu Spare Part (${repairs['Menunggu Spare Part'] ?? 0})',
                      selected: _filter == 'Menunggu Spare Part',
                      onSelected: () =>
                          setState(() => _filter = 'Menunggu Spare Part'),
                    ),
                    const SizedBox(width: 8),
                    _FilterChip(
                      label:
                          'Sudah Diperbaiki (${repairs['Sudah Diperbaiki'] ?? 0})',
                      selected: _filter == 'Sudah Diperbaiki',
                      onSelected: () =>
                          setState(() => _filter = 'Sudah Diperbaiki'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              SectionCard(
                title: 'Pengaduan Barang/Fasilitas Rusak',
                child: filteredComplaints.isEmpty
                    ? const EmptyState(
                        message: 'Tidak ada pengaduan pada periode ini.',
                      )
                    : Column(
                        children: [
                          for (final complaint in filteredComplaints)
                            Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: _DamagedItemCard(
                                data: asMap(complaint),
                                api: widget.api,
                              ),
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

class _DamagedItemCard extends StatelessWidget {
  const _DamagedItemCard({required this.data, required this.api});

  final Map<String, dynamic> data;
  final ApiService api;

  @override
  Widget build(BuildContext context) {
    final room = asMap(data['room']);
    final reporter = asMap(data['reporter']);
    final approval = data['approval_status']?.toString() ?? 'pending';
    final repair = data['status_repair']?.toString() ?? 'Belum Diperbaiki';
    final hasPhoto = data['photo_path'] != null;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: canvas,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: line),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  data['facility_name']?.toString() ?? 'Fasilitas',
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 15,
                  ),
                ),
              ),
              StatusBadge(status: approval),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            '${room['name'] ?? 'Ruangan'} · ${formatDateValue(data['created_at'])}',
            style: const TextStyle(color: muted, fontSize: 12),
          ),
          if (reporter['name'] != null) ...[
            const SizedBox(height: 2),
            Text(
              'Pelapor: ${reporter['name']}',
              style: const TextStyle(color: muted, fontSize: 12),
            ),
          ],
          const SizedBox(height: 6),
          Text(
            data['description']?.toString() ?? '-',
            style: const TextStyle(color: ink, fontSize: 13),
          ),
          if (hasPhoto) ...[
            const SizedBox(height: 8),
            InkWell(
              onTap: () {
                PhotoViewDialog.show(
                  context,
                  api.complaintPhotoUri(data['id'] as int),
                  api.requestHeaders,
                  title: 'Foto: ${data['facility_name'] ?? 'Kerusakan'}',
                );
              },
              child: ClipRRect(
                borderRadius: BorderRadius.circular(6),
                child: Image.network(
                  api.complaintPhotoUri(data['id'] as int).toString(),
                  headers: api.requestHeaders,
                  height: 120,
                  width: double.infinity,
                  fit: BoxFit.cover,
                ),
              ),
            ),
          ],
          const Divider(height: 18),
          Row(
            children: [
              const Text(
                'Perbaikan: ',
                style: TextStyle(color: muted, fontSize: 12),
              ),
              StatusBadge(status: repair),
            ],
          ),
          if (data['repair_notes'] != null &&
              data['repair_notes'].toString().trim().isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              'Catatan: ${data['repair_notes']}',
              style: const TextStyle(color: muted, fontSize: 12),
            ),
          ],
        ],
      ),
    );
  }
}
