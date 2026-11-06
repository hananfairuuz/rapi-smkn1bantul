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

  Future<void> _confirmDelete(int id, String name) async {
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
              content: Text('Gagal menghapus: $e'),
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
            return name.contains(q) || location.contains(q);
          }).toList();

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
                const SizedBox(height: 16),
                if (filteredRooms.isEmpty)
                  EmptyState(
                    message: _searchQuery.isEmpty
                        ? 'Belum ada data ruangan.'
                        : 'Tidak ada ruangan yang cocok dengan pencarian.',
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
                      onPressed: () => _confirmDelete(id, name),
                      icon: const Icon(Icons.delete_outline, size: 16),
                      label: const Text('Hapus'),
                      style: TextButton.styleFrom(
                        visualDensity: VisualDensity.compact,
                        foregroundColor: danger,
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
