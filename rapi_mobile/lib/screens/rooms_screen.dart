import 'package:flutter/material.dart';

import '../api_service.dart';
import '../constants.dart';
import '../widgets/common_widgets.dart';

class RoomsScreen extends StatefulWidget {
  const RoomsScreen({
    required this.api,
    this.user,
    this.canManage = false,
    super.key,
  });

  final ApiService api;
  final Map<String, dynamic>? user;
  final bool canManage;

  @override
  State<RoomsScreen> createState() => _RoomsScreenState();
}

class _RoomsScreenState extends State<RoomsScreen> {
  late Future<List<dynamic>> _rooms;
  final _searchController = TextEditingController();
  String _searchQuery = '';
  String _availabilityFilter = 'all';

  @override
  void initState() {
    super.initState();
    _rooms = widget.api.getRooms();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    final next = widget.api.getRooms();
    setState(() => _rooms = next);
    await next;
  }

  Future<void> _openRoomForm({Map<String, dynamic>? room}) async {
    final isEdit = room != null;
    final formKey = GlobalKey<FormState>();
    final nameCtrl = TextEditingController(text: room?['name']?.toString() ?? '');
    final locationCtrl = TextEditingController(text: room?['location']?.toString() ?? '');
    final capacityCtrl = TextEditingController(
      text: room?['capacity'] != null ? room!['capacity'].toString() : '',
    );
    final descCtrl = TextEditingController(text: room?['description']?.toString() ?? '');
    bool saving = false;
    String? formError;

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
            return Padding(
              padding: EdgeInsets.fromLTRB(
                20,
                16,
                20,
                MediaQuery.viewInsetsOf(context).bottom + 20,
              ),
              child: SingleChildScrollView(
                child: Form(
                  key: formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            isEdit ? 'Edit Ruangan' : 'Tambah Ruangan Baru',
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
                      const Text(
                        'Kelola data fasilitas ruangan dan kapasitas tampung sekolah.',
                        style: TextStyle(color: muted, fontSize: 12),
                      ),
                      const SizedBox(height: 14),
                      if (formError != null) ...[
                        ErrorBanner(message: formError!),
                        const SizedBox(height: 12),
                      ],
                      TextFormField(
                        controller: nameCtrl,
                        decoration: const InputDecoration(
                          labelText: 'Nama Ruangan *',
                          hintText: 'Contoh: Laboratorium Komputer 1',
                        ),
                        validator: (v) {
                          if (v == null || v.trim().isEmpty) {
                            return 'Nama ruangan wajib diisi.';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: locationCtrl,
                        decoration: const InputDecoration(
                          labelText: 'Lokasi Ruangan',
                          hintText: 'Contoh: Gedung A Lantai 2',
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: capacityCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Kapasitas Maksimal (Orang)',
                          hintText: 'Contoh: 40',
                          helperText: 'Jumlah daya tampung maksimal orang.',
                          suffixText: 'orang',
                        ),
                        validator: (v) {
                          if (v != null && v.trim().isNotEmpty) {
                            final parsed = int.tryParse(v.trim());
                            if (parsed == null || parsed < 1) {
                              return 'Kapasitas harus berupa angka bulat minimal 1.';
                            }
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: descCtrl,
                        maxLines: 3,
                        decoration: const InputDecoration(
                          labelText: 'Deskripsi / Fasilitas Ruangan',
                          hintText: 'Contoh: Dilengkapi AC, Proyektor, 36 Unit PC...',
                        ),
                      ),
                      const SizedBox(height: 20),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton(
                              onPressed: saving ? null : () => Navigator.pop(context),
                              child: const Text('Batal'),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            flex: 2,
                            child: ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: accent,
                                foregroundColor: Colors.white,
                              ),
                              onPressed: saving
                                  ? null
                                  : () async {
                                      if (!formKey.currentState!.validate()) return;
                                      setModalState(() {
                                        saving = true;
                                        formError = null;
                                      });
                                      try {
                                        final capText = capacityCtrl.text.trim();
                                        final payload = {
                                          'name': nameCtrl.text.trim(),
                                          'location': locationCtrl.text.trim().isEmpty
                                              ? null
                                              : locationCtrl.text.trim(),
                                          'capacity': capText.isNotEmpty
                                              ? int.tryParse(capText)
                                              : null,
                                          'description': descCtrl.text.trim().isEmpty
                                              ? null
                                              : descCtrl.text.trim(),
                                        };

                                        if (isEdit) {
                                          final id = room['id'] as int;
                                          await widget.api.updateRoom(id, payload);
                                        } else {
                                          await widget.api.createRoom(payload);
                                        }

                                        if (ctx.mounted) {
                                          Navigator.pop(ctx);
                                        }
                                        _reload();
                                        if (mounted) {
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(
                                              content: Text(
                                                isEdit
                                                    ? 'Ruangan berhasil diperbarui.'
                                                    : 'Ruangan berhasil ditambahkan.',
                                              ),
                                              backgroundColor: success,
                                            ),
                                          );
                                        }
                                      } catch (e) {
                                        setModalState(() {
                                          saving = false;
                                          formError = e.toString();
                                        });
                                      }
                                    },
                              child: saving
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : Text(isEdit ? 'Simpan Perubahan' : 'Tambah Ruangan'),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _confirmDelete(int id, String name, {bool hasActiveBookings = false, int activeCount = 0}) async {
    if (hasActiveBookings) {
      await showDialog<void>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Row(
            children: [
              Icon(Icons.lock_outline, color: danger),
              SizedBox(width: 8),
              Expanded(child: Text('Ruangan Tidak Dapat Dihapus')),
            ],
          ),
          content: Text(
            'Ruangan "$name" saat ini sedang dipakai atau masih dalam pembokingan ($activeCount agenda aktif).\n\nJika ingin menghapus ruangan ini, harus ada pergantian ruangan terlebih dahulu untuk yang meminjam ruangan tersebut (dapat dipindahkan lewat menu Kelola Agenda / Web Admin).',
          ),
          actions: [
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: accent, foregroundColor: Colors.white),
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Mengerti'),
            ),
          ],
        ),
      );
      return;
    }

    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Hapus Ruangan?'),
        content: Text('Yakin ingin menghapus ruangan "$name"? Tindakan ini tidak dapat dibatalkan.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: danger),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Hapus', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        await widget.api.deleteRoom(id);
        _reload();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Ruangan berhasil dihapus.'),
              backgroundColor: success,
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(e.toString().replaceFirst('Exception: ', '')),
              backgroundColor: danger,
            ),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      floatingActionButton: widget.canManage
          ? FloatingActionButton.extended(
              onPressed: () => _openRoomForm(),
              backgroundColor: accent,
              foregroundColor: Colors.white,
              icon: const Icon(Icons.add),
              label: const Text(
                'Tambah Ruangan',
                style: TextStyle(fontWeight: FontWeight.w700),
              ),
            )
          : null,
      body: FutureBuilder<List<dynamic>>(
        future: _rooms,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorState(error: snapshot.error, onRetry: _reload);
          }
          if (!snapshot.hasData) return const LoadingState();

          final allRooms = snapshot.data!;
          final filteredRooms = allRooms.where((room) {
            final map = asMap(room);
            final name = map['name']?.toString().toLowerCase() ?? '';
            final location = map['location']?.toString().toLowerCase() ?? '';
            final q = _searchQuery.toLowerCase();
            final matchesQuery = name.contains(q) || location.contains(q);

            final isInUse = map['is_in_use'] == true;
            final matchesAvailability = switch (_availabilityFilter) {
              'available' => !isInUse,
              'in_use' => isInUse,
              _ => true,
            };

            return matchesQuery && matchesAvailability;
          }).toList();

          final availableCount = allRooms.where((r) => asMap(r)['is_in_use'] != true).length;
          final inUseCount = allRooms.where((r) => asMap(r)['is_in_use'] == true).length;

          return RefreshIndicator(
            onRefresh: _reload,
            child: ListView(
              padding: EdgeInsets.fromLTRB(16, 16, 16, widget.canManage ? 88 : 16),
              children: [
                PageHeading(
                  title: widget.canManage ? 'Kelola Ruangan' : 'Ruangan Tugas Saya',
                  subtitle: widget.canManage
                      ? 'Daftar fasilitas ruangan dan kapasitas sekolah.'
                      : 'Daftar fasilitas ruangan yang menjadi tanggung jawab Anda.',
                ),
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Cari nama atau lokasi ruangan...',
                    prefixIcon: const Icon(Icons.search, size: 20),
                    suffixIcon: _searchQuery.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            onPressed: () {
                              _searchController.clear();
                              setState(() => _searchQuery = '');
                            },
                          )
                        : null,
                  ),
                  onChanged: (val) => setState(() => _searchQuery = val.trim()),
                ),
                const SizedBox(height: 10),
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      ChoiceChip(
                        label: Text('Semua (${allRooms.length})'),
                        selected: _availabilityFilter == 'all',
                        onSelected: (_) => setState(() => _availabilityFilter = 'all'),
                        selectedColor: accent,
                        labelStyle: TextStyle(
                          color: _availabilityFilter == 'all' ? Colors.white : ink,
                          fontWeight: _availabilityFilter == 'all' ? FontWeight.w700 : FontWeight.w500,
                          fontSize: 12,
                        ),
                      ),
                      const SizedBox(width: 8),
                      ChoiceChip(
                        avatar: const Icon(Icons.circle, color: Color(0xFF16A34A), size: 12),
                        label: Text('Tersedia ($availableCount)'),
                        selected: _availabilityFilter == 'available',
                        onSelected: (_) => setState(() => _availabilityFilter = 'available'),
                        selectedColor: const Color(0xFF16A34A),
                        labelStyle: TextStyle(
                          color: _availabilityFilter == 'available' ? Colors.white : ink,
                          fontWeight: _availabilityFilter == 'available' ? FontWeight.w700 : FontWeight.w500,
                          fontSize: 12,
                        ),
                      ),
                      const SizedBox(width: 8),
                      ChoiceChip(
                        avatar: const Icon(Icons.circle, color: Color(0xFFDC2626), size: 12),
                        label: Text('Sedang Dipakai ($inUseCount)'),
                        selected: _availabilityFilter == 'in_use',
                        onSelected: (_) => setState(() => _availabilityFilter = 'in_use'),
                        selectedColor: const Color(0xFFDC2626),
                        labelStyle: TextStyle(
                          color: _availabilityFilter == 'in_use' ? Colors.white : ink,
                          fontWeight: _availabilityFilter == 'in_use' ? FontWeight.w700 : FontWeight.w500,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                if (filteredRooms.isEmpty)
                  EmptyState(
                    message: _searchQuery.isEmpty
                        ? 'Belum ada data ruangan.'
                        : 'Tidak ada ruangan yang cocok dengan filter atau pencarian.',
                  )
                else
                  for (final item in filteredRooms)
                    _buildRoomCard(asMap(item)),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildRoomCard(Map<String, dynamic> room) {
    final id = room['id'] as int? ?? 0;
    final name = room['name']?.toString() ?? 'Ruangan';
    final location = room['location']?.toString();
    final capacity = room['capacity'];
    final description = room['description']?.toString();
    final pic = room['pic'] != null ? asMap(room['pic'])['name']?.toString() : null;
    final isInUse = room['is_in_use'] == true;
    final hasActiveBookings = room['has_active_bookings'] == true;
    final activeBookingsCount = (room['active_bookings_count'] as num?)?.toInt() ?? 0;
    final currentEvent = room['current_event'] != null ? asMap(room['current_event']) : null;

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: accentSoft,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(
                      Icons.meeting_room_outlined,
                      color: accentHover,
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                name,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                  fontSize: 16,
                                  color: ink,
                                ),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 3,
                              ),
                              decoration: BoxDecoration(
                                color: capacity != null ? const Color(0xFFE0F2FE) : const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.people_outline,
                                    size: 13,
                                    color: capacity != null ? const Color(0xFF0369A1) : muted,
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    capacity != null ? '$capacity orang' : 'Kapasitas -',
                                    style: TextStyle(
                                      color: capacity != null ? const Color(0xFF0369A1) : muted,
                                      fontSize: 11,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        if (location != null && location.isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              const Icon(
                                Icons.place_outlined,
                                size: 14,
                                color: muted,
                              ),
                              const SizedBox(width: 4),
                              Expanded(
                                child: Text(
                                  location,
                                  style: const TextStyle(
                                    color: muted,
                                    fontSize: 12,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              if (isInUse)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEF2F2),
                    border: Border.all(color: const Color(0xFFFECACA)),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Padding(
                        padding: EdgeInsets.only(top: 2),
                        child: Icon(Icons.circle, color: Color(0xFFDC2626), size: 10),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Sedang Digunakan Saat Ini',
                              style: TextStyle(
                                color: Color(0xFFB91C1C),
                                fontWeight: FontWeight.w700,
                                fontSize: 12,
                              ),
                            ),
                            if (currentEvent != null) ...[
                              const SizedBox(height: 2),
                              Text(
                                '${currentEvent['start_time']}–${currentEvent['end_time']} • ${currentEvent['title']}${currentEvent['borrower_name'] != null ? ' (${currentEvent['borrower_name']})' : ''}',
                                style: const TextStyle(
                                  color: Color(0xFF7F1D1D),
                                  fontSize: 11,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                )
              else
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF0FDF4),
                    border: Border.all(color: const Color(0xFFBBF7D0)),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.circle, color: Color(0xFF16A34A), size: 8),
                      SizedBox(width: 6),
                      Text(
                        'Tersedia Sekarang (Siap digunakan)',
                        style: TextStyle(
                          color: Color(0xFF15803D),
                          fontWeight: FontWeight.w700,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
              if (hasActiveBookings) ...[
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFFBEB),
                    border: Border.all(color: const Color(0xFFFDE68A)),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.lock_outline, color: Color(0xFFB45309), size: 13),
                      const SizedBox(width: 5),
                      Text(
                        '$activeBookingsCount Pembokingan Aktif (Terkunci dari hapus)',
                        style: const TextStyle(
                          color: Color(0xFF92400E),
                          fontWeight: FontWeight.w700,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              if (description != null && description.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  description,
                  style: const TextStyle(color: ink, fontSize: 13),
                ),
              ],
              if (pic != null && pic.isNotEmpty) ...[
                const SizedBox(height: 6),
                Text(
                  'PIC: $pic',
                  style: const TextStyle(fontSize: 12, color: muted),
                ),
              ],
              if (widget.canManage) ...[
                const SizedBox(height: 10),
                const Divider(height: 1),
                const SizedBox(height: 6),
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    TextButton.icon(
                      onPressed: () => _openRoomForm(room: room),
                      icon: const Icon(Icons.edit_outlined, size: 16),
                      label: const Text('Edit'),
                      style: TextButton.styleFrom(
                        visualDensity: VisualDensity.compact,
                        foregroundColor: accentHover,
                      ),
                    ),
                    const SizedBox(width: 4),
                    TextButton.icon(
                      onPressed: () => _confirmDelete(
                        id,
                        name,
                        hasActiveBookings: hasActiveBookings,
                        activeCount: activeBookingsCount,
                      ),
                      icon: Icon(
                        hasActiveBookings ? Icons.lock_outline : Icons.delete_outline,
                        size: 16,
                      ),
                      label: Text(hasActiveBookings ? 'Terkunci' : 'Hapus'),
                      style: TextButton.styleFrom(
                        visualDensity: VisualDensity.compact,
                        foregroundColor: hasActiveBookings ? const Color(0xFFB45309) : danger,
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
