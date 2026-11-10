import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class ComplaintsScreen extends StatefulWidget {
  const ComplaintsScreen({
    required this.api,
    required this.user,
    required this.canCreate,
    super.key,
  });

  final ApiService api;
  final Map<String, dynamic> user;
  final bool canCreate;

  @override
  State<ComplaintsScreen> createState() => ComplaintsScreenState();
}

class ComplaintsScreenState extends State<ComplaintsScreen> {
  late Future<List<dynamic>> _complaints;
  String _filter = 'all'; // all, pending, repair_needed, repaired

  @override
  void initState() {
    super.initState();
    _complaints = widget.api.getComplaints();
  }

  Future<void> _reload() async {
    final next = widget.api.getComplaints();
    setState(() => _complaints = next);
    await next;
  }

  Future<void> openCreateForm({Map<String, dynamic>? initialEvent}) async {
    final created = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => ComplaintForm(
        api: widget.api,
        user: widget.user,
        initialEvent: initialEvent,
      ),
    );
    if (created == true) {
      await _reload();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Pengaduan kerusakan berhasil dicatat dan menunggu persetujuan PIC.',
            ),
          ),
        );
      }
    }
  }

  Future<void> _reviewComplaint(int id, String status) async {
    final actionName = status == 'approved' ? 'menyetujui' : 'menolak';
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('${status == 'approved' ? 'Setujui' : 'Tolak'} Pengaduan?'),
        content: Text('Apakah Anda yakin ingin $actionName pengaduan ini?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            style: status == 'rejected'
                ? FilledButton.styleFrom(backgroundColor: danger)
                : null,
            onPressed: () => Navigator.pop(context, true),
            child: Text(status == 'approved' ? 'Setujui' : 'Tolak'),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      await widget.api.reviewComplaint(id, status);
      await _reload();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              status == 'approved'
                  ? 'Pengaduan disetujui. Sekarang Anda dapat memperbarui status perbaikan.'
                  : 'Pengaduan ditolak.',
            ),
          ),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    }
  }

  Future<void> _updateRepair(
    int id,
    String currentStatus,
    String? currentNotes, {
    bool hasExistingPhoto = false,
  }) async {
    final update = await showDialog<Map<String, String>>(
      context: context,
      builder: (_) => RepairUpdateDialog(
        initialStatus: currentStatus,
        initialNotes: currentNotes,
        hasExistingPhoto: hasExistingPhoto,
      ),
    );
    if (update == null) return;
    try {
      await widget.api.updateComplaintRepair(
        id,
        update['status_repair']!,
        update['repair_notes'],
        repairPhotoPath: update['repair_photo_path'],
      );
      await _reload();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Status pengaduan berhasil diperbarui.'),
          ),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(error.toString())));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isPic = widget.user['role'] == 'pic';

    return Scaffold(
      backgroundColor: Colors.transparent,
      floatingActionButton: widget.canCreate
          ? FloatingActionButton.extended(
              onPressed: () => openCreateForm(),
              backgroundColor: accent,
              foregroundColor: Colors.white,
              icon: const Icon(Icons.add),
              label: const Text('Buat pengaduan'),
            )
          : null,
      body: FutureBuilder<List<dynamic>>(
        future: _complaints,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorState(error: snapshot.error, onRetry: _reload);
          }
          if (!snapshot.hasData) return const LoadingState();

          final allComplaints = snapshot.data!;
          final filtered = allComplaints.where((c) {
            final approval = asMap(c)['approval_status']?.toString();
            final repair = asMap(c)['status_repair']?.toString();

            if (_filter == 'all') return true;
            if (_filter == 'pending') return approval == 'pending';
            if (_filter == 'repair_needed') {
              return approval == 'approved' && repair != 'Sudah Diperbaiki';
            }
            if (_filter == 'repaired') return repair == 'Sudah Diperbaiki';
            return true;
          }).toList();

          return RefreshIndicator(
            onRefresh: _reload,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
              children: [
                PageHeading(
                  title: 'Pengaduan Fasilitas',
                  subtitle: isPic
                      ? 'Kelola dan tindak lanjuti laporan kerusakan pada ruangan Anda.'
                      : 'Lihat perkembangan laporan kerusakan fasilitas sekolah.',
                ),
                // Filter chips
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
                            'Menunggu PIC (${allComplaints.where((c) => asMap(c)['approval_status'] == 'pending').length})',
                        selected: _filter == 'pending',
                        onSelected: () => setState(() => _filter = 'pending'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Perlu Perbaikan (${allComplaints.where((c) => asMap(c)['approval_status'] == 'approved' && asMap(c)['status_repair'] != 'Sudah Diperbaiki').length})',
                        selected: _filter == 'repair_needed',
                        onSelected: () =>
                            setState(() => _filter = 'repair_needed'),
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label:
                            'Selesai (${allComplaints.where((c) => asMap(c)['status_repair'] == 'Sudah Diperbaiki').length})',
                        selected: _filter == 'repaired',
                        onSelected: () => setState(() => _filter = 'repaired'),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                if (filtered.isEmpty)
                  const SectionCard(
                    title: 'Daftar Pengaduan',
                    child: EmptyState(
                      message: 'Tidak ada pengaduan pada kategori ini.',
                    ),
                  )
                else
                  for (final complaint in filtered)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: ComplaintCard(
                        data: asMap(complaint),
                        api: widget.api,
                        canManage: isPic,
                        onReview: (status) => _reviewComplaint(
                          asMap(complaint)['id'] as int,
                          status,
                        ),
                        onUpdateRepair: () => _updateRepair(
                          asMap(complaint)['id'] as int,
                          asMap(complaint)['status_repair']?.toString() ??
                              'Belum Diperbaiki',
                          asMap(complaint)['repair_notes']?.toString(),
                          hasExistingPhoto:
                              asMap(complaint)['repair_photo_path'] != null,
                        ),
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

class ComplaintCard extends StatelessWidget {
  const ComplaintCard({
    required this.data,
    required this.api,
    required this.canManage,
    required this.onReview,
    required this.onUpdateRepair,
    super.key,
  });

  final Map<String, dynamic> data;
  final ApiService api;
  final bool canManage;
  final ValueChanged<String> onReview;
  final VoidCallback onUpdateRepair;

  @override
  Widget build(BuildContext context) {
    final room = asMap(data['room']);
    final roomPic = asMap(room['pic']);
    final reporter = asMap(data['reporter']);
    final event = asMap(data['event']);
    final approval = data['approval_status']?.toString() ?? 'pending';
    final repair = data['status_repair']?.toString() ?? 'Belum Diperbaiki';
    final hasPhoto = data['photo_path'] != null;

    return Card(
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
                    data['facility_name']?.toString() ?? 'Fasilitas',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: ink,
                    ),
                  ),
                ),
                StatusBadge(status: approval),
              ],
            ),
            const SizedBox(height: 6),
            Text(
              '${room['name'] ?? 'Ruangan'}${room['location'] != null && room['location'].toString().isNotEmpty ? ' (${room['location']})' : ''} · ${formatDateValue(data['created_at'])}',
              style: const TextStyle(color: muted, fontSize: 12),
            ),
            if (reporter['name'] != null) ...[
              const SizedBox(height: 2),
              Text(
                'Pelapor: ${reporter['name']}',
                style: const TextStyle(color: muted, fontSize: 12),
              ),
            ],
            if (roomPic['name'] != null) ...[
              const SizedBox(height: 2),
              Text(
                'PIC Ruangan: ${roomPic['name']}',
                style: const TextStyle(color: muted, fontSize: 12),
              ),
            ],
            if (event['title'] != null) ...[
              const SizedBox(height: 2),
              Text(
                'Terkait agenda: ${event['title']}${event['date'] != null ? ' (${formatDateValue(event['date'])})' : ''}',
                style: const TextStyle(
                  color: accentHover,
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
            const SizedBox(height: 10),
            Text(
              data['description']?.toString() ?? '-',
              style: const TextStyle(color: ink, fontSize: 13),
            ),
            if (hasPhoto) ...[
              const SizedBox(height: 12),
              InkWell(
                onTap: () {
                  PhotoViewDialog.show(
                    context,
                    api.complaintPhotoUri(data['id'] as int),
                    api.requestHeaders,
                    title:
                        'Foto: ${data['facility_name'] ?? 'Bukti Kerusakan'}',
                  );
                },
                borderRadius: BorderRadius.circular(9),
                child: Stack(
                  alignment: Alignment.bottomRight,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(9),
                      child: Image.network(
                        api.complaintPhotoUri(data['id'] as int).toString(),
                        headers: api.requestHeaders,
                        height: 190,
                        width: double.infinity,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => Container(
                          height: 120,
                          color: canvas,
                          alignment: Alignment.center,
                          child: const Text(
                            'Foto bukti tidak dapat dimuat.',
                            style: TextStyle(color: muted, fontSize: 12),
                          ),
                        ),
                      ),
                    ),
                    Container(
                      margin: const EdgeInsets.all(8),
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.65),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.zoom_in, color: Colors.white, size: 14),
                          SizedBox(width: 4),
                          Text(
                            'Perbesar',
                            style: TextStyle(color: Colors.white, fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
            const Divider(height: 22),
            Row(
              children: [
                const Icon(Icons.handyman_outlined, size: 16, color: muted),
                const SizedBox(width: 6),
                const Text(
                  'Status perbaikan: ',
                  style: TextStyle(color: muted, fontSize: 13),
                ),
                StatusBadge(status: repair),
              ],
            ),
            if (data['repair_notes'] != null &&
                data['repair_notes'].toString().trim().isNotEmpty) ...[
              const SizedBox(height: 6),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: canvas,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  '${repair == 'Sudah Diperbaiki' ? 'Keterangan perbaikan' : 'Catatan PIC'}: ${data['repair_notes']}',
                  style: const TextStyle(color: ink, fontSize: 12),
                ),
              ),
            ] else if (repair == 'Sudah Diperbaiki') ...[
              const SizedBox(height: 6),
              const Text(
                'Keterangan perbaikan: Barang sudah diperbaiki.',
                style: TextStyle(color: ink, fontSize: 12),
              ),
            ],
            if (data['repair_photo_path'] != null) ...[
              const SizedBox(height: 10),
              InkWell(
                onTap: () {
                  PhotoViewDialog.show(
                    context,
                    api.complaintRepairPhotoUri(data['id'] as int),
                    api.requestHeaders,
                    title:
                        'Foto Perbaikan: ${data['facility_name'] ?? 'Bukti Selesai'}',
                  );
                },
                borderRadius: BorderRadius.circular(9),
                child: Container(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(9),
                    border: Border.all(
                      color: success.withValues(alpha: 0.6),
                      width: 1.5,
                    ),
                  ),
                  child: Stack(
                    alignment: Alignment.bottomRight,
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.network(
                          api
                              .complaintRepairPhotoUri(data['id'] as int)
                              .toString(),
                          headers: api.requestHeaders,
                          height: 160,
                          width: double.infinity,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) =>
                              Container(
                            height: 80,
                            color: canvas,
                            alignment: Alignment.center,
                            child: const Text(
                              'Foto bukti perbaikan tidak dapat dimuat.',
                              style: TextStyle(color: muted, fontSize: 12),
                            ),
                          ),
                        ),
                      ),
                      Container(
                        margin: const EdgeInsets.all(8),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.7),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.check_circle,
                                color: success, size: 14),
                            SizedBox(width: 4),
                            Text(
                              'Bukti Selesai Diperbaiki',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
            if (canManage && approval == 'pending') ...[
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(
                    child: FilledButton(
                      style: FilledButton.styleFrom(
                        backgroundColor: success,
                        foregroundColor: Colors.white,
                      ),
                      onPressed: () => onReview('approved'),
                      child: const Text('Setujui Pengaduan'),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        foregroundColor: danger,
                        side: const BorderSide(color: danger),
                      ),
                      onPressed: () => onReview('rejected'),
                      child: const Text('Tolak'),
                    ),
                  ),
                ],
              ),
            ],
            if (canManage && approval == 'approved') ...[
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: onUpdateRepair,
                  icon: const Icon(Icons.edit_outlined, size: 18),
                  label: const Text('Perbarui Status Perbaikan'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class ComplaintForm extends StatefulWidget {
  const ComplaintForm({
    required this.api,
    required this.user,
    this.initialEvent,
    super.key,
  });

  final ApiService api;
  final Map<String, dynamic> user;
  final Map<String, dynamic>? initialEvent;

  @override
  State<ComplaintForm> createState() => _ComplaintFormState();
}

class _ComplaintFormState extends State<ComplaintForm> {
  final _formKey = GlobalKey<FormState>();
  final _facility = TextEditingController();
  final _description = TextEditingController();
  final _picker = ImagePicker();
  List<dynamic> _rooms = [];
  List<dynamic> _events = [];
  int? _roomId;
  int? _eventId;
  String? _photoPath;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    if (widget.initialEvent != null) {
      _eventId = widget.initialEvent!['id'] as int?;
      _roomId = widget.initialEvent!['room_id'] as int?;
    }
    _loadOptions();
  }

  @override
  void dispose() {
    _facility.dispose();
    _description.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    try {
      final results = await Future.wait([
        widget.api.getRooms(),
        widget.api.getEvents(),
      ]);
      final rooms = results[0];
      final events = results[1]
          .where((event) => asMap(event)['status'] == 'approved')
          .toList();

      if (mounted) {
        setState(() {
          _rooms = rooms;
          _events = events;
          if (_roomId == null && rooms.isNotEmpty) {
            _roomId = asMap(rooms.first)['id'] as int;
          }
          _loading = false;
        });
      }
    } catch (error) {
      if (mounted) {
        setState(() {
          _error = error.toString();
          _loading = false;
        });
      }
    }
  }

  Future<void> _pickPhoto(ImageSource source) async {
    try {
      final image = await _picker.pickImage(source: source, imageQuality: 70);
      if (image == null) return;
      final fileSize = await File(image.path).length();
      if (fileSize > 10 * 1024 * 1024) {
        setState(() {
          _photoPath = null;
          _error = 'Ukuran foto maksimal 10 MB.';
        });
        return;
      }
      setState(() {
        _photoPath = image.path;
        _error = null;
      });
    } catch (error) {
      if (mounted) setState(() => _error = 'Gagal mengambil foto: $error');
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() ||
        (_eventId == null && _roomId == null)) {
      return;
    }
    if (_photoPath == null) {
      setState(() => _error = 'Foto kerusakan wajib disertakan.');
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final fields = <String, String>{
        'facility_name': _facility.text.trim(),
        'description': _description.text.trim(),
      };
      if (_eventId != null) {
        fields['event_id'] = '$_eventId';
      } else {
        fields['room_id'] = '$_roomId';
      }
      await widget.api.createComplaint(fields, photoPath: _photoPath);
      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final selectedRoom = _rooms.firstWhere(
      (r) => asMap(r)['id'] == _roomId,
      orElse: () => null,
    );
    final selectedPic = selectedRoom != null
        ? asMap(selectedRoom)['pic']
        : null;

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
                        Text(
                          widget.initialEvent != null
                              ? 'Catat Barang Rusak'
                              : 'Buat Pengaduan Fasilitas',
                          style: const TextStyle(
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
                    const SizedBox(height: 12),
                    if (_error != null) ...[
                      ErrorBanner(message: _error!),
                      const SizedBox(height: 12),
                    ],
                    // Event selection or direct room selection
                    if (widget.initialEvent != null) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: accentSoft,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: line),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Agenda: ${widget.initialEvent!['title']}',
                              style: const TextStyle(
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'Ruangan: ${asMap(widget.initialEvent!['room'])['name']} · ${formatDateValue(widget.initialEvent!['date'])}',
                              style: const TextStyle(
                                color: muted,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                    ] else ...[
                      if (_events.isNotEmpty) ...[
                        DropdownButtonFormField<int?>(
                          value: _eventId,
                          decoration: const InputDecoration(
                            labelText: 'Agenda Terkait (Opsional)',
                          ),
                          items: [
                            const DropdownMenuItem(
                              value: null,
                              child: Text(
                                'Tanpa agenda (pilih ruangan di bawah)',
                              ),
                            ),
                            for (final event in _events)
                              DropdownMenuItem(
                                value: asMap(event)['id'] as int,
                                child: Text(
                                  '${asMap(event)['title']} · ${asMap(asMap(event)['room'])['name']}',
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                          ],
                          onChanged: (value) {
                            setState(() {
                              _eventId = value;
                              if (value != null) {
                                final ev = _events.firstWhere(
                                  (e) => asMap(e)['id'] == value,
                                );
                                _roomId = asMap(ev)['room_id'] as int?;
                              }
                            });
                          },
                        ),
                        const SizedBox(height: 12),
                      ],
                      if (_eventId == null) ...[
                        DropdownButtonFormField<int>(
                          value: _roomId,
                          decoration: const InputDecoration(
                            labelText: 'Ruangan',
                          ),
                          items: [
                            for (final room in _rooms)
                              DropdownMenuItem(
                                value: asMap(room)['id'] as int,
                                child: Text(asMap(room)['name'].toString()),
                              ),
                          ],
                          onChanged: (value) => setState(() => _roomId = value),
                        ),
                        if (selectedPic != null &&
                            selectedPic['name'] != null) ...[
                          const SizedBox(height: 4),
                          Text(
                            'PIC Ruangan: ${selectedPic['name']}',
                            style: const TextStyle(color: muted, fontSize: 12),
                          ),
                        ],
                        const SizedBox(height: 12),
                      ],
                    ],
                    TextFormField(
                      controller: _facility,
                      decoration: const InputDecoration(
                        labelText: 'Fasilitas Rusak',
                        hintText:
                            'Contoh: AC Mati, Proyektor Buram, Kursi Patah',
                      ),
                      validator: requiredValidator,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _description,
                      minLines: 3,
                      maxLines: 5,
                      decoration: const InputDecoration(
                        labelText: 'Penjelasan Kerusakan',
                        hintText: 'Jelaskan kondisi kerusakan secara rinci...',
                      ),
                      validator: requiredValidator,
                    ),
                    const SizedBox(height: 14),
                    const Text(
                      'Foto Bukti Kerusakan (Wajib)',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () => _pickPhoto(ImageSource.camera),
                            icon: const Icon(
                              Icons.camera_alt_outlined,
                              size: 18,
                            ),
                            label: const Text('Buka Kamera'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () => _pickPhoto(ImageSource.gallery),
                            icon: const Icon(
                              Icons.photo_library_outlined,
                              size: 18,
                            ),
                            label: const Text('Pilih Galeri'),
                          ),
                        ),
                      ],
                    ),
                    if (_photoPath != null) ...[
                      const SizedBox(height: 10),
                      Stack(
                        alignment: Alignment.topRight,
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(8),
                            child: Image.file(
                              File(_photoPath!),
                              height: 160,
                              width: double.infinity,
                              fit: BoxFit.cover,
                            ),
                          ),
                          IconButton(
                            icon: const CircleAvatar(
                              radius: 14,
                              backgroundColor: Colors.black54,
                              child: Icon(
                                Icons.close,
                                color: Colors.white,
                                size: 16,
                              ),
                            ),
                            onPressed: () => setState(() => _photoPath = null),
                          ),
                        ],
                      ),
                    ],
                    const SizedBox(height: 18),
                    FilledButton(
                      style: FilledButton.styleFrom(
                        backgroundColor: accent,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed: _saving ? null : _submit,
                      child: _saving
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text(
                              'Kirim Pengaduan',
                              style: TextStyle(fontWeight: FontWeight.w700),
                            ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}

/// Dialog bagi PIC Ruangan untuk memperbarui status perbaikan barang/fasilitas.
/// Fitur unggulan:
/// - Memilih status: Belum Diperbaiki, Menunggu Spare Part, atau Sudah Diperbaiki.
/// - Wajib mengunggah foto bukti perbaikan bila status diubah menjadi "Sudah Diperbaiki".
/// - Mengompresi kualitas foto otomatis ke 70% dan memvalidasi ukuran file maks 10 MB.
class RepairUpdateDialog extends StatefulWidget {
  const RepairUpdateDialog({
    this.initialStatus = 'Belum Diperbaiki',
    this.initialNotes,
    this.hasExistingPhoto = false,
    super.key,
  });

  final String initialStatus;
  final String? initialNotes;
  final bool hasExistingPhoto;

  @override
  State<RepairUpdateDialog> createState() => _RepairUpdateDialogState();
}

class _RepairUpdateDialogState extends State<RepairUpdateDialog> {
  final _formKey = GlobalKey<FormState>();
  final _picker = ImagePicker();
  late String _status;
  late final TextEditingController _notes;
  String? _repairPhotoPath;
  String? _photoError;

  @override
  void initState() {
    super.initState();
    _status = widget.initialStatus;
    _notes = TextEditingController(text: widget.initialNotes ?? '');
  }

  @override
  void dispose() {
    _notes.dispose();
    super.dispose();
  }

  /// Mengambil foto bukti perbaikan dari Kamera langsung atau Galeri HP.
  /// Otomatis dikompresi ke kualitas 70% dan dicek agar tidak melebihi 10 MB.
  Future<void> _pickPhoto(ImageSource source) async {
    try {
      final image = await _picker.pickImage(source: source, imageQuality: 70);
      if (image == null) return;
      final fileSize = await File(image.path).length();
      if (fileSize > 10 * 1024 * 1024) {
        setState(() {
          _repairPhotoPath = null;
          _photoError = 'Ukuran foto maksimal 10 MB.';
        });
        return;
      }
      setState(() {
        _repairPhotoPath = image.path;
        _photoError = null;
      });
    } catch (e) {
      setState(() => _photoError = 'Gagal mengambil foto: $e');
    }
  }

  /// Validasi sebelum menyimpan status:
  /// Jika status 'Sudah Diperbaiki', foto bukti fisik WAJIB ada!
  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    if (_status == 'Sudah Diperbaiki' &&
        _repairPhotoPath == null &&
        !widget.hasExistingPhoto) {
      setState(() {
        _photoError = 'Foto barang yang sudah diperbaiki wajib disertakan.';
      });
      return;
    }

    Navigator.pop(context, {
      'status_repair': _status,
      'repair_notes': _notes.text.trim(),
      if (_repairPhotoPath != null) 'repair_photo_path': _repairPhotoPath!,
    });
  }

  @override
  Widget build(BuildContext context) {
    final isRepaired = _status == 'Sudah Diperbaiki';

    return AlertDialog(
      title: const Text('Perbarui Status Perbaikan'),
      content: SingleChildScrollView(
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              DropdownButtonFormField<String>(
                value: _status,
                decoration: const InputDecoration(
                  labelText: 'Status Perbaikan',
                ),
                items: const [
                  DropdownMenuItem(
                    value: 'Belum Diperbaiki',
                    child: Text('Belum Diperbaiki'),
                  ),
                  DropdownMenuItem(
                    value: 'Menunggu Spare Part',
                    child: Text('Menunggu Spare Part'),
                  ),
                  DropdownMenuItem(
                    value: 'Sudah Diperbaiki',
                    child: Text('Sudah Diperbaiki'),
                  ),
                ],
                onChanged: (value) => setState(() {
                  _status = value ?? _status;
                  _photoError = null;
                }),
              ),
              if (isRepaired) ...[
                const SizedBox(height: 14),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: canvas,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: _photoError != null
                          ? danger
                          : success.withValues(alpha: 0.4),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Icon(
                            Icons.camera_alt_outlined,
                            size: 16,
                            color: _photoError != null ? danger : success,
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              widget.hasExistingPhoto
                                  ? 'Foto Bukti Perbaikan (Ada Sebelumnya)'
                                  : 'Foto Bukti Perbaikan (Wajib)',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: _photoError != null ? danger : ink,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      if (_repairPhotoPath != null) ...[
                        ClipRRect(
                          borderRadius: BorderRadius.circular(6),
                          child: Stack(
                            alignment: Alignment.topRight,
                            children: [
                              Image.file(
                                File(_repairPhotoPath!),
                                height: 120,
                                width: double.infinity,
                                fit: BoxFit.cover,
                              ),
                              IconButton(
                                icon: const Icon(Icons.cancel,
                                    color: Colors.white, size: 22),
                                onPressed: () =>
                                    setState(() => _repairPhotoPath = null),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 8),
                      ] else if (widget.hasExistingPhoto) ...[
                        const Text(
                          'Foto bukti perbaikan sudah tersedia. Unggah foto baru jika ingin menggantinya.',
                          style: TextStyle(fontSize: 11, color: muted),
                        ),
                        const SizedBox(height: 8),
                      ],
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: () =>
                                  _pickPhoto(ImageSource.camera),
                              icon: const Icon(Icons.camera_alt, size: 16),
                              label: const Text('Kamera',
                                  style: TextStyle(fontSize: 12)),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: () =>
                                  _pickPhoto(ImageSource.gallery),
                              icon: const Icon(Icons.photo_library, size: 16),
                              label: const Text('Galeri',
                                  style: TextStyle(fontSize: 12)),
                            ),
                          ),
                        ],
                      ),
                      if (_photoError != null) ...[
                        const SizedBox(height: 6),
                        Text(
                          _photoError!,
                          style: const TextStyle(
                            color: danger,
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 12),
              TextFormField(
                controller: _notes,
                minLines: 2,
                maxLines: 4,
                decoration: const InputDecoration(
                  labelText: 'Catatan PIC / Keterangan (Opsional)',
                  hintText:
                      'Contoh: Sudah diganti kabel baru dan dites normal...',
                ),
              ),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Batal'),
        ),
        FilledButton(
          onPressed: _submit,
          child: const Text('Simpan'),
        ),
      ],
    );
  }
}
